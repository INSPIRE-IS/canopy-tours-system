# Public Booking V2 data contract

This document defines the server response consumed by the Public Booking V2 booking page. It is a presentation contract only; booking creation must still validate availability, ownership, capacity, and pricing again on the server.

## Initial page props

`PublicBookingV2Controller@index` returns:

- `team`: site identity and public logo/image paths.
- `services`: publicly bookable services with at least one public season and inventory.
- `selectedServiceId`: requested public service when valid, otherwise the first available service.

Each service includes:

- Identity: `id`, `name`, `tag`.
- Display: `image`, `hero_summary`, `age_label`, `arrival_label`.
- Booking context: `capacity`, `initial_date`, `from_price`, `gift_activity_id`.
- Information content: `arrangements`, `requirements`, `inclusions`, `exclusions`, `condition_text`, `notes`.

`from_price` is a display value across the service's eligible public seasons. It is not authoritative booking pricing.

## Availability request

`GET /front/v2/services/{service}/availability`

Query parameters:

- `start_date`: required `Y-m-d` date.
- `end_date`: required `Y-m-d` date on or after `start_date`.
- `adults`: optional non-negative integer.
- `children`: optional non-negative integer.

The inclusive request range is limited to 63 days. Public requests exclude staff-only seasons and internal-only date overrides.

## Availability response

Top-level fields:

- `service_id`: the requested service.
- `range.from`, `range.to`: inclusive response range.
- `requested_pax.adults`, `requested_pax.children`: pax used for capacity checks.
- `days`: one record for every date in the requested range.
- `slots_by_date`: selectable slots keyed by `Y-m-d` date.

Each day includes:

- `date`: `Y-m-d`.
- `is_open`: true when at least one slot can accept the requested pax.
- `status`: `available`, `full`, or `unavailable`.
- `from_price`: lowest available adult rate for that date, otherwise null.
- `slot_count`: number of selectable slots for that date.

Each selectable slot includes:

- Ownership: `season_id`, `inventory_id`, `slot_id`.
- Timing: `time` (`H:i:s`) and nullable `arrival_by` (`H:i`).
- Capacity: `spaces_remaining` after existing non-cancelled bookings.
- Pricing: `adult_price`, `child_price`, `guide_price` for the owning season.

The ownership fields are mandatory even when adjacent seasons display the same time. The client must retain them with the selection and must never infer ownership from date or display time.

## Boundary rules

- Season dates are inclusive.
- Adjacent public seasons are composed independently.
- A genuine gap remains `unavailable` and has no selectable slots.
- Staff-only seasons are never borrowed to fill a public gap.
- Overlapping public seasons are treated as a configuration error rather than selected by name, ID, or query order.
- Pricing and slot identity come from the season that owns the selected date.

See `docs/calendar-business-rules.md` for the canonical availability rules and regression matrix.
