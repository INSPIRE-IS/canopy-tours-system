import assert from 'node:assert/strict';
import test from 'node:test';
import { calendarAnchorSeasonForDate, calendarSeasonForDate, calendarSeasonsForRange, mergeSeasonCalendars } from '../../resources/js/Pages/Admin/Bookings/calendarSeasons.js';

const seasons = [
    { id: 8, start_date: '2026-07-01', end_date: '2026-12-31', internal_only: false, inventory: {} },
    { id: 13, start_date: '2027-01-02', end_date: '2027-12-31', internal_only: false, inventory: {} },
    { id: 14, start_date: '2028-01-01', end_date: '2028-12-31', internal_only: true, inventory: {} },
    { id: 16, start_date: '2026-08-12', end_date: '2028-08-12', internal_only: true, inventory: {} },
];

test('calendar follows adjoining seasons without borrowing an overlapping season', () => {
    assert.deepEqual(calendarSeasonsForRange(seasons, seasons[0], '2026-12-26', '2027-01-01').map(s => s.id), [8]);
    assert.deepEqual(calendarSeasonsForRange(seasons, seasons[0], '2026-12-29', '2027-01-04').map(s => s.id), [8, 13]);
    assert.deepEqual(calendarSeasonsForRange(seasons, seasons[1], '2027-12-29', '2028-01-03').map(s => s.id), [13, 14]);
    assert.deepEqual(calendarSeasonsForRange(seasons, seasons[1], '2027-02-01', '2027-02-01').map(s => s.id), [13]);
    const januaryFirst = calendarSeasonsForRange(seasons, seasons[0], '2027-01-01', '2027-01-01');
    const januarySecond = calendarSeasonsForRange(seasons, seasons[0], '2027-01-02', '2027-01-02');
    assert.equal(calendarSeasonForDate(januaryFirst, seasons[0], '2027-01-01'), null);
    assert.equal(calendarSeasonForDate(januarySecond, seasons[0], '2027-01-02')?.id, 13);
    assert.equal(calendarSeasonForDate(seasons, null, '2027-01-01'), null);
    assert.equal(calendarSeasonForDate([seasons[3]], null, '2027-01-01')?.id, 16);
});

test('a gap anchors to the normal season timeline without becoming bookable', () => {
    const anchor = calendarAnchorSeasonForDate(seasons, '2027-01-01');
    assert.equal(anchor?.id, 8);
    assert.deepEqual(calendarSeasonsForRange(seasons, anchor, '2027-01-01', '2027-01-01'), []);

    const staffOnlyDateAnchor = calendarAnchorSeasonForDate(seasons, '2028-02-01');
    const candidates = calendarSeasonsForRange(seasons, staffOnlyDateAnchor, '2028-02-01', '2028-02-01');
    assert.equal(calendarSeasonForDate(candidates, null, '2028-02-01')?.id, 14);
});

test('merged cells retain the owning season and time-slot ids', () => {
    const days = ['Thu 31', 'Fri 01', 'Sat 02'];
    const cell = (date, status) => ({ date, status, slots: status === 'available' ? 8 : 0 });
    const result = mergeSeasonCalendars([
        { season: seasons[0], calendar: { schedule: [{
            time: '09:00:00', slot_id: 30, days: {
                'Thu 31': cell('2026-12-31', 'available'),
                'Fri 01': cell('2027-01-01', 'unavailable'),
                'Sat 02': cell('2027-01-02', 'unavailable'),
            },
        }] } },
        { season: seasons[1], calendar: { schedule: [{
            time: '09:00:00', slot_id: 41, days: {
                'Thu 31': cell('2026-12-31', 'unavailable'),
                'Fri 01': cell('2027-01-01', 'unavailable'),
                'Sat 02': cell('2027-01-02', 'available'),
            },
        }] } },
    ], days, seasons[0]);

    const row = result.schedule[0];
    assert.equal(row.days['Thu 31'].season_id, 8);
    assert.equal(row.days['Thu 31'].slot_id, 30);
    assert.equal(row.days['Fri 01'].status, 'unavailable');
    assert.equal(row.days['Fri 01'].season_id, undefined);
    assert.equal(row.days['Sat 02'].season_id, 13);
    assert.equal(row.days['Sat 02'].slot_id, 41);
});

test('one-season calendar keeps its existing status while annotating slot ownership', () => {
    const result = mergeSeasonCalendars([{ season: seasons[1], calendar: { schedule: [{
        time: '10:00:00', slot_id: 42, days: { 'Sat 02': {
            date: '2027-01-02', status: 'blocked_slot', note: 'Closed', slots: 0,
        } },
    }] } }], ['Sat 02'], seasons[1]);
    assert.equal(result.schedule[0].days['Sat 02'].status, 'blocked_slot');
    assert.equal(result.schedule[0].days['Sat 02'].slot_id, 42);
});

test('a date with no configured inventory stays unavailable instead of borrowing adjacent availability', () => {
    const days = ['Fri 01', 'Sat 02'];
    const result = mergeSeasonCalendars([
        { season: seasons[0], calendar: { schedule: [{
            time: '09:00:00', slot_id: 30, days: {
                'Fri 01': { date: '2027-01-01', status: 'unavailable', note: 'Outside season' },
                'Sat 02': { date: '2027-01-02', status: 'unavailable', note: 'Outside season' },
            },
        }] } },
        { season: seasons[1], calendar: { schedule: [{
            time: '10:00:00', slot_id: 41, days: {
                'Fri 01': { date: '2027-01-01', status: 'unavailable', note: 'Outside season' },
                'Sat 02': { date: '2027-01-02', status: 'available', slots: 8 },
            },
        }] } },
    ], days, seasons[0]);

    assert.equal(result.schedule.length, 2);
    assert.equal(result.schedule[0].days['Fri 01'].status, 'unavailable');
    assert.equal(result.schedule[0].days['Fri 01'].season_id, undefined);
    assert.equal(result.schedule[1].days['Sat 02'].season_id, 13);
    assert.equal(result.schedule[1].days['Sat 02'].slot_id, 41);
});
