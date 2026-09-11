@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Payroll Umum'])

@section('styles')
    .card { margin-bottom: 20px; }
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
    .section-divider {
        margin: 24px 0 16px;
        padding-top: 20px;
        border-top: 1px solid #E2E8F0;
    }
    .section-divider:first-of-type {
        margin-top: 0;
        padding-top: 0;
        border-top: 0;
    }
    .section-title {
        font-size: 14px;
        font-weight: 700;
        color: #0F172A;
        margin: 0 0 6px;
    }
    .section-subtitle {
        font-size: 12px;
        color: #64748B;
        margin: 0 0 16px;
    }
    @media (max-width: 900px) {
        .two-columns { grid-template-columns: 1fr; }
    }
@endsection

@section('content')
    <div id="ajaxCrudFragment">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-money-check-dollar" style="color:#065F46; margin-right:8px;"></i>Pengaturan Payroll Umum</h3>
            </div>
            <div class="card-body">
                <div class="info-box info-blue">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Standar payroll global</strong><br>
                        Halaman ini mengatur divisor payroll, mode lembur, BPJS, PPh21, THR, dan fallback nominal default yang dipakai seluruh sistem.
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.payroll-settings.update') }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment">
                    @csrf

                    <div class="section-divider">
                        <h4 class="section-title">Payroll Dasar</h4>
                        <p class="section-subtitle">Atur divisor, prorata, lembur, dan fallback nominal harian.</p>
                        <div class="two-columns">
                            <div class="form-group">
                                <label>Divisor Payroll Bulanan</label>
                                <input type="number" min="1" max="31" name="payroll_divisor_bulanan" class="form-control" value="{{ old('payroll_divisor_bulanan', $settings->payroll_divisor_bulanan ?? 26) }}" required>
                                <small class="form-text">Umumnya memakai 25 atau 26. Nilai ini berlaku untuk semua karyawan bulanan.</small>
                            </div>
                            <div class="form-group">
                                <label>Mode Perhitungan Lembur</label>
                                <select name="overtime_calculation_mode" class="form-control" required>
                                    @foreach ($overtimeModeOptions as $modeKey => $modeLabel)
                                        <option value="{{ $modeKey }}" @selected(old('overtime_calculation_mode', $settings->overtime_calculation_mode ?? \App\Models\Setting::OVERTIME_MODE_FLAT_HOURLY) === $modeKey)>{{ $modeLabel }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text">Jam tetap memakai tarif flat. Mode UU Cipta Kerja memakai faktor bertingkat hari kerja dan hari libur.</small>
                            </div>
                        </div>
                        <div class="two-columns">
                            <div class="form-group">
                                <label>Metode Prorata Join/Resign</label>
                                <select name="payroll_prorate_method" class="form-control" required>
                                    <option value="work_days" @selected(old('payroll_prorate_method', $settings->payroll_prorate_method ?? 'work_days') === 'work_days')>Hari Kerja</option>
                                    <option value="calendar_days" @selected(old('payroll_prorate_method', $settings->payroll_prorate_method ?? 'work_days') === 'calendar_days')>Hari Kalender</option>
                                </select>
                                <small class="form-text">Hari kerja lebih cocok untuk payroll berbasis shift dan jadwal kerja.</small>
                            </div>
                            <div class="form-group">
                                <label>Default Gaji Harian</label>
                                <input type="text" name="gaji_per_hari" class="form-control" value="{{ old('gaji_per_hari', number_format((float) ($settings->gaji_per_hari ?? 100000), 0, ',', '.')) }}" required>
                                <small class="form-text">Dipakai sebagai fallback untuk karyawan tipe harian yang belum punya tarif sendiri.</small>
                            </div>
                        </div>
                        <div class="two-columns">
                            <div class="form-group">
                                <label>Potongan Telat per Menit</label>
                                <input type="text" name="potongan_per_menit" class="form-control" value="{{ old('potongan_per_menit', number_format((float) ($settings->potongan_per_menit ?? 1000), 0, ',', '.')) }}" required>
                                <small class="form-text">Jika lebih dari 0, payroll menghitung potongan telat dari total menit keterlambatan.</small>
                            </div>
                            <div class="form-group">
                                <label>Maksimum Potongan Telat per Hari</label>
                                <input type="text" name="max_potongan_harian" class="form-control" value="{{ old('max_potongan_harian', number_format((float) ($settings->max_potongan_harian ?? 50000), 0, ',', '.')) }}" required>
                                <small class="form-text">Batas potongan untuk satu hari kerja. Isi 0 jika tanpa cap harian.</small>
                            </div>
                        </div>
                    </div>

                    <div class="section-divider">
                        <h4 class="section-title">BPJS</h4>
                        <p class="section-subtitle">Atur persentase global BPJS dan batas gaji yang dipakai sistem.</p>
                        <div class="two-columns">
                            <div class="form-group">
                                <label>BPJS Otomatis Global</label>
                                <select name="bpjs_auto_enabled" class="form-control" required>
                                    <option value="1" @selected((string) old('bpjs_auto_enabled', (int) ($settings->bpjs_auto_enabled ?? true)) === '1')>Aktif</option>
                                    <option value="0" @selected((string) old('bpjs_auto_enabled', (int) ($settings->bpjs_auto_enabled ?? true)) === '0')>Nonaktif</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Batas Gaji BPJS Kesehatan</label>
                                <input type="text" name="bpjs_kesehatan_salary_cap" class="form-control" value="{{ old('bpjs_kesehatan_salary_cap', number_format((float) ($settings->bpjs_kesehatan_salary_cap ?? 12000000), 0, ',', '.')) }}" required>
                            </div>
                        </div>
                        <div class="two-columns">
                            <div class="form-group">
                                <label>BPJS Kesehatan Perusahaan (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="bpjs_kesehatan_company_percent" class="form-control" value="{{ old('bpjs_kesehatan_company_percent', $settings->bpjs_kesehatan_company_percent ?? 4) }}" required>
                            </div>
                            <div class="form-group">
                                <label>BPJS Kesehatan Karyawan (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="bpjs_kesehatan_employee_percent" class="form-control" value="{{ old('bpjs_kesehatan_employee_percent', $settings->bpjs_kesehatan_employee_percent ?? 1) }}" required>
                            </div>
                        </div>
                        <div class="two-columns">
                            <div class="form-group">
                                <label>Kelas Risiko JKK</label>
                                <select name="bpjs_jkk_risk_level" class="form-control" required>
                                    @foreach ($jkkRiskOptions as $riskKey => $riskConfig)
                                        <option value="{{ $riskKey }}" @selected(old('bpjs_jkk_risk_level', $settings->bpjs_jkk_risk_level ?? 'very_low') === $riskKey)>{{ $riskConfig['label'] }}{{ $riskConfig['percent'] !== null ? ' - '.$riskConfig['percent'].'%' : '' }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text">Jika memilih `Custom`, kolom JKK manual dipakai.</small>
                            </div>
                            <div class="form-group">
                                <label>JKK Perusahaan (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="bpjs_jkk_company_percent" class="form-control" value="{{ old('bpjs_jkk_company_percent', $settings->bpjs_jkk_company_percent ?? 0.24) }}" required>
                            </div>
                        </div>
                        <div class="two-columns">
                            <div class="form-group">
                                <label>JHT Perusahaan (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="bpjs_jht_company_percent" class="form-control" value="{{ old('bpjs_jht_company_percent', $settings->bpjs_jht_company_percent ?? 3.7) }}" required>
                            </div>
                            <div class="form-group">
                                <label>JHT Karyawan (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="bpjs_jht_employee_percent" class="form-control" value="{{ old('bpjs_jht_employee_percent', $settings->bpjs_jht_employee_percent ?? 2) }}" required>
                            </div>
                        </div>
                        <div class="two-columns">
                            <div class="form-group">
                                <label>JP Perusahaan (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="bpjs_jp_company_percent" class="form-control" value="{{ old('bpjs_jp_company_percent', $settings->bpjs_jp_company_percent ?? 2) }}" required>
                            </div>
                            <div class="form-group">
                                <label>JP Karyawan (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="bpjs_jp_employee_percent" class="form-control" value="{{ old('bpjs_jp_employee_percent', $settings->bpjs_jp_employee_percent ?? 1) }}" required>
                            </div>
                        </div>
                        <div class="two-columns">
                            <div class="form-group">
                                <label>Batas Gaji JP</label>
                                <input type="text" name="bpjs_jp_salary_cap" class="form-control" value="{{ old('bpjs_jp_salary_cap', number_format((float) ($settings->bpjs_jp_salary_cap ?? 0), 0, ',', '.')) }}" required>
                                <small class="form-text">Isi `0` untuk memakai batas resmi aktif. Default resmi saat ini: Rp {{ number_format((float) config('payroll_compliance.bpjs.jp_salary_cap', 10547400), 0, ',', '.') }}.</small>
                            </div>
                            <div class="form-group">
                                <label>JKM Perusahaan (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="bpjs_jkm_company_percent" class="form-control" value="{{ old('bpjs_jkm_company_percent', $settings->bpjs_jkm_company_percent ?? 0.3) }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="section-divider">
                        <h4 class="section-title">PPh21</h4>
                        <p class="section-subtitle">Atur mode otomatis dan dasar biaya jabatan PPh21.</p>
                        <div class="two-columns">
                            <div class="form-group">
                                <label>PPh21 Otomatis Global</label>
                                <select name="pph21_auto_enabled" class="form-control" required>
                                    <option value="1" @selected((string) old('pph21_auto_enabled', (int) ($settings->pph21_auto_enabled ?? true)) === '1')>Aktif</option>
                                    <option value="0" @selected((string) old('pph21_auto_enabled', (int) ($settings->pph21_auto_enabled ?? true)) === '0')>Nonaktif</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Biaya Jabatan PPh21 (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="pph21_job_expense_percent" class="form-control" value="{{ old('pph21_job_expense_percent', $settings->pph21_job_expense_percent ?? 5) }}" required>
                            </div>
                        </div>
                        <div class="two-columns">
                            <div class="form-group">
                                <label>Batas Biaya Jabatan per Bulan</label>
                                <input type="text" name="pph21_job_expense_monthly_cap" class="form-control" value="{{ old('pph21_job_expense_monthly_cap', number_format((float) ($settings->pph21_job_expense_monthly_cap ?? 500000), 0, ',', '.')) }}" required>
                            </div>
                            <div class="form-group">
                                <label>NPWP Pemotong Pajak</label>
                                <input type="text" name="tax_company_npwp" class="form-control" value="{{ old('tax_company_npwp', $settings->tax_company_npwp ?? '') }}" placeholder="16 digit NPWP pemotong">
                                <small class="form-text">Dipakai untuk export XML BPA1 ke Coretax DJP.</small>
                            </div>
                        </div>
                        <div class="two-columns">
                            <div class="form-group">
                                <label>ID TKU / NITKU Pemotong</label>
                                <input type="text" name="tax_company_nitku" class="form-control" value="{{ old('tax_company_nitku', $settings->tax_company_nitku ?? '') }}" placeholder="ID TKU / NITKU tempat kegiatan usaha">
                                <small class="form-text">Wajib diisi agar file XML BPA1 bisa mengikuti skema impor Coretax.</small>
                            </div>
                        </div>
                    </div>

                    <div class="section-divider">
                        <h4 class="section-title">THR</h4>
                        <p class="section-subtitle">Atur mode otomatis, periode pembayaran, dan komponen yang masuk ke basis THR.</p>
                        <div class="two-columns">
                            <div class="form-group">
                                <label>THR Otomatis</label>
                                <select name="thr_auto_enabled" class="form-control" required>
                                    <option value="1" @selected((string) old('thr_auto_enabled', (int) ($settings->thr_auto_enabled ?? false)) === '1')>Aktif</option>
                                    <option value="0" @selected((string) old('thr_auto_enabled', (int) ($settings->thr_auto_enabled ?? false)) === '0')>Nonaktif</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Bulan Pembayaran THR</label>
                                <select name="thr_payment_month" class="form-control">
                                    <option value="">Pilih bulan</option>
                                    @foreach (range(1, 12) as $monthNumber)
                                        <option value="{{ $monthNumber }}" @selected((string) old('thr_payment_month', $settings->thr_payment_month ?? '') === (string) $monthNumber)>{{ \Carbon\Carbon::create()->month($monthNumber)->translatedFormat('F') }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="two-columns">
                            <div class="form-group">
                                <label>Tanggal Hari Raya THR</label>
                                <input type="date" name="thr_payment_date" class="form-control" value="{{ old('thr_payment_date', optional($settings->thr_payment_date ?? null)->format('Y-m-d')) }}">
                            </div>
                            <div class="form-group"></div>
                        </div>
                        <div class="two-columns">
                            <div class="form-group">
                                <label>Tunjangan Jabatan Masuk THR</label>
                                <select name="thr_include_tunjangan_jabatan" class="form-control" required>
                                    <option value="1" @selected((string) old('thr_include_tunjangan_jabatan', (int) ($settings->thr_include_tunjangan_jabatan ?? true)) === '1')>Aktif</option>
                                    <option value="0" @selected((string) old('thr_include_tunjangan_jabatan', (int) ($settings->thr_include_tunjangan_jabatan ?? true)) === '0')>Nonaktif</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tunjangan Makan Masuk THR</label>
                                <select name="thr_include_tunjangan_makan" class="form-control" required>
                                    <option value="1" @selected((string) old('thr_include_tunjangan_makan', (int) ($settings->thr_include_tunjangan_makan ?? false)) === '1')>Aktif</option>
                                    <option value="0" @selected((string) old('thr_include_tunjangan_makan', (int) ($settings->thr_include_tunjangan_makan ?? false)) === '0')>Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="two-columns">
                            <div class="form-group">
                                <label>Tunjangan Transport Masuk THR</label>
                                <select name="thr_include_tunjangan_transport" class="form-control" required>
                                    <option value="1" @selected((string) old('thr_include_tunjangan_transport', (int) ($settings->thr_include_tunjangan_transport ?? false)) === '1')>Aktif</option>
                                    <option value="0" @selected((string) old('thr_include_tunjangan_transport', (int) ($settings->thr_include_tunjangan_transport ?? false)) === '0')>Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Pengaturan Payroll</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
