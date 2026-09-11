<div class="sidebar" id="sidebar">
    <div class="sidebar-user">
        <div class="user-avatar-sm">
            @if (!empty($appSettings?->logo) && file_exists(public_path('assets/logo/'.$appSettings->logo)))
                <img src="{{ asset('assets/logo/'.$appSettings->logo) }}" alt="{{ $appSettings->nama_instansi ?? config('app.name') }}">
            @else
                <i class="fas fa-building"></i>
            @endif
        </div>
        <div class="user-info-sm">
            <div class="user-name-sm">{{ $appSettings->nama_instansi ?? (auth()->user()->nama_lengkap ?? 'Administrator') }}</div>
            <div class="user-role-sm">{{ auth()->user()->display_role ?? 'Administrator' }}</div>
        </div>
    </div>

    <ul class="sidebar-nav">
        @can('dashboard.view')
            <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
                </a>
            </li>
        @endcan
        @can('karyawan.manage')
            <li class="nav-item">
                <a href="{{ route('admin.karyawan') }}" class="nav-link {{ request()->routeIs('admin.karyawan') ? 'active' : '' }}">
                    <i class="fas fa-users"></i><span>Kelola Karyawan</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.karyawan.resign') }}" class="nav-link {{ request()->routeIs('admin.karyawan.resign') ? 'active' : '' }}">
                    <i class="fas fa-user-minus"></i><span>Karyawan Resign</span>
                </a>
            </li>
        @endcan
        @canany(['absensi.view', 'absensi_makan.view', 'absensi_makan.manage'])
            <li class="nav-item">
                <a href="{{ route('admin.absensi') }}" class="nav-link {{ request()->routeIs('admin.absensi') ? 'active' : '' }}">
                    <i class="fas fa-camera"></i><span>Riwayat Absensi</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.absensi-makan') }}" class="nav-link {{ request()->routeIs('admin.absensi-makan*') ? 'active' : '' }}">
                    <i class="fas fa-utensils"></i><span>Absensi Makan</span>
                </a>
            </li>
        @endcanany
        @canany(['gaji.view', 'kasbon.manage'])
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link dropdown-toggle" data-target="keuanganSubmenu" aria-expanded="{{ request()->routeIs('admin.riwayat-gaji*') || request()->routeIs('admin.kasbon*') ? 'true' : 'false' }}">
                    <i class="fas fa-wallet"></i><span>Keuangan</span><i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu {{ request()->routeIs('admin.riwayat-gaji*') || request()->routeIs('admin.kasbon*') ? 'show' : '' }}" id="keuanganSubmenu">
                    @can('gaji.view')
                        <li><a href="{{ route('admin.riwayat-gaji') }}" class="{{ request()->routeIs('admin.riwayat-gaji*') ? 'active' : '' }}">Payroll</a></li>
                    @endcan
                    @can('kasbon.manage')
                        <li><a href="{{ route('admin.kasbon') }}" class="{{ request()->routeIs('admin.kasbon*') ? 'active' : '' }}">Kasbon</a></li>
                    @endcan
                </ul>
            </li>
        @endcanany
        @can('gaji.view')
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link dropdown-toggle" data-target="pajakSubmenu" aria-expanded="{{ request()->routeIs('admin.pajak-pph21*') || request()->routeIs('admin.bukti-potong-a1*') ? 'true' : 'false' }}">
                    <i class="fas fa-file-invoice-dollar"></i><span>Pajak</span><i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu {{ request()->routeIs('admin.pajak-pph21*') || request()->routeIs('admin.bukti-potong-a1*') ? 'show' : '' }}" id="pajakSubmenu">
                    <li><a href="{{ route('admin.pajak-pph21') }}" class="{{ request()->routeIs('admin.pajak-pph21*') ? 'active' : '' }}">PPh21</a></li>
                    <li><a href="{{ route('admin.bukti-potong-a1') }}" class="{{ request()->routeIs('admin.bukti-potong-a1*') ? 'active' : '' }}">Bukti Potong A1</a></li>
                </ul>
            </li>
        @endcan
        @can('izin.manage')
            <li class="nav-item">
                <a href="{{ route('admin.izin') }}" class="nav-link {{ request()->routeIs('admin.izin') ? 'active' : '' }}">
                    <i class="fas fa-envelope-open-text"></i><span>Izin / Cuti</span>
                </a>
            </li>
        @endcan
        @can('shift.manage')
            <li class="nav-item">
                <a href="{{ route('admin.kalender') }}" class="nav-link {{ request()->routeIs('admin.kalender*') ? 'active' : '' }}">
                    <i class="fas fa-calendar-day"></i><span>Kalender</span>
                </a>
            </li>
        @endcan
        @can('laporan.view')
            <li class="nav-item">
                <a href="{{ route('admin.laporan') }}" class="nav-link {{ request()->routeIs('admin.laporan') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar"></i><span>Laporan</span>
                </a>
            </li>
        @endcan
        <li class="nav-divider"></li>
        @canany(['karyawan.manage', 'shift.manage', 'lokasi.manage', 'whatsapp.manage', 'profil.manage', 'sistem.manage', 'device.manage'])
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link dropdown-toggle" data-target="pengaturanSubmenu" aria-expanded="{{ request()->routeIs('admin.jabatan') || request()->routeIs('admin.departemen') || request()->routeIs('admin.jam-shift*') || request()->routeIs('admin.radius-gps') || request()->routeIs('admin.whatsapp-gateway') || request()->routeIs('admin.mesin-absensi*') || request()->routeIs('admin.profil') || request()->routeIs('admin.sistem') || request()->routeIs('admin.payroll-settings*') || request()->routeIs('admin.jenis-izin*') || request()->routeIs('admin.update-aplikasi*') ? 'true' : 'false' }}">
                    <i class="fas fa-cog"></i><span>Pengaturan</span><i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu {{ request()->routeIs('admin.jabatan') || request()->routeIs('admin.departemen') || request()->routeIs('admin.jam-shift*') || request()->routeIs('admin.radius-gps') || request()->routeIs('admin.whatsapp-gateway') || request()->routeIs('admin.mesin-absensi*') || request()->routeIs('admin.profil') || request()->routeIs('admin.sistem') || request()->routeIs('admin.payroll-settings*') || request()->routeIs('admin.jenis-izin*') || request()->routeIs('admin.update-aplikasi*') ? 'show' : '' }}" id="pengaturanSubmenu">
                    @can('karyawan.manage')
                        <li><a href="{{ route('admin.jabatan') }}" class="{{ request()->routeIs('admin.jabatan') ? 'active' : '' }}">Jabatan</a></li>
                        <li><a href="{{ route('admin.departemen') }}" class="{{ request()->routeIs('admin.departemen') ? 'active' : '' }}">Departemen</a></li>
                    @endcan
                    @can('shift.manage')
                        <li><a href="{{ route('admin.jam-shift') }}" class="{{ request()->routeIs('admin.jam-shift*') ? 'active' : '' }}">Jam & Shift</a></li>
                    @endcan
                    @can('lokasi.manage')
                        <li><a href="{{ route('admin.radius-gps') }}" class="{{ request()->routeIs('admin.radius-gps') ? 'active' : '' }}">Radius GPS</a></li>
                    @endcan
                    @can('whatsapp.manage')
                        <li><a href="{{ route('admin.whatsapp-gateway') }}" class="{{ request()->routeIs('admin.whatsapp-gateway') ? 'active' : '' }}">WhatsApp Gateway</a></li>
                    @endcan
                    @can('device.manage')
                        <li><a href="{{ route('admin.mesin-absensi') }}" class="{{ request()->routeIs('admin.mesin-absensi*') ? 'active' : '' }}">Mesin Absensi</a></li>
                    @endcan
                    @can('profil.manage')
                        <li><a href="{{ route('admin.profil') }}" class="{{ request()->routeIs('admin.profil') ? 'active' : '' }}">Profil</a></li>
                    @endcan
                    @can('sistem.manage')
                        <li><a href="{{ route('admin.jenis-izin') }}" class="{{ request()->routeIs('admin.jenis-izin*') ? 'active' : '' }}">Jenis Cuti</a></li>
                        <li><a href="{{ route('admin.payroll-settings') }}" class="{{ request()->routeIs('admin.payroll-settings*') ? 'active' : '' }}">Payroll Umum</a></li>
                        <li><a href="{{ route('admin.update-aplikasi') }}" class="{{ request()->routeIs('admin.update-aplikasi*') ? 'active' : '' }}">Update Aplikasi</a></li>
                        <li><a href="{{ route('admin.sistem') }}" class="{{ request()->routeIs('admin.sistem') ? 'active' : '' }}">Sistem</a></li>
                    @endcan
                </ul>
            </li>
        @endcanany
        <li class="nav-divider"></li>
        <li class="nav-item">
            <form method="POST" action="{{ route('logout') }}" data-submit-loading="true">
                @csrf
                <button type="submit" class="nav-button logout-link" data-loading-text="Keluar...">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </button>
            </form>
        </li>
    </ul>
</div>
