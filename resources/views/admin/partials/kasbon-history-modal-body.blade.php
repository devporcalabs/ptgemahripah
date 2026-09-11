<div id="kasbonHistoryFragment" data-fragment-loading-scope>
    <div class="history-modal-summary">
        <div class="history-modal-summary-item">
            <span class="history-modal-summary-label">Karyawan</span>
            <strong>{{ $historyEmployee->nik }} - {{ $historyEmployee->nama_lengkap }}</strong>
        </div>
        <div class="history-modal-summary-item">
            <span class="history-modal-summary-label">Saldo Aktif</span>
            <strong>Rp {{ number_format((float) $historyEmployee->saldo_kasbon, 0, ',', '.') }}</strong>
        </div>
        <div class="history-modal-summary-item">
            <span class="history-modal-summary-label">Total Mutasi</span>
            <strong>{{ $historyMutations->total() }} data</strong>
        </div>
    </div>

    <form
        method="GET"
        action="{{ route('admin.kasbon.history', $historyEmployee) }}"
        class="filter-form history-modal-filter"
        data-ajax="true"
        data-auto-submit="true"
        data-refresh-target="#kasbonHistoryFragment">
        <div class="filter-group">
            <label>Arah</label>
            <select name="arah" class="filter-input">
                <option value="">Semua</option>
                <option value="plus" @selected(($historyDirection ?? '') === 'plus')>Penambahan</option>
                <option value="minus" @selected(($historyDirection ?? '') === 'minus')>Pengurangan</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Cari</label>
            <input type="search" name="q" class="filter-input" value="{{ $historySearch ?? '' }}" placeholder="Cari jenis, catatan, referensi...">
        </div>
        <div class="filter-group">
            <label>Tampil</label>
            <select name="per_page" class="filter-input">
                @foreach ([10, 25, 50, 100] as $size)
                    <option value="{{ $size }}" @selected(($historyPerPage ?? 10) === $size)>{{ $size }} / halaman</option>
                @endforeach
            </select>
        </div>
        <div class="button-group">
            <a href="{{ route('admin.kasbon.history', $historyEmployee) }}" class="btn-reset" data-ajax-link="true" data-refresh-target="#kasbonHistoryFragment">Reset</a>
        </div>
    </form>

    <div class="table-responsive history-modal-table-wrap">
        <table class="table-bordered">
            <thead>
                <tr class="table-header">
                    <th width="50">No</th>
                    <th width="180">Tanggal</th>
                    <th width="150">Jenis</th>
                    <th width="120">Arah</th>
                    <th width="150">Nominal</th>
                    <th width="150">Kasbon Awal</th>
                    <th width="150">Kasbon Akhir</th>
                    <th>Catatan</th>
                    <th width="140">Dibuat Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($historyMutations as $index => $mutation)
                    <tr>
                        <td class="text-center">{{ ($historyMutations->firstItem() ?? 1) + $index }}</td>
                        <td>
                            <strong>{{ optional($mutation->tanggal)?->translatedFormat('d M Y') }}</strong>
                            <span class="kasbon-ref">{{ optional($mutation->created_at)?->format('H:i') }} WIB</span>
                        </td>
                        <td>
                            <span class="badge {{ $mutation->is_system_generated ? 'badge-info' : 'badge-success' }}">
                                {{ $mutation->jenis_label }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $mutation->arah === 'minus' ? 'badge-danger' : 'badge-success' }}">
                                {{ $mutation->arah_label }}
                            </span>
                        </td>
                        <td class="text-right {{ $mutation->arah === 'minus' ? 'kasbon-amount-minus' : 'kasbon-amount-plus' }}">
                            {{ $mutation->arah === 'minus' ? '- ' : '+ ' }}Rp {{ number_format((float) $mutation->nominal, 0, ',', '.') }}
                        </td>
                        <td class="text-right">Rp {{ number_format((float) ($mutation->kasbon_awal ?? 0), 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format((float) ($mutation->kasbon_akhir ?? 0), 0, ',', '.') }}</td>
                        <td>
                            {{ $mutation->catatan ?: '-' }}
                            @if ($mutation->referensi_tipe || $mutation->referensi_id)
                                <span class="kasbon-ref">{{ $mutation->referensi_tipe ?? 'manual' }}{{ $mutation->referensi_id ? ' #'.$mutation->referensi_id : '' }}</span>
                            @endif
                        </td>
                        <td>{{ $mutation->createdBy?->name ?? 'Sistem' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="empty-state">Belum ada riwayat mutasi kasbon untuk karyawan ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="history-modal-pagination">
        {{ $historyMutations->links('partials.pagination-ajax', ['target' => '#kasbonHistoryFragment']) }}
    </div>
</div>
