# Verification

Actual validation on 2026-10-13:
- `php artisan test --compact`: 20 tests, 97 assertions passed; includes output escaping and private security headers.
- `npm run build`: Vite production build succeeded.
- `php vendor/bin/pint --format agent`: formatted PHP files.
- `php artisan migrate --no-interaction`: new migration succeeded on local SQLite without resetting data.
- `php artisan view:cache`: Blade compiled.

Tests cover login/logout/inactive accounts, staff/member role boundaries, normalized registration and duplicate phone, invoice idempotency/pricing snapshot, repeated settlement, pre-expiry/expired renewal, end-month/leap-year/end-exclusive access, successful/duplicate/capped/unpaid/suspended attendance, cash shift requirement, immutable reconciliation, cross-member invoice denial, transfer confirmation/reference, negative pricing, rendering all core staff screens, live revenue/CSV and owner staff/suspension controls.

Browser QA uses a local isolated Chrome context and clearly labeled Demo data. Owner login and dashboard inspected at 1440px and 375px; 375px dashboard had no document horizontal overflow. Owner registration -> verified transfer -> active membership -> successful check-in -> repeat rejection confirmed through actual forms. Member login rendered only linked personal invoices/visits; member mobile page had no horizontal overflow and no error/warning console messages. Browser testing does not replace deployment-specific load tests.

Limitations: automated suite uses SQLite in memory; no true parallel PostgreSQL race/load tests. No real provider/email/WhatsApp calls. Gateway, QR and PWA are absent, not covered. Production launch requires row-lock behavior, backup restoration, HTTPS and real cashier policy acceptance.

Formatter note: `--dirty` cannot run because this directory has no Git repository; full-project formatter completed instead. CI configuration created but not executed by GitHub Actions.

Member redesign suite: 28 tests / 157 assertions passed. New coverage includes separate owned pages, role boundaries, inactive plan rejection, renewal request idempotency and tampering protection, cross-member invoice 404, whitelist profile updates, local-month attendance boundaries, invoice status filters and password/session invalidation. Mobile browser QA covered all five pages at 320px, active navigation and image loading; desktop calendar checked at 1440px.
