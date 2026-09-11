<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()?->loadMissing([
            'karyawan.jabatanData:id,nama_jabatan',
            'karyawan.departemenData:id,nama_departemen',
            'karyawan.lokasiGps:id,nama_lokasi',
            'karyawan.shift:id,nama_shift',
        ]);

        $employee = $user?->karyawan;

        abort_unless($employee, 404);

        return view('karyawan.profil', [
            'user' => $user,
            'employee' => $employee,
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'new_password.min' => 'Password baru minimal 6 karakter.',
        ]);

        $user = $request->user();

        if (! $user || ! Hash::check($validated['old_password'], $user->password)) {
            return $this->respondError($request, route('karyawan.profil'), 'Password lama salah.', status: 422);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->respondSuccess(
            $request,
            route('login'),
            'Password berhasil diubah. Silakan login kembali.',
            ['redirect' => route('login')],
        );
    }
}
