# Devuni Notifier Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/devuni/notifier-package.svg?style=flat-square)](https://packagist.org/packages/devuni/notifier-package)
[![Tests](https://github.com/devuni-cz/notifier-package/actions/workflows/ci.yml/badge.svg)](https://github.com/devuni-cz/notifier-package/actions/workflows/ci.yml)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![License: MIT](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE.md)

Encrypted database & storage backups for Laravel apps, shipped to the [Devuni Notifier](https://notifier.devuni.cz) central server. AES-256 ZIPs, chunked HTTPS upload, token auth, queue support.

## How it works

```
Your Laravel app  ──[AES-256 ZIP, chunked HTTPS]──▶  notifier.devuni.cz
(this package)                                        (central server)
```

> **Heads up:** This is the **client side** of the Devuni Notifier platform. Without a central server configured via `NOTIFIER_URL`, there's nowhere to send backups. If you don't have it, try [spatie/laravel-backup](https://github.com/spatie/laravel-backup) instead.

## Install

```bash
composer require devuni/notifier-package
php artisan vendor:publish --tag="notifier-config"
php artisan notifier:install   # interactive .env wizard
php artisan notifier:check     # verify setup (env, DB, 7z, mysqldump, URL)
```

**Requirements:** PHP 8.4+, Laravel 12+, `mysqldump`, and `p7zip-full` (recommended) or PHP `zip` extension.

## Usage

### Scheduled backups (recommended)

Add to `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('notifier:database-backup')->dailyAt('02:00')->onOneServer();
Schedule::command('notifier:storage-backup')->weeklyOn(0, '03:00')->onOneServer();
```

### On demand

```bash
php artisan notifier:database-backup
php artisan notifier:storage-backup
```

### HTTP API

Trigger backups from an external scheduler. Rate-limited to 10 req/min.

```bash
curl -X POST https://your-app.com/api/notifier/backup \
  -H "X-Notifier-Token: your-token" \
  -d "type=backup_database"   # or backup_storage
```

On failure the response returns an opaque `error_id` (UUID) — the full detail (stack trace, `mysqldump`/7z stderr) stays in your `backup` log channel. Grep logs for the UUID to correlate.

## Configure

Minimum `.env`:

```bash
NOTIFIER_BACKUP_CODE=...                                        # auth token
NOTIFIER_URL=https://notifier.devuni.cz/api/v1/repositories/123 # your endpoint
NOTIFIER_BACKUP_PASSWORD=...                                    # ZIP password
```

Optional: `NOTIFIER_LOGGING_CHANNEL`, `NOTIFIER_ROUTES_ENABLED`, `NOTIFIER_ROUTE_PREFIX`, `NOTIFIER_ZIP_STRATEGY` (`auto`/`cli`/`php`), `NOTIFIER_CHUNK_SIZE`, `NOTIFIER_QUEUE_CONNECTION`. See [`config/notifier.php`](config/notifier.php) for defaults and descriptions.

### Exclusions

Arrays — edit `config/notifier.php`:

```php
'excluded_tables' => ['telescope_entries', 'sessions', 'cache', 'jobs', 'failed_jobs'],
'excluded_files'  => ['.gitignore', 'temp', 'logs/debug.log'],
```

### Queue offloading

API-triggered backups can be offloaded to avoid PHP timeouts:

```bash
NOTIFIER_QUEUE_CONNECTION=redis   # or database, sqs, beanstalkd
```

Artisan commands always run synchronously regardless of this setting.

## Security

- **At rest:** AES-256 encrypted archives with `0600` permissions, cleaned up after upload
- **In transit:** HTTPS-only, `hash_equals` token comparison, per-chunk + full-file SHA-256 verification
- **No leaks:** ZIP password passed via stdin (not argv — invisible to `ps` / `/proc/*/cmdline`); API errors return opaque UUIDs, not raw exception messages
- **Report vulnerabilities:** see [security policy](../../security/policy) — don't open public issues

## Links

- [Changelog](CHANGELOG.md) — see what's new in each release
- [Contributing](CONTRIBUTING.md)
- [Full config reference](config/notifier.php)

## Credits

- [Ludwig Tomas](https://github.com/ludwigtomas)
- [All contributors](../../contributors)

## License

MIT — see [LICENSE.md](LICENSE.md).
