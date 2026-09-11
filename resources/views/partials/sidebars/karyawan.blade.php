<div class="sidebar" id="sidebar">
    <div class="sidebar-user">
        <div class="user-avatar-sm">
            @if (!empty($appSettings?->logo) && file_exists(public_path('assets/logo/'.$appSettings->logo)))
                <img src="{{ asset('assets/logo/'.$appSettings->logo) }}" alt="{{ $appSettings->nama_instansi ?? config('app.name') }}">
            @else
                <i class="fas fa-user"></i>
            @endif
        </div>
        <div class="user-info-sm">
            <div class="user-name-sm">{{ auth()->user()->name ?? 'Karyawan' }}</div>
            <div class="user-role-sm">Portal Karyawan</div>
        </div>
    </div>

    <ul class="sidebar-nav">
        <li class="nav-item">
            <a href="{{ route('karyawan.dashboard') }}" class="nav-link {{ request()->routeIs('karyawan.dashboard') ? 'active' : '' }}">
                <i class="fas fa-house-user"></i><span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('karyawan.jadwal') }}" class="nav-link {{ request()->routeIs('karyawan.jadwal') ? 'active' : '' }}">
                <i class="fas fa-calendar-alt"></i><span>Jadwal Kerja</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('karyawan.absensi') }}" class="nav-link {{ request()->routeIs('karyawan.absensi') ? 'active' : '' }}">
                <i class="fas fa-camera"></i><span>Absensi Saya</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('karyawan.absensi-makan') }}" class="nav-link {{ request()->routeIs('karyawan.absensi-makan*') ? 'active' : '' }}">
                <i class="fas fa-utensils"></i><span>Kupon Makan</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('karyawan.payroll') }}" class="nav-link {{ request()->routeIs('karyawan.payroll*') ? 'active' : '' }}">
                <i class="fas fa-wallet"></i><span>Payroll Saya</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('karyawan.pajak') }}" class="nav-link {{ request()->routeIs('karyawan.pajak*') ? 'active' : '' }}">
                <i class="fas fa-receipt"></i><span>Pajak Saya</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('karyawan.izin') }}" class="nav-link {{ request()->routeIs('karyawan.izin*') ? 'active' : '' }}">
                <i class="fas fa-envelope-open-text"></i><span>Izin / Cuti Saya</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('karyawan.kasbon') }}" class="nav-link {{ request()->routeIs('karyawan.kasbon') ? 'active' : '' }}">
                <i class="fas fa-file-invoice-dollar"></i><span>Kasbon Saya</span>
            </a>
        </li>

        <li class="nav-divider"></li>

        <li class="nav-item">
            <a href="{{ route('karyawan.profil') }}" class="nav-link {{ request()->routeIs('karyawan.profil*') ? 'active' : '' }}">
                <i class="fas fa-user-cog"></i><span>Profil Saya</span>
            </a>
        </li>

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
