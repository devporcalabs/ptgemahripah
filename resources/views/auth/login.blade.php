<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $faviconUrl = ! empty($settings?->favicon) && file_exists(public_path('assets/favicon/'.$settings->favicon))
            ? asset('assets/favicon/'.$settings->favicon)
            : null;
    @endphp
    <title>Login - {{ $settings->nama_instansi ?? config('app.name') }}</title>
    @if ($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}">
        <link rel="shortcut icon" href="{{ $faviconUrl }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: url('{{ asset('assets/bkk.jpg') }}') no-repeat center center;
            background-size: cover;
        }
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background: rgba(13, 92, 63, 0.75);
        }
        .login-container { width: 100%; max-width: 380px; position: relative; z-index: 1; }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 32px 28px;
            box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.3);
        }
        .logo { text-align: center; margin-bottom: 28px; }
        .logo-icon {
            width: 60px;
            height: 60px;
            background: #0D5C3F;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            overflow: hidden;
        }
        .logo-icon img { width: 100%; height: 100%; object-fit: cover; }
        .logo-icon i { font-size: 28px; color: white; }
        .logo h2 { font-size: 18px; font-weight: 700; color: #1F2937; margin-bottom: 4px; }
        .logo p { color: #6B7280; font-size: 11px; }
        .login-toast-stack {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1200;
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: min(360px, calc(100vw - 32px));
        }
        .login-toast {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 12px;
            box-shadow: 0 18px 35px rgba(15, 23, 42, 0.18);
            font-size: 12px;
            line-height: 1.45;
            transform: translateY(-8px);
            opacity: 0;
            pointer-events: none;
            transition: all 0.22s ease;
        }
        .login-toast.show {
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }
        .login-toast.error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #B91C1C;
        }
        .login-toast.success {
            background: #ECFDF5;
            border: 1px solid #A7F3D0;
            color: #065F46;
        }
        .login-toast i {
            margin-top: 1px;
            flex-shrink: 0;
        }
        .login-toast button {
            margin-left: auto;
            border: none;
            background: transparent;
            color: currentColor;
            font-size: 16px;
            line-height: 1;
            cursor: pointer;
            padding: 0;
        }
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #374151;
            font-size: 12px;
        }
        .input-wrapper { position: relative; }
        .input-wrapper i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 10px 12px 10px 38px;
            font-size: 13px;
            background: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            color: #1F2937;
            font-family: 'Inter', sans-serif;
        }
        .form-control:focus {
            outline: none;
            border-color: #0D5C3F;
            background: white;
            box-shadow: 0 0 0 3px rgba(13, 92, 63, 0.1);
        }
        .btn-login {
            width: 100%;
            padding: 10px;
            background: #0D5C3F;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 6px;
            font-family: 'Inter', sans-serif;
        }
        .btn-login:hover { background: #0A4D35; }
        .btn-login.is-loading {
            opacity: 0.8;
            cursor: wait;
            pointer-events: none;
        }
        .btn-login-content {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-spinner {
            width: 14px;
            height: 14px;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 999px;
            animation: button-spin 0.7s linear infinite;
            flex-shrink: 0;
        }
        @keyframes button-spin {
            to { transform: rotate(360deg); }
        }
        .footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
        }
        .copyright {
            font-size: 10px;
            color: #9CA3AF;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div id="loginToastStack" class="login-toast-stack">
        @if (session('error'))
            <div class="login-toast error" data-login-toast>
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
                <button type="button" aria-label="Tutup">&times;</button>
            </div>
        @endif

        @if ($errors->any())
            <div class="login-toast error" data-login-toast>
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ $errors->first() }}</span>
                <button type="button" aria-label="Tutup">&times;</button>
            </div>
        @endif

        @if (session('success'))
            <div class="login-toast success" data-login-toast>
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
                <button type="button" aria-label="Tutup">&times;</button>
            </div>
        @endif
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <div class="logo-icon">
                    @if (!empty($settings?->logo) && file_exists(public_path('assets/logo/'.$settings->logo)))
                        <img src="{{ asset('assets/logo/'.$settings->logo) }}" alt="Logo">
                    @else
                        <i class="fas fa-clock"></i>
                    @endif
                </div>
                <h2>{{ $settings->nama_instansi ?? config('app.name') }}</h2>
                <p>Silakan login untuk melanjutkan</p>
            </div>

            <form method="POST" action="{{ route('login.attempt') }}" id="loginForm">
                @csrf
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username atau NIK" required autocomplete="off" value="{{ old('username') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="loginSubmit">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="footer">
                <div class="copyright">&copy; {{ now()->year }} {{ $settings->nama_instansi ?? config('app.name') }}</div>
            </div>
        </div>
    </div>
    <script>
        const loginForm = document.getElementById('loginForm');
        const loginSubmit = document.getElementById('loginSubmit');
        const loginToastStack = document.getElementById('loginToastStack');
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');

        if (loginForm && loginSubmit) {
            const defaultButtonHtml = loginSubmit.innerHTML;

            const hideToast = function (toast) {
                if (! toast) {
                    return;
                }

                toast.classList.remove('show');
                window.setTimeout(function () {
                    toast.remove();
                }, 220);
            };

            const bindToastClose = function (toast) {
                const closeButton = toast?.querySelector('button');
                if (closeButton) {
                    closeButton.addEventListener('click', function () {
                        hideToast(toast);
                    });
                }
            };

            const showToast = function (type, message) {
                if (! loginToastStack) {
                    return;
                }

                loginToastStack.querySelectorAll('.login-toast').forEach(function (toast) {
                    hideToast(toast);
                });

                const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
                const toast = document.createElement('div');
                toast.className = `login-toast ${type}`;
                toast.innerHTML = `
                    <i class="fas ${icon}"></i>
                    <span>${message}</span>
                    <button type="button" aria-label="Tutup">&times;</button>
                `;

                loginToastStack.appendChild(toast);
                bindToastClose(toast);

                window.requestAnimationFrame(function () {
                    toast.classList.add('show');
                });

                window.setTimeout(function () {
                    hideToast(toast);
                }, 4200);
            };

            loginToastStack?.querySelectorAll('[data-login-toast]').forEach(function (toast) {
                bindToastClose(toast);

                window.requestAnimationFrame(function () {
                    toast.classList.add('show');
                });

                window.setTimeout(function () {
                    hideToast(toast);
                }, 4200);
            });

            const clearErrors = function () {
                loginForm.querySelectorAll('.is-invalid').forEach(function (field) {
                    field.classList.remove('is-invalid');
                });
            };

            const updateCsrfToken = function (token) {
                if (! token) {
                    return;
                }

                if (csrfMeta) {
                    csrfMeta.setAttribute('content', token);
                }

                const csrfInput = loginForm.querySelector('input[name="_token"]');
                if (csrfInput) {
                    csrfInput.value = token;
                }
            };

            const setLoading = function (loading) {
                loginSubmit.disabled = true;
                loginSubmit.classList.toggle('is-loading', loading);
                loginSubmit.disabled = loading;
                loginSubmit.innerHTML = loading
                    ? '<span class="btn-login-content"><span class="btn-spinner"></span><span>Memproses...</span></span>'
                    : defaultButtonHtml;
            };

            loginForm.addEventListener('submit', async function (event) {
                event.preventDefault();

                clearErrors();
                setLoading(true);

                try {
                    const response = await fetch(loginForm.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfMeta?.getAttribute('content') || '',
                        },
                        body: new FormData(loginForm),
                        credentials: 'same-origin',
                    });

                    const contentType = response.headers.get('content-type') || '';

                    if (! contentType.includes('application/json')) {
                        if (response.redirected) {
                            window.location.href = response.url;
                            return;
                        }

                        throw new Error('Respons server tidak valid.');
                    }

                    const payload = await response.json();
                    updateCsrfToken(payload.csrf_token);

                    if (! response.ok || payload.ok === false) {
                        showToast(payload.level || 'error', payload.message || 'Login gagal.');
                        return;
                    }

                    if (payload.redirect) {
                        window.location.href = payload.redirect;
                        return;
                    }

                    showToast('success', payload.message || 'Login berhasil.');
                } catch (error) {
                    showToast('error', error.message || 'Terjadi kesalahan saat login.');
                } finally {
                    setLoading(false);
                }
            });
        }
    </script>
</body>
</html>
