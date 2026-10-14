import assert from 'node:assert/strict';
import test from 'node:test';
import {
    availabilityState,
    calculateBookingTotal,
    findFirstOpenDate,
    findSelectedSlot,
    formatHoldCountdown,
    hasSelectedGuests,
    remainingHoldSeconds,
} from '../../resources/js/Pages/PublicBookingV2/bookingState.js';

const slots = [{ slot_id: 31, adult_price: 895, child_price: 795 }];
const days = [
    { date: '2026-10-08', is_open: false },
    { date: '2026-10-09', is_open: true },
];

test('resolves a valid slot and calculates its current pax total', () => {
    const slot = findSelectedSlot(slots, 31);

    assert.equal(slot?.slot_id, 31);
    assert.equal(calculateBookingTotal(slot, 2, 1), 2585);
});

test('rejects a slot id that is no longer in the available slot list', () => {
    const slot = findSelectedSlot(slots, 99);

    assert.equal(slot, null);
    assert.equal(calculateBookingTotal(slot, 2, 0), 0);
});

test('requires at least one adult or child before continuing', () => {
    assert.equal(hasSelectedGuests(1, 0), true);
    assert.equal(hasSelectedGuests(0, 1), true);
    assert.equal(hasSelectedGuests(0, 0), false);
});

test('reports empty availability and does not invent a selectable date', () => {
    const unavailableDays = days.map((day) => ({ ...day, is_open: false }));

    assert.equal(availabilityState(unavailableDays, '2026-10-01', '2026-10-31', false, ''), 'empty');
    assert.equal(findFirstOpenDate(unavailableDays, '2026-10-01', '2026-10-31'), null);
});

test('reports request failure separately from loading and empty states', () => {
    assert.equal(availabilityState([], '2026-10-01', '2026-10-31', false, 'Request failed'), 'error');
    assert.equal(availabilityState([], '2026-10-01', '2026-10-31', true, ''), 'loading');
});

test('chooses only an open date inside the requested calendar month', () => {
    assert.equal(findFirstOpenDate(days, '2026-10-01', '2026-10-31'), '2026-10-09');
    assert.equal(findFirstOpenDate(days, '2026-11-01', '2026-11-30'), null);
});

test('counts a server expiry down without returning negative seconds', () => {
    const now = Date.parse('2026-10-10T10:00:00Z');

    assert.equal(remainingHoldSeconds('2026-10-10T10:15:00Z', now), 900);
    assert.equal(remainingHoldSeconds('2026-10-10T09:59:59Z', now), 0);
    assert.equal(remainingHoldSeconds(null, now), null);
});

test('formats the checkout hold as minutes and seconds', () => {
    assert.equal(formatHoldCountdown(900), '15:00');
    assert.equal(formatHoldCountdown(61), '1:01');
    assert.equal(formatHoldCountdown(0), '0:00');
});
