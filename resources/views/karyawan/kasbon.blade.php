@extends('layouts.panel', ['panel' => 'karyawan', 'pageTitle' => 'Kasbon Saya'])

@section('styles')
    .portal-page { display:grid; gap:18px; }
    .stats-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; }
    .stat-card,.panel-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
    .stat-card { padding:18px; }
    .stat-label { font-size:12px; color:#64748b; margin-bottom:6px; }
    .stat-value { font-size:24px; font-weight:800; color:#0f172a; }
    .stat-note { margin-top:8px; font-size:12px; color:#475569; }
    .panel-head { padding:16px 18px; border-bottom:1px solid #eef2f7; display:flex; justify-content:space-between; gap:16px; align-items:center; flex-wrap:wrap; }
    .panel-title { margin:0; font-size:16px; font-weight:700; color:#0f172a; }
    .panel-subtitle { margin-top:4px; font-size:12px; color:#64748b; }
    .panel-body { padding:18px; }
    .filter-form { display:flex; gap:12px; align-items:end; flex-wrap:wrap; }
    .filter-group label { display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:6px; }
    .amount-plus { color:#166534; font-weight:700; }
    .amount-minus { color:#b91c1c; font-weight:700; }
    @media (max-width:992px) { .stats-grid { grid-template-columns:1fr 1fr; } }
    @media (max-width:640px) { .stats-grid { grid-template-columns:1fr; } }
@endsection

@section('content')
    <div class="portal-page">
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-label">Saldo Aktif</div><div class="stat-value">Rp {{ number_format($stats['saldo'], 0, ',', '.') }}</div><div class="stat-note">Saldo kasbon berjalan</div></div>
            <div class="stat-card"><div class="stat-label">Penambahan</div><div class="stat-value">Rp {{ number_format($stats['plus'], 0, ',', '.') }}</div><div class="stat-note">{{ $period->translatedFormat('F Y') }}</div></div>
            <div class="stat-card"><div class="stat-label">Pengurangan</div><div class="stat-value">Rp {{ number_format($stats['minus'], 0, ',', '.') }}</div><div class="stat-note">{{ $period->translatedFormat('F Y') }}</div></div>
            <div class="stat-card"><div class="stat-label">Mutasi</div><div class="stat-value">{{ $stats['count'] }}</div><div class="stat-note">Transaksi bulan ini</div></div>
        </div>

        <div class="panel-card">
            <div class="panel-head">
                <div>
                    <h3 class="panel-title">Riwayat Kasbon</h3>
                    <div class="panel-subtitle">{{ $employee->nama_lengkap }} · {{ $employee->nik }}</div>
                </div>
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label>Bulan</label>
                        <input type="month" name="bulan" class="filter-input" value="{{ $period->format('Y-m') }}">
                    </div>
                    <div class="filter-group">
                        <label>Arah</label>
                        <select name="arah" class="filter-input">
                            <option value="">Semua</option>
                            <option value="plus" @selected($direction === 'plus')>Penambahan</option>
                            <option value="minus" @selected($direction === 'minus')>Pengurangan</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Tampil</label>
                        <select name="per_page" class="filter-input">
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} / halaman</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="button-group"><button type="submit" class="btn btn-primary">Filter</button></div>
                </form>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table-bordered">
                        <thead>
                            <tr class="table-header">
                                <th width="50">No</th>
                                <th width="170">Tanggal</th>
                                <th width="150">Jenis</th>
                                <th width="120">Arah</th>
                                <th width="150">Nominal</th>
                                <th width="150">Kasbon Awal</th>
                                <th width="150">Kasbon Akhir</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($mutationRows as $index => $row)
                                <tr>
                                    <td class="text-center">{{ ($mutationRows->firstItem() ?? 1) + $index }}</td>
                                    <td>
                                        <strong>{{ optional($row->tanggal)->translatedFormat('d M Y') }}</strong>
                                        <div class="muted-sm">{{ optional($row->created_at)->format('H:i') }} WIB</div>
                                    </td>
                                    <td>{{ $row->jenis_label }}</td>
                                    <td>{{ $row->arah_label }}</td>
                                    <td class="text-right {{ $row->arah === 'minus' ? 'amount-minus' : 'amount-plus' }}">
                                        {{ $row->arah === 'minus' ? '- ' : '+ ' }}Rp {{ number_format((float) $row->nominal, 0, ',', '.') }}
                                    </td>
                                    <td class="text-right">Rp {{ number_format((float) ($row->kasbon_awal ?? 0), 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format((float) ($row->kasbon_akhir ?? 0), 0, ',', '.') }}</td>
                                    <td>{{ $row->catatan ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="empty-state">Belum ada mutasi kasbon.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $mutationRows->links() }}
            </div>
        </div>
    </div>
@endsection
