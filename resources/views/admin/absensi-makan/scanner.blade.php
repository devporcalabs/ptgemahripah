@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Kios Scanner Kantin'])

@section('content')
<div style="max-width: 1000px; margin: 0 auto;">
    <!-- Kiosk Header -->
    <div style="background: linear-gradient(135deg, #065F46 0%, #0D5C3F 100%); color: #fff; border-radius: 16px; padding: 24px 30px; box-shadow: 0 10px 25px -5px rgba(6, 95, 70, 0.3); margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="fas fa-utensils"></i>
                </div>
                <h2 style="margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -0.5px;">Kios Scanner Kantin PT Gemah Ripah</h2>
            </div>
            <p style="margin: 0; opacity: 0.85; font-size: 14px;">Tempelkan kartu RFID karyawan atau scan barcode NIK untuk verifikasi kupon makan.</p>
        </div>

        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="{{ route('admin.absensi-makan') }}" style="background: rgba(255,255,255,0.15); color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-arrow-left"></i> Kembali ke Riwayat
            </a>
        </div>
    </div>

    <!-- Active Session & Live Counters -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: #fff; border-radius: 12px; padding: 16px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border-left: 4px solid #10B981;">
            <div style="font-size: 12px; font-weight: 600; color: #64748B; text-transform: uppercase;">Sesi Aktif</div>
            <div style="margin-top: 6px; display: flex; align-items: center; gap: 8px;">
                <select id="sessionSelector" style="padding: 6px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-weight: 700; color: #065F46; font-size: 15px; background: #ECFDF5;">
                    <option value="siang" {{ $currentSession === 'siang' ? 'selected' : '' }}>☀️ Makan Siang</option>
                    <option value="malam" {{ $currentSession === 'malam' ? 'selected' : '' }}>🌙 Makan Malam / Shift</option>
                    <option value="lembur" {{ $currentSession === 'lembur' ? 'selected' : '' }}>⏱️ Makan Lembur</option>
                    <option value="sahur" {{ $currentSession === 'sahur' ? 'selected' : '' }}>🌙 Sahur</option>
                </select>
            </div>
        </div>

        <div style="background: #fff; border-radius: 12px; padding: 16px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border-left: 4px solid #3B82F6;">
            <div style="font-size: 12px; font-weight: 600; color: #64748B; text-transform: uppercase;">Porsi Hari Ini</div>
            <div style="font-size: 26px; font-weight: 800; color: #1E293B; margin-top: 4px;" id="statTotalPorsi">
                {{ $stats['total_porsi'] }}
            </div>
        </div>

        <div style="background: #fff; border-radius: 12px; padding: 16px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border-left: 4px solid #8B5CF6;">
            <div style="font-size: 12px; font-weight: 600; color: #64748B; text-transform: uppercase;">Porsi Sesi Ini</div>
            <div style="font-size: 26px; font-weight: 800; color: #1E293B; margin-top: 4px;" id="statPorsiSesi">
                {{ $currentSession === 'malam' ? $stats['porsi_malam'] : ($currentSession === 'lembur' ? $stats['porsi_lembur'] : $stats['porsi_siang']) }}
            </div>
        </div>
    </div>

    <!-- Scanner Input Area -->
    <div style="background: #fff; border-radius: 16px; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); text-align: center; margin-bottom: 24px;">
        <div style="max-width: 500px; margin: 0 auto;">
            <label for="scannerInput" style="display: block; font-size: 16px; font-weight: 700; color: #1E293B; margin-bottom: 12px;">
                <i class="fas fa-id-card-clip me-1" style="color: #065F46;"></i> Tap Kartu RFID atau Masukkan NIK:
            </label>
            <div style="position: relative;">
                <input type="text" id="scannerInput" autocomplete="off" autofocus placeholder="Menunggu tap kartu RFID..."
                       style="width: 100%; font-size: 22px; font-weight: 700; text-align: center; letter-spacing: 2px; padding: 14px 20px; border: 2px solid #065F46; border-radius: 12px; outline: none; background: #F8FAFC; color: #0F172A; box-shadow: 0 0 0 4px rgba(6, 95, 70, 0.1);">
                <div id="loadingSpinner" style="display: none; position: absolute; right: 16px; top: 50%; transform: translateY(-50%);">
                    <i class="fas fa-spinner fa-spin" style="font-size: 22px; color: #065F46;"></i>
                </div>
            </div>
            <div style="font-size: 12px; color: #94A3B8; margin-top: 8px;">
                <i class="fas fa-circle-info me-1"></i> Kolom ini selalu fokus otomatis. Cukup tempelkan kartu RFID pada card reader.
            </div>
        </div>
    </div>

    <!-- Verification Result Card -->
    <div id="resultCard" style="display: none; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 24px; transition: all 0.3s ease;">
        <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
            <div id="avatarContainer" style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 36px; color: #fff; flex-shrink: 0; overflow: hidden; background: #065F46;">
                <i class="fas fa-user"></i>
            </div>
            <div style="flex-grow: 1;">
                <div id="resultBadge" style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 700; margin-bottom: 6px;"></div>
                <h3 id="resultName" style="margin: 0 0 4px 0; font-size: 22px; font-weight: 700; color: #0F172A;">Nama Karyawan</h3>
                <div id="resultDetails" style="font-size: 14px; color: #475569; display: flex; gap: 16px; flex-wrap: wrap;">
                    <span id="detailNik">NIK: -</span>
                    <span id="detailDept">Dept: -</span>
                    <span id="detailJabatan">Jabatan: -</span>
                </div>
                <div id="resultMessage" style="margin-top: 8px; font-size: 14px; font-weight: 600;"></div>
            </div>
        </div>
    </div>

    <!-- Live Stream of Recent Scans -->
    <div style="background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #E2E8F0; background: #F8FAFC; display: flex; justify-content: space-between; align-items: center;">
            <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #1E293B;">
                <i class="fas fa-clock-rotate-left me-2" style="color: #065F46;"></i>Aktivitas Tap Terkini
            </h4>
            <span style="font-size: 12px; color: #64748B;">Update otomatis</span>
        </div>
        <div style="max-height: 280px; overflow-y: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #F1F5F9; color: #64748B; text-align: left;">
                        <th style="padding: 10px 16px;">Jam</th>
                        <th style="padding: 10px 16px;">Nama Karyawan</th>
                        <th style="padding: 10px 16px;">Departemen</th>
                        <th style="padding: 10px 16px;">Sesi</th>
                        <th style="padding: 10px 16px;">Status</th>
                    </tr>
                </thead>
                <tbody id="recentScanBody">
                    <tr>
                        <td colspan="5" style="padding: 24px; text-align: center; color: #94A3B8;">
                            Menunggu proses tap kartu pertama...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const scannerInput = document.getElementById('scannerInput');
    const sessionSelector = document.getElementById('sessionSelector');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const resultCard = document.getElementById('resultCard');
    const resultBadge = document.getElementById('resultBadge');
    const resultName = document.getElementById('resultName');
    const detailNik = document.getElementById('detailNik');
    const detailDept = document.getElementById('detailDept');
    const detailJabatan = document.getElementById('detailJabatan');
    const resultMessage = document.getElementById('resultMessage');
    const avatarContainer = document.getElementById('avatarContainer');
    const recentScanBody = document.getElementById('recentScanBody');
    const statTotalPorsi = document.getElementById('statTotalPorsi');
    const statPorsiSesi = document.getElementById('statPorsiSesi');

    // Keep input field focused at all times
    function ensureFocus() {
        if (document.activeElement !== sessionSelector) {
            scannerInput.focus();
        }
    }
    document.addEventListener('click', ensureFocus);
    setInterval(ensureFocus, 2000);

    // Synthesized Audio Beeps
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    function playBeep(type = 'success') {
        try {
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);

            if (type === 'success') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
                osc.frequency.setValueAtTime(880, audioCtx.currentTime + 0.1); // A5
                gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.35);
            } else {
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(220, audioCtx.currentTime); // A3
                gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.4);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.4);
            }
        } catch (e) {
            console.log('Audio error:', e);
        }
    }

    let isSubmitting = false;

    scannerInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = this.value.trim();
            if (val && !isSubmitting) {
                processScan(val);
            }
        }
    });

    function processScan(identifier) {
        isSubmitting = true;
        loadingSpinner.style.display = 'block';

        fetch('{{ route('admin.absensi-makan.verify') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                identifier: identifier,
                jenis_makan: sessionSelector.value
            })
        })
        .then(res => res.json())
        .then(data => {
            isSubmitting = false;
            loadingSpinner.style.display = 'none';
            scannerInput.value = '';
            scannerInput.focus();

            renderResult(data);
        })
        .catch(err => {
            isSubmitting = false;
            loadingSpinner.style.display = 'none';
            scannerInput.value = '';
            scannerInput.focus();
            playBeep('error');
            console.error('Scan error:', err);
        });
    }

    let hasScans = false;

    function renderResult(data) {
        resultCard.style.display = 'block';

        if (data.success) {
            playBeep('success');
            resultCard.style.background = '#ECFDF5';
            resultCard.style.border = '2px solid #10B981';

            resultBadge.textContent = 'BERHASIL TERVERIFIKASI';
            resultBadge.style.background = '#10B981';
            resultBadge.style.color = '#fff';

            resultMessage.textContent = data.message;
            resultMessage.style.color = '#065F46';

            if (data.karyawan_info) {
                resultName.textContent = data.karyawan_info.nama;
                detailNik.textContent = 'NIK: ' + data.karyawan_info.nik;
                detailDept.textContent = 'Dept: ' + data.karyawan_info.departemen;
                detailJabatan.textContent = 'Jabatan: ' + data.karyawan_info.jabatan;

                if (data.karyawan_info.foto) {
                    avatarContainer.innerHTML = `<img src="${data.karyawan_info.foto}" style="width: 100%; height: 100%; object-fit: cover;">`;
                } else {
                    avatarContainer.innerHTML = '<i class="fas fa-user-check"></i>';
                }

                addToRecentStream(data.karyawan_info.nama, data.karyawan_info.departemen, sessionSelector.value, true);
            }

            if (data.stats) {
                statTotalPorsi.textContent = data.stats.total_porsi;
                statPorsiSesi.textContent = sessionSelector.value === 'malam' ? data.stats.porsi_malam :
                                            (sessionSelector.value === 'lembur' ? data.stats.porsi_lembur : data.stats.porsi_siang);
            }
        } else {
            playBeep('error');
            resultCard.style.background = data.reason === 'already_claimed' ? '#FFFBEB' : '#FEF2F2';
            resultCard.style.border = data.reason === 'already_claimed' ? '2px solid #F59E0B' : '2px solid #EF4444';

            resultBadge.textContent = data.reason === 'already_claimed' ? 'SUDAH MENGAMBIL KUPON' : 'VERIFIKASI GAGAL';
            resultBadge.style.background = data.reason === 'already_claimed' ? '#F59E0B' : '#EF4444';
            resultBadge.style.color = '#fff';

            resultMessage.textContent = data.message;
            resultMessage.style.color = data.reason === 'already_claimed' ? '#B45309' : '#B91C1C';

            if (data.karyawan_info) {
                resultName.textContent = data.karyawan_info.nama;
                detailNik.textContent = 'NIK: ' + data.karyawan_info.nik;
                detailDept.textContent = 'Dept: ' + data.karyawan_info.departemen;
                detailJabatan.textContent = 'Jabatan: ' + data.karyawan_info.jabatan;
                avatarContainer.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                addToRecentStream(data.karyawan_info.nama, data.karyawan_info.departemen, sessionSelector.value, false);
            } else {
                resultName.textContent = 'Kartu Tidak Terdaftar';
                detailNik.textContent = '-';
                detailDept.textContent = '-';
                detailJabatan.textContent = '-';
                avatarContainer.innerHTML = '<i class="fas fa-ban"></i>';
            }
        }
    }

    function addToRecentStream(nama, dept, sesi, isSuccess) {
        if (!hasScans) {
            recentScanBody.innerHTML = '';
            hasScans = true;
        }

        const now = new Date();
        const timeStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ':' + String(now.getSeconds()).padStart(2, '0');

        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid #F1F5F9';
        tr.innerHTML = `
            <td style="padding: 10px 16px; font-weight: 600; color: #475569;">${timeStr}</td>
            <td style="padding: 10px 16px; font-weight: 700; color: #0F172A;">${nama}</td>
            <td style="padding: 10px 16px; color: #475569;">${dept}</td>
            <td style="padding: 10px 16px;"><span style="text-transform: capitalize;">${sesi}</span></td>
            <td style="padding: 10px 16px;">
                ${isSuccess ? '<span style="color: #065F46; font-weight: 700;"><i class="fas fa-check-circle me-1"></i>Sukses</span>' : '<span style="color: #DC2626; font-weight: 700;"><i class="fas fa-times-circle me-1"></i>Ditolak</span>'}
            </td>
        `;

        recentScanBody.insertBefore(tr, recentScanBody.firstChild);
    }
</script>
@endsection
