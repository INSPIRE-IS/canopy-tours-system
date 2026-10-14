# Public Booking V2 business rules

Read this before changing the public booking checkout, holds, public extras, PayFast handoff, or unpaid-booking expiry.

## Canonical lifecycle

1. The booking page reads public availability from `PublicServiceAvailability`. Availability combines active bookings and active `public_booking_holds`.
2. Continue creates or refreshes one session-owned lightweight hold when `teams.frontend_hold_enabled` is enabled. No customer, booking, invoice, or payment row exists yet.
3. Checkout displays the server expiry time. An expired hold releases capacity and returns the guest to date/time selection with a one-time session notice; expiry state is not accepted from a public URL parameter.
4. Confirm & pay validates the hold and recalculates availability, season ownership, rack/promo pricing, selected public extras, and accepted terms inside one database transaction.
5. A successful confirmation creates one pending provisional booking and its customer, booking item, manifest, detail, inventory, fee, price lines, invoice, selected extras, and terms-acceptance snapshot.
6. The guest is sent to the existing `front.payment.payfast.booking` endpoint. V2 does not implement a second gateway or callback flow.
7. PayFast notify is the trusted completion path. It marks the booking paid, records the payment and audit entry, and allocates only extras whose metadata source is `public_booking_v2`.
8. After payment commits, `FulfillBookingPayment` checks the normal site email lookup gates. When enabled it reuses the established `RendererService`, `BookingMail`, invoice Blade, and boarding-pass Blade flow. It does not use the experimental invoice-template email content.
9. The scheduled `bookings:expire-unpaid-front --hours=3 --force` command marks abandoned pending frontend bookings expired. It does not delete them.

## Timing rules

- The short checkout hold starts before customer details are persisted. Its duration comes from the site setting and is normally 15 minutes.
- The three-hour provisional payment window starts from the latest PayFast submit log, or from booking creation if PayFast was never opened.
- These timers serve different stages and must not be combined: the short hold protects Checkout capacity; the longer window protects an existing provisional booking while PayFast is in progress.
- A paid booking always consumes capacity, including one paid after its provisional window expired.
- A late successful payment is never discarded. It creates a staff alert requiring capacity review because the expired space may have been sold again.

## Availability and operational visibility

- Active pending bookings and paid bookings consume capacity.
- Expired unpaid provisional bookings do not consume capacity.
- `Booking::operational()` means `payment_window_expired_at IS NULL OR status_payment_id = paid`.
- Normal admin booking lists and operational reports use that grouped rule, so expired unpaid provisional records are hidden while paid-late records remain visible.
- Expired provisional records remain stored for audit, reconciliation, and support. Do not soft-delete them merely to hide them.

## Money and extras

- Adult and child values are people counts and must be non-negative integers; regular and gift checkout each require at least one guest in total, which may be an adult or a child.
- Money is recalculated server-side and rounded to two decimals.
- Public checkout total equals the activity total after any valid public promo plus selected extras.
- Public extras are selected from the current season. Per-booking quantity is one; per-guest quantity is adults plus children.
- Selected extras are snapshotted to `booking_extras` with `meta.source = public_booking_v2` and remain itemized on invoices.
- Staff/admin extras use existing additional-payment semantics and must not be automatically allocated by the V2 PayFast completion helper.
- The invoice total, booking grand total, PayFast amount, and payment total must agree.

## Terms acceptance

- Checkout sends the displayed terms hash, but the server recreates the current wording and rejects a stale hash.
- Acceptance stores the exact wording snapshot, SHA-256 hash, server timestamp, IP address, user agent, booking, and team in `booking_terms_acceptances`.
- A matching audit-log event is also stored. The checkbox alone is not the legal record.

## Idempotency and failures

- Confirm & pay is session-blocked and recoverable by hold UUID/linked booking. Retrying the same confirmation must return the existing booking rather than create a duplicate.
- Any validation or persistence failure rolls back customer, booking, invoice, extras, and acceptance records together.
- A cancelled or incomplete PayFast attempt leaves the provisional booking pending until payment succeeds or the three-hour expiry command marks it expired.
- Duplicate PayFast notifications must not create duplicate payment, extra-allocation, audit, or late-payment alert records.
- Booking email/PDF fulfilment runs only after payment commits. Disabled email settings send nothing; delivery failures leave payment intact and remain retryable through the queue or a repeated PayFast callback.

## Legacy boundary

- `/front/legacy/welcome`, `ActivityController::store`, `/booking/store`, the session cart, and the intermediate `PaymentController::booking` screen are legacy fallback flows.
- They are still referenced by the legacy public UI and signed staff payment links. Mark them as legacy; do not remove them until route, UI, and rollback references are deliberately migrated and tested.
- V2 regular checkout must not append to the legacy session cart.
- Gift checkout intentionally continues through `front.booking.gift-store` and the existing voucher PayFast flow.

## Troubleshooting order

1. Record team, service, activity date, slot ID, pax, hold UUID, booking ID/reference, and PayFast `m_payment_id`.
2. Check `public_booking_holds` for ownership, expiry, cancellation, completion, and linked `booking_id`.
3. Check `bookings`, `booking_items`, `booking_inventories`, `booking_fees`, `booking_extras`, `invoices`, and `booking_terms_acceptances` for transaction consistency.
4. Check `gateway_logs` for submit/notify/return/cancel events and validation messages.
5. Check `booking_payments`, `booking_extra_payment_allocations`, `audit_logs`, and late-payment alerts.
6. Reproduce with the focused V2 checkout, expiry, payment, report-preview, and frontend-state tests before changing rules.

