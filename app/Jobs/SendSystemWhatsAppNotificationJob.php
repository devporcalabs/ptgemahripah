<?php

namespace App\Jobs;

use App\Services\WhatsAppNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSystemWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly string $type,
        public readonly int $targetId,
        public readonly array $payload = [],
    ) {
        $this->onQueue('notifications');
    }

    public function handle(WhatsAppNotificationService $service): void
    {
        match ($this->type) {
            'attendance' => $service->deliverAttendanceNotification($this->targetId, $this->payload),
            'leave_submitted' => $service->deliverLeaveSubmittedNotification($this->targetId),
            'leave_status' => $service->deliverLeaveStatusNotification($this->targetId),
            default => null,
        };
    }
}
