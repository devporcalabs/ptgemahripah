<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PDO;
use PDOException;

class InstallerService
{
    public function requirements(): array
    {
        $extensions = [
            'bcmath',
            'ctype',
            'dom',
            'fileinfo',
            'gd',
            'json',
            'mbstring',
            'openssl',
            'pdo',
            'pdo_mysql',
            'tokenizer',
            'xml',
        ];

        return [
            'server' => [
                [
                    'label' => 'PHP 8.2 atau lebih baru',
                    'ok' => version_compare(PHP_VERSION, '8.2.0', '>='),
                    'value' => PHP_VERSION,
                ],
                ...collect($extensions)->map(fn (string $extension): array => [
                    'label' => "Ekstensi {$extension}",
                    'ok' => extension_loaded($extension),
                    'value' => extension_loaded($extension) ? 'Aktif' : 'Tidak aktif',
                ])->all(),
            ],
            'directories' => [
                [
                    'label' => 'Folder storage dapat ditulis',
                    'ok' => is_writable(storage_path()),
                    'value' => storage_path(),
                ],
                [
                    'label' => 'Folder bootstrap/cache dapat ditulis',
                    'ok' => is_writable(base_path('bootstrap/cache')),
                    'value' => base_path('bootstrap/cache'),
                ],
            ],
        ];
    }

    public function requirementsPass(): bool
    {
        $requirements = $this->requirements();

        return collect($requirements['server'])->every(fn (array $item): bool => $item['ok'])
            && collect($requirements['directories'])->every(fn (array $item): bool => $item['ok']);
    }

    public function supportedTimezones(): array
    {
        return [
            'Asia/Jakarta' => 'Asia/Jakarta (WIB)',
            'Asia/Makassar' => 'Asia/Makassar (WITA)',
            'Asia/Jayapura' => 'Asia/Jayapura (WIT)',
        ];
    }

    public function testDatabase(array $payload): array
    {
        try {
            $serverPdo = $this->connectToServer($payload);
            $databaseExists = $this->databaseExists($serverPdo, $payload['db_database']);

            return [
                'ok' => true,
                'message' => $databaseExists
                    ? 'Koneksi database berhasil.'
                    : 'Koneksi server database berhasil. Database akan dibuat saat instalasi.',
            ];
        } catch (PDOException $exception) {
            return [
                'ok' => false,
                'message' => 'Koneksi database gagal: '.$exception->getMessage(),
            ];
        }
    }

    public function install(array $payload): void
    {
        $seedDummyKaryawan = (bool) ($payload['seed_dummy_karyawan'] ?? false);

        $this->writeEnvironment([
            'APP_NAME' => $payload['app_name'],
            'APP_URL' => $payload['app_url'],
            'APP_TIMEZONE' => $payload['app_timezone'],
            'APP_INSTALLED' => 'false',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $payload['db_host'],
            'DB_PORT' => (string) $payload['db_port'],
            'DB_DATABASE' => $payload['db_database'],
            'DB_USERNAME' => $payload['db_username'],
            'DB_PASSWORD' => (string) ($payload['db_password'] ?? ''),
            'SEED_DUMMY_KARYAWAN' => $seedDummyKaryawan ? 'true' : 'false',
        ]);

        $this->applyRuntimeConfiguration($payload);
        $this->ensureDatabaseExists($payload);

        Artisan::call('optimize:clear');

        if (! config('app.key')) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        Config::set('installer.skip_admin_seed', true);

        try {
            Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        } finally {
            Config::set('installer.skip_admin_seed', false);
        }

        $this->createOrUpdateAdminUser($payload);

        try {
            Artisan::call('storage:link');
        } catch (\Throwable) {
        }

        $this->writeEnvironment([
            'APP_INSTALLED' => 'true',
        ]);

        Artisan::call('optimize:clear');
    }

    public function writeEnvironmentValues(array $values): void
    {
        $this->writeEnvironment($values);
    }

    private function applyRuntimeConfiguration(array $payload): void
    {
        Config::set('app.name', $payload['app_name']);
        Config::set('app.url', $payload['app_url']);
        Config::set('app.timezone', $payload['app_timezone']);
        Config::set('app.installed', false);
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.host', $payload['db_host']);
        Config::set('database.connections.mysql.port', (string) $payload['db_port']);
        Config::set('database.connections.mysql.database', $payload['db_database']);
        Config::set('database.connections.mysql.username', $payload['db_username']);
        Config::set('database.connections.mysql.password', (string) ($payload['db_password'] ?? ''));
        Config::set('database.connections.mysql.charset', 'utf8mb4');
        Config::set('database.connections.mysql.collation', 'utf8mb4_unicode_ci');
        Config::set('database.connections.mysql.engine', 'InnoDB');
        Config::set('session.driver', 'file');
        Config::set('cache.default', 'file');
        Config::set('queue.default', 'sync');

        DB::purge('mysql');
    }

    private function ensureDatabaseExists(array $payload): void
    {
        $serverPdo = $this->connectToServer($payload);
        $databaseName = str_replace('`', '``', $payload['db_database']);
        $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    private function connectToServer(array $payload): PDO
    {
        return new PDO(
            sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $payload['db_host'], $payload['db_port']),
            $payload['db_username'],
            (string) ($payload['db_password'] ?? ''),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5,
            ],
        );
    }

    private function databaseExists(PDO $pdo, string $database): bool
    {
        $statement = $pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :database LIMIT 1');
        $statement->execute(['database' => $database]);

        return (bool) $statement->fetchColumn();
    }

    private function createOrUpdateAdminUser(array $payload): void
    {
        $user = User::query()->updateOrCreate(
            ['username' => $payload['admin_username']],
            [
                'name' => $payload['admin_name'],
                'email' => $payload['admin_email'] ?: null,
                'password' => Hash::make($payload['admin_password']),
                'role' => 'super-admin',
            ],
        );

        $user->syncRoles(['super-admin']);
    }

    private function writeEnvironment(array $values): void
    {
        $path = base_path('.env');
        $fallbackPath = base_path('.env.example');
        $content = file_exists($path)
            ? (string) file_get_contents($path)
            : (string) file_get_contents($fallbackPath);

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->formatEnvValue((string) $value);
            $pattern = "/^".preg_quote($key, '/')."=.*/m";

            if (preg_match($pattern, $content) === 1) {
                $content = (string) preg_replace($pattern, $line, $content);
                continue;
            }

            $content = rtrim($content).PHP_EOL.$line.PHP_EOL;
        }

        file_put_contents($path, $content);
    }

    private function formatEnvValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/^(true|false|null|[0-9]+)$/i', $value) === 1) {
            return $value;
        }

        if (preg_match('/^[A-Za-z0-9_:\\/.\\-@]+$/', $value) === 1) {
            return $value;
        }

        return '"'.str_replace('"', '\"', $value).'"';
    }
}
