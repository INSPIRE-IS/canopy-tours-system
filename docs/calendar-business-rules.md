# Calendar and availability business rules

Read this document before changing seasons, inventories, availability previews, booking calendars, closeouts, date overrides, capacity, or frontend activity availability.

This is the canonical starting point for calendar work. It deliberately separates agreed business rules from current implementation and known gaps. Current code or existing data must not be treated as a business rule merely because it exists.

## Core domain model

- A **Service** is the activity being sold, for example Canopy Tour.
- A **Season** belongs to one service and defines an inclusive `start_date` and `end_date`, rates, visibility, and related configuration.
- An **Inventory** belongs to one season and supplies allowed weekdays, time slots, cutoff settings, capacity-related configuration, and date overrides.
- An **Inventory Time Slot** belongs to one inventory. Slot IDs are inventory-specific even when two seasons both have a `09:00` slot.
- A **Booking Item** must retain the season and inventory that priced and supplied its booked slot.
- A **Closeout** closes configured dates or slots for a specific service and season.
- An **Inventory Slot Range** is a date/slot override for one inventory. It may be internal-only and may override capacity.

Primary models:

- `app/Models/Service.php`
- `app/Models/Season.php`
- `app/Models/Inventory.php`
- `app/Models/InventoryTimeSlot.php`
- `app/Models/InventorySlotRange.php`
- `app/Models/Closeout.php`
- `app/Models/BookingItem.php`
- `app/Models/BookingInventory.php`

## Confirmed business rules

### Dates and seasons

1. Season date bounds are inclusive.
2. Season **names are labels only**. Never infer availability, ordering, or a year from a name such as `2027`.
3. A date is not available merely because it appears between two seasons in a week or month display. If no intended season covers the date, it remains unavailable.
4. A week or month view may span more than one season for the same service. Valid days should continue seamlessly into the next intended season rather than blanking the whole view.
5. Every selectable calendar cell must retain the owning `season_id`, inventory, and inventory-specific slot ID. Displaying a common time such as `09:00` is not enough to identify the slot.
6. Selecting a cell from the next season must switch pricing, rates, inventory rules, wholesaler rates, and persisted booking references to that cell's season.
7. A gap between seasons must remain visibly unavailable. Do not invent availability by extending the previous season, starting the next season early, or borrowing an unrelated season.
8. Do not automatically treat an overlapping staff-only season as the continuation of a normal online season. Overlap is a data/configuration condition requiring an explicit deterministic rule or human correction.

Example: if a normal season ends on 31 December and the next normal season starts on 2 January, then 1 January is unavailable unless a deliberately applicable season or override has been confirmed. A broad, overlapping staff-only season is not automatically proof that 1 January should open.

### Visibility

- `services.is_online_bookable` controls whether the service is offered publicly.
- `seasons.internal_only` controls whether a season is staff-only.
- Staff-only availability may be visible to authorised staff but must not leak into the public booking flow.
- Visibility is not the same as date validity. A visible season still applies only within its configured dates.
- `seasons.status` exists, but its exact operational meaning must be traced at the relevant call site before relying on it as an availability rule.

### Availability and capacity

- Availability is derived from the selected season's inventory; it is not stored as one global service calendar.
- Allowed weekdays and inventory time slots establish base availability.
- Past dates are unavailable.
- Slot cutoff rules may make otherwise valid slots unavailable.
- Existing non-cancelled bookings reduce remaining capacity.
- A slot is full when effective remaining capacity cannot accommodate the requested pax.
- On Edit Booking, the booking's current pax may be added back when checking its existing date/time so the booking does not consume capacity twice.
- Date overrides can deliberately open or alter a slot. Internal-only overrides are visible to staff but not the public.
- Closeouts close matching dates/slots. A deliberate date override currently wins over a matching closeout, while the suppressed closeout is retained in response metadata for explanation.
- Calendar status values currently include `available`, `full`, `unavailable`, `blocked_day`, and `blocked_slot`. UI labels may differ by screen.

### Booking integrity

- The selected booking date, season, inventory, and slot must belong together.
- Store/create must resolve the weekday and time slot against the selected season's inventory, not another season with the same displayed time.
- Pricing must use the selected date's season and rates.
- Wholesaler rates, promotions, overrides, and closeouts may be season-specific and must be re-evaluated after crossing a season boundary.
- Historic bookings must continue to use their saved season/rate references unless an authorised edit deliberately changes them.

## Shared availability engine

The main availability engine is:

- `app/Services/Calendar/ServiceCalendarBuilder.php`

Its high-level order is:

1. Load allowed weekdays, applicable inventory time slots, and date overrides.
2. Build base cells for the requested date range.
3. Reject dates outside the supplied season.
4. Apply override-only slot and allowed-weekday rules.
5. Apply past-date and cutoff rules.
6. Apply booking capacity.
7. Apply closeouts through `app/Services/Calendar/ServiceCloseoutCalendarBuilder.php`.
8. Apply time-slot promotions through `app/Services/Calendar/ServicePromoCalendarBuilder.php`.
9. Apply slot funneling when configured.

The builder operates on **one supplied inventory and season**. It does not discover adjacent seasons itself. Any cross-season view must resolve the relevant seasons safely and then compose their independent results without losing ownership metadata.

## Calendar surfaces and differences

### Inventory availability preview

Files:

- `app/Http/Controllers/Admin/InventoryController.php` (`availabilityPreview`)
- `resources/js/Pages/Admin/Inventories/AvailabilityPreviewModal.jsx`
- Route: `admin.inventories.availability-preview`

Behaviour:

- The user explicitly previews one inventory/season.
- The requested range is clamped to that season's bounds.
- It calls the shared `ServiceCalendarBuilder` with internal overrides enabled.
- It shows effective base inventory, overrides, closeouts, capacity, bookings, and rule metadata.
- It is diagnostic and read-only.
- It is not expected to transition automatically into another season. The user selects another inventory to inspect it.

Use this preview as the first comparison point when Create/Edit/Public availability looks wrong. If preview is wrong, investigate inventory data or the shared builder. If preview is right but another surface is wrong, investigate that surface's range, season selection, filtering, or rendering.

### Admin Create Booking

Files:

- `app/Http/Controllers/Admin/BookingsController.php` (`create`, `calendar`, `store`)
- `resources/js/Pages/Admin/Bookings/Create.jsx`
- Route: `admin.booking.activity.calendar`

Existing endpoint behaviour:

- `activity_id` is historically named but is a **season ID** for this endpoint.
- The endpoint loads that season's inventory and calls the shared builder.
- Week/month requests can include dates outside that supplied season; those cells should be unavailable rather than causing the whole response to be empty.
- Store resolves the selected time by `start_time` against the selected season's inventory.

Required cross-season behaviour:

- Day/week/month navigation should continue into the intended adjoining season for the same service.
- Each composed cell must carry the season and inventory-specific slot ownership needed when selected.
- Genuine gaps stay unavailable.
- Overlapping seasons must not be chosen by array/database order or season name.

Current implementation status (September 2026):

- `resources/js/Pages/Admin/Bookings/calendarSeasons.js` composes the Admin Create calendar across the nearest non-overlapping predecessor/successor seasons.
- The corrected tests keep 1 January 2027 unavailable, move into season #13 on 2 January, and do not borrow availability from overlapping staff-only season #16.
- Selecting a gap date retains a nearby season only as the calendar browsing anchor; it clears the booking season, time, and selected slot so the gap cannot be submitted as a booking.
- The focused helper tests and production frontend build pass. Browser interaction across the boundary must still be verified before treating the complete Create flow as release-proven.
- Temporary diagnostics are query-gated with `?calendar_debug=1` and log only calendar dates, season IDs, row counts, and status counts. Remove them after diagnosis unless deliberately retained.
- Store resolves weekdays and time slots against the submitted season inventory, but no explicit season-bound check was found for `booking_date`. Do not assume the UI alone prevents an out-of-season season/date pairing; add and test server validation when the boundary implementation is finalised.

### Admin Edit Booking

Files:

- `app/Http/Controllers/Admin/BookingsController.php` (`edit`, `calendar`, update flow)
- `resources/js/Pages/Admin/Bookings/Edit.jsx`

Behaviour and constraints:

- Edit starts from the booking item's saved season, inventory, date, time, pricing, and wholesaler references.
- Capacity calculation can ignore requested pax and add the current booking's pax back for its existing slot.
- Edit currently requests one season calendar and has separate client-side season selection logic from Create.
- Cross-season improvements to Create do not automatically fix Edit. Both flows require explicit testing.
- Historic, cancelled, deleted-related, and rate-mismatch records are higher-risk and must retain their saved references for review.

### Public activity calendar

Files:

- `app/Http/Controllers/Front/ActivityController.php` (`activity`, `calendar`)
- `resources/js/Pages/Activities.jsx`
- Routes: `front.activity`, `front.activity.calendar`

Behaviour:

- The public flow chooses a season before opening its activity page.
- Public listing filters staff-only seasons for unauthenticated guests and filters services that are not online-bookable.
- The calendar endpoint uses one selected season and excludes internal overrides.
- Closed, blocked, full, and unavailable cells are not selectable; the public UI generally simplifies closed states to `Not Available`.
- The endpoint currently returns an empty schedule when any requested multi-day range crosses the selected season boundary. This is a known boundary gap and is separate from the Admin Create change.
- The public activity response and calendar endpoint currently disagree on the number of days represented by `week` (five in `activity`, three in `calendar`). Treat this as a known implementation inconsistency, not a business rule.
- Any public cross-season solution must also update the selected season used for rates, promotions, payment, and booking submission. Merging visual cells alone is unsafe.

## Known risks and data conditions

### Overlapping seasons

Overlapping date ranges for the same service make automatic selection ambiguous. The current Canopy Tour data includes a staff-only season whose dates overlap normal seasons. This record was involved in earlier wholesaler-rate/data issues and must not silently become the fallback calendar.

Before implementing automatic overlap resolution, establish the intended priority. A likely default is public/bookable season before staff-only season, but that is not sufficient when a date is covered only by an anomalous broad staff-only season. Do not infer intent from `internal_only`, ID order, creation date, shortest duration, or name alone.

### Gaps

A date not covered by an intended season is a real gap. It should render unavailable. Calendar labels may still include the date because week/month headers describe a continuous date range; the presence of a header is not availability.

### Different time slots by season

Rows may share a displayed start time while their slot IDs differ. Adjacent seasons may also contain different sets of times. A composed calendar must merge by display time for presentation while preserving per-cell slot IDs for selection and persistence.

### Different rules by season

Adjacent seasons may have different:

- allowed weekdays;
- time slots and override-only slots;
- closeouts and date overrides;
- capacity settings;
- cutoff and funneling settings;
- rack and wholesaler rates;
- promotions;
- internal visibility.

Never copy availability from one season into another to make a view look continuous.

## Investigation checklist

1. Identify the surface: inventory preview, Admin Create, Admin Edit, or public activity calendar.
2. Record service ID, season ID, inventory ID, date range, view mode, requested pax, and optional slot filter.
3. Query all non-deleted seasons for that service with dates, `internal_only`, status, and inventory IDs.
4. Check for date gaps and overlaps before changing code.
5. Compare the inventory preview for each relevant season.
6. Call the relevant calendar endpoint for each season independently and compare `days`, row times, slot IDs, statuses, notes, and rule metadata.
7. Confirm the UI requested all intended seasons and did not select one by array order.
8. Confirm a clicked cell submits the owning season and slot, not merely the row's first/default slot.
9. Confirm store/update resolves that slot against the same inventory and uses the same season for pricing.
10. Check public filtering separately; authenticated admin behaviour is not evidence of public behaviour.
11. Remove temporary diagnostics after confirmation.

Useful local read-only checks can be run through Herd PHP and Tinker. Do not mutate live season or inventory data merely to make a test pass.

## Minimum regression matrix

Test at least:

1. Ordinary date well inside one season.
2. Last valid day of a season.
3. First valid day of the next season.
4. Week spanning the boundary.
5. Month spanning the boundary.
6. A genuine one-day or multi-day gap.
7. Overlapping normal and staff-only seasons.
8. Adjacent seasons with the same display times but different slot IDs.
9. Adjacent seasons with different time sets.
10. Closeout on the old season and availability on the new season.
11. Date override winning over a closeout.
12. Full capacity and requested pax greater than remaining capacity.
13. Admin-only override hidden from an unauthenticated frontend guest.
14. Create selection persists the correct season/inventory/slot and rate.
15. Edit retains the existing booking slot without double-counting pax, then safely changes to a new season if deliberately selected.

Tests must use realistic endpoint responses or database-backed fixtures. A pure merge-helper test is useful but insufficient: it can pass while the page requests the wrong seasons or while fixtures encode an incorrect business assumption.

## Change discipline

- Read `docs/change-review.md` before changing calendar business behaviour.
- Do not solve a calendar issue only in rendering if it affects persisted season, inventory, slot, rates, payments, or reports.
- Keep diagnostics free of customer data.
- Do not alter overlapping or historic season records without an explicit data-cleanup decision and impact review.
- Do not claim a calendar fix safe based only on PHP lint, a frontend build, or helper tests. Verify endpoint responses and the rendered click-to-save flow.

## Relevant history

- Commit `105051c` (`fix calendar near season end show last week month correctly`) removed the Admin Create endpoint's whole-range rejection and made the shared builder mark only spillover cells as outside the supplied season.
- That fix made the last days of a season visible but did not implement transition into an adjoining season.
- The September 2026 uncommitted composition experiment attempted that transition and exposed the need for an explicit overlap rule and end-to-end cell ownership testing.
