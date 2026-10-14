# GYM OS implementation

Laravel 13 modular monolith with Blade, Vite and database-backed sessions. Preserve the existing integer user IDs; domain entities use UUIDs. SQLite is the local default; PostgreSQL is required for production concurrent financial operations. No production data is seeded automatically.

1. M1: session login, role middleware, responsive shell and design tokens.
2. M2: persistent member registration/search/profile and owner-managed plans.
3. M3: invoice snapshots, confirmed manual payments, transactional activation and calendar-aware renewal.
4. M4: staff check-in, duplicate and visit-limit enforcement, attempt log.
5. M5: cashier reconciliation and isolated member portal.
6. M6: live dashboard, CSV export, audit review, tests and deployment instructions.

Scope decisions: Indonesian interface; Asia/Makassar business calendar; integer IDR. Manual cash requires an open cashier shift. Bank transfers require staff verification. Gateway, QR scanning, activation email and outbound notifications remain separate future integrations until credentials and approved business policies exist. Do not represent those integrations as live.

## Delivered status

M1 and core M2/M3 delivered. M4 manual check-in delivered; QR pending. M5 cashier and isolated portal delivered; PWA pending. M6 collections dashboard/report/CSV/audit/security tests delivered; extended analytics, provider integration and production PostgreSQL concurrency acceptance pending. Core suite: 20 passing tests / 97 assertions. Local browser flow verified on desktop/mobile. No production readiness or deployment claim.

## Member experience redesign

Dedicated home, membership, attendance calendar, billing, profile and invoice pages. Real mobile/desktop navigation, scoped queries and pagination, own-member invoice requests, safe password change with database-session invalidation. Separate MemberPortalController/FormRequest and page-specific CSS. Existing WebP assets reused; glass limited to mobile navigation. Native 150ms page transitions honor reduced motion; no new animation dependency. Verified 28 tests / 157 assertions, build and Blade compilation; five pages rendered at 320px without horizontal overflow.
