# KejaPad

KejaPad is a property and rental management platform with integrated **M-Pesa** rent collection. Landlords manage properties and units, onboard tenants, generate and track rent, assign maintenance to caretakers, publish announcements, and review reports. Tenants pay rent via M-Pesa STK Push and raise maintenance requests.

## Tech Stack

- **Laravel 13** on **PHP 8.3**
- **MySQL** in production; **SQLite** for the example env / tests
- Blade + Bootstrap 5 UI
- `barryvdh/laravel-dompdf` (PDF reports) and `maatwebsite/excel` (Excel exports)
- `simplesoftwareio/simple-qrcode`
- M-Pesa (Safaricom Daraja) STK Push + callback

## Roles

| Role | Capabilities |
| --- | --- |
| `super_admin` | Manage all user accounts |
| `landlord` | Properties, units, tenants, rent, maintenance, announcements, reports, M-Pesa payments |
| `caretaker` | View assigned maintenance requests and announcements |
| `tenant` | Pay rent (M-Pesa), view payment history, submit maintenance requests, view announcements |

Public registration is limited to `landlord`, `caretaker`, and `tenant`.

## Getting Started

```sh
composer install
copy .env.example .env        # Windows; use `cp` on Unix
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

Configure the M-Pesa (Daraja) credentials in `.env` — see `.env.example` for the full
`MPESA_*` list.

## M-Pesa Callback Security

The `/api/mpesa/callback` route is public and hardened:

- **IP allowlist** — `MPESA_CALLBACK_ALLOWED_IPS` (comma-separated). Set
  `MPESA_CALLBACK_ENFORCE_IP=false` for local development or when behind a proxy you
  do not control.
- **Idempotency** — duplicate callbacks never double-credit a rent record.
- **Amount reconciliation** — the paid amount in the callback is parsed and compared
  against the initiated amount.
- All rent crediting happens inside a database transaction with row locking.

## Scheduled Tasks

`php artisan schedule:run` should be registered with the OS scheduler. The
`rent:mark-overdue` command runs daily at 01:00 to flag overdue rent records.

## Useful Commands

```sh
php artisan test                       # run the test suite
vendor/bin/pint                        # format code (Laravel Pint)
php artisan migrate                    # run migrations
php artisan rent:mark-overdue          # mark overdue rent records
php artisan dev                        # convenience dev runner (composer run dev)
```

## License

MIT.
