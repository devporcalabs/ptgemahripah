<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    private const LOGO_DIRECTORY = 'assets/logo';
    private const FAVICON_DIRECTORY = 'assets/favicon';

    public function index()
    {
        return view('admin.profile', [
            'settings' => Setting::query()->find(1),
            'adminUser' => auth()->user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'nama_instansi' => ['required', 'string', 'max:100'],
            'alamat' => ['nullable', 'string'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'website' => ['nullable', 'string', 'max:100'],
            'deskripsi_instansi' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,svg,webp', 'max:1024'],
        ], [
            'logo.image' => 'Logo instansi harus berupa gambar.',
            'logo.mimes' => 'Logo instansi harus berformat JPG, JPEG, PNG, atau GIF.',
            'logo.max' => 'Ukuran logo instansi maksimal 2 MB.',
            'favicon.file' => 'Favicon harus berupa file.',
            'favicon.mimes' => 'Favicon harus berformat ICO, PNG, JPG, JPEG, SVG, atau WEBP.',
            'favicon.max' => 'Ukuran favicon maksimal 1 MB.',
        ], [
            'nama_instansi' => 'nama instansi',
            'alamat' => 'alamat',
            'telepon' => 'telepon',
            'email' => 'email',
            'website' => 'website',
            'deskripsi_instansi' => 'deskripsi instansi',
            'logo' => 'logo instansi',
            'favicon' => 'favicon',
        ]);

        $settings = Setting::query()->firstOrCreate(['id' => 1]);
        $payload = $validated;

        if ($fileName = $this->storeUploadedAsset($request, 'logo', self::LOGO_DIRECTORY, $settings->logo)) {
            $payload['logo'] = $fileName;
        }

        if ($fileName = $this->storeUploadedAsset($request, 'favicon', self::FAVICON_DIRECTORY, $settings->favicon)) {
            $payload['favicon'] = $fileName;
        }

        $settings->update($payload);

        return $this->respondSuccess($request, route('admin.profil'), 'Profil instansi berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $admin = auth()->user();

        if (! Hash::check($validated['old_password'], $admin->password)) {
            return $this->respondError($request, route('admin.profil'), 'Password lama salah.', status: 422);
        }

        $admin->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->respondSuccess(
            $request,
            route('login'),
            'Password admin berhasil diubah. Silakan login kembali.',
            ['redirect' => route('login')],
        );
    }

    public function destroyLogo(Request $request)
    {
        $settings = Setting::query()->firstOrCreate(['id' => 1]);

        if (empty($settings->logo)) {
            return $this->respondError($request, route('admin.profil'), 'Logo belum tersedia untuk dihapus.', status: 422);
        }

        $this->deleteStoredAsset(self::LOGO_DIRECTORY, $settings->logo);

        $settings->update([
            'logo' => null,
        ]);

        return $this->respondSuccess($request, route('admin.profil'), 'Logo berhasil dihapus.');
    }

    public function destroyFavicon(Request $request)
    {
        $settings = Setting::query()->firstOrCreate(['id' => 1]);

        if (empty($settings->favicon)) {
            return $this->respondError($request, route('admin.profil'), 'Favicon belum tersedia untuk dihapus.', status: 422);
        }

        $this->deleteStoredAsset(self::FAVICON_DIRECTORY, $settings->favicon);

        $settings->update([
            'favicon' => null,
        ]);

        return $this->respondSuccess($request, route('admin.profil'), 'Favicon berhasil dihapus.');
    }

    private function storeUploadedAsset(Request $request, string $field, string $directory, ?string $oldFile): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        $file = $request->file($field);
        $fileName = time().'_'.$field.'.'.$file->getClientOriginalExtension();
        $targetDirectory = public_path($directory);

        if (! is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0777, true);
        }

        $file->move($targetDirectory, $fileName);

        if (! empty($oldFile) && file_exists($targetDirectory.DIRECTORY_SEPARATOR.$oldFile)) {
            @unlink($targetDirectory.DIRECTORY_SEPARATOR.$oldFile);
        }

        return $fileName;
    }

    private function deleteStoredAsset(string $directory, ?string $fileName): void
    {
        if (empty($fileName)) {
            return;
        }

        $path = public_path($directory.DIRECTORY_SEPARATOR.$fileName);
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}
