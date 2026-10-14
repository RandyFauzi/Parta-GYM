# Business rules and KPI semantics

Timezone: GYM_TIMEZONE (default Asia/Makassar); persistence UTC. Currency IDR. A period is valid iff status=active and starts_at <= now < ends_at. Member status must additionally be active to enter. Plan deactivation does not invalidate paid periods.

Invoices preserve plan name, duration, cap and price. Invoice creation never grants access. Staff confirms full cash or bank payment; bank reference and explicit confirmation are required. Cash requires staff's own open shift. No partial payment, surcharge, discount or gateway settlement is currently supported.

Repeat invoice request key returns the original for the same member. One payment and entitlement per invoice. Any purchase queues after the member's last unexpired active period, including plan changes, to prevent overlapping access; expired purchase starts at confirmation time. Different plan queued changes are deliberate policy for this release.

Calendar months/years use Carbon no-overflow arithmetic in gym timezone. Jan 31 + one month clamps to Feb end; subsequent renewal anchors at the clamped date. Leap-day + one year clamps to Feb 28. Daily pass lasts a calendar day from payment time, not until midnight.

Check-in requires receptionist identity confirmation. Lock member, validate state/current period, reject repeat in previous GYM_DUPLICATE_MINUTES (15 default), enforce cap across period checkins, save success and attempt atomically. Denied attempts do not consume visits. No offline access.

Shift: expected = opening cash + linked cash receipts; difference = counted - expected. Closing locks shift against settlement. Closed shifts cannot be edited/reclosed. Each staff can have one active shift. Suspension requires owner, reason and before/after audit; it does not extend period dates.

Dashboard calculation definitions (GymController::dashboard):
- Active members: unique active member rows having a valid current period now; stock metric.
- New members: member created_at in selected half-open UTC-converted gym date range; flow.
- Revenue: sum payments.amount_idr with paid_at in range; confirmed gross collections, no refunds implemented.
- Check-ins today: successful visits in local midnight-to-next-midnight; visits, not unique people.
- Expiring: members with current period ending within seven days (includes members with later queued renewals); operational period-end list, not definitive churn.
- Outstanding: sum all unpaid invoices across all dates; stock value.
- Revenue chart: actual payment sums grouped by local payment date. Zero days shown as zero.

Report range is at most 366 days. End date inclusive in UI, exclusive next-midnight in query. Collections CSV includes UTC timestamps and actual received payments.
