<?php

$defaultTimeout = 20;

return [
    /*
    |--------------------------------------------------------------------------
    | Update Server Manifest URL
    |--------------------------------------------------------------------------
    |
    | Simpan konfigurasi updater langsung di file ini, bukan di .env.
    | Contoh:
    | https://release.absensindo.com/api/apps/karyawan/manifest
    |
    */
    'manifest_url' => 'https://release.absensindo.com/api/apps/karyawan/manifest',

    /*
    |--------------------------------------------------------------------------
    | Update Public Key (PEM)
    |--------------------------------------------------------------------------
    |
    | Public key untuk verifikasi signature update (RSA).
    | Isi langsung dalam format PEM atau base64 dari PEM.
    |
    */
    'public_key' => <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAy9QgptyHO34okfzkjm7J
xsE/MLwDTk+KaHqRsDnCXmH1FrxsYb3dlT7TuoOsnXzSlzwGn0qPGmrSat6e0a44
ShEgmqnqsTytsMijjmqL3a27rIJwD0swJcbZ255oOalRW8147zPU56bTt5dKf59o
jDcWnYL12TvCOHVrFo3dCx305xXqvvmm0tkpUadxLSY5ri/U22sgLSBRokKLEhsZ
4C9dA1Gw3HdvSKLpX7JXxRFi15g5g4X1CjuEbQbEaSskK3N+N2O26sgOtRE3xRR+
D6Y/mhzNHUtTaG0Us3hxujnu+07OARz0mGLoYtn1XB1N0/dXxL4nnRhVTI3Nbt40
HwIDAQAB
-----END PUBLIC KEY-----
PEM,

    /*
    |--------------------------------------------------------------------------
    | License / Client Identifier (Opsional)
    |--------------------------------------------------------------------------
    */
    'license_key' => 'fd06344b1e76df978fc4cfd8c10c121f5acb95720b0bf0211a047968b19fab0f',

    /*
    |--------------------------------------------------------------------------
    | Allowed Hosts (Opsional)
    |--------------------------------------------------------------------------
    |
    | Jika diisi, ZIP update hanya boleh berasal dari host ini.
    |
    */
    'allowed_hosts' => [
        'release.absensindo.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    */
    'timeout' => $defaultTimeout,

    /*
    |--------------------------------------------------------------------------
    | HTTP Connect Timeout
    |--------------------------------------------------------------------------
    |
    | Batas waktu untuk koneksi awal ke update server (detik).
    |
    */
    'connect_timeout' => 10,

    /*
    |--------------------------------------------------------------------------
    | Download Timeout & Retry
    |--------------------------------------------------------------------------
    |
    | Karena ukuran paket update bisa besar, timeout download sebaiknya lebih
    | lama dibanding manifest.
    |
    */
    'download_timeout' => max(60, $defaultTimeout),
    'download_retry' => 2,
    'download_retry_sleep_ms' => 750,

    'allow_insecure_http' => false,

    /*
    |--------------------------------------------------------------------------
    | Excluded Paths (Tidak akan ditimpa oleh updater)
    |--------------------------------------------------------------------------
    */
    'exclude' => [
        '.env',
        'storage/',
        'public/storage/',
        'bootstrap/cache/',
    ],
];
