# PHP MySQL Class (PDO)

A production-oriented PDO helper for PHP applications that need a small, dependency-free database abstraction with prepared statements, transaction helpers, and predictable error handling.

## Highlights

- PDO-first API with prepared statements by default
- Works with MySQL/MariaDB and Microsoft SQL Server
- Safe defaults for modern workloads:
  - `PDO::ERRMODE_EXCEPTION`
  - `PDO::ATTR_EMULATE_PREPARES = false` (MySQL)
  - UTF-8 (`utf8mb4`) MySQL charset by default
- Optional connection singleton keyed per connection config
- Transaction helpers (`beginTransaction`, `commit`, `rollBack`, `transaction`)
- Query execution helpers (`execute`, `fetch`, `fetchAll`)
- Optional query logger for observability and diagnostics

## Installation

```bash
composer require a1phanumeric/php-mysql-class
```

## Requirements

- PHP `>=7.4`
- `ext-pdo`
- For MySQL: `ext-pdo_mysql`
- For SQL Server: `ext-sqlsrv`

## Quick Start

```php
<?php

use A1phanumeric\DBPDO;

$db = new DBPDO('127.0.0.1', 'app_db', 'app_user', 'secret');

$user = $db->fetch('SELECT id, email FROM users WHERE id = ?', 42);
```

## Usage

### 1) Standard queries

```php
$db->execute(
    'UPDATE users SET email = ? WHERE id = ?',
    ['new-email@example.com', 42]
);
```

### 2) Fetch a single row

```php
$user = $db->fetch('SELECT * FROM users WHERE id = ?', 42);

if ($user === null) {
    // Not found or query failed
}
```

### 3) Fetch many rows

```php
$users = $db->fetchAll('SELECT id, email FROM users WHERE status = ?', 'active');
```

### 4) Key `fetchAll` by a column

```php
$usersByEmail = $db->fetchAll('SELECT id, email FROM users', null, 'email');
```

### 5) Transactions (recommended for multi-step writes)

```php
$db->transaction(function (DBPDO $tx) {
    $tx->execute('UPDATE accounts SET balance = balance - ? WHERE id = ?', [100, 1]);
    $tx->execute('UPDATE accounts SET balance = balance + ? WHERE id = ?', [100, 2]);
});
```

### 6) Singleton connection (per unique connection configuration)

```php
$db = DBPDO::getInstance('127.0.0.1', 'app_db', 'app_user', 'secret');
```

### 7) Connection options

```php
$db = new DBPDO('127.0.0.1', 'app_db', 'app_user', 'secret', false, [
    'persistent' => false,
    'timeout' => 5,
    'charset' => 'utf8mb4',
    'port' => 3306,
]);
```

SQL Server example:

```php
$db = new DBPDO('sql.example.local', 'app_db', 'app_user', 'secret', true, [
    'port' => 1433,
    'encrypt' => true,
    'trust_server_certificate' => false,
]);
```

### 8) Diagnostics and observability

```php
$db->setQueryLogger(function (string $query, array $params, ?float $durationMs, ?string $error) {
    error_log(json_encode([
        'query' => $query,
        'params' => $params,
        'duration_ms' => $durationMs,
        'error' => $error,
    ]));
});
```

If a query fails, inspect:

```php
$lastError = $db->getLastError();
```

## API Reference

- `execute(string $query, $values = null, bool $debug = false): PDOStatement|false`
- `fetch(string $query, $values = null): ?array`
- `fetchAll(string $query, $values = null, ?string $key = null): array`
- `table_exists(string $table): bool`
- `beginTransaction(): bool`
- `commit(): bool`
- `rollBack(): bool`
- `inTransaction(): bool`
- `transaction(Closure $callback)`
- `lastInsertId(): string`
- `getPdo(): ?PDO`
- `getLastError(): ?string`
- `setQueryLogger(callable $logger): self`

## Backward Compatibility Notes

- Existing constructor usage continues to work.
- Existing `execute`, `fetch`, and `fetchAll` method names remain unchanged.
- `execute(..., $debug = true)` is deprecated and should be replaced with `setQueryLogger()`.
- Singleton behavior is now safer for multi-database apps: each unique connection configuration gets its own instance.

## Security Notes

- Always use placeholders with bound parameters for user input.
- Avoid persistent connections unless you have validated behavior under your workload.
- Enable TLS/secure transport for SQL Server (`encrypt => true`) in production.

## Releasing / Tagging for Packagist

Packagist picks up new versions from Git tags.

1. Update docs/changelog for the release.
2. Commit and push to your default branch.
3. Tag using semantic versioning.

```bash
git tag -a v3.0.0 -m "Release v3.0.0"
git push origin v3.0.0
```

4. Ensure your Packagist package auto-update webhook is configured, or trigger an update manually in Packagist.

Recommended versioning for these changes: **major release** (`v3.0.0`) for a clean SemVer boundary on the upgraded production behavior and API surface.

## Legacy Class

The legacy class is still included in this repository:

- `class.MySQL.php`
- `class.MySQL.README.md`

## License

MIT
