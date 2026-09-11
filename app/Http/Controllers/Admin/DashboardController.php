<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Izin;
use App\Models\Karyawan;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        return view('admin.dashboard', [
            'totalKaryawan' => Karyawan::query()->where('status', 'aktif')->count(),
            'absensiHariIni' => Absensi::query()->whereDate('tanggal', $today)->count(),
            'totalIzinPending' => Izin::query()->where('status', 'pending')->count(),
            'totalHadirBulanIni' => Absensi::query()
                ->whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->where('status', 'hadir')
                ->count(),
            'absensiTerbaru' => DB::table('absensi as a')
                ->join('karyawan as k', 'a.karyawan_id', '=', 'k.id')
                ->select('a.*', 'k.nama_lengkap', 'k.nik')
                ->orderByDesc('a.tanggal')
                ->orderByDesc('a.jam_masuk')
                ->limit(5)
                ->get(),
            'izinTerbaru' => DB::table('izin as i')
                ->join('karyawan as k', 'i.karyawan_id', '=', 'k.id')
                ->select('i.*', 'k.nama_lengkap', 'k.nik')
                ->where('i.status', 'pending')
                ->orderByDesc('i.created_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
