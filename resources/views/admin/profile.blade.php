@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Profil Instansi'])

@section('styles')
    .file-preview-field {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 74px;
        gap: 12px;
        align-items: start;
    }
    .file-preview-side {
        display: grid;
        gap: 6px;
        align-content: start;
    }
    .file-preview-box {
        min-height: 74px;
        border: 1px dashed #CBD5E1;
        border-radius: 12px;
        background: #F8FAFC;
        display: grid;
        place-items: center;
        padding: 6px;
        text-align: center;
        color: #94A3B8;
    }
    .file-preview-box img {
        max-width: 100%;
        max-height: 56px;
        object-fit: contain;
    }
    .file-preview-box .placeholder-icon {
        font-size: 18px;
        margin-bottom: 4px;
    }
    .file-preview-box div:not(.placeholder-icon) {
        font-size: 10px;
        line-height: 1.3;
    }
    .file-preview-action {
        margin-top: 6px;
        width: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        padding: 5px 8px;
        border-radius: 8px;
        font-size: 10px;
        gap: 5px;
    }
    @media (max-width: 640px) {
        .file-preview-field {
            grid-template-columns: 1fr;
        }
        .file-preview-box {
            min-height: 74px;
        }
    }
@endsection

@section('content')
    <div id="ajaxCrudFragment" class="content-grid-2">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-building" style="color:#065F46; margin-right:8px;"></i>Informasi Instansi</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.profil.update') }}" enctype="multipart/form-data" data-ajax="true" data-refresh-target="#ajaxCrudFragment">
                    @csrf
                    <div class="form-group">
                        <label>Nama Instansi</label>
                        <input type="text" name="nama_instansi" class="form-control" value="{{ old('nama_instansi', $settings->nama_instansi ?? '') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3">{{ old('alamat', $settings->alamat ?? '') }}</textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Telepon</label>
                            <input type="text" name="telepon" class="form-control" value="{{ old('telepon', $settings->telepon ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $settings->email ?? '') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Website</label>
                        <input type="text" name="website" class="form-control" value="{{ old('website', $settings->website ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi_instansi" class="form-control" rows="3" placeholder="Deskripsi singkat tentang instansi...">{{ old('deskripsi_instansi', $settings->deskripsi_instansi ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Logo Instansi</label>
                        <div class="file-preview-field">
                            <div>
                                <input type="file" name="logo" class="form-control-file" accept="image/jpeg,image/png,image/gif">
                                <small class="form-text">Format: JPG, PNG, GIF. Kosongkan jika tidak ingin mengubah logo.</small>
                            </div>
                            <div class="file-preview-side">
                                <div class="file-preview-box">
                                    @if (!empty($settings?->logo) && file_exists(public_path('assets/logo/'.$settings->logo)))
                                        <img src="{{ asset('assets/logo/'.$settings->logo) }}" alt="Logo">
                                    @else
                                        <div>
                                            <div class="placeholder-icon"><i class="fas fa-building"></i></div>
                                            <div>Belum ada logo</div>
                                        </div>
                                    @endif
                                </div>
                                @if (!empty($settings?->logo) && file_exists(public_path('assets/logo/'.$settings->logo)))
                                    <button type="submit" form="deleteLogoForm" class="btn btn-danger file-preview-action" data-loading-text="Menghapus...">
                                        <i class="fas fa-trash-alt"></i> Hapus
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Favicon</label>
                        <div class="file-preview-field">
                            <div>
                                <input type="file" name="favicon" class="form-control-file" accept=".ico,image/png,image/jpeg,image/svg+xml,image/webp">
                                <small class="form-text">Format: ICO, PNG, JPG, SVG, WEBP. Kosongkan jika tidak ingin mengubah favicon.</small>
                            </div>
                            <div class="file-preview-side">
                                <div class="file-preview-box">
                                    @if (!empty($settings?->favicon) && file_exists(public_path('assets/favicon/'.$settings->favicon)))
                                        <img src="{{ asset('assets/favicon/'.$settings->favicon) }}" alt="Favicon">
                                    @else
                                        <div>
                                            <div class="placeholder-icon"><i class="fas fa-bookmark"></i></div>
                                            <div>Belum ada favicon</div>
                                        </div>
                                    @endif
                                </div>
                                @if (!empty($settings?->favicon) && file_exists(public_path('assets/favicon/'.$settings->favicon)))
                                    <button type="submit" form="deleteFaviconForm" class="btn btn-danger file-preview-action" data-loading-text="Menghapus...">
                                        <i class="fas fa-trash-alt"></i> Hapus
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <span></span>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        @if (!empty($settings?->logo) && file_exists(public_path('assets/logo/'.$settings->logo)))
            <form method="POST" action="{{ route('admin.profil.logo.destroy') }}" id="deleteLogoForm" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Yakin ingin menghapus logo saat ini?" hidden>
                @csrf
                @method('DELETE')
            </form>
        @endif

        @if (!empty($settings?->favicon) && file_exists(public_path('assets/favicon/'.$settings->favicon)))
            <form method="POST" action="{{ route('admin.profil.favicon.destroy') }}" id="deleteFaviconForm" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Yakin ingin menghapus favicon saat ini?" hidden>
                @csrf
                @method('DELETE')
            </form>
        @endif

        <div class="section-stack">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-key" style="color:#065F46; margin-right:8px;"></i>Ubah Password Admin</h3>
                </div>
                <div class="card-body">
                    <div class="info-note" style="margin-bottom:16px;">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Username Admin:</strong> {{ $adminUser->username }}<br>
                            <small>Ganti password untuk keamanan akun administrator Anda.</small>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.profil.password') }}" id="formPassword" data-ajax="true" data-refresh-target="#ajaxCrudFragment">
                        @csrf
                        <div class="form-group">
                            <label>Password Lama</label>
                            <input type="password" name="old_password" id="old_password" class="form-control" placeholder="Masukkan password lama" required>
                        </div>
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Masukkan password baru" required>
                            <small class="form-text">Minimal 6 karakter</small>
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password Baru</label>
                            <input type="password" name="new_password_confirmation" id="confirm_password" class="form-control" placeholder="Konfirmasi password baru" required>
                        </div>
                        <div class="form-actions">
                            <span></span>
                            <button type="submit" class="btn btn-warning" onclick="return validatePassword()"><i class="fas fa-save"></i> Ubah Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function validatePassword() {
            const oldPassword = document.getElementById('old_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (!oldPassword || !newPassword || !confirmPassword) {
                return false;
            }

            if (newPassword.length < 6) {
                alert('Password baru minimal 6 karakter.');
                return false;
            }

            if (newPassword !== confirmPassword) {
                alert('Konfirmasi password baru tidak cocok.');
                return false;
            }

            return true;
        }
    </script>
@endsection
