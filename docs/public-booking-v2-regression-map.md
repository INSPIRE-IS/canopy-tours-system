# Public Booking V2 regression map

Use this note when a complaint appears after Public Booking V2 deployment. Diagnose the affected surface before reverting anything: most V2 files are isolated, while a small number of changes touch shared or legacy code.

## Current uncommitted Part 3A checkout handoff

The current working tree adds the first Checkout checkpoint but deliberately does not create a booking, reserve capacity, apply promos, or initiate payment yet.

- `app/Http/Controllers/Front/PublicBookingV2Controller.php`: validates team/service ownership, current/future date, at least one adult, total pax against service capacity, and current slot availability before returning a server-owned rack quote. Regression symptom: valid Continue actions return validation errors, stale slots reach Checkout, or totals differ from the selected season rates.
- `routes/web.php`: adds only the V2 Checkout GET route. Regression symptom: Continue returns 404 or resolves to the wrong controller.
- `resources/js/Pages/PublicBookingV2/Book.jsx` and `resources/js/Pages/PublicBookingV2/BookingComponents.jsx`: pass the selected date, slot, and pax to Checkout. Regression symptom: Continue remains disabled after a valid selection or sends stale selection data.
- `resources/js/Pages/PublicBookingV2/Checkout.jsx` and `resources/js/Pages/PublicBookingV2/booking-v2.css`: render the approved responsive Checkout shell. Desktop/tablet retain the two-column format; mobile uses booking details, customer details, extras, terms, then promo/total/Confirm & Pay in task order. The extras are explicitly preview-only placeholder data for visual approval and do not affect totals or persistence. Confirm & pay and promo remain intentionally disabled until their server flows are implemented. Regression symptom: V2 styling changes outside Checkout, mobile fields overflow or appear out of order, or summary values differ from controller props.
- `tests/Feature/PublicBookingV2AvailabilityContractTest.php`: covers valid handoff, stale slot, wrong-team service, past date, zero adults, and excessive pax. Test-only risk is low.

Do not treat Part 3A as a completed customer journey. A rollback can remove the Checkout route/page and Continue handler without touching the Part 2 availability contract.

## Known-good checkpoint

- Branch: `feature/public-booking-v2-karkloof`
- Main baseline before this work: `982baca`
- Current tested checkpoint: `1a9968a`
- Public V1 welcome/activity flow was browser-checked after the V2 changes.
- Admin Create calendar helper tests passed, and Admin Create/Edit were manually confirmed unchanged.

## Commit and risk map

### `9255eac` - restore public gift voucher entry

- Runtime file: `app/Http/Controllers/Front/ActivityController.php`
- Surface: legacy Public V1 service/gift-voucher entry.
- Investigate first if gift eligibility, the gift button, or legacy activity loading changes unexpectedly.
- This is the only commit in the V2 branch that modifies the legacy `ActivityController`.

### `c6de462` - add Public Booking V2 availability foundation

- Runtime files:
  - `app/Http/Controllers/Front/PublicBookingV2Controller.php`
  - `app/Services/Calendar/PublicSeasonRangeResolver.php`
  - `app/Services/Calendar/PublicServiceAvailability.php`
  - `routes/web.php`
- Surface: V2 routes and V2 availability only.
- Likely symptoms: V2 route errors, missing months, incorrect season transitions, invented gap dates, or wrong slot ownership.
- Existing Public V1 and Admin booking routes do not call these V2 controller/service classes.

### `e5f80e2` - match approved Public Booking V2 interface

- Main runtime files:
  - `resources/js/Pages/PublicBookingV2/Book.jsx`
  - `resources/js/Pages/PublicBookingV2/booking-v2.css`
  - `resources/css/fonts.css`
  - V2 image/font assets.
- Surface: V2 presentation. `resources/css/fonts.css` is shared and is the first place to inspect if typography changes outside V2.
- Likely V2 symptoms: layout, responsive behavior, fonts, images, header/footer, or sticky-summary regressions.

### `d297caf` - complete the V2 availability contract and safe test setup

- Runtime files:
  - `app/Services/Calendar/PublicServiceAvailability.php`
  - `resources/js/Pages/PublicBookingV2/Book.jsx`
- Test/config files include `phpunit.xml`, Pest setup, and safe test-database protection.
- Likely symptoms: V2 month collisions, incomplete availability responses, or client/server contract mismatches.
- The PHPUnit/Pest files do not affect production runtime, but they must not be removed casually because they protect the working database during tests.

### `76c50fb` - harden Public Booking V2 interactions

- Runtime files:
  - `resources/js/Pages/PublicBookingV2/Book.jsx`
  - `resources/js/Pages/PublicBookingV2/BookingComponents.jsx`
  - `resources/js/Pages/PublicBookingV2/booking-v2.css`
  - `resources/js/Pages/PublicBookingV2/bookingState.js`
- Surface: V2 desktop/mobile selection state, loading/error states, month caching, pax recalculation, and component rendering.
- Likely symptoms: selection not clearing, stale pax availability, calendar flashing, dates/times remaining disabled, desktop/mobile disagreement, or incorrect displayed totals.
- `package.json` only adds the focused V2 frontend test command.

### `65b4555` - improve dismissible toast feedback

- Shared runtime file: `resources/js/Context/ToastContext.jsx`
- Surface: every application page using the global toast provider.
- Investigate first if unrelated screens report toast positioning, dismissal, timing, stacking, or accessibility regressions.
- The existing `showToast(message, type, duration)` signature and default duration were preserved, so callers did not require migration.
- Prefer repairing this shared component separately instead of reverting unrelated V2 calendar work.

### `1a9968a` - expand availability regression coverage

- Test-only file: `tests/Feature/PublicBookingV2AvailabilityContractTest.php`
- No production runtime risk.

## Fast symptom triage

| Complaint | Inspect first |
| --- | --- |
| Admin Create/Edit calendar stopped working | Confirm no post-checkpoint Admin files changed; compare against `1a9968a`, then inspect the shared calendar builder and the later commit, not the isolated V2 page |
| Legacy Public V1 activity or gift flow changed | `9255eac`, then `ActivityController.php` |
| Toasts changed across unrelated admin/public pages | `65b4555` and `ToastContext.jsx` |
| Fonts changed outside V2 | `e5f80e2` and `resources/css/fonts.css` |
| V2 dates/months/gaps are wrong | `c6de462`, `d297caf`, `PublicSeasonRangeResolver`, and `PublicServiceAvailability` |
| V2 flashes or becomes disabled after pax changes | `76c50fb`, especially the debounced stale-while-refresh flow in `Book.jsx` |
| V2 desktop and mobile disagree | `76c50fb`, `BookingComponents.jsx`, and `bookingState.js` |

## Safe investigation sequence

1. Record the exact URL, service, season, date range, pax, browser, and whether the issue affects V1, V2, Admin, or all surfaces.
2. Check browser console and the failing network response before changing code.
3. Compare the affected file with the tested checkpoint: `git diff 1a9968a..HEAD -- path/to/file`.
4. Use `git show <commit> -- path/to/file` to review the isolated change.
5. Reproduce locally and add a focused regression test before repairing it.
6. Rework the smallest affected component first. Do not revert all V2 commits for a shared-toast or legacy gift-flow issue.
7. Run the focused PHP tests, Public V2 frontend tests, Admin calendar helper tests, production build, and the relevant rendered browser flow.

## Revert cautions

- Do not blindly revert `c6de462`, `d297caf`, or `76c50fb` individually: their V2 backend contract and frontend consumer build on one another.
- `65b4555` is deliberately isolated and is the narrowest rollback/rework point for global toast complaints.
- `1a9968a` is test-only and cannot explain a production failure.
- If a later commit changes Admin Create/Edit, compare that later commit first because the tested V2 checkpoint does not modify Admin booking controllers or pages.
