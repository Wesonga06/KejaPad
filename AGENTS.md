# AGENTS.md

Guidance for AI coding agents working in the KejaPad repository.

## Project

KejaPad is a **Laravel 13 / PHP 8.3** property & rental management app with M-Pesa rent
collection. See `README.md` for the full overview. Roles: `super_admin`, `landlord`,
`caretaker`, `tenant`.

## Commands

```sh
php artisan test           # run the test suite
vendor/bin/pint            # format code; run on the files you touch
php artisan migrate        # migrations (MySQL in dev; SQLite for tests)
php artisan rent:mark-overdue
```

- Do **not** add code comments unless explicitly requested.
- Only commit when explicitly asked.

## Conventions

- **Authorization**: every controller action is guarded by a policy in `app/Policies`.
  Reads/actions without a FormRequest call `$this->authorize(...)`; mutating actions
  authorize inside their FormRequest's `authorize()` method. Nested-resource routes
  (`properties/{property}/units/{unit}`) check `abort_if($unit->property_id !== $property->id, 404)`
  **before** authorization so a mismatched unit is a 404, not a 403.
- **Validation**: all request validation lives in FormRequests under
  `app\Http\Requests\{Domain}`.
- **Pagination**: list screens use `->paginate()` (Bootstrap 5 markup via
  `Paginator::useBootstrapFive()` in `AppServiceProvider`). Summary stats on paginated
  screens are computed with separate aggregate queries and passed to the view — never
  call collection methods (e.g. `->where()`, `->sum()`) on a paginator in a Blade view.
- **Money**: rent crediting goes through `App\Services\RentPaymentService` inside a
  transaction with `lockForUpdate()`. Do not credit rent records ad hoc.
- **M-Pesa**: the callback is IP-restricted, idempotent, and amount-checked. Keep it
  that way; never log full callback payloads or phone numbers.

## Environment Notes

- The checked-in `.env` may point at MySQL on `127.0.0.1:3306`; if MySQL is not running
  migrations/tests against it will fail with `SQLSTATE[HY000] [2002]`. The test suite
  uses an in-memory SQLite database (`RefreshDatabase`).
- The unique-index migration
  `2026_10_13_000000_add_unique_constraints_to_tables.php` will fail if duplicate rows
  already exist; de-duplicate first.
