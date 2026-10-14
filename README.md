# SPARTA Gym & Pilates

A working Laravel 13 gym operations application: server-managed login, owner/receptionist/member access, member registration, membership plans, invoice snapshots, verified manual payments, calendar-aware renewal, attendance, cashier reconciliation, member portal, live owner dashboard, CSV collections and audit history.

## Requirements
PHP 8.3+, Composer, Node 22.12+ (Node 24 tested), SQLite for local development. Use PostgreSQL for production row locking and run concurrency tests against that deployment database before launch.

## Local setup
```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
npm ci --ignore-scripts
npm run build
php artisan gym:create-owner owner@example.com --name="Gym Owner"
php artisan serve --host=127.0.0.1 --port=8000
```
The owner command asks for a password without echoing it. Use `/login`. No default production credentials exist. Laragon can also serve this application if its virtual host points to `public/`.

Optional clearly labeled local sample data:
```powershell
php artisan db:seed --class=DemoSeeder
```
This prints a generated password for `owner@gym.test`, `receptionist@gym.test`, and `member@gym.test`. It refuses production and does not change an existing demo. The default DatabaseSeeder creates nothing. Never run DemoSeeder on business data.

To provision a member portal account after identity verification:
```powershell
php artisan gym:link-member PG-MEMBERNUMBER member@example.com
```
This prompts for a secure password; member records do not require a login account.

## Operations
1. Owner creates plans and receptionist accounts from the UI.
2. Staff opens a cashier shift for cash payments.
3. Register a member and choose an optional plan.
4. Confirm invoice payment only after cash or bank funds are received.
5. Membership is activated atomically; repeat confirmations do not duplicate it.
6. Find member at `/frontdesk/checkin`, verify identity, and confirm check-in.
7. Renew through the member profile. Remaining paid time is preserved.
8. Close cashier shift with counted cash; discrepancy is stored permanently.

## Validation
```powershell
php artisan test --compact
php vendor/bin/pint --format agent
npm run build
php artisan view:cache
```
See docs/TESTING.md for verified scenarios and limits. See docs/DEPLOYMENT.md before production use.

## Status
The core manual-payment vertical slice is implemented. QR credentials/scanner, production gateway/webhook adapters, automated email/WhatsApp reminders, refunds/freeze/period corrections, installable PWA, photo upload, account activation links/password recovery and extended attendance/membership reports remain pending. No controls pretend these features are active. SQLite local tests do not prove PostgreSQL concurrency behavior. No production deployment has been performed.

## Member experience
Member area now has five separate layouts: home summary, membership card/plans, monthly attendance calendar, filtered invoices and profile/security. Members can request an unpaid package invoice and change their own name/password; staff still confirms payments. Navigation adapts to desktop sidebar or mobile glass dock. CSS-only transitions honor reduced motion; existing local WebP photography reused.

## Clean Git workflow

Run `npm run repo:setup` once per checkout to enable the repository pre-push hook.
`npm run check` verifies PHP style, the feature tests and the production frontend build.
The hook runs these checks before every local push and stops the push when a check fails.
GitHub Actions repeats the checks for pushes and pull requests.

Use focused Conventional Commit messages such as `feat(members): add registration`,
`fix(checkin): reject duplicate entry` and `style(portal): improve tablet layout`.
Review `git diff` and stage intentional files before committing. Fetch/pull remote changes
before pushing; keep shared branch history intact and avoid force pushes to main.
Use feature branches and pull requests for future substantive changes.
Local environment files, databases, dependencies and generated build output are excluded.

## Local code graph (Graphify)

Official tool: https://github.com/Graphify-Labs/graphify (package `graphifyy`, pinned to 0.9.90).
Install in an isolated environment; it does not change application dependencies:

```powershell
python -m venv .codex/tools/graphify-venv
.codex/tools/graphify-venv/Scripts/python.exe -m pip install "graphifyy[mcp]==0.9.90"
npm run graphify -- install --project --platform codex
npm run graph:index
```

On Linux/macOS use `.codex/tools/graphify-venv/bin/python` for the installation command.
Use `npm run graphify -- query "MembershipService" --budget 1500` before broad source reads,
and `npm run graph:update` after code changes. `.mcp.json` also registers the local stdio server;
restart the client to load it. The generated graph and Python environment stay local and ignored.
The code-only index uses AST parsing without an external model API. It does not cover CSS or
Blade layout semantics completely; verify relevant source and tests before changing behavior.
Token savings depend on the task and have not been benchmarked for this project.
