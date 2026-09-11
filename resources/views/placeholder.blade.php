@extends('layouts.panel', ['panel' => $panel ?? 'admin', 'pageTitle' => $pageTitle ?? 'Modul'])

@section('content')
    <div class="card">
        <div class="card-body">
            <h3 style="margin-top:0; font-size:16px;">{{ $moduleName ?? 'Modul' }}</h3>
            <p style="font-size:13px; color:#64748B; line-height:1.6; margin-bottom:0;">
                Halaman ini sudah dipindahkan ke Laravel dan disiapkan sebagai route baru.
                Implementasi detail modulnya sedang dilanjutkan di fondasi Laravel yang sama.
            </p>
        </div>
    </div>
@endsection
