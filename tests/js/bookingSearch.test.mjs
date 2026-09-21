import assert from 'node:assert/strict';
import test from 'node:test';
import { phoneDigitsForSearch } from '../../resources/js/Pages/Admin/Bookings/bookingSearch.js';

test('booking references containing digits do not become phone searches', () => {
    assert.equal(phoneDigitsForSearch('REF-T9COQV'), '');
    assert.equal(phoneDigitsForSearch('guest9@example.com'), '');
});

test('phone-like input is normalized for phone matching', () => {
    assert.equal(phoneDigitsForSearch('+27 83 582 970'), '2783582970');
    assert.equal(phoneDigitsForSearch('083-582-970'), '083582970');
});
