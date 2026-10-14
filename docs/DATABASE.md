# Database

Migration `2026_10_13_063501_create_gym_tables.php` adds user role/is_active and:
- members (UUID, optional unique user_id, unique number/normalized phone)
- membership_plans (integer rupiah, calendar unit/value, optional visit_limit)
- subscriptions (unique member/plan, historical references restricted)
- invoices (unique number/request key, total, immutable JSON terms snapshot)
- membership_periods (unique source invoice, UTC half-open interval, visit cap snapshot)
- payments (one full settlement per invoice; optional cash shift)
- staff_shifts (unique nullable active staff, immutable closure/reconciliation)
- checkins (member/period/staff, UTC indexed timestamp)
- checkin_attempts (failed and accepted results)
- audit_logs (actor/action/entity/minimized metadata)

Money uses bigint integer rupiah. Historical entities have restrictive foreign keys. No financial deletion route. Existing Laravel users/session/cache/jobs migrations are retained. Prices change future sales only. Invoice item detail is stored in the single-plan JSON snapshot for this slice, rather than a separate item table.

Not yet modeled: refunds/adjustments, provider events, QR credentials, notification logs, movement ledger. PostgreSQL migration and concurrent request testing remain deployment gates. Use dedicated app DB credentials; back up before migrations.
