@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Radius GPS'])

@php
    $locationItems = method_exists($locations, 'getCollection') ? $locations->getCollection() : $locations;
    $markers = $locationItems->map(fn ($location) => [
        'id' => $location->id,
        'name' => $location->nama_lokasi,
        'lat' => (float) $location->latitude,
        'lng' => (float) $location->longitude,
        'radius' => (int) $location->radius,
        'address' => $location->alamat,
        'default' => (bool) $location->is_default,
        'status' => (bool) $location->status,
    ])->values();
@endphp

@section('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endsection

@section('styles')
    #map { height:320px; width:100%; }
    .row-two { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px; }
    .card { border-radius:16px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .card-head { padding:14px 18px; background:#FFFFFF; border-bottom:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center; }
    .card-head h3 { margin:0; font-size:15px; font-weight:600; color:#1E293B; }
    .card-actions { display:flex; gap:6px; }
    .action-btn { background:#F1F5F9; border:none; width:30px; height:30px; border-radius:8px; cursor:pointer; color:#475569; transition:.2s; }
    .action-btn:hover { background:#065F46; color:white; }
    .card-body { padding:18px; }
    .coord-panel { display:flex; padding:12px 16px; background:#F8FAFC; border-top:1px solid #E2E8F0; gap:24px; flex-wrap:wrap; }
    .coord-item { display:flex; gap:8px; font-size:12px; }
    .coord-label { color:#64748B; }
    .coord-value { font-weight:600; color:#065F46; font-family:monospace; }
    .form-field { margin-bottom:16px; }
    .form-field label { display:block; margin-bottom:6px; font-size:12px; font-weight:500; color:#374151; }
    .form-row-status { display:grid; grid-template-columns:minmax(0, 1fr) 180px; gap:16px; align-items:end; }
    .form-input, .form-textarea { width:100%; padding:10px 12px; border:1px solid #E2E8F0; border-radius:10px; font-size:13px; font-family:inherit; transition:.2s; }
    .form-input:focus, .form-textarea:focus { outline:none; border-color:#065F46; box-shadow:0 0 0 3px rgba(6,95,70,0.1); }
    .form-textarea { resize:vertical; }
    .btn-outline.w-100 { width:100%; justify-content:center; padding:10px; border-radius:10px; font-weight:500; font-size:12px; }
    .btn-gps { flex:1; padding:10px; background:#F0FDF4; border:1px solid #D1FAE5; border-radius:10px; color:#065F46; font-weight:500; font-size:12px; cursor:pointer; transition:.2s; }
    .btn-gps:hover { background:#D1FAE5; }
    .btn-save { flex:1; padding:10px; background:#065F46; border:none; border-radius:10px; color:white; font-weight:500; font-size:12px; cursor:pointer; transition:.2s; }
    .btn-save:hover { background:#0D7C5A; }
    .warning-fake { background:#FEF3C7; border-radius:12px; padding:12px 18px; margin-bottom:24px; display:flex; align-items:center; gap:12px; border-left:4px solid #F59E0B; color:#92400E; }
    .warning-fake i { color:#D97706; font-size:18px; }
    .location-name { font-weight:600; color:#1E293B; }
    .location-address { color:#64748B; }
    .coords { font-size:11px; }
    .radius-badge, .status-active, .status-inactive, .badge-star { display:inline-flex; align-items:center; justify-content:center; padding:4px 8px; border-radius:999px; font-size:11px; font-weight:600; }
    .radius-badge { background:#EFF6FF; color:#1D4ED8; }
    .status-active { background:#D1FAE5; color:#065F46; }
    .status-inactive { background:#FEE2E2; color:#B91C1C; }
    .badge-star { background:#FEF3C7; color:#B45309; margin-right:4px; }
    .row-default { background:#F0FDF4; }
    .action-cell, .action-buttons { display:flex; gap:6px; flex-wrap:wrap; }
    .action-view, .action-edit, .action-default, .action-disable, .action-enable, .action-delete, .btn-cancel {
        display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:6px; font-size:11px; font-weight:500; text-decoration:none; border:none; cursor:pointer;
    }
    .action-view { background:#EFF6FF; color:#3B82F6; }
    .action-edit { background:#FEF3C7; color:#D97706; }
    .action-default { background:#F3E8FF; color:#7E22CE; }
    .action-disable { background:#FEE2E2; color:#EF4444; }
    .action-enable { background:#D1FAE5; color:#065F46; }
    .action-delete, .btn-cancel { background:#E2E8F0; color:#1E293B; }
    .modal-custom { display:none; position:fixed; inset:0; z-index:1400; background:rgba(0,0,0,0.5); padding:20px; overflow-y:auto; }
    .modal-custom.show { display:flex; align-items:center; justify-content:center; }
    .modal-container { background:white; border-radius:20px; overflow:hidden; }
    .no-padding { padding:0 !important; }
    @media (max-width: 960px) {
        .row-two { grid-template-columns:1fr; }
        .form-row-status { grid-template-columns:1fr; }
    }
@endsection

@section('content')
    <div id="ajaxCrudFragment">
    <script type="application/json" id="locationMarkersData">@json($markers)</script>
    <div class="info-banner">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>Informasi Lokasi GPS</strong><br>
            Karyawan dapat melakukan absensi jika berada di dalam radius salah satu lokasi yang terdaftar dan aktif.
            Sistem akan menolak lokasi palsu jika perangkat/browser memberi data yang tidak valid.
        </div>
    </div>

    <div class="row-two">
        <div class="card">
            <div class="card-head">
                <h3><i class="fas fa-map" style="color:#065F46; margin-right:8px;"></i>Peta Lokasi</h3>
                <div class="card-actions">
                    <button class="action-btn" onclick="recenterMap()" title="Reset Peta"><i class="fas fa-crosshairs"></i></button>
                    <button class="action-btn" onclick="zoomIn()" title="Perbesar"><i class="fas fa-search-plus"></i></button>
                    <button class="action-btn" onclick="zoomOut()" title="Perkecil"><i class="fas fa-search-minus"></i></button>
                    <button class="action-btn" onclick="deteksiLokasiSaya()" title="Deteksi GPS Saya"><i class="fas fa-location-dot"></i></button>
                </div>
            </div>
            <div class="card-body no-padding">
                <div id="map"></div>
                <div class="coord-panel">
                    <div class="coord-item"><span class="coord-label">Latitude</span><span class="coord-value" id="currentLat">{{ $centerLat }}</span></div>
                    <div class="coord-item"><span class="coord-label">Longitude</span><span class="coord-value" id="currentLng">{{ $centerLng }}</span></div>
                    <div class="coord-item"><span class="coord-label">Radius</span><span class="coord-value" id="currentRadius">{{ $centerRadius }} meter</span></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3><i class="fas fa-plus-circle" style="color:#065F46; margin-right:8px;"></i>Tambah Lokasi</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.radius-gps.store') }}" id="tambahForm" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-reset-on-success="true">
                    @csrf
                    <div class="form-row-status">
                        <div class="form-field">
                            <label>Nama Lokasi</label>
                            <input type="text" name="nama_lokasi" class="form-input" placeholder="Contoh: Kantor Pusat" value="{{ old('nama_lokasi') }}" required>
                        </div>
                        <div class="form-field">
                            <label>Status</label>
                            <select name="status" class="form-input">
                                <option value="1" @selected(old('status', '1') === '1')>Aktif</option>
                                <option value="0" @selected(old('status') === '0')>Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label>Latitude</label>
                            <input type="text" name="latitude" id="tambah_lat" class="form-input" value="{{ old('latitude', $centerLat) }}" placeholder="-6.20000000" required>
                        </div>
                        <div class="form-field">
                            <label>Longitude</label>
                            <input type="text" name="longitude" id="tambah_lng" class="form-input" value="{{ old('longitude', $centerLng) }}" placeholder="106.81666667" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label>Radius (meter)</label>
                            <input type="number" name="radius" id="tambah_radius" class="form-input" value="{{ old('radius', $centerRadius) }}" min="10" max="1000" required>
                        </div>
                        <div class="form-field">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-outline w-100" onclick="ambilKoordinatDariPeta()"><i class="fas fa-map-marker-alt"></i> Ambil dari Peta</button>
                        </div>
                    </div>
                    <div class="form-field">
                        <label>Alamat</label>
                        <textarea name="alamat" id="tambah_alamat" class="form-textarea" rows="2" placeholder="Alamat akan terisi otomatis saat deteksi GPS">{{ old('alamat') }}</textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-field" style="align-content:end;">
                            <label style="display:flex; align-items:center; gap:8px;">
                                <input type="checkbox" name="is_default" value="1" @checked(old('is_default'))> Jadikan default
                            </label>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-gps" onclick="deteksiDanIsiAlamat()"><i class="fas fa-satellite-dish"></i> Deteksi GPS & Isi Alamat</button>
                        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan Lokasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="fakeGpsWarning" class="warning-fake" style="display:none;">
        <i class="fas fa-exclamation-triangle"></i>
        <div><strong>Peringatan Keamanan!</strong> Sistem mendeteksi data lokasi tidak valid.</div>
    </div>

    <div class="card">
        <div class="card-head">
            <h3><i class="fas fa-list" style="color:#065F46; margin-right:8px;"></i>Daftar Lokasi GPS</h3>
        </div>
        <div class="card-body no-padding">
            <div class="table-responsive">
                <table class="table-bordered">
                    <thead>
                        <tr class="table-header">
                            <th width="50">No</th>
                            <th>Nama Lokasi</th>
                            <th>Koordinat</th>
                            <th>Radius</th>
                            <th>Status</th>
                            <th width="240">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($locations as $index => $location)
                            <tr class="{{ $location->is_default ? 'row-default' : '' }}">
                                <td class="text-center">
                                    @if ($location->is_default)
                                        <span class="badge-star">&#11088;</span>
                                    @endif
                                    {{ ($locations->firstItem() ?? 1) + $index }}
                                </td>
                                <td>
                                    <span class="location-name">{{ $location->nama_lokasi }}</span>
                                    @if ($location->alamat)
                                        <br><small class="location-address">{{ \Illuminate\Support\Str::limit($location->alamat, 50) }}</small>
                                    @endif
                                </td>
                                <td><code class="coords">{{ $location->latitude }}, {{ $location->longitude }}</code></td>
                                <td><span class="radius-badge">{{ $location->radius }} m</span></td>
                                <td>{!! $location->status ? '<span class="status-active">Aktif</span>' : '<span class="status-inactive">Nonaktif</span>' !!}</td>
                                <td class="action-cell">
                                    <button class="action-view" onclick="lihatDiPeta({{ (float) $location->latitude }}, {{ (float) $location->longitude }}, @js($location->nama_lokasi), {{ (int) $location->radius }})"><i class="fas fa-map"></i> Lihat</button>
                                    <button
                                        class="action-edit"
                                        data-id="{{ $location->id }}"
                                        data-update-url="{{ route('admin.radius-gps.update', $location) }}"
                                        data-nama="{{ $location->nama_lokasi }}"
                                        data-lat="{{ $location->latitude }}"
                                        data-lng="{{ $location->longitude }}"
                                        data-radius="{{ $location->radius }}"
                                        data-alamat="{{ $location->alamat }}"
                                        data-status="{{ $location->status ? '1' : '0' }}"
                                        onclick="openEditModal(this)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    @if (! $location->is_default)
                                        <form method="POST" action="{{ route('admin.radius-gps.default', $location) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment">
                                            @csrf
                                            <button type="submit" class="action-default"><i class="fas fa-star"></i> Default</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.radius-gps.status', $location) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment">
                                        @csrf
                                        <button type="submit" class="{{ $location->status ? 'action-disable' : 'action-enable' }}"><i class="fas {{ $location->status ? 'fa-ban' : 'fa-check' }}"></i> {{ $location->status ? 'Nonaktif' : 'Aktif' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.radius-gps.destroy', $location) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Yakin ingin menghapus lokasi ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-delete"><i class="fas fa-trash"></i> Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty-state">Belum ada data lokasi GPS.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $locations->links('partials.pagination-ajax', ['target' => '#ajaxCrudFragment']) }}
        </div>
    </div>
    </div>

    <div id="editModal" class="modal-custom">
        <div class="modal-dialog">
            <div class="modal-container">
                <div class="modal-header">
                    <h3>Edit Lokasi GPS</h3>
                    <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
                </div>
                <form method="POST" id="editForm" action="#" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#editModal">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="form-field">
                            <label>Nama Lokasi</label>
                            <input type="text" name="nama_lokasi" id="edit_nama" class="form-input" required>
                        </div>
                        <div class="form-row">
                            <div class="form-field">
                                <label>Latitude</label>
                                <input type="text" name="latitude" id="edit_latitude" class="form-input" required>
                            </div>
                            <div class="form-field">
                                <label>Longitude</label>
                                <input type="text" name="longitude" id="edit_longitude" class="form-input" required>
                            </div>
                        </div>
                        <div class="form-field">
                            <label>Radius (meter)</label>
                            <input type="number" name="radius" id="edit_radius" class="form-input" min="10" max="1000" required>
                        </div>
                        <div class="form-field">
                            <label>Alamat</label>
                            <textarea name="alamat" id="edit_alamat" class="form-textarea" rows="2"></textarea>
                        </div>
                        <div class="form-field">
                            <label>Status</label>
                            <select name="status" id="edit_status" class="form-input">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" onclick="closeEditModal()">Batal</button>
                        <button type="submit" class="btn-save">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let locationMarkers = [];
        let map;
        let activeMarker;
        let activeCircle;
        let currentLat = parseFloat(@json((float) $centerLat));
        let currentLng = parseFloat(@json((float) $centerLng));
        let currentRadius = parseInt(@json((int) $centerRadius), 10);

        document.addEventListener('DOMContentLoaded', function () {
            initMap();
        });

        window.addEventListener('panel:fragment-refreshed', function (event) {
            if (event.detail?.selector === '#ajaxCrudFragment' && document.getElementById('map')) {
                initMap();
            }
        });

        function readLocationMarkers() {
            const source = document.getElementById('locationMarkersData');

            if (! source) {
                return [];
            }

            try {
                return JSON.parse(source.textContent || '[]');
            } catch (error) {
                return [];
            }
        }

        function readMapState() {
            currentLat = parseFloat(document.getElementById('tambah_lat')?.value || @json((float) $centerLat));
            currentLng = parseFloat(document.getElementById('tambah_lng')?.value || @json((float) $centerLng));
            currentRadius = parseInt(document.getElementById('tambah_radius')?.value || @json((int) $centerRadius), 10);
            locationMarkers = readLocationMarkers();
        }

        function initMap() {
            readMapState();

            if (map) {
                map.remove();
            }

            map = L.map('map').setView([currentLat, currentLng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);

            activeMarker = L.marker([currentLat, currentLng], { draggable: true }).addTo(map);
            activeCircle = L.circle([currentLat, currentLng], {
                color: '#065F46',
                fillColor: '#065F46',
                fillOpacity: 0.12,
                radius: currentRadius,
            }).addTo(map);

            activeMarker.on('dragend', function (event) {
                const point = event.target.getLatLng();
                updatePoint(point.lat, point.lng);
            });

            map.on('click', function (event) {
                updatePoint(event.latlng.lat, event.latlng.lng);
            });

            locationMarkers.forEach(function (location) {
                const marker = L.marker([location.lat, location.lng]).addTo(map);
                marker.bindPopup(`<strong>${location.name}</strong><br>${location.address ?? '-'}<br>Radius: ${location.radius} meter`);
            });
        }

        function updatePoint(lat, lng) {
            currentLat = lat;
            currentLng = lng;
            activeMarker.setLatLng([lat, lng]);
            activeCircle.setLatLng([lat, lng]);
            document.getElementById('currentLat').innerText = lat.toFixed(7);
            document.getElementById('currentLng').innerText = lng.toFixed(7);
        }

        function updateRadius(radius) {
            currentRadius = radius;
            activeCircle.setRadius(radius);
            document.getElementById('currentRadius').innerText = radius + ' meter';
        }

        function recenterMap() {
            map.setView([currentLat, currentLng], 16);
        }

        function zoomIn() { map.zoomIn(); }
        function zoomOut() { map.zoomOut(); }

        function ambilKoordinatDariPeta() {
            document.getElementById('tambah_lat').value = currentLat.toFixed(7);
            document.getElementById('tambah_lng').value = currentLng.toFixed(7);
            const radius = parseInt(document.getElementById('tambah_radius').value || currentRadius, 10);
            updateRadius(radius);
        }

        function lihatDiPeta(lat, lng, nama, radius) {
            updatePoint(lat, lng);
            updateRadius(radius || currentRadius);
            map.setView([lat, lng], 17);
            activeMarker.bindPopup(`<strong>${nama}</strong>`).openPopup();
        }

        function deteksiLokasiSaya() {
            if (!navigator.geolocation) {
                alert('Browser tidak mendukung geolocation.');
                return;
            }

            navigator.geolocation.getCurrentPosition(function (position) {
                updatePoint(position.coords.latitude, position.coords.longitude);
                map.setView([currentLat, currentLng], 17);
            }, function () {
                alert('Lokasi gagal dideteksi. Pastikan izin lokasi browser aktif.');
            });
        }

        async function reverseGeocode(lat, lng) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`);
                const data = await response.json();
                return data.display_name ? data.display_name.substring(0, 200) : '';
            } catch (error) {
                return '';
            }
        }

        async function deteksiDanIsiAlamat() {
            deteksiLokasiSaya();
            setTimeout(async function () {
                document.getElementById('tambah_lat').value = currentLat.toFixed(7);
                document.getElementById('tambah_lng').value = currentLng.toFixed(7);
                const address = await reverseGeocode(currentLat, currentLng);
                if (address) {
                    document.getElementById('tambah_alamat').value = address;
                }
            }, 600);
        }

        function openEditModal(button) {
            const data = button.dataset;
            document.getElementById('edit_nama').value = data.nama || '';
            document.getElementById('edit_latitude').value = data.lat || '';
            document.getElementById('edit_longitude').value = data.lng || '';
            document.getElementById('edit_radius').value = data.radius || '';
            document.getElementById('edit_alamat').value = data.alamat || '';
            document.getElementById('edit_status').value = data.status || '1';
            document.getElementById('editForm').action = data.updateUrl || '#';
            document.getElementById('editModal').classList.add('show');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
        }

        document.getElementById('tambah_radius')?.addEventListener('input', function () {
            updateRadius(parseInt(this.value || '100', 10));
        });

        window.addEventListener('click', function (event) {
            if (event.target.id === 'editModal') {
                closeEditModal();
            }
        });
    </script>
@endsection
