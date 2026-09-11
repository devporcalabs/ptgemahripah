@extends('layouts.panel', ['panel' => 'karyawan', 'pageTitle' => 'Profil Saya'])

@section('styles')
    .portal-page { display:grid; gap:18px; }
    .portal-grid { display:grid; grid-template-columns:minmax(0, 1.1fr) minmax(340px, 0.9fr); gap:18px; }
    .panel-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
    .panel-head { padding:16px 18px; border-bottom:1px solid #eef2f7; }
    .panel-title { margin:0; font-size:16px; font-weight:700; color:#0f172a; }
    .panel-subtitle { margin-top:4px; font-size:12px; color:#64748b; }
    .panel-body { padding:18px; }
    .profile-list { display:grid; gap:12px; }
    .profile-item { display:flex; justify-content:space-between; gap:18px; padding:12px 0; border-bottom:1px solid #eef2f7; }
    .profile-item:last-child { padding-bottom:0; border-bottom:none; }
    .profile-label { font-size:12px; color:#64748b; }
    .profile-value { font-size:13px; font-weight:600; color:#0f172a; text-align:right; }
    .badge-soft { display:inline-flex; align-items:center; justify-content:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; }
    .badge-success { background:#dcfce7; color:#166534; }
    .badge-warning { background:#fef3c7; color:#92400e; }
    .badge-danger { background:#fee2e2; color:#b91c1c; }
    @media (max-width:992px) { .portal-grid { grid-template-columns:1fr; } }
@endsection

@section('content')
    <div class="portal-page">
        <div class="portal-grid">
            <div class="panel-card">
                <div class="panel-head">
                    <h3 class="panel-title">Informasi Akun</h3>
                    <div class="panel-subtitle">Data login dan profil pegawai.</div>
                </div>
                <div class="panel-body">
                    <div class="profile-list">
                        <div class="profile-item"><div class="profile-label">Nama Lengkap</div><div class="profile-value">{{ $employee->nama_lengkap }}</div></div>
                        <div class="profile-item"><div class="profile-label">Username Login</div><div class="profile-value">{{ $user->username }}</div></div>
                        <div class="profile-item"><div class="profile-label">NIK</div><div class="profile-value">{{ $employee->nik }}</div></div>
                        <div class="profile-item"><div class="profile-label">Jabatan</div><div class="profile-value">{{ $employee->jabatanData?->nama_jabatan ?? $employee->jabatan ?? '-' }}</div></div>
                        <div class="profile-item"><div class="profile-label">Departemen</div><div class="profile-value">{{ $employee->departemenData?->nama_departemen ?? $employee->departemen ?? '-' }}</div></div>
                        <div class="profile-item"><div class="profile-label">Status</div><div class="profile-value"><span class="badge-soft {{ $employee->employment_status_badge_class }}">{{ $employee->employment_status_label }}</span></div></div>
                        <div class="profile-item"><div class="profile-label">Jenis Karyawan</div><div class="profile-value"><span class="badge-soft {{ $employee->jenis_karyawan_badge_class }}">{{ $employee->jenis_karyawan_label }}</span></div></div>
                        <div class="profile-item"><div class="profile-label">Tanggal Join</div><div class="profile-value">{{ optional($employee->tgl_join)->translatedFormat('d M Y') ?? '-' }}</div></div>
                        <div class="profile-item"><div class="profile-label">Email</div><div class="profile-value">{{ $employee->email ?: ($user->email ?: '-') }}</div></div>
                        <div class="profile-item"><div class="profile-label">Telepon</div><div class="profile-value">{{ $employee->telepon ?? $employee->no_telp ?? '-' }}</div></div>
                        <div class="profile-item"><div class="profile-label">Lokasi Kerja</div><div class="profile-value">{{ $employee->lokasiGps?->nama_lokasi ?? '-' }}</div></div>
                        <div class="profile-item"><div class="profile-label">Shift Utama</div><div class="profile-value">{{ $employee->shift?->nama_shift ?? '-' }}</div></div>
                    </div>
                </div>
            </div>

            <div class="panel-card">
                <div class="panel-head">
                    <h3 class="panel-title">Ganti Password</h3>
                    <div class="panel-subtitle">Setelah tersimpan, Anda akan diminta login ulang.</div>
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('karyawan.profil.password') }}">
                        @csrf
                        <div class="form-group">
                            <label>Password Lama</label>
                            <input type="password" name="old_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="new_password" class="form-control" minlength="6" required>
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password Baru</label>
                            <input type="password" name="new_password_confirmation" class="form-control" minlength="6" required>
                        </div>
                        <div class="button-group">
                            <button type="submit" class="btn btn-primary">Simpan Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
