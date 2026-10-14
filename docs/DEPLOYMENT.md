# Deployment and recovery

Not deployed. Use a dedicated PostgreSQL database and production web server with document root `public/`; never expose repository/.env/storage paths. Laravel PHP 8.3+ and Node 22.12+ build runtime. PostgreSQL PDO extension needed. Run migrations after a verified backup; never migrate:fresh on real data.

Production env: APP_ENV=production, APP_DEBUG=false, APP_URL=https://your-domain, unique protected APP_KEY, DB_CONNECTION=pgsql and dedicated database credentials. SESSION_SECURE_COOKIE=true, SESSION_HTTP_ONLY=true, SESSION_SAME_SITE=lax, SESSION_ENCRYPT=true. Log level info. Gym business timezone explicitly confirmed. Do not copy local demo DB. No real mail provider currently configured.

```sh
composer install --no-dev --optimize-autoloader
npm ci --ignore-scripts
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan gym:create-owner owner@example.com
```

Create a production owner with interactive secret prompt. Provision member logins only after identity verification. Disable demo/test accounts if ever imported accidentally. Configure scheduler/queue worker when asynchronous features are introduced; this slice has none.

Launch gates: run feature tests/build; test production PostgreSQL concurrent settlements/checkins and shift-close race; verify cookies/headers/CSRF; validate restore; accept documented policy (daily pass/calendar anchoring/queued package changes); perform PII retention and Indonesian regulatory review separately. No legal-compliance claim.

Backup: encrypted daily pg_dump plus protected app key/config and any future uploads, separate account/object storage, least-privilege access and operator-defined retention. Never log passwords or QR/payment secrets. Perform periodic restore to an isolated environment and compare invoice/payments/entitlements/cash totals. Roll back code with compatible migrations; prefer forward corrective migration for data. Keep audit and payment history immutable. Monitor Laravel logs and /up; alert on exceptions/failed jobs once workers exist.
