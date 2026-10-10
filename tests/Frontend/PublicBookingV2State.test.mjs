import assert from 'node:assert/strict';
import test from 'node:test';
import {
    availabilityState,
    calculateBookingTotal,
    findFirstOpenDate,
    findSelectedSlot,
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
