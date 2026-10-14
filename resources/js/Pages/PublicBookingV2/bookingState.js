export function findSelectedSlot(slots, selectedSlotId) {
    if (selectedSlotId === null || selectedSlotId === undefined) return null;

    return slots.find((slot) => Number(slot.slot_id) === Number(selectedSlotId)) || null;
}

export function calculateBookingTotal(slot, adults, children) {
    if (!slot) return 0;

    return (adults * Number(slot.adult_price)) + (children * Number(slot.child_price));
}

export function hasSelectedGuests(adults, children) {
    return (Number(adults) + Number(children)) > 0;
}

export function findFirstOpenDate(days, startDate, endDate) {
    return [...days]
        .sort((left, right) => left.date.localeCompare(right.date))
        .find((day) => day.date >= startDate && day.date <= endDate && day.is_open)
        ?.date || null;
}

export function availabilityState(days, startDate, endDate, loading, error) {
    if (loading) return 'loading';
    if (error) return 'error';

    const visibleDays = days.filter((day) => day.date >= startDate && day.date <= endDate);
    if (visibleDays.length === 0) return 'idle';

    return visibleDays.some((day) => day.is_open) ? 'available' : 'empty';
}

export function remainingHoldSeconds(expiresAt, nowMilliseconds = Date.now()) {
    if (!expiresAt) return null;

    const expiryMilliseconds = new Date(expiresAt).getTime();
    if (!Number.isFinite(expiryMilliseconds)) return 0;

    return Math.max(0, Math.ceil((expiryMilliseconds - nowMilliseconds) / 1000));
}

export function formatHoldCountdown(seconds) {
    const safeSeconds = Math.max(0, Number(seconds) || 0);

    return `${Math.floor(safeSeconds / 60)}:${String(safeSeconds % 60).padStart(2, '0')}`;
}
