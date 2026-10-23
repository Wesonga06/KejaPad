# MySQL concurrency test setup

The normal test configuration uses SQLite in memory and cannot establish InnoDB locking behavior. `phpunit.mysql.xml` pins tests to the local database `kejapad_concurrency_test` and the local-only user `kejapad_test`. Feature tests use Laravel's `RefreshDatabase`, so they recreate tables in that database. Never use this configuration with development or production credentials/data.

## Prepare an isolated database

On a disposable local MySQL 8.x server, create a dedicated database and an account with rights only to that database:

```sql
CREATE DATABASE kejapad_concurrency_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kejapad_test'@'127.0.0.1' IDENTIFIED BY '<new-test-only-password>';
GRANT ALL PRIVILEGES ON kejapad_concurrency_test.* TO 'kejapad_test'@'127.0.0.1';
```

Confirm the account is scoped to that database and that the target is empty/disposable before starting tests:

```sql
SELECT DATABASE();
SELECT TABLE_NAME, ENGINE FROM information_schema.tables
WHERE table_schema = 'kejapad_concurrency_test' AND table_type = 'BASE TABLE';
```

In PowerShell, provide the test-only password in the current process and run the suite with the pinned configuration:

```powershell
$env:DB_PASSWORD = '<new-test-only-password>'
php artisan test --configuration=phpunit.mysql.xml --compact
Remove-Item Env:DB_PASSWORD
```

The fixed XML values prevent the database host/name/user from being overridden by a `.env` setting; the password is supplied only through the process environment. `Http::preventStrayRequests()` blocks unfaked outbound HTTP during tests, SMS workflows are disabled, and the M-Pesa environment is pinned to sandbox. No test should use the Daraja sandbox; callback tests must stay local and synthetic.

## Concurrency scenarios still requiring independent processes

The current feature suite verifies idempotency and lifecycle rules serially. To prove concurrency, add/run barrier-coordinated workers with separate Laravel processes/connections against this dedicated database for:

- simultaneous approval of pending reversals sharing one payment, asserting no duplicate approval and no net reversal above its original amount;
- simultaneous initiation reserving a payment's remaining reversible balance;
- reversal approval racing with `applyTenantCredit` and `reallocate`, asserting held credit and rent ledger totals;
- duplicate callback delivery against one pending `mpesa_payments` row;
- duplicate monthly rent generation and reminder runs;
- deposit deduction approval and refund/settlement completion races.

Do not run these scenarios until the database name, host, account grants, and InnoDB table engines have been verified. This test setup is preparatory; it does not itself prove concurrent behavior.
