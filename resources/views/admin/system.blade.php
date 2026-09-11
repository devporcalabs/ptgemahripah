@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Sistem'])

@php
    $zonaSekarang = $settings->zona_waktu ?? 'Asia/Jakarta';
    $wib = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    $wita = new DateTime('now', new DateTimeZone('Asia/Makassar'));
    $wit = new DateTime('now', new DateTimeZone('Asia/Jayapura'));
    $sekarang = new DateTime('now', new DateTimeZone($zonaSekarang));
@endphp

@section('styles')
    .card { margin-bottom: 20px; }
    .stats-grid { grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
    .stat-card .stat-card-body { padding: 16px; }
    .stat-icon { width: 40px; height: 40px; border-radius: 10px; font-size: 18px; }
    .two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .info-box {
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
        font-size: 12px;
        display: flex;
        gap: 10px;
    }
    .info-box i { font-size: 16px; margin-top: 2px; }
    .info-blue { background: #EFF6FF; color: #1E40AF; }
    .info-yellow { background: #FEF3C7; color: #92400E; }
    .info-red { background: #FEE2E2; color: #991B1B; }
    .btn-block { width: 100%; }
    .preview-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .preview-item {
        background: #F8FAFC;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .preview-icon { font-size: 28px; line-height: 1; }
    .preview-title { font-size: 12px; color: #64748B; margin-bottom: 4px; }
    .preview-time { font-size: 18px; font-weight: 700; color: #1E293B; }
    .preview-date { font-size: 11px; color: #64748B; }
    .info-list { display: flex; flex-direction: column; gap: 12px; }
    .info-item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid #F1F5F9;
        padding-bottom: 10px;
    }
    .info-label { font-size: 12px; color: #64748B; }
    .info-value { font-size: 12px; font-weight: 500; color: #1E293B; text-align: right; }
    @media (max-width: 900px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .two-columns { grid-template-columns: 1fr; }
        .preview-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) { .stats-grid { grid-template-columns: 1fr; } }
@endsection

@section('content')
    <div id="ajaxCrudFragment">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-globe" style="color:#065F46; margin-right:8px;"></i>Pengaturan Zona Waktu</h3>
        </div>
        <div class="card-body">
            <div class="info-box info-blue">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Zona Waktu Saat Ini:</strong>
                    @if ($zonaSekarang === 'Asia/Jakarta')
                        WIB (UTC+7) - Waktu Indonesia Barat
                    @elseif ($zonaSekarang === 'Asia/Makassar')
                        WITA (UTC+8) - Waktu Indonesia Tengah
                    @elseif ($zonaSekarang === 'Asia/Jayapura')
                        WIT (UTC+9) - Waktu Indonesia Timur
                    @else
                        {{ $zonaSekarang }}
                    @endif
                    <br>
                    <small>Waktu server saat ini: {{ $sekarang->format('d/m/Y H:i:s') }}</small>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.sistem.timezone') }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment">
                @csrf
                <div class="form-group">
                    <label>Pilih Zona Waktu</label>
                    <select name="zona_waktu" class="form-select" required>
                        <option value="Asia/Jakarta" @selected($zonaSekarang === 'Asia/Jakarta')>WIB (UTC+7) - Jakarta, Sumatera, Jawa Barat, Kalimantan Barat, Kalimantan Tengah</option>
                        <option value="Asia/Makassar" @selected($zonaSekarang === 'Asia/Makassar')>WITA (UTC+8) - Bali, NTB, NTT, Sulawesi, Kalimantan Timur, Kalimantan Selatan, Kalimantan Utara</option>
                        <option value="Asia/Jayapura" @selected($zonaSekarang === 'Asia/Jayapura')>WIT (UTC+9) - Maluku, Papua, Papua Barat, Maluku Utara</option>
                    </select>
                    <small class="form-text">Pilih zona waktu sesuai lokasi kantor Anda. Perubahan akan langsung diterapkan pada seluruh sistem absensi.</small>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Zona Waktu</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clock" style="color:#065F46; margin-right:8px;"></i>Preview Zona Waktu</h3>
        </div>
        <div class="card-body">
            <div class="preview-grid">
                <div class="preview-item">
                    <div class="preview-icon">🕐</div>
                    <div class="preview-info">
                        <div class="preview-title">WIB (Jakarta)</div>
                        <div class="preview-time">{{ $wib->format('H:i:s') }}</div>
                        <div class="preview-date">{{ $wib->format('d/m/Y') }}</div>
                    </div>
                </div>
                <div class="preview-item">
                    <div class="preview-icon">🕑</div>
                    <div class="preview-info">
                        <div class="preview-title">WITA (Makassar)</div>
                        <div class="preview-time">{{ $wita->format('H:i:s') }}</div>
                        <div class="preview-date">{{ $wita->format('d/m/Y') }}</div>
                    </div>
                </div>
                <div class="preview-item">
                    <div class="preview-icon">🕒</div>
                    <div class="preview-info">
                        <div class="preview-title">WIT (Jayapura)</div>
                        <div class="preview-time">{{ $wit->format('H:i:s') }}</div>
                        <div class="preview-date">{{ $wit->format('d/m/Y') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ number_format($stats['karyawan'] ?? 0, 0, ',', '.') }}</div><div class="stat-label">Total Karyawan</div></div><div class="stat-icon"><i class="fas fa-users"></i></div></div></div>
        <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ number_format($stats['absensi'] ?? 0, 0, ',', '.') }}</div><div class="stat-label">Data Absensi</div></div><div class="stat-icon"><i class="fas fa-calendar-check"></i></div></div></div>
        <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ number_format($stats['izin'] ?? 0, 0, ',', '.') }}</div><div class="stat-label">Data Izin</div></div><div class="stat-icon"><i class="fas fa-file-alt"></i></div></div></div>
        <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ number_format($stats['gaji'] ?? 0, 0, ',', '.') }}</div><div class="stat-label">Data Payroll</div></div><div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div></div></div>
    </div>

    <div class="two-columns">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-database" style="color:#065F46; margin-right:8px;"></i>Backup Database</h3>
            </div>
            <div class="card-body">
                <div class="info-box info-blue">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Backup Database</strong><br>
                        Backup akan menyimpan semua data ke file SQL yang dapat didownload. Gunakan untuk cadangan data.
                    </div>
                </div>
                <a href="{{ route('admin.sistem.backup') }}" class="btn btn-primary btn-block">
                    <i class="fas fa-download"></i> Download Backup SQL
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-trash-alt" style="color:#065F46; margin-right:8px;"></i>Reset Data</h3>
            </div>
            <div class="card-body">
                <div class="info-box info-yellow">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Reset Data (Karyawan Tetap)</strong><br>
                        Menghapus semua data absensi, izin, payroll, dan log operasional. Data karyawan tetap ada.
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.sistem.reset-data') }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Yakin reset data operasional?">
                    @csrf
                    <div class="form-group">
                        <label>Ketik <strong>RESET</strong> untuk konfirmasi</label>
                        <input type="text" name="confirm_reset" class="form-control" placeholder="RESET" required>
                    </div>
                    <button type="submit" class="btn btn-warning btn-block">
                        <i class="fas fa-trash-alt"></i> Reset Data
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-radiation" style="color:#065F46; margin-right:8px;"></i>Reset All Data</h3>
            </div>
            <div class="card-body">
                <div class="info-box info-red">
                    <i class="fas fa-skull-crossbones"></i>
                    <div>
                        <strong>Reset Semua Data (Termasuk Karyawan)</strong><br>
                        Menghapus SEMUA data termasuk karyawan. Data tidak dapat dikembalikan.
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.sistem.reset-all') }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Yakin reset semua data non-master?">
                    @csrf
                    <div class="form-group">
                        <label>Ketik <strong>RESET ALL</strong> untuk konfirmasi</label>
                        <input type="text" name="confirm_reset_all" class="form-control" placeholder="RESET ALL" required>
                    </div>
                    <button type="submit" class="btn btn-danger btn-block">
                        <i class="fas fa-radiation"></i> Reset All Data
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-info-circle" style="color:#065F46; margin-right:8px;"></i>Informasi Sistem</h3>
            </div>
            <div class="card-body">
                <div class="info-list">
                    <div class="info-item"><span class="info-label">Versi Aplikasi</span><span class="info-value">v2.0.0</span></div>
                    <div class="info-item"><span class="info-label">PHP Version</span><span class="info-value">{{ phpversion() }}</span></div>
                    <div class="info-item"><span class="info-label">MySQL Version</span><span class="info-value">{{ $mysqlVersion ?: '-' }}</span></div>
                    <div class="info-item"><span class="info-label">Server</span><span class="info-value">{{ $serverSoftware ?: '-' }}</span></div>
                    <div class="info-item">
                        <span class="info-label">Zona Waktu</span>
                        <span class="info-value">
                            @if ($zonaSekarang === 'Asia/Jakarta')
                                WIB (UTC+7)
                            @elseif ($zonaSekarang === 'Asia/Makassar')
                                WITA (UTC+8)
                            @elseif ($zonaSekarang === 'Asia/Jayapura')
                                WIT (UTC+9)
                            @else
                                {{ $zonaSekarang }}
                            @endif
                        </span>
                    </div>
                    <div class="info-item"><span class="info-label">Waktu Server</span><span class="info-value">{{ $sekarang->format('d/m/Y H:i:s') }}</span></div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
