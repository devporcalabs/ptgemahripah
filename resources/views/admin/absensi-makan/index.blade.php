@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => $pageTitle])

@section('content')
    <div class="summary-banner d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <i class="fas fa-utensils me-2"></i>
            <span>Manajemen <strong>Absensi Makan & Kupon Kantin</strong> PT Gemah Ripah.</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.absensi-makan.scanner') }}" class="btn btn-sm btn-success shadow-sm" style="background:#065F46; border-color:#065F46; color:#fff; padding: 6px 14px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                <i class="fas fa-qrcode me-1"></i> Buka Kios Scanner Kantin
            </a>
            <button type="button" class="btn btn-sm btn-primary" onclick="openManualModal()" style="background:#1D4ED8; border-color:#1D4ED8; color:#fff; padding: 6px 14px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                <i class="fas fa-plus me-1"></i> Input Manual
            </button>
            <a href="{{ route('admin.absensi-makan.export', request()->query()) }}" class="btn btn-sm btn-secondary" style="background:#475569; border-color:#475569; color:#fff; padding: 6px 14px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                <i class="fas fa-file-excel me-1"></i> Export Data
            </a>
        </div>
    </div>

    <div id="ajaxFilterFragment">
        <!-- Stats Grid -->
        <div class="stats-grid mb-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div class="stat-card" style="background: #fff; padding: 18px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-left: 4px solid #065F46;">
                <div class="stat-card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-value" style="font-size: 28px; font-weight: 700; color: #0F172A;">{{ $stats['total_porsi'] }}</div>
                        <div class="stat-label" style="font-size: 13px; color: #64748B; font-weight: 500;">Total Porsi ({{ $selectedDate->format('d M Y') }})</div>
                    </div>
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: #D1FAE5; display: flex; align-items: center; justify-content: center; color: #065F46; font-size: 20px;">
                        <i class="fas fa-bowl-rice"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card" style="background: #fff; padding: 18px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-left: 4px solid #3B82F6;">
                <div class="stat-card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-value" style="font-size: 28px; font-weight: 700; color: #0F172A;">{{ $stats['porsi_siang'] }}</div>
                        <div class="stat-label" style="font-size: 13px; color: #64748B; font-weight: 500;">Makan Siang</div>
                    </div>
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: #EFF6FF; display: flex; align-items: center; justify-content: center; color: #3B82F6; font-size: 20px;">
                        <i class="fas fa-sun"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card" style="background: #fff; padding: 18px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-left: 4px solid #8B5CF6;">
                <div class="stat-card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-value" style="font-size: 28px; font-weight: 700; color: #0F172A;">{{ $stats['porsi_malam'] }}</div>
                        <div class="stat-label" style="font-size: 13px; color: #64748B; font-weight: 500;">Makan Malam / Shift</div>
                    </div>
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: #F3E8FF; display: flex; align-items: center; justify-content: center; color: #8B5CF6; font-size: 20px;">
                        <i class="fas fa-moon"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card" style="background: #fff; padding: 18px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-left: 4px solid #F59E0B;">
                <div class="stat-card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-value" style="font-size: 28px; font-weight: 700; color: #0F172A;">{{ $stats['porsi_lembur'] }}</div>
                        <div class="stat-label" style="font-size: 13px; color: #64748B; font-weight: 500;">Makan Lembur</div>
                    </div>
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: #FEF3C7; display: flex; align-items: center; justify-content: center; color: #D97706; font-size: 20px;">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card" style="background: #fff; padding: 18px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-left: 4px solid #10B981;">
                <div class="stat-card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-value" style="font-size: 20px; font-weight: 700; color: #065F46;">{{ $stats['formatted_nominal'] }}</div>
                        <div class="stat-label" style="font-size: 13px; color: #64748B; font-weight: 500;">Estimasi Biaya Makan</div>
                    </div>
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: #ECFDF5; display: flex; align-items: center; justify-content: center; color: #10B981; font-size: 20px;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card mb-4" style="background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
            <form method="GET" action="{{ route('admin.absensi-makan') }}" class="d-flex flex-wrap gap-3 align-items-center" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxFilterFragment">
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ $selectedDate->format('Y-m-d') }}" class="form-control" style="padding: 6px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px;">
                </div>

                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Sesi Makan</label>
                    <select name="jenis_makan" class="form-control" style="padding: 6px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px;">
                        <option value="">Semua Sesi</option>
                        <option value="siang" {{ request('jenis_makan') === 'siang' ? 'selected' : '' }}>Makan Siang</option>
                        <option value="malam" {{ request('jenis_makan') === 'malam' ? 'selected' : '' }}>Makan Malam</option>
                        <option value="lembur" {{ request('jenis_makan') === 'lembur' ? 'selected' : '' }}>Makan Lembur</option>
                    </select>
                </div>

                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Departemen</label>
                    <select name="departemen_id" class="form-control" style="padding: 6px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px;">
                        <option value="">Semua Departemen</option>
                        @foreach ($departemenList as $dep)
                            <option value="{{ $dep->id }}" {{ (string) request('departemen_id') === (string) $dep->id ? 'selected' : '' }}>{{ $dep->nama_departemen }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="flex-grow: 1; min-width: 180px;">
                    <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Cari Karyawan / NIK / RFID</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau NIK..." class="form-control" style="width: 100%; padding: 6px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px;">
                </div>

                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Per Halaman</label>
                    <select name="per_page" class="form-control" style="padding: 6px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px;">
                        @foreach ([10, 20, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int) request('per_page', $perPage ?? 20) === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="align-self: flex-end;">
                    <button type="submit" class="btn btn-primary" style="background: #065F46; border: none; color: #fff; padding: 7px 16px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    @if (request()->hasAny(['jenis_makan', 'departemen_id', 'q', 'per_page']) || (request('tanggal') && request('tanggal') !== now()->format('Y-m-d')))
                        <a href="{{ route('admin.absensi-makan') }}" class="btn btn-link" data-ajax-link="true" data-refresh-target="#ajaxFilterFragment" style="color: #64748B; text-decoration: none; font-size: 13px; margin-left: 8px;">Reset</a>
                    @endif
                </div>
            </form>
        </div>

    <!-- Table Card -->
    <div class="card" style="background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden;">
        <div class="card-header d-flex justify-content-between align-items-center" style="padding: 16px 20px; border-bottom: 1px solid #E2E8F0; background: #F8FAFC;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0F172A;">
                <i class="fas fa-list-check me-2" style="color: #065F46;"></i>Riwayat Kupon & Absensi Makan
            </h3>
            <span style="font-size: 13px; color: #64748B;">Total: <strong>{{ $records->total() }}</strong> catatan</span>
        </div>

        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background: #F1F5F9; color: #475569; text-align: left; font-weight: 600; border-bottom: 1px solid #E2E8F0;">
                        <th style="padding: 12px 16px;">Waktu Tap</th>
                        <th style="padding: 12px 16px;">Karyawan</th>
                        <th style="padding: 12px 16px;">Departemen & Jabatan</th>
                        <th style="padding: 12px 16px;">Sesi Makan</th>
                        <th style="padding: 12px 16px;">Metode</th>
                        <th style="padding: 12px 16px;">Lokasi Kantin</th>
                        <th style="padding: 12px 16px;">Nominal</th>
                        <th style="padding: 12px 16px;">Status</th>
                        <th style="padding: 12px 16px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $r)
                        <tr style="border-bottom: 1px solid #F1F5F9;">
                            <td style="padding: 12px 16px; white-space: nowrap;">
                                <div style="font-weight: 600; color: #0F172A;">{{ Carbon\Carbon::parse($r->jam_makan)->format('H:i') }} WIB</div>
                                <div style="font-size: 12px; color: #64748B;">{{ $r->tanggal->format('d/m/Y') }}</div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <div style="font-weight: 600; color: #0F172A;">{{ $r->karyawan?->nama_lengkap ?? '-' }}</div>
                                <div style="font-size: 12px; color: #64748B;">NIK: {{ $r->karyawan?->nik ?? '-' }}</div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <div style="color: #0F172A;">{{ $r->karyawan?->departemen ?? '-' }}</div>
                                <div style="font-size: 12px; color: #64748B;">{{ $r->karyawan?->jabatan ?? '-' }}</div>
                            </td>
                            <td style="padding: 12px 16px;">
                                @if ($r->jenis_makan === 'siang')
                                    <span style="background: #EFF6FF; color: #1D4ED8; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-sun"></i> Siang
                                    </span>
                                @elseif ($r->jenis_makan === 'malam')
                                    <span style="background: #F3E8FF; color: #7E22CE; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-moon"></i> Malam
                                    </span>
                                @elseif ($r->jenis_makan === 'lembur')
                                    <span style="background: #FEF3C7; color: #B45309; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-clock"></i> Lembur
                                    </span>
                                @else
                                    <span style="background: #F1F5F9; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">{{ $r->jenis_makan_label }}</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px;">
                                <span style="font-size: 13px; color: #334155;">
                                    @if ($r->metode === 'rfid')
                                        <i class="fas fa-id-card me-1" style="color:#065F46;"></i> Tap Kartu
                                    @elseif ($r->metode === 'web')
                                        <i class="fas fa-globe me-1" style="color:#2563EB;"></i> Portal Web
                                    @elseif ($r->metode === 'qr_scan')
                                        <i class="fas fa-qrcode me-1" style="color:#D97706;"></i> Scan QR
                                    @else
                                        <i class="fas fa-keyboard me-1" style="color:#64748B;"></i> Manual
                                    @endif
                                </span>
                            </td>
                            <td style="padding: 12px 16px; font-size: 13px; color: #475569;">
                                {{ $r->lokasiGps?->nama_lokasi ?? 'Kantin Utama' }}
                            </td>
                            <td style="padding: 12px 16px; font-weight: 600; color: #065F46;">
                                Rp {{ number_format($r->nominal, 0, ',', '.') }}
                            </td>
                            <td style="padding: 12px 16px;">
                                {!! $r->status_badge !!}
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <form action="{{ route('admin.absensi-makan.destroy', $r->id) }}" method="POST" data-ajax="true" data-refresh-target="#ajaxFilterFragment" data-confirm="Apakah Anda yakin ingin membatalkan kupon makan ini?" data-confirm-title="Batal Kupon Makan" data-confirm-button="Ya, Batalkan" data-confirm-variant="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" style="background: none; border: 1px solid #EF4444; color: #EF4444; padding: 4px 8px; border-radius: 6px; cursor: pointer;" title="Hapus Kupon">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding: 40px; text-align: center; color: #94A3B8;">
                                <i class="fas fa-utensils" style="font-size: 40px; margin-bottom: 12px; color: #CBD5E1; display: block;"></i>
                                Belum ada data absensi makan pada filter tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $records->links('partials.pagination-ajax', ['target' => '#ajaxFilterFragment']) }}
    </div>
</div>

    <!-- Modal Input Manual -->
    <div id="manualModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center;">
        <div style="background: #fff; width: 100%; max-width: 500px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; margin: 16px;">
            <div style="padding: 16px 20px; background: #065F46; color: #fff; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin: 0; font-size: 16px; font-weight: 600;"><i class="fas fa-plus-circle me-2"></i>Input Absensi Makan Manual</h4>
                <button type="button" onclick="closeManualModal()" style="background: none; border: none; color: #fff; font-size: 20px; cursor: pointer;">&times;</button>
            </div>

            <form action="{{ route('admin.absensi-makan.store') }}" method="POST" data-ajax="true" data-refresh-target="#ajaxFilterFragment" data-close-modal="#manualModal" data-reset-on-success="true" style="padding: 20px;">
                @csrf
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 13px; font-weight: 600; color: #334155; display: block; margin-bottom: 6px;">Pilih Karyawan *</label>
                    <select name="karyawan_id" class="form-control" required style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px;">
                        <option value="">-- Pilih Karyawan --</option>
                        @foreach ($activeEmployees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->nama_lengkap }} ({{ $emp->nik }}) - {{ $emp->departemen }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div>
                        <label style="font-size: 13px; font-weight: 600; color: #334155; display: block; margin-bottom: 6px;">Tanggal *</label>
                        <input type="date" name="tanggal" value="{{ now()->format('Y-m-d') }}" required class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="font-size: 13px; font-weight: 600; color: #334155; display: block; margin-bottom: 6px;">Jam Makan</label>
                        <input type="time" name="jam_makan" value="{{ now()->format('H:i') }}" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px;">
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="font-size: 13px; font-weight: 600; color: #334155; display: block; margin-bottom: 6px;">Sesi Makan *</label>
                    <select name="jenis_makan" class="form-control" required style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px;">
                        <option value="siang" selected>Makan Siang</option>
                        <option value="malam">Makan Malam / Shift</option>
                        <option value="lembur">Makan Lembur</option>
                        <option value="sahur">Sahur</option>
                    </select>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-size: 13px; font-weight: 600; color: #334155; display: block; margin-bottom: 6px;">Catatan (Opsional)</label>
                    <input type="text" name="catatan" placeholder="Contoh: Kartu RFID tertinggal di loker" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeManualModal()" class="btn btn-secondary" style="background: #64748B; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background: #065F46; color: #fff; border: none; padding: 8px 18px; border-radius: 8px; font-weight: 600; cursor: pointer;">Simpan Kupon</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openManualModal() {
            document.getElementById('manualModal').style.display = 'flex';
        }
        function closeManualModal() {
            document.getElementById('manualModal').style.display = 'none';
        }
        window.onclick = function(event) {
            const modal = document.getElementById('manualModal');
            if (event.target === modal) {
                closeManualModal();
            }
        }
    </script>
@endsection
