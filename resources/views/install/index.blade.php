<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Installer</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --line: #e5e7eb;
            --primary: #4f46e5;
            --primary-soft: #eef2ff;
            --success: #059669;
            --danger: #dc2626;
            --shadow: 0 16px 40px rgba(15, 23, 42, .08);
            --radius: 22px;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(180deg, #f8fbff 0%, var(--bg) 100%);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .shell {
            width: 100%;
            max-width: 760px;
        }

        .brand {
            text-align: center;
            margin-bottom: 16px;
        }

        .brand h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .brand p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .card {
            background: var(--card);
            border: 1px solid rgba(226, 232, 240, .85);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            height: min(780px, calc(100vh - 48px));
            display: flex;
            flex-direction: column;
        }

        #installForm {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .card-head {
            padding: 22px 24px 16px;
            border-bottom: 1px solid var(--line);
        }

        .step-title {
            display: none;
            align-items: center;
            gap: 14px;
        }

        .step-title.active {
            display: flex;
        }

        .step-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: var(--primary-soft);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 800;
            flex: 0 0 auto;
        }

        .step-meta small {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 4px;
        }

        .step-meta strong {
            display: block;
            font-size: 20px;
            line-height: 1.2;
        }

        .card-body {
            padding: 20px 24px;
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .panel {
            display: none;
        }

        .panel.active {
            display: block;
        }

        .alert {
            border-radius: 16px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 13px;
            line-height: 1.55;
        }

        .alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .alert ul {
            margin: 0;
            padding-left: 18px;
        }

        .info-text {
            margin: 0 0 16px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .table-box {
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--line);
            font-size: 13px;
            text-align: left;
            vertical-align: middle;
        }

        thead th {
            background: #f8fafc;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #475569;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .ok { color: var(--success); font-weight: 700; }
        .bad { color: var(--danger); font-weight: 700; }

        .field {
            margin-bottom: 14px;
        }

        .field label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 8px;
        }

        .field small {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-top: 6px;
        }

        .control,
        select.control {
            width: 100%;
            height: 48px;
            border-radius: 14px;
            border: 1px solid #dbe1ea;
            background: #fff;
            padding: 0 14px;
            font-size: 14px;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .control:focus,
        select.control:focus {
            border-color: #a5b4fc;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, .10);
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            align-items: start;
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fafbff;
            margin-top: 6px;
        }

        .checkbox-row input {
            width: 18px;
            height: 18px;
        }

        .inline-action {
            margin-top: 10px;
        }

        .btn {
            height: 46px;
            padding: 0 18px;
            border-radius: 14px;
            border: 1px solid transparent;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: .15s ease;
        }

        .btn:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-primary:hover:not(:disabled) {
            background: #4338ca;
        }

        .btn-light {
            background: #fff;
            color: #334155;
            border-color: var(--line);
        }

        .btn-light:hover:not(:disabled) {
            background: #f8fafc;
        }

        .btn-block {
            width: 100%;
        }

        .card-foot {
            padding: 16px 24px 22px;
            border-top: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex: 0 0 auto;
        }

        .foot-right {
            margin-left: auto;
            display: flex;
            gap: 12px;
        }

        .muted-note {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
        }

        .loading::after {
            content: '';
            display: inline-block;
            width: 14px;
            height: 14px;
            margin-left: 8px;
            border: 2px solid rgba(255,255,255,.45);
            border-top-color: #fff;
            border-radius: 999px;
            vertical-align: -2px;
            animation: spin .8s linear infinite;
        }

        .loading-light::after {
            border-color: rgba(79, 70, 229, .20);
            border-top-color: var(--primary);
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .success-box {
            text-align: center;
        }

        .success-badge {
            width: 66px;
            height: 66px;
            margin: 0 auto 16px;
            border-radius: 999px;
            background: #ecfdf5;
            color: var(--success);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0;
            font-weight: 800;
        }

        .success-badge::before {
            content: '\2713';
            font-size: 28px;
            line-height: 1;
        }

        .account-box {
            text-align: left;
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: hidden;
            margin-top: 18px;
        }

        .account-box th,
        .account-box td {
            font-size: 13px;
        }

        .hidden {
            display: none !important;
        }

        @media (max-width: 520px) {
            body { padding: 14px; }
            .shell { max-width: 100%; }
            .card { height: calc(100vh - 28px); }
            .grid-2 { grid-template-columns: 1fr; }
            .card-head, .card-body, .card-foot { padding-left: 18px; padding-right: 18px; }
        }
    </style>
</head>
<body>
    @php
        $wizardStep = (int) ($completed ? 4 : ($step ?? 1));
        $values = $values ?? [];
    @endphp

    <div class="shell" id="installerApp" data-initial-step="{{ $wizardStep }}" data-all-ok="{{ $allRequirementsPassed ? '1' : '0' }}">
        <div class="card">
            <div class="card-head">
                <div class="step-title {{ $wizardStep === 1 ? 'active' : '' }}" data-title-step="1">
                    <div class="step-icon">1</div>
                    <div class="step-meta">
                        <small>Langkah 1 dari 4</small>
                        <strong>Cek Persyaratan Server</strong>
                    </div>
                </div>
                <div class="step-title {{ $wizardStep === 2 ? 'active' : '' }}" data-title-step="2">
                    <div class="step-icon">2</div>
                    <div class="step-meta">
                        <small>Langkah 2 dari 4</small>
                        <strong>Konfigurasi Database</strong>
                    </div>
                </div>
                <div class="step-title {{ $wizardStep === 3 ? 'active' : '' }}" data-title-step="3">
                    <div class="step-icon">3</div>
                    <div class="step-meta">
                        <small>Langkah 3 dari 4</small>
                        <strong>Pengaturan Website</strong>
                    </div>
                </div>
                <div class="step-title {{ $wizardStep === 4 ? 'active' : '' }}" data-title-step="4">
                    <div class="step-icon">4</div>
                    <div class="step-meta">
                        <small>Langkah 4 dari 4</small>
                        <strong>{{ $completed ? 'Instalasi Berhasil' : 'Akun Administrator' }}</strong>
                    </div>
                </div>
            </div>

            <form id="installForm" method="POST" action="{{ route('install.run') }}">
                @csrf
                <input type="hidden" name="wizard_step" value="4">
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if (!empty($installError))
                        <div class="alert alert-danger">{{ $installError }}</div>
                    @endif

                    <div class="panel {{ $wizardStep === 1 ? 'active' : '' }}" data-panel-step="1">
                        <p class="info-text">Pastikan server siap sebelum instalasi dijalankan.</p>

                        <div class="table-box">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Komponen</th>
                                        <th>Nilai</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($requirements['server'] as $item)
                                        <tr>
                                            <td>{{ $item['label'] }}</td>
                                            <td>{{ $item['value'] }}</td>
                                            <td class="{{ $item['ok'] ? 'ok' : 'bad' }}">{{ $item['ok'] ? 'OK' : 'Gagal' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="table-box">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Folder</th>
                                        <th>Lokasi</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($requirements['directories'] as $item)
                                        <tr>
                                            <td>{{ $item['label'] }}</td>
                                            <td>{{ $item['value'] }}</td>
                                            <td class="{{ $item['ok'] ? 'ok' : 'bad' }}">{{ $item['ok'] ? 'OK' : 'Gagal' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="panel {{ $wizardStep === 2 ? 'active' : '' }}" data-panel-step="2">
                        <p class="info-text">Isi koneksi MySQL. Tombol lanjut baru aktif setelah koneksi lolos pengecekan.</p>

                        <input type="hidden" name="db_port" value="{{ $values['db_port'] ?? '3306' }}">

                        <div class="grid-2">
                            <div class="field">
                                <label for="db_host">DB Host</label>
                                <input id="db_host" class="control" type="text" name="db_host" value="{{ $values['db_host'] ?? '127.0.0.1' }}" required>
                            </div>
                            <div class="field">
                                <label for="db_username">DB Username</label>
                                <input id="db_username" class="control" type="text" name="db_username" value="{{ $values['db_username'] ?? '' }}" required>
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="field">
                                <label for="db_database">DB Name</label>
                                <input id="db_database" class="control" type="text" name="db_database" value="{{ $values['db_database'] ?? '' }}" required>
                            </div>
                            <div class="field">
                                <label for="db_password">DB Password</label>
                                <input id="db_password" class="control" type="password" name="db_password" value="{{ $values['db_password'] ?? '' }}">
                            </div>
                        </div>

                        <div class="inline-action">
                            <button type="button" class="btn btn-light btn-block" id="testDbButton">Cek Koneksi Database</button>
                        </div>

                        <div class="inline-action hidden" id="dbTestSuccess">
                            <div class="alert alert-success" style="margin-bottom:0;">Koneksi database berhasil.</div>
                        </div>
                    </div>

                    <div class="panel {{ $wizardStep === 3 ? 'active' : '' }}" data-panel-step="3">
                        <p class="info-text">Atur identitas aplikasi. URL website diambil otomatis dari domain aktif.</p>

                        <div class="grid-2">
                            <div class="field">
                                <label for="app_name">Nama Aplikasi</label>
                                <input id="app_name" class="control" type="text" name="app_name" value="{{ $values['app_name'] ?? '' }}" required>
                            </div>

                            <div class="field">
                                <label for="app_timezone">Zona Waktu</label>
                                <select id="app_timezone" class="control" name="app_timezone" required>
                                    @foreach ($timezones as $timezoneValue => $timezoneLabel)
                                        <option value="{{ $timezoneValue }}" @selected(($values['app_timezone'] ?? 'Asia/Jakarta') === $timezoneValue)>{{ $timezoneLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                    </div>

                    <div class="panel {{ $wizardStep === 4 ? 'active' : '' }}" data-panel-step="4">
                        @if ($completed)
                            <div class="success-box">
                                <div class="success-badge">✓</div>
                                <h2 style="margin:0 0 8px; font-size:24px;">Instalasi berhasil</h2>
                                <p class="info-text" style="margin-bottom:0;">{{ $websiteName ?? 'Aplikasi' }} sudah siap dipakai.</p>
                            </div>

                            <div class="account-box">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Role</th>
                                            <th>Nama</th>
                                            <th>Username</th>
                                            <th>Password</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (($accounts ?? []) as $account)
                                            <tr>
                                                <td>{{ $account['role'] }}</td>
                                                <td>{{ $account['name'] }}</td>
                                                <td>{{ $account['username'] }}</td>
                                                <td>{{ $account['password'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="inline-action" style="margin-top:18px;">
                                <a href="{{ $loginUrl ?? route('login') }}" class="btn btn-primary btn-block" style="display:flex; align-items:center; justify-content:center; text-decoration:none;">Masuk ke Login</a>
                            </div>
                        @else
                            <p class="info-text">Buat akun admin utama untuk pertama kali login.</p>

                            <div class="field">
                                <label for="admin_name">Nama Admin</label>
                                <input id="admin_name" class="control" type="text" name="admin_name" value="{{ $values['admin_name'] ?? 'Administrator' }}" required>
                            </div>

                            <div class="grid-2">
                                <div class="field">
                                    <label for="admin_username">Username Admin</label>
                                    <input id="admin_username" class="control" type="text" name="admin_username" value="{{ $values['admin_username'] ?? 'admin' }}" required>
                                </div>
                                <div class="field">
                                    <label for="admin_email">Email Admin</label>
                                    <input id="admin_email" class="control" type="email" name="admin_email" value="{{ $values['admin_email'] ?? '' }}">
                                </div>
                            </div>

                            <div class="grid-2">
                                <div class="field">
                                    <label for="admin_password">Password Admin</label>
                                    <input id="admin_password" class="control" type="password" name="admin_password" required>
                                </div>
                                <div class="field">
                                    <label for="admin_password_confirmation">Konfirmasi Password</label>
                                    <input id="admin_password_confirmation" class="control" type="password" name="admin_password_confirmation" required>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @if (! $completed)
                    <div class="card-foot">
                        <button type="button" class="btn btn-light" id="prevButton">Sebelumnya</button>
                        <div class="foot-right">
                            <button type="button" class="btn btn-primary" id="nextButton">Lanjut</button>
                            <button type="submit" class="btn btn-primary hidden" id="submitButton">Install Sekarang</button>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @if (! $completed)
        <script>
            (() => {
                const root = document.getElementById('installerApp');
                const form = document.getElementById('installForm');
                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const panels = Array.from(document.querySelectorAll('[data-panel-step]'));
                const titles = Array.from(document.querySelectorAll('[data-title-step]'));
                const prevButton = document.getElementById('prevButton');
                const nextButton = document.getElementById('nextButton');
                const submitButton = document.getElementById('submitButton');
                const testDbButton = document.getElementById('testDbButton');
                const dbTestSuccess = document.getElementById('dbTestSuccess');
                const allRequirementsPassed = root.dataset.allOk === '1';
                let step = Number(root.dataset.initialStep || '1');
                let dbPassed = false;

                const setLoading = (button, state, light = false) => {
                    button.disabled = state;
                    button.classList.toggle('loading', state && !light);
                    button.classList.toggle('loading-light', state && light);
                };

                const notify = (message, type = 'danger') => {
                    const existing = form.querySelector('.alert-runtime');
                    if (existing) {
                        existing.remove();
                    }

                    const box = document.createElement('div');
                    box.className = `alert alert-${type} alert-runtime`;
                    box.textContent = message;
                    form.querySelector('.card-body').prepend(box);
                };

                const clearNotify = () => {
                    const existing = form.querySelector('.alert-runtime');
                    if (existing) {
                        existing.remove();
                    }
                };

                const render = () => {
                    titles.forEach((item) => item.classList.toggle('active', Number(item.dataset.titleStep) === step));
                    panels.forEach((item) => item.classList.toggle('active', Number(item.dataset.panelStep) === step));
                    prevButton.classList.toggle('hidden', step === 1);
                    nextButton.classList.toggle('hidden', step === 4);
                    submitButton.classList.toggle('hidden', step !== 4);
                };

                const payload = () => {
                    const formData = new FormData(form);
                    return {
                        db_host: formData.get('db_host') || '',
                        db_port: formData.get('db_port') || '',
                        db_database: formData.get('db_database') || '',
                        db_username: formData.get('db_username') || '',
                        db_password: formData.get('db_password') || '',
                    };
                };

                const validateStepFields = (stepNumber) => {
                    const panel = form.querySelector(`[data-panel-step="${stepNumber}"]`);

                    if (!panel) {
                        return true;
                    }

                    const fields = Array.from(panel.querySelectorAll('input, select, textarea'))
                        .filter((field) => field.type !== 'hidden');

                    for (const field of fields) {
                        if (typeof field.reportValidity === 'function' && !field.reportValidity()) {
                            return false;
                        }
                    }

                    return true;
                };

                const testDatabase = async () => {
                    clearNotify();
                    dbTestSuccess.classList.add('hidden');
                    setLoading(testDbButton, true, true);

                    try {
                        const response = await fetch('{{ route('install.test-database') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify(payload()),
                        });

                        const result = await response.json();

                        if (!response.ok || !result.ok) {
                            dbPassed = false;
                            notify(result.message || 'Koneksi database gagal.');
                            return false;
                        }

                        dbPassed = true;
                        dbTestSuccess.querySelector('.alert').textContent = result.message || 'Koneksi database berhasil.';
                        dbTestSuccess.classList.remove('hidden');
                        return true;
                    } catch (error) {
                        dbPassed = false;
                        notify('Gagal menghubungi server installer.');
                        return false;
                    } finally {
                        setLoading(testDbButton, false, true);
                    }
                };

                prevButton.addEventListener('click', () => {
                    clearNotify();
                    if (step > 1) {
                        step -= 1;
                        render();
                    }
                });

                testDbButton.addEventListener('click', async () => {
                    await testDatabase();
                });

                nextButton.addEventListener('click', async () => {
                    clearNotify();

                    if (step === 1) {
                        if (!allRequirementsPassed) {
                            notify('Perbaiki persyaratan server dulu sebelum lanjut.');
                            return;
                        }

                        step = 2;
                        render();
                        return;
                    }

                    if (step === 2) {
                        const passed = await testDatabase();
                        if (!passed) {
                            return;
                        }

                        step = 3;
                        render();
                        return;
                    }

                    if (step === 3) {
                        if (!validateStepFields(3)) {
                            return;
                        }

                        step = 4;
                        render();
                    }
                });

                form.addEventListener('submit', (event) => {
                    clearNotify();

                    if (step !== 4) {
                        event.preventDefault();
                        return;
                    }

                    setLoading(submitButton, true);
                    submitButton.textContent = 'Memproses';
                });

                [
                    'db_host',
                    'db_port',
                    'db_database',
                    'db_username',
                    'db_password'
                ].forEach((name) => {
                    const field = form.querySelector(`[name="${name}"]`);
                    if (!field) {
                        return;
                    }

                    field.addEventListener('input', () => {
                        dbPassed = false;
                        dbTestSuccess.classList.add('hidden');
                    });
                });

                render();
            })();
        </script>
    @endif
</body>
</html>
