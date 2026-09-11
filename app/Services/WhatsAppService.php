<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\WhatsappLog;
use Illuminate\Support\Facades\Http;
use Throwable;

class WhatsAppService
{
    public function send(string $phone, string $message): bool
    {
        $setting = Setting::query()->find(1);

        if (! $setting || ! $setting->whatsapp_enabled || empty($setting->whatsapp_api_key)) {
            return false;
        }

        $normalizedPhone = $this->normalizePhone($phone);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Secret-Key' => $setting->whatsapp_api_key,
                ])
                ->post('https://api.sidobe.com/wa/v1/send-message', [
                    'phone' => $normalizedPhone,
                    'message' => $message,
                ]);

            $success = false;
            $payload = $response->json();

            if (is_array($payload) && ($payload['is_success'] ?? false) === true) {
                $success = true;
            } elseif ($response->successful()) {
                $success = true;
            }

            WhatsappLog::query()->create([
                'phone_number' => $normalizedPhone,
                'message' => $message,
                'response' => substr($response->body(), 0, 1000),
                'status' => $success ? 'success' : 'failed',
            ]);

            return $success;
        } catch (Throwable $throwable) {
            report($throwable);

            WhatsappLog::query()->create([
                'phone_number' => $normalizedPhone,
                'message' => $message,
                'response' => substr($throwable->getMessage(), 0, 1000),
                'status' => 'failed',
            ]);

            return false;
        }
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone) ?: '';

        if (str_starts_with($digits, '0')) {
            return '+62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '62')) {
            return '+'.$digits;
        }

        return '+62'.$digits;
    }
}
