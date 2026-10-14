# Architecture

Laravel 13 / PHP 8.3 modular monolith. Blade owns presentation; named session-authenticated web routes own transport; FormRequests validate member/plan/staff inputs; MembershipService owns invoice/activation/renewal/check-in invariants; Eloquent owns persistence. Controllers currently coordinate related screens; split into domain-specific controllers as modules expand.

AuthController performs session regeneration and invalidates on logout. RoleMiddleware protects staff/owner/member groups. Invoice receipt explicitly checks member ownership. SecurityHeaders forbids framing/sniffing and caches private responses with no-store. User input cannot choose privileged roles during registration or staff creation.

Transactions lock member rows to serialize activation/attendance. Settlement additionally locks invoice/shift. Unique invoice payment, source invoice entitlement, active shift staff ID and request key add database safeguards. Requires a row-locking production database. SQLite is only a local convenience.

No Redis, public API token store, microservices or external provider added. Existing integer user IDs retained; UUID domain IDs. Future gateway and QR modules must call existing authoritative services and add their own signature/event validation.
