@extends('layouts.panel', ['panel' => 'karyawan', 'pageTitle' => $pageTitle])

@section('content')
<div style="max-width: 900px; margin: 0 auto;">
    <!-- Banner -->
    <div class="summary-banner mb-4" style="background: linear-gradient(135deg, #065F46 0%, #0D5C3F 100%); color: #fff; padding: 20px 24px; border-radius: 16px;">
        <i class="fas fa-utensils me-2"></i>
        <span>Portal <strong>Kupon & Presensi Makan</strong> Anda, <strong>{{ $employee->nama_lengkap }}</strong>. Tunjukkan kartu RFID atau scan QR di kasir kantin.</span>
    </div>

    <!-- Digital Coupon Today Cards -->
    <div class="card mb-4" style="background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.06);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #E2E8F0; padding-bottom: 16px; margin-bottom: 20px;">
            <div>
                <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #0F172A;">
                    <i class="fas fa-ticket-simple me-2" style="color: #065F46;"></i>Status Kupon Makan Hari Ini
                </h3>
                <div style="font-size: 13px; color: #64748B; margin-top: 4px;">{{ now()->translatedFormat('l, d F Y') }}</div>
            </div>
            <div style="background: #ECFDF5; color: #065F46; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 700;">
                <i class="fas fa-id-card me-1"></i> RFID: {{ $employee->rfid_uid ?? 'Tidak terdaftar' }}
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
            <!-- Makan Siang -->
            <div style="border: 2px solid {{ isset($todayMeals['siang']) ? '#10B981' : '#CBD5E1' }}; border-radius: 12px; padding: 18px; background: {{ isset($todayMeals['siang']) ? '#F0FDF4' : '#F8FAFC' }};">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase;">☀️ Makan Siang</div>
                        <div style="font-size: 12px; color: #64748B;">11:30 - 13:30 WIB</div>
                    </div>
                    <div style="font-size: 24px; color: {{ isset($todayMeals['siang']) ? '#10B981' : '#94A3B8' }};">
                        <i class="fas {{ isset($todayMeals['siang']) ? 'fa-circle-check' : 'fa-circle-dot' }}"></i>
                    </div>
                </div>

                <div style="margin-top: 16px;">
                    @if (isset($todayMeals['siang']))
                        <div style="color: #065F46; font-weight: 700; font-size: 14px;">
                            <i class="fas fa-check me-1"></i> Sudah Diambil
                        </div>
                        <div style="font-size: 12px; color: #64748B; margin-top: 2px;">
                            Pukul {{ Carbon\Carbon::parse($todayMeals['siang']->jam_makan)->format('H:i') }} WIB ({{ $todayMeals['siang']->metode_label }})
                        </div>
                    @else
                        <div style="color: #64748B; font-weight: 600; font-size: 13px; margin-bottom: 10px;">
                            Belum diambil hari ini
                        </div>
                        @if ($selfClaimEnabled)
                            <form action="{{ route('karyawan.absensi-makan.claim') }}" method="POST" onsubmit="return confirm('Apakah Anda ingin mengklaim kupon makan siang sekarang?')">
                                @csrf
                                <input type="hidden" name="jenis_makan" value="siang">
                                <button type="submit" class="btn btn-sm" style="background: #065F46; color: #fff; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 600; width: 100%; cursor: pointer;">
                                    <i class="fas fa-utensils me-1"></i> Ambil Kupon Siang
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Makan Malam / Shift -->
            <div style="border: 2px solid {{ isset($todayMeals['malam']) ? '#8B5CF6' : '#CBD5E1' }}; border-radius: 12px; padding: 18px; background: {{ isset($todayMeals['malam']) ? '#FAF5FF' : '#F8FAFC' }};">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase;">🌙 Makan Malam</div>
                        <div style="font-size: 12px; color: #64748B;">18:30 - 20:30 WIB</div>
                    </div>
                    <div style="font-size: 24px; color: {{ isset($todayMeals['malam']) ? '#8B5CF6' : '#94A3B8' }};">
                        <i class="fas {{ isset($todayMeals['malam']) ? 'fa-circle-check' : 'fa-circle-dot' }}"></i>
                    </div>
                </div>

                <div style="margin-top: 16px;">
                    @if (isset($todayMeals['malam']))
                        <div style="color: #6B21A8; font-weight: 700; font-size: 14px;">
                            <i class="fas fa-check me-1"></i> Sudah Diambil
                        </div>
                        <div style="font-size: 12px; color: #64748B; margin-top: 2px;">
                            Pukul {{ Carbon\Carbon::parse($todayMeals['malam']->jam_makan)->format('H:i') }} WIB ({{ $todayMeals['malam']->metode_label }})
                        </div>
                    @else
                        <div style="color: #64748B; font-weight: 600; font-size: 13px; margin-bottom: 10px;">
                            Belum diambil hari ini
                        </div>
                        @if ($selfClaimEnabled)
                            <form action="{{ route('karyawan.absensi-makan.claim') }}" method="POST" onsubmit="return confirm('Apakah Anda ingin mengklaim kupon makan malam sekarang?')">
                                @csrf
                                <input type="hidden" name="jenis_makan" value="malam">
                                <button type="submit" class="btn btn-sm" style="background: #7E22CE; color: #fff; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 600; width: 100%; cursor: pointer;">
                                    <i class="fas fa-utensils me-1"></i> Ambil Kupon Malam
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Makan Lembur -->
            <div style="border: 2px solid {{ isset($todayMeals['lembur']) ? '#F59E0B' : '#CBD5E1' }}; border-radius: 12px; padding: 18px; background: {{ isset($todayMeals['lembur']) ? '#FFFBEB' : '#F8FAFC' }};">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase;">⏱️ Makan Lembur</div>
                        <div style="font-size: 12px; color: #64748B;">21:00 - 23:00 WIB</div>
                    </div>
                    <div style="font-size: 24px; color: {{ isset($todayMeals['lembur']) ? '#F59E0B' : '#94A3B8' }};">
                        <i class="fas {{ isset($todayMeals['lembur']) ? 'fa-circle-check' : 'fa-circle-dot' }}"></i>
                    </div>
                </div>

                <div style="margin-top: 16px;">
                    @if (isset($todayMeals['lembur']))
                        <div style="color: #B45309; font-weight: 700; font-size: 14px;">
                            <i class="fas fa-check me-1"></i> Sudah Diambil
                        </div>
                        <div style="font-size: 12px; color: #64748B; margin-top: 2px;">
                            Pukul {{ Carbon\Carbon::parse($todayMeals['lembur']->jam_makan)->format('H:i') }} WIB ({{ $todayMeals['lembur']->metode_label }})
                        </div>
                    @else
                        <div style="color: #64748B; font-weight: 600; font-size: 13px; margin-bottom: 10px;">
                            Khusus karyawan dinas lembur
                        </div>
                        @if ($selfClaimEnabled)
                            <form action="{{ route('karyawan.absensi-makan.claim') }}" method="POST" onsubmit="return confirm('Apakah Anda ingin mengklaim kupon makan lembur sekarang?')">
                                @csrf
                                <input type="hidden" name="jenis_makan" value="lembur">
                                <button type="submit" class="btn btn-sm" style="background: #D97706; color: #fff; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 600; width: 100%; cursor: pointer;">
                                    <i class="fas fa-utensils me-1"></i> Ambil Kupon Lembur
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- History Card -->
    <div class="card" id="ajaxMealHistoryFragment" style="background: #fff; border-radius: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.06); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #E2E8F0; background: #F8FAFC; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #0F172A;">
                <i class="fas fa-history me-2" style="color: #065F46;"></i>Riwayat Kupon Makan Saya
            </h4>

            <form method="GET" action="{{ route('karyawan.absensi-makan') }}" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxMealHistoryFragment" style="display: flex; gap: 8px; align-items: center;">
                <input type="month" name="bulan" value="{{ $period->format('Y-m') }}" class="form-control" style="padding: 6px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 13px;">
            </form>
        </div>

        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background: #F1F5F9; color: #475569; text-align: left; font-weight: 600;">
                        <th style="padding: 12px 16px;">Tanggal</th>
                        <th style="padding: 12px 16px;">Jam Ambil</th>
                        <th style="padding: 12px 16px;">Sesi Makan</th>
                        <th style="padding: 12px 16px;">Metode</th>
                        <th style="padding: 12px 16px;">Lokasi Kantin</th>
                        <th style="padding: 12px 16px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $item)
                        <tr style="border-bottom: 1px solid #F1F5F9;">
                            <td style="padding: 12px 16px; font-weight: 600; color: #0F172A;">
                                {{ $item->tanggal->translatedFormat('d M Y') }}
                            </td>
                            <td style="padding: 12px 16px;">
                                {{ Carbon\Carbon::parse($item->jam_makan)->format('H:i') }} WIB
                            </td>
                            <td style="padding: 12px 16px;">
                                @if ($item->jenis_makan === 'siang')
                                    <span style="background: #EFF6FF; color: #1D4ED8; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">☀️ Siang</span>
                                @elseif ($item->jenis_makan === 'malam')
                                    <span style="background: #F3E8FF; color: #7E22CE; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">🌙 Malam</span>
                                @else
                                    <span style="background: #FEF3C7; color: #B45309; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">⏱️ Lembur</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; font-size: 13px; color: #475569;">
                                {{ $item->metode_label }}
                            </td>
                            <td style="padding: 12px 16px; font-size: 13px; color: #475569;">
                                {{ $item->lokasiGps?->nama_lokasi ?? 'Kantin Utama' }}
                            </td>
                            <td style="padding: 12px 16px;">
                                {!! $item->status_badge !!}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: #94A3B8;">
                                Belum ada riwayat pengambilan kupon makan pada bulan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $history->links('partials.pagination-ajax', ['target' => '#ajaxMealHistoryFragment']) }}
    </div>
</div>
@endsection
