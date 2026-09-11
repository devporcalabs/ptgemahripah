@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Update Aplikasi'])

@php
    $currentVersionLabel = trim((string) ($currentVersion ?? ''));
    $lastFrom = is_array($lastUpdate ?? null) ? (string) ($lastUpdate['from'] ?? '') : '';
    $lastTo = is_array($lastUpdate ?? null) ? (string) ($lastUpdate['to'] ?? '') : '';
    $lastInstalledAt = is_array($lastUpdate ?? null) ? (string) ($lastUpdate['installed_at'] ?? '') : '';

    $tenantTimezone = trim((string) config('app.timezone', 'Asia/Jakarta'));
    $tenantTimezoneLabelShort = $tenantTimezone === 'Asia/Jakarta' ? 'WIB' : $tenantTimezone;

    $lastInstalledAtLabel = $lastInstalledAt !== '' ? $lastInstalledAt : '-';
    if ($lastInstalledAt !== '') {
        try {
            $lastInstalledAtLabel = \Illuminate\Support\Carbon::parse($lastInstalledAt)
                ->timezone($tenantTimezone !== '' ? $tenantTimezone : 'Asia/Jakarta')
                ->format('d-m-Y H:i:s');
            if ($tenantTimezoneLabelShort !== '') {
                $lastInstalledAtLabel .= ' ' . $tenantTimezoneLabelShort;
            }
        } catch (\Throwable $e) {
            $lastInstalledAtLabel = $lastInstalledAt;
        }
    }
@endphp

@section('styles')
    .update-page-shell {
        background: #FFFFFF;
        border-radius: 18px;
        border: 1px solid #E5E7EB;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }
    .update-page-hero {
        position: relative;
        overflow: hidden;
    }
    .update-page-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, #0D5C3F 0%, #065F46 58%, #0F766E 100%);
    }
    .update-page-hero::after {
        content: '';
        position: absolute;
        right: -48px;
        top: -48px;
        width: 180px;
        height: 180px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        filter: blur(28px);
    }
    .update-page-hero-glow {
        position: absolute;
        left: -56px;
        bottom: -56px;
        width: 220px;
        height: 220px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.10);
        filter: blur(34px);
    }
    .update-page-hero-content {
        position: relative;
        padding: 20px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }
    .update-page-hero-title {
        margin: 0;
        font-size: 17px;
        font-weight: 800;
        color: #FFFFFF;
    }
    .update-page-hero-text {
        margin: 6px 0 0;
        font-size: 12px;
        line-height: 1.6;
        color: #D1FAE5;
    }
    .update-page-version-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #FFFFFF;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
    }
    .update-page-content {
        padding: 16px;
        display: grid;
        gap: 16px;
    }
    .update-page-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }
    .update-page-stat-card {
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        background: #FFFFFF;
        padding: 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }
    .update-page-stat-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }
    .update-page-stat-label {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #6B7280;
    }
    .update-page-stat-value {
        margin-top: 6px;
        font-size: 15px;
        font-weight: 800;
        color: #111827;
        word-break: break-word;
    }
    .update-page-stat-hint {
        margin-top: 8px;
        font-size: 11px;
        line-height: 1.5;
        color: #6B7280;
    }
    .update-page-stat-icon {
        width: 36px;
        height: 36px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 14px;
    }
    .update-page-stat-icon.indigo {
        background: #EEF2FF;
        border: 1px solid #C7D2FE;
        color: #4F46E5;
    }
    .update-page-stat-icon.emerald {
        background: #ECFDF5;
        border: 1px solid #A7F3D0;
        color: #059669;
    }
    .update-page-stat-icon.slate {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        color: #475569;
    }
    .update-page-history {
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        background: rgba(249, 250, 251, 0.7);
        padding: 14px 16px;
        font-size: 12px;
        line-height: 1.7;
        color: #374151;
    }
    .update-page-history-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 800;
        color: #111827;
    }
    .update-page-actions {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .update-page-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    .update-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 10px;
        border: none;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: .2s ease;
        min-width: 150px;
    }
    .update-page-btn:disabled {
        opacity: .65;
        cursor: not-allowed;
    }
    .update-page-btn-primary {
        background: #4F46E5;
        color: #FFFFFF;
    }
    .update-page-btn-primary:hover {
        background: #4338CA;
    }
    .update-page-btn-success {
        background: #059669;
        color: #FFFFFF;
    }
    .update-page-btn-success:hover {
        background: #047857;
    }
    .update-page-status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        padding: 0 14px;
        border-radius: 999px;
        border: 1px solid #E5E7EB;
        background: #F8FAFC;
        color: #475569;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .update-page-status-badge.available {
        background: #FFFBEB;
        border-color: #FDE68A;
        color: #B45309;
    }
    .update-page-status-badge.success {
        background: #ECFDF5;
        border-color: #A7F3D0;
        color: #047857;
    }
    .update-page-status-badge.error {
        background: #FEF2F2;
        border-color: #FECACA;
        color: #B91C1C;
    }
    .update-page-helper-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }
    .update-page-helper-card {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px;
        border: 1px solid #E5E7EB;
        border-radius: 14px;
        background: #FFFFFF;
        font-size: 12px;
        color: #6B7280;
        line-height: 1.55;
    }
    .update-page-helper-card i {
        color: #4F46E5;
        margin-top: 2px;
    }
    .update-page-helper-card strong {
        display: block;
        margin-bottom: 4px;
        font-size: 12px;
        color: #111827;
    }
    .update-page-result {
        display: none;
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        background: #FFFFFF;
        overflow: hidden;
    }
    .update-page-result.show {
        display: block;
    }
    .update-page-result-head {
        padding: 16px;
        border-bottom: 1px solid #F3F4F6;
        background: rgba(249, 250, 251, 0.5);
    }
    .update-page-result-label {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #6B7280;
    }
    .update-page-result-title {
        margin-top: 6px;
        font-size: 15px;
        font-weight: 800;
        color: #111827;
    }
    .update-page-result-meta {
        margin-top: 6px;
        font-size: 11px;
        color: #6B7280;
    }
    .update-page-result-body {
        padding: 16px;
    }
    .update-page-result-notes-label {
        margin-bottom: 8px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #6B7280;
    }
    .update-page-result-notes {
        font-size: 13px;
        line-height: 1.65;
        color: #374151;
    }
    .update-progress-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
    }
    .update-progress-modal.show {
        display: block;
    }
    .update-progress-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(17, 24, 39, 0.5);
    }
    .update-progress-wrap {
        position: relative;
        z-index: 10;
        min-height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .update-progress-dialog {
        width: min(440px, calc(100vw - 24px));
    }
    .update-progress-card {
        overflow: hidden;
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        background: #FFFFFF;
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.18);
    }
    .update-progress-card-head {
        padding: 14px 16px;
        border-bottom: 1px solid #F3F4F6;
        background: rgba(249, 250, 251, 0.7);
    }
    .update-progress-title {
        font-size: 14px;
        font-weight: 800;
        color: #111827;
    }
    .update-progress-version {
        margin-top: 4px;
        font-size: 11px;
        color: #6B7280;
    }
    .update-progress-card-body {
        padding: 16px;
        display: grid;
        gap: 12px;
    }
    .update-progress-step {
        font-size: 12px;
        color: #374151;
    }
    .update-progress-bar-wrap {
        width: 100%;
        height: 12px;
        border-radius: 999px;
        background: #E5E7EB;
        overflow: hidden;
    }
    .update-progress-bar {
        width: 0%;
        height: 100%;
        border-radius: 999px;
        background: #4F46E5;
        transition: width .3s ease;
    }
    .update-progress-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-size: 11px;
        color: #6B7280;
    }
    .update-progress-percent {
        font-size: 12px;
        font-weight: 800;
        color: #111827;
    }
    .update-progress-hint {
        font-size: 11px;
        color: #6B7280;
    }
    .update-progress-actions {
        padding-top: 4px;
        display: flex;
        justify-content: flex-end;
    }
    .update-progress-close {
        display: none;
        align-items: center;
        gap: 8px;
        padding: 9px 14px;
        border-radius: 10px;
        border: 1px solid #E5E7EB;
        background: #FFFFFF;
        color: #374151;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }
    .update-progress-close.show {
        display: inline-flex;
    }
    @media (max-width: 900px) {
        .update-page-stats,
        .update-page-helper-grid {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 640px) {
        .update-page-hero-content {
            flex-direction: column;
            align-items: flex-start;
        }
        .update-page-content {
            padding: 14px;
        }
        .update-page-buttons {
            flex-direction: column;
            align-items: stretch;
        }
        .update-page-btn {
            width: 100%;
        }
    }
@endsection

@section('content')
<div class="view-section active animate-fade-in">
    <div class="update-page-shell">
        <div class="update-page-hero">
            <div class="update-page-hero-glow"></div>
            <div class="update-page-hero-content">
                <div>
                    <h3 class="update-page-hero-title">Update Aplikasi</h3>
                    <p class="update-page-hero-text">Cek versi terbaru dan pasang update resmi secara otomatis langsung dari panel admin.</p>
                </div>
                <div class="update-page-version-badge">
                    <i class="fas fa-tag"></i>
                    <span>v{{ $currentVersionLabel !== '' ? $currentVersionLabel : '-' }}</span>
                </div>
            </div>
        </div>

        <div class="update-page-content">
            <div class="update-page-stats">
                <div class="update-page-stat-card">
                    <div class="update-page-stat-head">
                        <div>
                            <div class="update-page-stat-label">Versi Saat Ini</div>
                            <div class="update-page-stat-value" id="current-version">{{ $currentVersionLabel !== '' ? $currentVersionLabel : '-' }}</div>
                        </div>
                        <div class="update-page-stat-icon indigo">
                            <i class="fas fa-code-branch"></i>
                        </div>
                    </div>
                    <div class="update-page-stat-hint">Versi yang sedang aktif di aplikasi.</div>
                </div>

                <div class="update-page-stat-card">
                    <div class="update-page-stat-head">
                        <div>
                            <div class="update-page-stat-label">Versi Terbaru</div>
                            <div class="update-page-stat-value" id="latest-version-mini">-</div>
                        </div>
                        <div class="update-page-stat-icon emerald">
                            <i class="fas fa-cloud-download-alt"></i>
                        </div>
                    </div>
                    <div class="update-page-stat-hint">Muncul setelah klik cek update.</div>
                </div>

                <div class="update-page-stat-card">
                    <div class="update-page-stat-head">
                        <div>
                            <div class="update-page-stat-label">Status</div>
                            <div class="update-page-stat-value" id="update-status">Belum dicek</div>
                            <div class="update-page-stat-hint" id="update-status-hint">Klik tombol cek update.</div>
                        </div>
                        <span id="badge-update" class="update-page-status-badge">-</span>
                    </div>
                    <div class="update-page-stat-hint">Paket update resmi diverifikasi signature.</div>
                </div>
            </div>

            <div class="update-page-history">
                <div class="update-page-history-title">
                    <i class="fas fa-history" style="color:#64748B;"></i>
                    <span>Update Terakhir</span>
                </div>
                <div style="margin-top:6px;">
                    @if ($lastFrom !== '' || $lastTo !== '' || $lastInstalledAt !== '')
                        Dari <span style="font-weight:800;">{{ $lastFrom !== '' ? $lastFrom : '-' }}</span>
                        ke <span style="font-weight:800;">{{ $lastTo !== '' ? $lastTo : '-' }}</span>
                        <span style="color:#6B7280;">({{ $lastInstalledAtLabel }})</span>
                    @else
                        Belum ada riwayat update.
                    @endif
                </div>
            </div>

            <div class="update-page-actions">
                <div class="update-page-buttons">
                    <button id="btnCheckUpdate" type="button" class="update-page-btn update-page-btn-primary">
                        <i class="fas fa-sync-alt"></i>
                        <span>Cek Update</span>
                    </button>
                    <button id="btnInstallUpdate" type="button" class="update-page-btn update-page-btn-success" disabled>
                        <i class="fas fa-download"></i>
                        <span>Install Update</span>
                    </button>
                </div>

                <div class="update-page-helper-grid">
                    <div class="update-page-helper-card">
                        <i class="fas fa-wifi"></i>
                        <div>
                            <strong>Koneksi Stabil</strong>
                            <span>Gunakan internet stabil saat proses update.</span>
                        </div>
                    </div>
                    <div class="update-page-helper-card">
                        <i class="fas fa-database"></i>
                        <div>
                            <strong>Backup Dulu</strong>
                            <span>Disarankan backup database dan file sebelum update.</span>
                        </div>
                    </div>
                    <div class="update-page-helper-card">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <strong>Terverifikasi</strong>
                            <span>{{ $publicKeyConfigured ? 'Update diverifikasi signature untuk keamanan.' : 'Public key updater belum diatur.' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div id="update-result" class="update-page-result">
                <div class="update-page-result-head">
                    <div class="update-page-result-label">Info Update</div>
                    <div class="update-page-result-title">Versi terbaru: <span id="latest-version">-</span></div>
                    <div class="update-page-result-meta">
                        Rilis: <span id="released-at">-</span> • Minimal PHP: <span id="min-php">-</span>
                    </div>
                </div>
                <div class="update-page-result-body">
                    <div class="update-page-result-notes-label">Catatan Rilis</div>
                    <div id="notes-html" class="update-page-result-notes"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="update-progress-modal" class="update-progress-modal" aria-hidden="true">
    <div class="update-progress-backdrop"></div>
    <div class="update-progress-wrap">
        <div class="update-progress-dialog">
            <div class="update-progress-card">
                <div class="update-progress-card-head">
                    <div class="update-progress-title" id="update-progress-title">Install Update</div>
                    <div class="update-progress-version" id="update-progress-version"></div>
                </div>
                <div class="update-progress-card-body">
                    <div class="update-progress-step" id="update-progress-step">Menyiapkan...</div>
                    <div class="update-progress-bar-wrap">
                        <div id="update-progress-bar" class="update-progress-bar"></div>
                    </div>
                    <div class="update-progress-meta">
                        <div class="update-progress-percent" id="update-progress-percent">0%</div>
                        <div id="update-progress-bytes"></div>
                    </div>
                    <div class="update-progress-hint" id="update-progress-hint">Jangan tutup halaman saat proses update berjalan.</div>
                    <div class="update-progress-actions">
                        <button id="update-progress-close" type="button" class="update-progress-close">
                            <i class="fas fa-times"></i>
                            <span>Tutup</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const checkUrl = @json(route('admin.update-aplikasi.check'));
    const installUrl = @json(route('admin.update-aplikasi.install'));
    const progressUrl = @json(route('admin.update-aplikasi.progress'));

    const btnCheck = document.getElementById('btnCheckUpdate');
    const btnInstall = document.getElementById('btnInstallUpdate');
    const resultBox = document.getElementById('update-result');
    const latestMiniEl = document.getElementById('latest-version-mini');
    const latestVersionEl = document.getElementById('latest-version');
    const releasedAtEl = document.getElementById('released-at');
    const minPhpEl = document.getElementById('min-php');
    const notesEl = document.getElementById('notes-html');
    const statusEl = document.getElementById('update-status');
    const statusHintEl = document.getElementById('update-status-hint');
    const badgeEl = document.getElementById('badge-update');

    const progressModal = document.getElementById('update-progress-modal');
    const progressBarEl = document.getElementById('update-progress-bar');
    const progressPercentEl = document.getElementById('update-progress-percent');
    const progressStepEl = document.getElementById('update-progress-step');
    const progressBytesEl = document.getElementById('update-progress-bytes');
    const progressCloseEl = document.getElementById('update-progress-close');
    const progressHintEl = document.getElementById('update-progress-hint');
    const progressVersionEl = document.getElementById('update-progress-version');

    let progressPolling = false;
    let progressPollTimer = null;
    let latestPayload = null;

    const postJson = async (url, payload = {}) => {
        const res = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload || {})
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            const msg = data?.message || 'Gagal memproses permintaan.';
            throw new Error(msg);
        }
        return data;
    };

    const getJson = async (url) => {
        const res = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
            }
        });

        if (!res.ok) return null;
        return await res.json().catch(() => null);
    };

    const formatBytes = (bytes) => {
        const val = Number(bytes || 0);
        if (!Number.isFinite(val) || val <= 0) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        const idx = Math.min(units.length - 1, Math.floor(Math.log(val) / Math.log(1024)));
        const size = val / Math.pow(1024, idx);
        return `${size >= 10 ? size.toFixed(0) : size.toFixed(1)} ${units[idx]}`;
    };

    const setLoading = (button, isLoading, text = 'Memproses...') => {
        if (!button) return;
        if (!button.dataset.originalHtml) {
            button.dataset.originalHtml = button.innerHTML;
        }
        button.disabled = !!isLoading;
        button.innerHTML = isLoading
            ? `<i class="fas fa-spinner fa-spin"></i><span>${text}</span>`
            : button.dataset.originalHtml;
    };

    const setStatus = (text, mode = 'neutral') => {
        if (statusEl) statusEl.textContent = text;

        if (!badgeEl) return;

        badgeEl.textContent = mode === 'available' ? 'Update' : mode === 'error' ? 'Error' : mode === 'success' ? 'OK' : text;
        badgeEl.className = 'update-page-status-badge';

        if (mode === 'available') badgeEl.classList.add('available');
        else if (mode === 'error') badgeEl.classList.add('error');
        else if (mode === 'success') badgeEl.classList.add('success');
    };

    const applyResult = (payload) => {
        const data = payload?.data || {};
        const available = !!data.update_available;

        latestPayload = data;

        if (statusEl) statusEl.textContent = available ? 'Update tersedia' : 'Versi terbaru';
        if (statusHintEl) statusHintEl.textContent = available ? 'Klik Install Update untuk memasang.' : 'Tidak ada update.';

        if (latestMiniEl) latestMiniEl.textContent = data.latest_version || '-';
        if (latestVersionEl) latestVersionEl.textContent = data.latest_version || '-';
        if (releasedAtEl) releasedAtEl.textContent = data.released_at || '-';
        if (minPhpEl) minPhpEl.textContent = data.min_php || '-';
        if (notesEl) {
            notesEl.innerHTML = data.notes_html || '<div style="font-size:12px;color:#6B7280;font-style:italic;">Tidak ada catatan rilis.</div>';
        }

        if (resultBox) resultBox.classList.add('show');

        if (available) {
            setStatus('Update tersedia', 'available');
            if (btnInstall) {
                btnInstall.dataset.canInstall = '1';
                btnInstall.disabled = false;
            }
        } else {
            setStatus('Sudah terbaru', 'success');
            if (btnInstall) {
                btnInstall.dataset.canInstall = '0';
                btnInstall.disabled = true;
            }
        }
    };

    const showError = (message) => {
        if (statusEl) statusEl.textContent = 'Gagal';
        if (statusHintEl) statusHintEl.textContent = message;
        setStatus('Gagal', 'error');

        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Gagal', text: message });
        } else {
            alert(message);
        }
    };

    const openProgressModal = () => {
        if (!progressModal) return;
        progressModal.classList.add('show');
        progressModal.setAttribute('aria-hidden', 'false');
        document.documentElement.classList.add('overflow-hidden');

        if (progressCloseEl) progressCloseEl.classList.remove('show');
        if (progressBarEl) {
            progressBarEl.style.width = '0%';
            progressBarEl.style.background = '#4F46E5';
        }
        if (progressPercentEl) progressPercentEl.textContent = '0%';
        if (progressStepEl) progressStepEl.textContent = 'Menyiapkan...';
        if (progressBytesEl) progressBytesEl.textContent = '';
        if (progressHintEl) progressHintEl.textContent = 'Jangan tutup halaman saat proses update berjalan.';
        if (progressVersionEl) progressVersionEl.textContent = latestPayload?.latest_version ? `Target versi ${latestPayload.latest_version}` : '';
    };

    const closeProgressModal = () => {
        if (!progressModal) return;
        progressModal.classList.remove('show');
        progressModal.setAttribute('aria-hidden', 'true');
        document.documentElement.classList.remove('overflow-hidden');
    };

    const stopProgressPolling = () => {
        progressPolling = false;
        if (progressPollTimer) {
            window.clearTimeout(progressPollTimer);
            progressPollTimer = null;
        }
    };

    const renderProgress = (payload) => {
        const status = String(payload?.status || '');
        const step = String(payload?.step || '');
        const message = String(payload?.message || '');
        const percent = Math.max(0, Math.min(100, Number(payload?.percent ?? 0)));

        const downloadPercent = Number(payload?.download_percent ?? 0);
        const downloadTotal = Number(payload?.download_total_bytes ?? 0);
        const hasDownloadTotal = Number.isFinite(downloadTotal) && downloadTotal > 0;
        const displayPercent = step === 'download' && hasDownloadTotal && Number.isFinite(downloadPercent)
            ? Math.max(0, Math.min(100, downloadPercent))
            : percent;

        const fromVer = String(payload?.from_version || payload?.from || '');
        const toVer = String(payload?.to_version || payload?.to || '');

        if (progressStepEl) {
            progressStepEl.textContent = message !== '' ? message : (step !== '' ? step : 'Memproses...');
        }

        if (progressVersionEl) {
            if (fromVer !== '' || toVer !== '') {
                progressVersionEl.textContent = `Dari v${fromVer || '-'} ke v${toVer || '-'}`;
            } else {
                progressVersionEl.textContent = latestPayload?.latest_version ? `Target versi ${latestPayload.latest_version}` : '';
            }
        }

        if (progressBarEl) progressBarEl.style.width = `${displayPercent}%`;
        if (progressPercentEl) progressPercentEl.textContent = `${Math.round(displayPercent)}%`;

        if (progressBytesEl) {
            const downloaded = Number(payload?.downloaded_bytes ?? 0);
            const total = Number(payload?.download_total_bytes ?? 0);

            if (step === 'download' && Number.isFinite(downloaded) && downloaded > 0) {
                progressBytesEl.textContent = Number.isFinite(total) && total > 0
                    ? `${formatBytes(downloaded)} / ${formatBytes(total)}`
                    : formatBytes(downloaded);
            } else {
                progressBytesEl.textContent = '';
            }
        }

        if (status === 'done') {
            if (progressHintEl) progressHintEl.textContent = 'Update selesai. Halaman akan dimuat ulang.';
            if (progressBarEl) progressBarEl.style.background = '#10B981';
            if (progressCloseEl) progressCloseEl.classList.add('show');
        }

        if (status === 'error') {
            if (progressHintEl) progressHintEl.textContent = 'Update gagal. Klik Tutup, periksa koneksi/izin file, lalu coba lagi.';
            if (progressBarEl) progressBarEl.style.background = '#DC2626';
            if (progressCloseEl) progressCloseEl.classList.add('show');
        }
    };

    const pollProgress = async () => {
        if (!progressPolling) return;

        try {
            const payload = await getJson(progressUrl);
            if (payload) {
                renderProgress(payload);
                const st = String(payload?.status || '');
                if (st === 'done' || st === 'error') {
                    stopProgressPolling();
                    return;
                }
            }
        } catch (e) {
            // ignore
        }

        if (progressPolling) {
            progressPollTimer = window.setTimeout(pollProgress, 800);
        }
    };

    const startProgressPolling = () => {
        if (progressPolling) return;
        progressPolling = true;
        pollProgress();
    };

    const setButtonsLoading = (isLoading) => {
        setLoading(btnCheck, isLoading, 'Memeriksa...');
        if (btnInstall) {
            btnInstall.disabled = isLoading || btnInstall.dataset.canInstall !== '1';
        }
    };

    btnCheck?.addEventListener('click', async () => {
        try {
            setButtonsLoading(true);
            if (statusEl) statusEl.textContent = 'Memeriksa...';
            if (statusHintEl) statusHintEl.textContent = 'Memeriksa versi terbaru.';
            setStatus('Memeriksa...', 'neutral');

            const res = await postJson(checkUrl);
            if (!res?.success) throw new Error(res?.message || 'Gagal cek update.');

            applyResult(res);
        } catch (e) {
            showError(e?.message || String(e));
        } finally {
            setButtonsLoading(false);
        }
    });

    btnInstall?.addEventListener('click', async () => {
        if (btnInstall.dataset.canInstall !== '1') return;

        const ok = await (window.panelConfirm?.show?.({
            title: 'Install Update',
            subtitle: 'Paket update akan dipasang ke aplikasi ini.',
            message: 'Lanjut install update sekarang?',
            note: 'Proses ini akan menimpa file aplikasi. Pastikan backup database dan file sudah tersedia.',
            confirmText: 'Ya, Install Update',
            cancelText: 'Batal',
            variant: 'warning',
            icon: 'fa-download'
        }) ?? Promise.resolve(confirm('Install update sekarang? Pastikan sudah backup.')));

        if (!ok) return;

        try {
            setButtonsLoading(true);
            openProgressModal();
            startProgressPolling();

            const res = await postJson(installUrl);
            if (!res?.success) throw new Error(res?.message || 'Update gagal.');

            renderProgress({
                status: 'done',
                step: 'done',
                message: res?.message || 'Update selesai.',
                percent: 100,
                from_version: res?.data?.from || '',
                to_version: res?.data?.to || '',
            });
        } catch (e) {
            const msg = e?.message || String(e);
            renderProgress({ status: 'error', step: 'error', message: msg, percent: 100 });

            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Update Gagal', text: msg });
            } else {
                alert(msg);
            }
        } finally {
            stopProgressPolling();
            setButtonsLoading(false);
        }
    });

    progressCloseEl?.addEventListener('click', () => closeProgressModal());

    (async () => {
        const payload = await getJson(progressUrl);
        if (payload && String(payload?.status || '') === 'running') {
            openProgressModal();
            renderProgress(payload);
            startProgressPolling();
        }
    })();
})();
</script>
@endsection
