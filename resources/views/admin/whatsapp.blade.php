@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'WhatsApp Gateway'])

@section('styles')
    .info-card {
        background: #EFF6FF;
        border-left: 4px solid #3B82F6;
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 12px;
        color: #1E40AF;
    }
    .info-card i { font-size: 20px; }
    .two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .checkbox { margin-bottom: 12px; }
    .checkbox-label { display: flex; align-items: center; gap: 8px; cursor: pointer; }
    .checkbox-label input { width: 16px; height: 16px; cursor: pointer; }
    .checkbox-label span { font-size: 13px; font-weight: 500; }
    .subtitle { font-size: 13px; font-weight: 600; color: #1E293B; margin-bottom: 12px; }
    .info-box {
        background: #EFF6FF;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 20px;
        font-size: 12px;
        color: #1E40AF;
        display: flex;
        gap: 10px;
    }
    .warning-box {
        background: #FEF3C7;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 20px;
        font-size: 12px;
        color: #92400E;
        display: flex;
        gap: 10px;
    }
    .stack-cards {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }
    .form-actions { margin-top: 20px; }
    @media (max-width: 900px) { .two-columns { grid-template-columns: 1fr; } }
@endsection

@section('content')
    <div id="ajaxCrudFragment">
    <div class="info-card">
        <i class="fab fa-whatsapp"></i>
        <div>
            <strong>Status API: {{ ($settings->whatsapp_enabled ?? 0) ? 'AKTIF' : 'NONAKTIF' }}</strong><br>
            Notifikasi Absensi: {{ ($settings->notif_absensi ?? 1) ? 'Aktif' : 'Nonaktif' }} |
            Notifikasi Izin: {{ ($settings->notif_izin ?? 1) ? 'Aktif' : 'Nonaktif' }} |
            Notifikasi Gaji: {{ ($settings->notif_gaji ?? 1) ? 'Aktif' : 'Nonaktif' }}
        </div>
    </div>

    <div class="two-columns">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-cog" style="color:#065F46; margin-right:8px;"></i>Pengaturan API WhatsApp</h3>
            </div>
            <div class="card-body">
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Panduan Mendapatkan API Key Sidobe:</strong><br>
                        1. Daftar akun di <a href="https://sidobe.com" target="_blank">sidobe.com</a><br>
                        2. Login ke dashboard Sidobe<br>
                        3. Buka menu Device → Add Device → Scan QR WhatsApp<br>
                        4. Pada menu API / WhatsApp Gateway, pilih Generate Secret Key<br>
                        5. Salin kode Secret Key yang diberikan
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.whatsapp-gateway.settings') }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment">
                    @csrf
                    <div class="form-group">
                        <label>API Key (X-Secret-Key)</label>
                        <input type="text" name="whatsapp_api_key" class="form-control" value="{{ old('whatsapp_api_key', $settings->whatsapp_api_key ?? '') }}" placeholder="Masukkan Secret Key dari Sidobe">
                    </div>

                    <div class="form-group">
                        <label>Nomor Pengirim</label>
                        <input type="text" name="whatsapp_sender" class="form-control" value="{{ old('whatsapp_sender', $settings->whatsapp_sender ?? '') }}" placeholder="628123456789">
                        <small class="form-text">Nomor WhatsApp yang terdaftar di Sidobe (format 62...)</small>
                    </div>

                    <div class="form-group checkbox">
                        <label class="checkbox-label">
                            <input type="checkbox" name="whatsapp_enabled" value="1" @checked(old('whatsapp_enabled', $settings->whatsapp_enabled ?? false))>
                            <span>Aktifkan Notifikasi WhatsApp</span>
                        </label>
                    </div>

                    <hr style="margin:16px 0; border:none; border-top:1px solid #E2E8F0;">
                    <h4 class="subtitle">Jenis Notifikasi</h4>

                    <div class="form-group checkbox">
                        <label class="checkbox-label">
                            <input type="checkbox" name="notif_absensi" value="1" @checked(old('notif_absensi', $settings->notif_absensi ?? true))>
                            <span>Notifikasi Absensi</span>
                        </label>
                        <small class="form-text">Kirim notifikasi ketika karyawan melakukan absensi</small>
                    </div>

                    <div class="form-group checkbox">
                        <label class="checkbox-label">
                            <input type="checkbox" name="notif_izin" value="1" @checked(old('notif_izin', $settings->notif_izin ?? true))>
                            <span>Notifikasi Izin</span>
                        </label>
                        <small class="form-text">Kirim notifikasi ketika ada pengajuan izin baru</small>
                    </div>

                    <div class="form-group checkbox">
                        <label class="checkbox-label">
                            <input type="checkbox" name="notif_gaji" value="1" @checked(old('notif_gaji', $settings->notif_gaji ?? true))>
                            <span>Notifikasi Slip Gaji</span>
                        </label>
                        <small class="form-text">Kirim slip gaji via WhatsApp</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="stack-cards">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-vial" style="color:#065F46; margin-right:8px;"></i>Test Kirim Pesan</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.whatsapp-gateway.test') }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-reset-on-success="true">
                        @csrf
                        <div class="form-group">
                            <label>Nomor Tujuan</label>
                            <input type="text" name="test_phone" class="form-control" placeholder="08123456789" value="{{ old('test_phone', '') }}">
                            <small class="form-text">Contoh: 08123456789 (akan otomatis dikonversi ke 62...)</small>
                        </div>

                        <div class="form-group">
                            <label>Pesan Test</label>
                            <textarea name="test_message" class="form-control" rows="3" placeholder="Tulis pesan test...">{{ old('test_message', 'Test WhatsApp dari '.($settings->nama_instansi ?? config('app.name')).' - '.now()->format('Y-m-d H:i:s')) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-warning">
                            <i class="fab fa-whatsapp"></i> Kirim Test Pesan
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bullhorn" style="color:#065F46; margin-right:8px;"></i>Broadcast Pesan</h3>
                </div>
                <div class="card-body">
                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong>Peringatan:</strong> Pesan akan dikirim ke <strong>{{ $totalRecipients }}</strong> karyawan yang memiliki nomor WhatsApp.
                            Proses ini mungkin memakan waktu beberapa menit.
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.whatsapp-gateway.broadcast') }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Yakin kirim broadcast ke {{ $totalRecipients }} karyawan?">
                        @csrf
                        <div class="form-group">
                            <label>Pesan Broadcast</label>
                            <textarea name="broadcast_message" class="form-control" rows="4" placeholder="Tulis pesan yang akan dikirim ke semua karyawan...">{{ old('broadcast_message') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-paper-plane"></i> Kirim Broadcast
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
