# HTTP interface

This release uses same-origin session-authenticated HTML routes rather than a separate SPA REST API. GETs render Blade, successful mutations redirect with flash, validation redirects with errors (422 for JSON requests), unauthorized role/object access is 403, guests redirect to /login. All mutations require Laravel request forgery protection.

- POST /login (6/minute), POST /logout
- GET /admin/dashboard (owner; from/to ISO dates)
- GET/POST /members; GET /members/new; GET/PUT /members/{member}
- POST /members/{member}/invoices (plan_id, UUID request_key)
- GET /payments; POST /payments/{invoice} (cash/bank_transfer, reference, accepted confirmed)
- GET /frontdesk/checkin (q); POST /frontdesk/checkin/{member} (accepted verified; 60/minute)
- GET/POST /shifts; POST /shifts/{shift}/close (actual_cash_idr; own shift)
- GET/POST /admin/membership-plans; PUT /admin/membership-plans/{plan} (owner)
- GET/POST /admin/staff; POST /admin/staff/{user}/toggle (owner; receptionist targets only)
- POST /admin/members/{member}/status (owner; status/reason)
- GET /admin/reports, GET /admin/reports/export (owner; from/to)
- GET /admin/audit (owner)
- GET /invoices/{invoice} (staff or linked invoice member)
- GET /member; PUT /member/profile (linked member only; permitted name field)
- GET /up (framework health endpoint)

Internal check-in reason codes: MEMBERSHIP_EXPIRED, MEMBERSHIP_SUSPENDED, DUPLICATE_CHECKIN, VISIT_LIMIT_REACHED. QR and provider webhook endpoints intentionally absent until implemented and verified.

### Member pages (updated)
GET /member, /member/membership, /member/visits?month=YYYY-MM, /member/payments?status=paid|unpaid, /member/profile. GET /member/invoices/{invoice} returns 404 for another member. POST /member/renewals accepts plan_id and UUID request_key; creates an unpaid own-member invoice only (10/minute). PUT /member/profile updates permitted name only. PUT /member/password requires current_password, new password and confirmation (12+ characters, 6/minute), closes database sessions and requires re-login.
