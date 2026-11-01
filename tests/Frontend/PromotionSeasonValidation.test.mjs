import assert from 'node:assert/strict';
import test from 'node:test';
import {
    normalizePromotionDate,
    validatePromotionSeasonWindow,
} from '../../resources/js/Pages/Admin/Promotions/promotionSeasonValidation.js';

const seasons = [
    { id: 10, start_date: '2030-07-01 00:00:00', end_date: '2030-12-31 00:00:00', internal_only: false },
    { id: 11, start_date: '2031-01-02 00:00:00', end_date: '2032-12-31 00:00:00', internal_only: false },
    { id: 12, start_date: '2030-01-01 00:00:00', end_date: '2034-12-31 00:00:00', internal_only: true },
];

test('normalizes serialized season dates before boundary comparisons', () => {
    assert.equal(normalizePromotionDate('2031-01-02 00:00:00'), '2031-01-02');

    const result = validatePromotionSeasonWindow({
        type: 'percent',
        seasons,
        activityStartDate: '2031-01-02',
        activityEndDate: '2032-12-31',
    });

    assert.equal(result.valid, true);
});

test('allows a service-level promotion to start and end in different public seasons', () => {
    const result = validatePromotionSeasonWindow({
        type: 'varied_child',
        seasons,
        activityStartDate: '2030-10-01',
        activityEndDate: '2032-12-31',
    });

    assert.equal(result.valid, true);
});

test('does not use an internal-only season to validate a public promotion', () => {
    const result = validatePromotionSeasonWindow({
        type: 'percent',
        seasons,
        activityStartDate: '2030-02-01',
        activityEndDate: '2032-12-31',
    });

    assert.equal(result.valid, false);
});

test('keeps time-slot promotions inside their selected season', () => {
    const result = validatePromotionSeasonWindow({
        type: 'time_slot',
        seasonId: 10,
        seasons,
        activityStartDate: '2030-10-01',
        activityEndDate: '2032-12-31',
    });

    assert.equal(result.valid, false);
});
