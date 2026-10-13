# CLAUDE.md

See `AGENTS.md` for project guidance, commands, and conventions.

Quick reference for KejaPad (Laravel 13 / PHP 8.3, property & rental management + M-Pesa):

- Run `php artisan test` before finishing; format with `vendor/bin/pint`.
- Do not add code comments unless asked; only commit when asked.
- Authorization lives in `app/Policies` (via `$this->authorize()` or FormRequest
  `authorize()`); validation lives in `app\Http\Requests`.
- List screens paginate with Bootstrap 5; compute stats with queries, not on paginators.
- Rent crediting goes through `App\Services\RentPaymentService` (transactional).
- The M-Pesa callback is IP-restricted, idempotent, and amount-checked.
