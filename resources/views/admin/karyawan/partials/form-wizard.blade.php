@php
    $employee = $employee ?? null;
    $formType = $formType ?? 'create';
    $payrollProfile = $payrollProfile ?? [];
    $payrollDefaults = $payrollDefaults ?? [];

    $shouldUseOld = $formType === 'create'
        ? ($openTambahModal ?? false)
        : ($openEditModal ?? false);

    $resolveValue = function (string $key, mixed $default = '') use ($shouldUseOld) {
        return $shouldUseOld ? old($key, $default) : $default;
    };

    $resolveArray = function (string $key, array $default = []) use ($shouldUseOld) {
        $value = $shouldUseOld ? old($key, $default) : $default;

        return is_array($value) ? array_values($value) : $default;
    };

    $formatMoney = function (mixed $value): string {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 0, ',', '.');
    };

    $modalPayrollSource = $formType === 'create'
        ? $payrollDefaults
        : $payrollProfile;

    $shiftTypeValue = (string) $resolveValue('jenis_jam_kerja', $employee?->jenis_jam_kerja ?? 'tetap');
    $shiftTypeValue = in_array($shiftTypeValue, ['tetap', 'rolling', 'fleksibel'], true) ? $shiftTypeValue : 'tetap';
    $thrModeValue = (string) $resolveValue('thr_mode', $modalPayrollSource['thr_mode'] ?? $employee?->thr_mode ?? 'auto');
    $thrModeValue = in_array($thrModeValue, ['off', 'auto', 'manual'], true) ? $thrModeValue : 'auto';

    $rotationValues = $resolveArray('shift_rotation_ids', $employee?->shift_rotation_ids ?? []);
    $rotationModeValue = (string) $resolveValue('shift_rotation_mode', $employee?->shift_rotation_mode ?? 'daily');
    $rotationModeValue = in_array($rotationModeValue, ['daily', 'weekly', 'biweekly', 'monthly'], true) ? $rotationModeValue : 'daily';
    $joinDateDefault = $formType === 'create'
        ? now()->format('Y-m-d')
        : (optional($employee?->tgl_join)->format('Y-m-d') ?? '');
    $genderOptionsList = $genderOptions ?? [];
    $maritalStatusOptionsList = $maritalStatusOptions ?? [];
    $employmentTypeOptionsList = $employmentTypeOptions ?? ['tetap', 'kontrak', 'magang'];
    $premiKehadiranModeOptionsList = $premiKehadiranModeOptions ?? [];
    $payrollTypeOptionsList = $payrollTypeOptions ?? ['bulanan', 'harian'];
    $bpjsModeOptionsList = $bpjsModeOptions ?? ['off', 'auto'];
    $autoModeOptionsList = $autoModeOptions ?? ['off', 'auto'];
    $thrModeOptionsList = $thrModeOptions ?? ['off', 'auto', 'manual'];
    $taxCounterpartOptionsList = $taxCounterpartOptions ?? ['Resident', 'Foreign'];
    $taxCertificateOptionsList = $taxCertificateOptions ?? ['N/A', 'DTP'];
@endphp

<div class="employee-wizard" data-employee-wizard>
    <div class="employee-wizard-panel is-active" data-wizard-panel="0">
        <div class="employee-wizard-panel-card">
            <div class="employee-wizard-panel-head">
                <div>
                    <h4>Profil Dasar</h4>
                    <p>Identitas utama pegawai dan kontak yang dipakai untuk login atau notifikasi.</p>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>NIK <span class="required">*</span></label>
                    <input type="text" name="nik" class="form-control" value="{{ $resolveValue('nik', $employee?->nik ?? '') }}" required>
                    <small class="form-text">NIK dipakai sebagai username login karyawan.</small>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap <span class="required">*</span></label>
                    <input type="text" name="nama_lengkap" class="form-control" value="{{ $resolveValue('nama_lengkap', $employee?->nama_lengkap ?? '') }}" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="{{ $resolveValue('email', $employee?->email ?? '') }}">
                </div>
                <div class="form-group">
                    <label>No Telepon/WhatsApp</label>
                    <input type="text" name="telepon" class="form-control" value="{{ $resolveValue('telepon', $employee?->telepon ?? $employee?->no_telp ?? '') }}" placeholder="08235456789">
                    <small class="form-text">Format 08xxxxxxxxx untuk notifikasi WhatsApp.</small>
                </div>
            </div>
            <div class="form-row rfid-status-row">
                <div class="form-group">
                    <label>UID RFID</label>
                    <div class="rfid-input-group">
                        <input type="text" name="rfid_uid" id="{{ $formType === 'create' ? 'create_rfid_uid' : 'edit_rfid_uid' }}" class="form-control" value="{{ $resolveValue('rfid_uid', $employee?->rfid_uid ?? '') }}" placeholder="Contoh: 04A1B2C3D4">
                        <button
                            type="button"
                            id="{{ $formType === 'create' ? 'createPairButton' : 'editPairButton' }}"
                            class="btn btn-info rfid-pair-button"
                            data-target-input="{{ $formType === 'create' ? 'create_rfid_uid' : 'edit_rfid_uid' }}"
                            @if ($formType === 'edit')
                                data-employee-id="{{ $resolveValue('edit_id', $employee?->id ?? '') }}"
                            @endif
                            data-feedback-target="{{ $formType === 'create' ? 'create_rfid_feedback' : 'edit_rfid_feedback' }}"
                            data-context-label="{{ $formType === 'create' ? 'Tambah Karyawan' : 'Edit Karyawan' }}"
                            data-loading-text="Menunggu..."
                            onclick="startRfidPairing(this)">
                            <i class="fas fa-id-card"></i> Pair RFID
                        </button>
                    </div>
                    <small id="{{ $formType === 'create' ? 'create_rfid_feedback' : 'edit_rfid_feedback' }}" class="rfid-feedback" data-default-text="Klik Pair RFID lalu tempel kartu ke mesin untuk isi UID otomatis.">Klik Pair RFID lalu tempel kartu ke mesin untuk isi UID otomatis.</small>
                </div>
                <div class="form-group rfid-status-field">
                    <label>Status</label>
                    <select name="status" class="form-select">
                        <option value="aktif" @selected($resolveValue('status', $employee?->status ?? 'aktif') === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected($resolveValue('status', $employee?->status ?? 'aktif') === 'nonaktif')>Nonaktif</option>
                    </select>
                </div>
            </div>
            @if ($formType === 'create')
                <div class="info-note">
                    <i class="fas fa-info-circle"></i>
                    <span>Password default karyawan baru adalah <strong>123456</strong>.</span>
                </div>
            @endif
        </div>
    </div>

    <div class="employee-wizard-panel" data-wizard-panel="1">
        <div class="employee-wizard-panel-card">
            <div class="employee-wizard-panel-head">
                <div>
                    <h4>Data Kepegawaian</h4>
                    <p>Jabatan, departemen, lokasi, shift, dan pola jam kerja.</p>
                </div>
            </div>

        <div class="form-row">
            <div class="form-group">
                <label>Jabatan</label>
                    <select name="jabatan_id" class="form-select">
                        <option value="">Pilih jabatan</option>
                        @foreach ($jabatanOptions as $jabatan)
                            <option value="{{ $jabatan->id }}" @selected((string) $resolveValue('jabatan_id', $employee?->jabatan_id ?? '') === (string) $jabatan->id)>{{ $jabatan->nama_jabatan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Departemen</label>
                    <select name="departemen_id" class="form-select">
                        <option value="">Pilih departemen</option>
                        @foreach ($departemenOptions as $departemen)
                            <option value="{{ $departemen->id }}" @selected((string) $resolveValue('departemen_id', $employee?->departemen_id ?? '') === (string) $departemen->id)>{{ $departemen->nama_departemen }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Jenis Karyawan</label>
                    <select name="jenis_karyawan" class="form-select">
                        @foreach ($employmentTypeOptionsList as $employmentType)
                            @php
                                $employmentTypeLabel = match ($employmentType) {
                                    'kontrak' => 'Kontrak',
                                    'magang' => 'Magang',
                                    default => 'Tetap',
                                };
                            @endphp
                            <option value="{{ $employmentType }}" @selected($resolveValue('jenis_karyawan', $employee?->jenis_karyawan ?? 'tetap') === $employmentType)>{{ $employmentTypeLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal Join</label>
                    <input type="date" name="tgl_join" class="form-control" value="{{ $resolveValue('tgl_join', $joinDateDefault) }}">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Lokasi Default</label>
                    <select name="lokasi_gps_id" class="form-select">
                        <option value="">Pilih lokasi</option>
                        @foreach ($locationOptions ?? [] as $location)
                            <option value="{{ $location->id }}" @selected((string) $resolveValue('lokasi_gps_id', $employee?->lokasi_gps_id ?? '') === (string) $location->id)>
                                {{ $location->nama_lokasi }}{{ !$location->status ? ' - Nonaktif' : '' }}{{ $location->is_default ? ' - Default' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Jenis Jam Kerja</label>
                    <select name="jenis_jam_kerja" class="form-select">
                        <option value="tetap" @selected($shiftTypeValue === 'tetap')>Tetap</option>
                        <option value="rolling" @selected($shiftTypeValue === 'rolling')>Rolling</option>
                        <option value="fleksibel" @selected($shiftTypeValue === 'fleksibel')>Fleksibel</option>
                    </select>
                </div>
            </div>

            <div class="form-group js-shift-default-field">
                <label>Shift Utama</label>
                <select name="shift_id" class="form-select">
                    <option value="">Pilih shift</option>
                    @foreach ($shiftOptions ?? [] as $shift)
                        <option value="{{ $shift->id }}" @selected((string) $resolveValue('shift_id', $employee?->shift_id ?? '') === (string) $shift->id)>
                            {{ $shift->nama_shift }} ({{ \Carbon\Carbon::parse($shift->jam_masuk)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->jam_keluar)->format('H:i') }}){{ !$shift->aktif ? ' - Nonaktif' : '' }}
                        </option>
                    @endforeach
                </select>
                <div class="mini-help">Opsional untuk rolling. Jika dikosongkan, sistem memakai daftar rotasi sebagai sumber utama.</div>
            </div>

            <div class="form-row js-rolling-work-field">
                <div class="form-group">
                    <label>Pola Rotasi Rolling</label>
                    <select name="shift_rotation_mode" class="form-select">
                        <option value="daily" @selected($rotationModeValue === 'daily')>Harian</option>
                        <option value="weekly" @selected($rotationModeValue === 'weekly')>Mingguan</option>
                        <option value="biweekly" @selected($rotationModeValue === 'biweekly')>2 Mingguan</option>
                        <option value="monthly" @selected($rotationModeValue === 'monthly')>Bulanan</option>
                    </select>
                    <div class="mini-help">Harian = pindah tiap 1 hari, mingguan = tiap 7 hari, 2 mingguan = tiap 14 hari, bulanan = tiap 1 bulan sejak tanggal mulai rotasi.</div>
                </div>
                <div class="form-group">
                    <label>Tanggal Mulai Rotasi</label>
                    <input type="date" name="shift_rotation_start" class="form-control" value="{{ $resolveValue('shift_rotation_start', optional($employee?->shift_rotation_start)->format('Y-m-d') ?? '') }}">
                </div>
            </div>

            <div class="form-group js-rolling-work-field full-width">
                <label>Pola Rotasi Shift</label>
                <div class="shift-rotation-grid">
                    @for ($slot = 0; $slot < 3; $slot++)
                        <div class="form-group">
                            <label>Urutan {{ $slot + 1 }}</label>
                            <select name="shift_rotation_ids[{{ $slot }}]" class="form-select">
                                <option value="">Pilih shift</option>
                                @foreach ($shiftOptions ?? [] as $shift)
                                    <option value="{{ $shift->id }}" @selected((string) ($rotationValues[$slot] ?? '') === (string) $shift->id)>
                                        {{ $shift->nama_shift }} ({{ \Carbon\Carbon::parse($shift->jam_masuk)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->jam_keluar)->format('H:i') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endfor
                </div>
                <div class="mini-help">Urutan shift akan berputar berdasarkan tanggal mulai rotasi. Slot kosong diabaikan.</div>
            </div>

            @if ($employee)
                <div class="form-group">
                    <label>Masa Kerja</label>
                    <input type="text" class="form-control readonly-field" value="{{ $employee->masa_kerja_label }}" readonly>
                </div>
            @endif

            <div class="form-group js-flex-work-field">
                <label>Durasi Kerja Fleksibel (Jam)</label>
                <input type="number" step="0.5" min="0" max="24" name="durasi_kerja_fleksibel" class="form-control" value="{{ $resolveValue('durasi_kerja_fleksibel', $employee?->durasi_kerja_fleksibel ?? ($formType === 'create' ? 8 : 8)) }}">
                <div class="mini-help">Dipakai untuk hitung jam pulang. Scan kedua sebelum durasi ini selesai akan dihitung pulang cepat.</div>
            </div>

        </div>
    </div>

    <div class="employee-wizard-panel" data-wizard-panel="2">
            <div class="employee-wizard-panel-card">
                <div class="employee-wizard-panel-head">
                    <div>
                        <h4>Data Personal & Legal</h4>
                        <p>Data pribadi, identitas legal, dan pengaturan administratif dasar.</p>
                    </div>
                </div>
            <div class="detail-form-grid full-3">
                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="tgl_lahir" class="form-control" value="{{ $resolveValue('tgl_lahir', optional($employee?->tgl_lahir)->format('Y-m-d') ?? '') }}">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">Pilih gender</option>
                        @foreach ($genderOptionsList as $gender)
                            <option value="{{ $gender }}" @selected($resolveValue('gender', $employee?->gender ?? '') === $gender)>{{ $gender }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Status PTKP</label>
                    <select name="status_nikah" class="form-select">
                        <option value="">Pilih status</option>
                        @foreach ($maritalStatusOptionsList as $statusNikah)
                            <option value="{{ $statusNikah }}" @selected($resolveValue('status_nikah', $employee?->status_nikah ?? '') === $statusNikah)>{{ $statusNikah }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Mode BPJS</label>
                    <select name="bpjs_mode" class="form-control">
                        @foreach ($bpjsModeOptionsList as $mode)
                            <option value="{{ $mode }}" @selected($resolveValue('bpjs_mode', $modalPayrollSource['bpjs_mode'] ?? $employee?->bpjs_mode ?? 'auto') === $mode)>{{ $mode === 'auto' ? 'Otomatis' : 'Nonaktif' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>PPh21</label>
                    <select name="pph21_mode" class="form-control">
                        @foreach ($autoModeOptionsList as $mode)
                            <option value="{{ $mode }}" @selected($resolveValue('pph21_mode', $modalPayrollSource['pph21_mode'] ?? $employee?->pph21_mode ?? 'auto') === $mode)>{{ $mode === 'auto' ? 'Otomatis' : 'Nonaktif' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>THR</label>
                    <select name="thr_mode" class="form-control">
                        @foreach ($thrModeOptionsList as $mode)
                            @php
                                $thrModeLabel = match ($mode) {
                                    'auto' => 'Otomatis',
                                    'manual' => 'Manual',
                                    default => 'Nonaktif',
                                };
                            @endphp
                            <option value="{{ $mode }}" @selected($thrModeValue === $mode)>{{ $thrModeLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Nomor KTP</label>
                    <input type="text" name="ktp" class="form-control" value="{{ $resolveValue('ktp', $employee?->ktp ?? '') }}">
                </div>
                <div class="form-group">
                    <label>Nomor Kartu Keluarga</label>
                    <input type="text" name="kartu_keluarga" class="form-control" value="{{ $resolveValue('kartu_keluarga', $employee?->kartu_keluarga ?? '') }}">
                </div>
                <div class="form-group">
                    <label>Nomor SIM</label>
                    <input type="text" name="sim" class="form-control" value="{{ $resolveValue('sim', $employee?->sim ?? '') }}">
                </div>
                <div class="form-group">
                    <label>BPJS Kesehatan</label>
                    <input type="text" name="bpjs_kesehatan" class="form-control" value="{{ $resolveValue('bpjs_kesehatan', $employee?->bpjs_kesehatan ?? '') }}">
                </div>
                <div class="form-group">
                    <label>BPJS Ketenagakerjaan</label>
                    <input type="text" name="bpjs_ketenagakerjaan" class="form-control" value="{{ $resolveValue('bpjs_ketenagakerjaan', $employee?->bpjs_ketenagakerjaan ?? '') }}">
                </div>
                <div class="form-group">
                    <label>NPWP / NIK Pajak</label>
                    <input type="text" name="npwp" class="form-control" value="{{ $resolveValue('npwp', $employee?->npwp ?? '') }}">
                </div>
                <div class="form-group">
                    <label>Status Subjek BPA1</label>
                    <select name="tax_counterpart_opt" class="form-select">
                        @foreach ($taxCounterpartOptionsList as $option)
                            <option value="{{ $option }}" @selected($resolveValue('tax_counterpart_opt', $employee?->tax_counterpart_opt ?? 'Resident') === $option)>
                                {{ $option === 'Foreign' ? 'Foreign / Luar Negeri' : 'Resident / Dalam Negeri' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group js-tax-passport-field">
                    <label>No Paspor</label>
                    <input type="text" name="tax_passport_number" class="form-control" value="{{ $resolveValue('tax_passport_number', $employee?->tax_passport_number ?? '') }}">
                </div>
                <div class="form-group">
                    <label>Pemberi Kerja Lain</label>
                    <select name="tax_has_second_employer" class="form-select">
                        <option value="0" @selected((string) $resolveValue('tax_has_second_employer', $employee?->tax_has_second_employer ? '1' : '0') === '0')>Tidak</option>
                        <option value="1" @selected((string) $resolveValue('tax_has_second_employer', $employee?->tax_has_second_employer ? '1' : '0') === '1')>Ya</option>
                    </select>
                </div>
                <div class="form-group js-tax-prior-employer-field">
                    <label>No Bukti Potong Sebelumnya</label>
                    <input type="text" name="tax_prev_withholding_slip_number" class="form-control" value="{{ $resolveValue('tax_prev_withholding_slip_number', $employee?->tax_prev_withholding_slip_number ?? '') }}">
                </div>
                <div class="form-group money-suffix js-tax-prior-employer-field">
                    <label>Bruto Pajak Sebelumnya</label>
                    <input type="text" name="tax_prev_gross_income" class="form-control money-field" value="{{ $formatMoney($resolveValue('tax_prev_gross_income', $employee?->tax_prev_gross_income ?? 0)) }}">
                    <span class="suffix">/ Tahun Berjalan</span>
                </div>
                <div class="form-group money-suffix js-tax-prior-employer-field">
                    <label>PPh21 Dipotong Sebelumnya</label>
                    <input type="text" name="tax_prev_pph21_paid" class="form-control money-field" value="{{ $formatMoney($resolveValue('tax_prev_pph21_paid', $employee?->tax_prev_pph21_paid ?? 0)) }}">
                    <span class="suffix">/ Tahun Berjalan</span>
                </div>
                <div class="form-group money-suffix js-tax-prior-employer-field">
                    <label>Iuran Pensiun Sebelumnya</label>
                    <input type="text" name="tax_prev_retirement_contribution" class="form-control money-field" value="{{ $formatMoney($resolveValue('tax_prev_retirement_contribution', $employee?->tax_prev_retirement_contribution ?? 0)) }}">
                    <span class="suffix">/ Tahun Berjalan</span>
                </div>
                <div class="form-group">
                    <label>Fasilitas Pajak</label>
                    <select name="tax_certificate" class="form-select">
                        @foreach ($taxCertificateOptionsList as $option)
                            <option value="{{ $option }}" @selected($resolveValue('tax_certificate', $employee?->tax_certificate ?? 'N/A') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Alamat</label>
                    <textarea name="alamat" class="form-control" rows="3">{{ $resolveValue('alamat', $employee?->alamat ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="employee-wizard-panel" data-wizard-panel="3">
            <div class="employee-wizard-panel-card">
                <div class="employee-wizard-panel-head">
                    <div>
                        <h4>Kontrak & Rekening</h4>
                        <p>Nomor kontrak, masa berlaku, dan rekening penerimaan gaji.</p>
                    </div>
                </div>
            <div class="detail-form-grid full-3 js-contract-employment-field">
                <div class="form-group">
                    <label>Nomor PKWT</label>
                    <input type="text" name="no_pkwt" class="form-control" value="{{ $resolveValue('no_pkwt', $employee?->no_pkwt ?? '') }}">
                </div>
                <div class="form-group">
                    <label>Nomor Kontrak</label>
                    <input type="text" name="no_kontrak" class="form-control" value="{{ $resolveValue('no_kontrak', $employee?->no_kontrak ?? '') }}">
                </div>
                <div class="form-group">
                    <label>Masa Berlaku</label>
                    <input type="date" name="masa_berlaku" class="form-control" value="{{ $resolveValue('masa_berlaku', optional($employee?->masa_berlaku)->format('Y-m-d') ?? '') }}">
                </div>
                <div class="form-group">
                    <label>Tanggal Mulai PKWT</label>
                    <input type="date" name="tanggal_mulai_pkwt" class="form-control" value="{{ $resolveValue('tanggal_mulai_pkwt', optional($employee?->tanggal_mulai_pkwt)->format('Y-m-d') ?? '') }}">
                </div>
                <div class="form-group">
                    <label>Tanggal Berakhir PKWT</label>
                    <input type="date" name="tanggal_berakhir_pkwt" class="form-control" value="{{ $resolveValue('tanggal_berakhir_pkwt', optional($employee?->tanggal_berakhir_pkwt)->format('Y-m-d') ?? '') }}">
                </div>
            </div>

            <div class="bank-info-card full-width">
                <div class="bank-info-card-head">
                    <i class="fas fa-wallet"></i>
                    <div>
                        <h5>Informasi Rekening</h5>
                        <p>Data rekening gaji disimpan di bagian ini.</p>
                    </div>
                </div>
                <div class="detail-form-grid full-3">
                    <div class="form-group">
                        <label>Nama Bank</label>
                        <input type="text" name="nama_bank" class="form-control" value="{{ $resolveValue('nama_bank', $employee?->nama_bank ?? '') }}" placeholder="Contoh: BCA">
                    </div>
                    <div class="form-group">
                        <label>Nama Pemilik Rekening</label>
                        <input type="text" name="nama_rekening" class="form-control" value="{{ $resolveValue('nama_rekening', $employee?->nama_rekening ?? '') }}" placeholder="Sesuai buku tabungan">
                    </div>
                    <div class="form-group">
                        <label>Nomor Rekening</label>
                        <input type="text" name="rekening" class="form-control" value="{{ $resolveValue('rekening', $employee?->rekening ?? '') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="employee-wizard-panel" data-wizard-panel="4">
        <div class="employee-wizard-panel-card">
            <div class="employee-wizard-panel-head">
                <div>
                    <h4>Payroll & Kuota Izin</h4>
                    <p>Parameter gaji, tunjangan, potongan, dan kuota administratif pegawai.</p>
                </div>
            </div>
            <div class="detail-form-grid full-3">
                <div class="form-group">
                    <label>Cuti</label>
                    <input type="number" min="0" name="izin_cuti" class="form-control" value="{{ $resolveValue('izin_cuti', $employee?->izin_cuti ?? 0) }}">
                </div>
                <div class="form-group">
                    <label>Izin Lainnya</label>
                    <input type="number" min="0" name="izin_lainnya" class="form-control" value="{{ $resolveValue('izin_lainnya', $employee?->izin_lainnya ?? 0) }}">
                </div>
                <div class="form-group">
                    <label>Izin Telat</label>
                    <input type="number" min="0" name="izin_telat" class="form-control" value="{{ $resolveValue('izin_telat', $employee?->izin_telat ?? 0) }}">
                </div>
                <div class="form-group">
                    <label>Izin Pulang Cepat</label>
                    <input type="number" min="0" name="izin_pulang_cepat" class="form-control" value="{{ $resolveValue('izin_pulang_cepat', $employee?->izin_pulang_cepat ?? 0) }}">
                </div>
            </div>

            <div class="payroll-group-grid">
                <div class="bank-info-card payroll-group-card">
                    <div class="bank-info-card-head">
                        <i class="fas fa-calculator"></i>
                        <div>
                            <h5>Parameter Dasar</h5>
                            <p>Atur model payroll bulanan atau harian, lalu isi nominal dasar yang dipakai sistem.</p>
                        </div>
                    </div>
                    <div class="detail-form-grid full-3">
                        <div class="form-group">
                            <label>Tipe Penggajian</label>
                            <select name="tipe_penggajian" class="form-control">
                                @foreach ($payrollTypeOptionsList as $payrollType)
                                    <option value="{{ $payrollType }}" @selected($resolveValue('tipe_penggajian', $modalPayrollSource['tipe_penggajian'] ?? $employee?->tipe_penggajian ?? 'bulanan') === $payrollType)>
                                        {{ $payrollType === 'harian' ? 'Harian' : 'Bulanan' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group money-suffix js-payroll-bulanan-field">
                            <label>Gaji Pokok</label>
                            <input type="text" name="gaji_pokok" class="form-control money-field" value="{{ $formatMoney($resolveValue('gaji_pokok', $employee?->gaji_pokok ?? 0)) }}">
                            <span class="suffix">/ Bulan</span>
                        </div>
                        <div class="form-group money-suffix js-payroll-harian-field">
                            <label>Gaji per Hari</label>
                            <input type="text" name="gaji_per_hari" class="form-control money-field" value="{{ $formatMoney($resolveValue('gaji_per_hari', $modalPayrollSource['gaji_per_hari'] ?? $employee?->gaji_per_hari ?? ($settings?->gaji_per_hari ?? 0))) }}">
                            <span class="suffix">/ Hari</span>
                        </div>
                        <div class="form-group money-suffix">
                            <label>Tarif Lembur per Jam</label>
                            <input type="text" name="tarif_lembur_per_jam" class="form-control money-field" value="{{ $formatMoney($resolveValue('tarif_lembur_per_jam', $modalPayrollSource['tarif_lembur_per_jam'] ?? 0)) }}">
                            <span class="suffix">/ Jam</span>
                            <div class="mini-help">Hanya berlaku jika Mode Perhitungan Lembur diatur ke mode Jam Tetap.</div>
                        </div>
                        <div class="form-group money-suffix js-thr-manual-field" style="{{ $thrModeValue === 'manual' ? '' : 'display:none;' }}">
                            <label>THR Manual</label>
                            <input type="text" name="thr_manual_amount" class="form-control money-field" value="{{ $formatMoney($resolveValue('thr_manual_amount', $modalPayrollSource['thr_manual_amount'] ?? 0)) }}" @disabled($thrModeValue !== 'manual')>
                            <span class="suffix">/ Periode</span>
                            <div class="mini-help">Dipakai hanya jika mode THR diatur ke Manual.</div>
                        </div>
                    </div>
                </div>

                <div class="bank-info-card payroll-group-card is-addition">
                    <div class="bank-info-card-head">
                        <i class="fas fa-plus"></i>
                        <div>
                            <h5>Penjumlahan</h5>
                            <p>Tunjangan dan bonus yang menambah nilai payroll pegawai.</p>
                        </div>
                    </div>
                    <div class="detail-form-grid full-3">
                        <div class="form-group money-suffix">
                            <label>Tunjangan Jabatan</label>
                            <input type="text" name="tunjangan_jabatan" class="form-control money-field" value="{{ $formatMoney($resolveValue('tunjangan_jabatan', $modalPayrollSource['tunjangan_jabatan'] ?? 0)) }}">
                            <span class="suffix">/ Bulan</span>
                        </div>
                        <div class="form-group money-suffix">
                            <label>Tunjangan Makan</label>
                            <input type="text" name="tunjangan_makan" class="form-control money-field" value="{{ $formatMoney($resolveValue('tunjangan_makan', $modalPayrollSource['tunjangan_makan'] ?? 0)) }}">
                            <span class="suffix">/ Hari</span>
                        </div>
                        <div class="form-group money-suffix">
                            <label>Tunjangan Transport</label>
                            <input type="text" name="tunjangan_transport" class="form-control money-field" value="{{ $formatMoney($resolveValue('tunjangan_transport', $modalPayrollSource['tunjangan_transport'] ?? 0)) }}">
                            <span class="suffix">/ Hari</span>
                        </div>
                        <div class="form-group money-suffix">
                            <label>Bonus Pribadi</label>
                            <input type="text" name="bonus_pribadi" class="form-control money-field" value="{{ $formatMoney($resolveValue('bonus_pribadi', $employee?->bonus_pribadi ?? 0)) }}">
                            <span class="suffix">/ Bulan</span>
                        </div>
                        <div class="form-group money-suffix">
                            <label>Bonus Team</label>
                            <input type="text" name="bonus_team" class="form-control money-field" value="{{ $formatMoney($resolveValue('bonus_team', $employee?->bonus_team ?? 0)) }}">
                            <span class="suffix">/ Bulan</span>
                        </div>
                        <div class="form-group">
                            <label>Mode Premi Kehadiran</label>
                            <select name="premi_kehadiran_mode" class="form-control">
                                @foreach ($premiKehadiranModeOptionsList as $mode)
                                    @php
                                        $modeLabel = match ($mode) {
                                            'penuh' => 'Hadir Penuh',
                                            'toleran' => 'Toleran',
                                            'prorata' => 'Prorata',
                                            default => 'Nonaktif',
                                        };
                                    @endphp
                                    <option value="{{ $mode }}" @selected($resolveValue('premi_kehadiran_mode', $modalPayrollSource['premi_kehadiran_mode'] ?? $employee?->premi_kehadiran_mode ?? 'nonaktif') === $mode)>{{ $modeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group money-suffix js-premi-amount-field">
                            <label>Premi Kehadiran</label>
                            <input type="text" name="premi_kehadiran" class="form-control money-field" value="{{ $formatMoney($resolveValue('premi_kehadiran', $modalPayrollSource['premi_kehadiran'] ?? $employee?->premi_kehadiran ?? 0)) }}">
                            <span class="suffix">/ Bulan</span>
                        </div>
                        <div class="form-group money-suffix js-premi-toleran-field">
                            <label>Toleransi Telat</label>
                            <input type="number" min="0" name="premi_kehadiran_toleransi_telat" class="form-control" value="{{ $resolveValue('premi_kehadiran_toleransi_telat', $modalPayrollSource['premi_kehadiran_toleransi_telat'] ?? $employee?->premi_kehadiran_toleransi_telat ?? 0) }}">
                            <span class="suffix">/ Kali</span>
                        </div>
                        <div class="form-group money-suffix js-premi-toleran-field">
                            <label>Toleransi Pulang Cepat</label>
                            <input type="number" min="0" name="premi_kehadiran_toleransi_pulang_cepat" class="form-control" value="{{ $resolveValue('premi_kehadiran_toleransi_pulang_cepat', $modalPayrollSource['premi_kehadiran_toleransi_pulang_cepat'] ?? $employee?->premi_kehadiran_toleransi_pulang_cepat ?? 0) }}">
                            <span class="suffix">/ Kali</span>
                        </div>
                    </div>
                </div>

                <div class="bank-info-card payroll-group-card is-deduction">
                    <div class="bank-info-card-head">
                        <i class="fas fa-minus"></i>
                        <div>
                            <h5>Pengurangan</h5>
                            <p>Potongan yang mengurangi payroll. Isi `0` untuk izin atau mangkir jika ingin otomatis ikut tarif dasar payroll.</p>
                        </div>
                    </div>
                    <div class="detail-form-grid full-3">
                        <div class="form-group money-suffix">
                            <label>Potongan Izin</label>
                            <input type="text" name="potongan_izin" class="form-control money-field" value="{{ $formatMoney($resolveValue('potongan_izin', $modalPayrollSource['potongan_izin'] ?? 0)) }}">
                            <span class="suffix">/ Hari</span>
                        </div>
                        <div class="form-group money-suffix">
                            <label>Potongan Mangkir</label>
                            <input type="text" name="potongan_mangkir" class="form-control money-field" value="{{ $formatMoney($resolveValue('potongan_mangkir', $modalPayrollSource['potongan_mangkir'] ?? 0)) }}">
                            <span class="suffix">/ Hari</span>
                        </div>
                        <div class="form-group money-suffix">
                            <label>Potongan Terlambat</label>
                            <input type="text" name="potongan_terlambat" class="form-control money-field" value="{{ $formatMoney($resolveValue('potongan_terlambat', $modalPayrollSource['potongan_terlambat'] ?? 0)) }}">
                            <span class="suffix">/ Kali</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
