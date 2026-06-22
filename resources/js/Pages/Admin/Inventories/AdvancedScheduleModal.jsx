import React, { useEffect, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { addDays, format, isAfter, isBefore, isValid, parseISO, startOfDay } from 'date-fns';
import BlueButton from '@/Components/BlueButton';
import OutlineButton from '@/Components/OutlineButton';

const VIEW_DAYS = {
    week: 7,
    month: 30,
};

const WEEKDAYS = [
    { id: 1, short: 'Mon' },
    { id: 2, short: 'Tue' },
    { id: 3, short: 'Wed' },
    { id: 4, short: 'Thu' },
    { id: 5, short: 'Fri' },
    { id: 6, short: 'Sat' },
    { id: 7, short: 'Sun' },
];

const ADJUSTED_STATUSES = ['override', 'admin_only', 'capacity', 'closeout'];

const parseDate = (value) => {
    const parsed = parseISO(value || '');
    return isValid(parsed) ? parsed : null;
};

const clampDate = (date, min, max) => {
    if (min && isBefore(date, min)) return min;
    if (max && isAfter(date, max)) return max;
    return date;
};

const toDateInput = (date) => format(date, 'yyyy-MM-dd');

const normalizeTime = (time) => (time || '').slice(0, 5);

const isoDay = (date) => Number(format(date, 'i'));

const statusStyles = {
    open: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    closed: 'border-slate-200 bg-slate-50 text-slate-400',
    closeout: 'border-rose-200 bg-rose-50 text-rose-800',
    override: 'border-sky-200 bg-sky-50 text-sky-800',
    admin_only: 'border-amber-200 bg-amber-50 text-amber-800',
    capacity: 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-800',
};

export default function AdvancedScheduleModal({ isOpen, onClose, inventory, closeouts = [], bookedSlots = [] }) {
    const seasonStart = parseDate(inventory?.season?.start_date);
    const seasonEnd = parseDate(inventory?.season?.end_date);
    const initialStart = seasonStart && seasonEnd
        ? toDateInput(clampDate(startOfDay(new Date()), seasonStart, seasonEnd))
        : toDateInput(startOfDay(new Date()));

    const [viewMode, setViewMode] = useState('week');
    const [startDate, setStartDate] = useState(initialStart);
    const [selectedCells, setSelectedCells] = useState([]);
    const [bulkAction, setBulkAction] = useState('close');
    const [capacityOverride, setCapacityOverride] = useState('');
    const [internalOnly, setInternalOnly] = useState(false);
    const [confirmBooked, setConfirmBooked] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [selectedWeekdays, setSelectedWeekdays] = useState(WEEKDAYS.map((day) => day.id));
    const [selectedSlotIds, setSelectedSlotIds] = useState([]);
    const [showAdjustedOnly, setShowAdjustedOnly] = useState(false);
    const [showBookedOnly, setShowBookedOnly] = useState(false);

    useEffect(() => {
        if (isOpen) {
            setViewMode('week');
            setStartDate(initialStart);
            setSelectedCells([]);
            setBulkAction('close');
            setCapacityOverride('');
            setInternalOnly(false);
            setConfirmBooked(false);
            setIsSaving(false);
            setSelectedWeekdays(WEEKDAYS.map((day) => day.id));
            setSelectedSlotIds([]);
            setShowAdjustedOnly(false);
            setShowBookedOnly(false);
        }
    }, [isOpen, initialStart]);

    useEffect(() => {
        if (!['open', 'capacity'].includes(bulkAction)) {
            setInternalOnly(false);
            return;
        }

        setInternalOnly(selectedCells.some((cell) => cell.status === 'admin_only'));
    }, [bulkAction, selectedCells]);

    const baseSlots = useMemo(() => {
        return (inventory?.inventory_time_slots || [])
            .filter((slot) => !slot.is_override_only)
            .map((slot) => ({
                ...slot,
                label: normalizeTime(slot.start_time),
            }))
            .sort((a, b) => a.label.localeCompare(b.label));
    }, [inventory]);

    const overrideSlots = useMemo(() => {
        return (inventory?.inventory_time_slots || [])
            .filter((slot) => slot.is_override_only)
            .map((slot) => ({
                ...slot,
                label: normalizeTime(slot.start_time),
            }))
            .sort((a, b) => a.label.localeCompare(b.label));
    }, [inventory]);

    const allSlots = useMemo(() => {
        const byId = new Map([...baseSlots, ...overrideSlots].map((slot) => [Number(slot.id), slot]));
        return Array.from(byId.values()).sort((a, b) => a.label.localeCompare(b.label));
    }, [baseSlots, overrideSlots]);

    const allowedDays = useMemo(() => {
        return (inventory?.inventory_type_days || []).map((day) => Number(day.type_day_id));
    }, [inventory]);

    const baseCapacity = inventory?.service?.capacity ?? inventory?.capacity ?? null;

    const visibleDays = useMemo(() => {
        const parsedStart = parseDate(startDate) || startOfDay(new Date());
        const days = [];
        for (let i = 0; i < VIEW_DAYS[viewMode]; i += 1) {
            const date = addDays(parsedStart, i);
            if (seasonStart && isBefore(date, seasonStart)) continue;
            if (seasonEnd && isAfter(date, seasonEnd)) continue;
            if (!selectedWeekdays.includes(isoDay(date))) continue;
            days.push(date);
        }
        return days;
    }, [startDate, viewMode, seasonStart, seasonEnd, selectedWeekdays]);

    const filteredSlots = useMemo(() => {
        if (selectedSlotIds.length === 0) {
            return allSlots;
        }

        return allSlots.filter((slot) => selectedSlotIds.includes(Number(slot.id)));
    }, [allSlots, selectedSlotIds]);

    const overrideMap = useMemo(() => {
        const map = new Map();
        (inventory?.inventory_slot_ranges || []).forEach((range) => {
            const rangeStart = parseDate(range.start_date);
            const rangeEnd = parseDate(range.end_date);
            if (!rangeStart || !rangeEnd) return;

            visibleDays.forEach((date) => {
                if (isBefore(date, rangeStart) || isAfter(date, rangeEnd)) return;
                const dateKey = toDateInput(date);
                if (!map.has(dateKey)) {
                    map.set(dateKey, new Map());
                }
                map.get(dateKey).set(Number(range.time_slot_id), range);
            });
        });
        return map;
    }, [inventory, visibleDays]);

    const bookedMap = useMemo(() => {
        const map = new Map();
        (bookedSlots || [])
            .filter((slot) => Number(slot.inventory_id) === Number(inventory?.id))
            .forEach((slot) => {
                const key = `${slot.date}|${slot.inventory_time_slot_id}`;
                map.set(key, {
                    pax: Number(slot.pax || 0),
                    bookingsCount: Number(slot.bookings_count || 0),
                });
            });
        return map;
    }, [bookedSlots, inventory]);

    const isSchedulerRecord = (value) => String(value || '').startsWith('[Scheduler]');

    const recordSource = (record, fallback = 'Manual') => {
        if (!record) return fallback;
        if (isSchedulerRecord(record.note) || isSchedulerRecord(record.name) || isSchedulerRecord(record.reason)) {
            return 'Scheduler';
        }
        return fallback;
    };

    const getCloseoutForSlot = (date, slotId) => {
        const dayId = isoDay(date);

        return closeouts.find((closeout) => {
            if (Number(closeout.service_id) !== Number(inventory?.service_id) || Number(closeout.season_id) !== Number(inventory?.season_id)) {
                return false;
            }

            const closeoutStart = parseDate(closeout.start_date);
            const closeoutEnd = parseDate(closeout.end_date);
            if (!closeoutStart || !closeoutEnd || isBefore(date, closeoutStart) || isAfter(date, closeoutEnd)) {
                return false;
            }

            const blockedSlots = closeout.closeout_day_time_slots || [];
            if (blockedSlots.length === 0) {
                return true;
            }

            return blockedSlots.some((blockedSlot) => {
                return Number(blockedSlot.day_id) === Number(dayId)
                    && Number(blockedSlot.time_slot_id) === Number(slotId);
            });
        }) || null;
    };

    const getCell = (date, slot) => {
        const dateKey = toDateInput(date);
        const dayOverrides = overrideMap.get(dateKey);
        const slotOverride = dayOverrides?.get(Number(slot.id));
        const booked = bookedMap.get(`${dateKey}|${slot.id}`) || null;

        if (dayOverrides) {
            if (!slotOverride) {
                return { status: 'closed', label: 'Closed', note: 'Date override active', source: 'Date override', capacity: null, booked };
            }
            const closeout = getCloseoutForSlot(date, slot.id);
            if (closeout) {
                return {
                    status: 'closeout',
                    label: 'Closed',
                    note: 'Closeout',
                    source: `${recordSource(closeout)} closeout`,
                    capacity: null,
                    booked,
                };
            }
            if (slotOverride.capacity_override !== null && slotOverride.capacity_override !== undefined && slotOverride.capacity_override !== '') {
                return {
                    status: slotOverride.internal_only ? 'admin_only' : 'capacity',
                    label: slotOverride.internal_only ? 'Admin' : 'Capacity',
                    note: `${slotOverride.internal_only ? 'Admin-only / ' : ''}Capacity ${slotOverride.capacity_override}`,
                    source: `${recordSource(slotOverride)} date override`,
                    capacity: slotOverride.capacity_override,
                    booked,
                };
            }
            return {
                status: slotOverride.internal_only ? 'admin_only' : 'override',
                label: slotOverride.internal_only ? 'Admin' : 'Open',
                note: 'Date override',
                source: `${recordSource(slotOverride)} date override`,
                capacity: baseCapacity,
                booked,
            };
        }

        const closeout = getCloseoutForSlot(date, slot.id);
        if (closeout) {
            return {
                status: 'closeout',
                label: 'Closed',
                note: 'Closeout',
                source: `${recordSource(closeout)} closeout`,
                capacity: null,
                booked,
            };
        }

        if (slot.is_override_only || !allowedDays.includes(isoDay(date))) {
            return { status: 'closed', label: 'Closed', note: 'Not in base schedule', source: 'No base schedule', capacity: null, booked };
        }

        return { status: 'open', label: 'Open', note: 'Base inventory', source: 'Base inventory', capacity: baseCapacity, booked };
    };

    const makeCellKey = (date, slotId) => `${toDateInput(date)}|${slotId}`;

    const selectedCellSet = useMemo(() => {
        return new Set(selectedCells.map((cell) => cell.key));
    }, [selectedCells]);

    const selectedSummary = useMemo(() => {
        return selectedCells.reduce((summary, cell) => {
            summary.total += 1;
            if (cell.booked) {
                summary.bookedCells += 1;
                summary.bookedPax += cell.booked.pax;
            }
            summary.statuses[cell.status] = (summary.statuses[cell.status] || 0) + 1;
            return summary;
        }, {
            total: 0,
            bookedCells: 0,
            bookedPax: 0,
            statuses: {},
        });
    }, [selectedCells]);

    const toggleCellSelection = (date, slot, cell) => {
        const key = makeCellKey(date, slot.id);
        setSelectedCells((current) => {
            if (current.some((item) => item.key === key)) {
                return current.filter((item) => item.key !== key);
            }

            return [
                ...current,
                {
                    key,
                    date: toDateInput(date),
                    slotId: slot.id,
                    time: slot.label,
                    status: cell.status,
                    booked: cell.booked,
                    capacity: cell.capacity,
                },
            ];
        });
    };

    const cellPassesDisplayFilters = (cell) => {
        if (showAdjustedOnly && !ADJUSTED_STATUSES.includes(cell.status)) return false;
        if (showBookedOnly && !cell.booked) return false;
        return true;
    };

    const buildVisibleSelections = (filter = 'all') => {
        const selections = [];

        filteredSlots.forEach((slot) => {
            visibleDays.forEach((date) => {
                const cell = getCell(date, slot);
                if (!cellPassesDisplayFilters(cell)) return;
                if (filter === 'open' && cell.status !== 'open' && cell.status !== 'override') return;
                if (filter === 'closed' && cell.status !== 'closed' && cell.status !== 'closeout') return;
                if (filter === 'booked' && !cell.booked) return;
                if (filter === 'adjusted' && !['override', 'admin_only', 'capacity', 'closeout'].includes(cell.status)) return;

                selections.push({
                    key: makeCellKey(date, slot.id),
                    date: toDateInput(date),
                    slotId: slot.id,
                    time: slot.label,
                    status: cell.status,
                    booked: cell.booked,
                    capacity: cell.capacity,
                });
            });
        });

        return selections;
    };

    const selectVisibleCells = (filter = 'all') => {
        setSelectedCells(buildVisibleSelections(filter));
        setConfirmBooked(false);
    };

    const toggleWeekday = (dayId) => {
        setSelectedWeekdays((current) => {
            if (current.includes(dayId)) {
                return current.filter((id) => id !== dayId);
            }
            return [...current, dayId].sort((a, b) => a - b);
        });
        setSelectedCells([]);
    };

    const toggleSlotFilter = (slotId) => {
        setSelectedSlotIds((current) => {
            if (current.includes(slotId)) {
                return current.filter((id) => id !== slotId);
            }
            return [...current, slotId];
        });
        setSelectedCells([]);
    };

    const clearFilters = () => {
        setSelectedWeekdays(WEEKDAYS.map((day) => day.id));
        setSelectedSlotIds([]);
        setShowAdjustedOnly(false);
        setShowBookedOnly(false);
        setSelectedCells([]);
    };

    const pendingChanges = useMemo(() => {
        return selectedCells.map((cell) => {
            const base = {
                key: cell.key,
                date: cell.date,
                time: cell.time,
                slotId: cell.slotId,
                currentStatus: cell.status,
                booked: cell.booked,
                capacity: cell.capacity,
            };

            if (bulkAction === 'close') {
                return {
                    ...base,
                    type: 'closeout',
                    action: 'Close slot',
                    record: 'Closeout',
                    note: '[Scheduler] Close selected slot',
                };
            }

            if (bulkAction === 'open') {
                return {
                    ...base,
                    type: 'date_override',
                    action: internalOnly ? 'Open admin-only slot' : 'Open slot',
                    record: 'Date Override',
                    note: internalOnly ? '[Scheduler] Open selected slot as admin-only' : '[Scheduler] Open selected slot',
                };
            }

            if (bulkAction === 'capacity') {
                return {
                    ...base,
                    type: 'date_override',
                    action: 'Set capacity',
                    record: 'Date Override',
                    capacity: capacityOverride,
                    note: `[Scheduler] Set capacity override${capacityOverride ? ` to ${capacityOverride}` : ''}${internalOnly ? ' and keep admin-only' : ''}`,
                };
            }

            return {
                ...base,
                type: 'clear',
                action: 'Clear scheduler adjustment',
                record: 'Closeout / Date Override',
                note: '[Scheduler] Clear selected adjustment',
            };
        });
    }, [selectedCells, bulkAction, capacityOverride, internalOnly]);

    const pendingSummary = useMemo(() => {
        return pendingChanges.reduce((summary, change) => {
            summary[change.record] = (summary[change.record] || 0) + 1;
            return summary;
        }, {});
    }, [pendingChanges]);

    const saveDisabled = selectedSummary.total === 0
        || isSaving
        || (bulkAction === 'capacity' && capacityOverride === '')
        || (bulkAction === 'capacity' && ((selectedSummary.statuses.closed || 0) > 0 || (selectedSummary.statuses.closeout || 0) > 0))
        || (selectedSummary.bookedCells > 0 && !confirmBooked);

    const handleSave = () => {
        if (saveDisabled) return;

        setIsSaving(true);
        router.post(route('admin.inventories.schedule', inventory.id), {
            action: bulkAction,
            capacity_override: bulkAction === 'capacity' ? capacityOverride : null,
            internal_only: internalOnly,
            confirm_booked: confirmBooked,
            cells: selectedCells.map((cell) => ({
                date: cell.date,
                slot_id: cell.slotId,
                status: cell.status,
            })),
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setSelectedCells([]);
                setConfirmBooked(false);
                onClose();
            },
            onFinish: () => setIsSaving(false),
        });
    };

    if (!isOpen || !inventory) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black bg-opacity-50 px-4 py-6">
            <div className="flex h-[92vh] w-[95vw] max-w-[1400px] flex-col overflow-hidden rounded-lg bg-white shadow-lg">
                <div className=" border-b border-secondary-100 px-6 py-4 ">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h2 className="text-xl font-bold text-gray-900">Schedule</h2>
                            <p className="mt-1 text-sm text-gray-500">
                                {inventory.service?.name || 'Service'} / {inventory.season?.name || 'Season'}
                            </p>
                            <p className="mt-1 text-xs text-gray-400">
                                This preview is generated from base inventory and existing date overrides. Saving actions will create scheduler-managed closeouts or date overrides.
                            </p>
                        </div>
                        <button type="button" onClick={onClose} className="text-sm text-gray-400 hover:text-gray-700">
                            x
                        </button>
                    </div>
                </div>

                <div className="max-h-[55vh] overflow-y-auto border-b border-secondary-100 px-6 py-4 ">
                    <div className="flex flex-wrap items-end gap-3">
                        <div>
                            <label className="block input-header">Start Date</label>
                            <input
                                type="date"
                                value={startDate}
                                min={inventory.season?.start_date}
                                max={inventory.season?.end_date}
                                onChange={(e) => setStartDate(e.target.value)}
                                className="mt-1 h-[40px] rounded-xl border border-gray-300 px-3 text-sm focus:border-black focus:outline-none focus:ring-0"
                            />
                        </div>
                        <div className="flex gap-2">
                            <button
                                type="button"
                                onClick={() => setViewMode('week')}
                                className={`h-[40px] rounded-[10px] border px-4 text-sm ${viewMode === 'week' ? 'border-[#3863C6] bg-[#3863C6] text-white' : 'border-secondary-100 bg-white text-gray-700 hover:bg-gray-50'}`}
                            >
                                Week
                            </button>
                            <button
                                type="button"
                                onClick={() => setViewMode('month')}
                                className={`h-[40px] rounded-[10px] border px-4 text-sm ${viewMode === 'month' ? 'border-[#3863C6] bg-[#3863C6] text-white' : 'border-secondary-100 bg-white text-gray-700 hover:bg-gray-50'}`}
                            >
                                Month
                            </button>
                        </div>
                        <div className="flex flex-wrap gap-2 text-xs">
                            <span className="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-800">Online</span>
                            <span className="rounded-full border border-amber-200 bg-amber-50 px-3 py-2 text-amber-800">Admin-only</span>
                            <span className="rounded-full border border-fuchsia-200 bg-fuchsia-50 px-3 py-2 text-fuchsia-800">Capacity</span>
                            <span className="rounded-full border border-sky-200 bg-sky-50 px-3 py-2 text-sky-800">Date override</span>
                            <span className="rounded-full border border-rose-200 bg-rose-50 px-3 py-2 text-rose-800">Closeout</span>
                            <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-slate-400">Closed</span>
                            <span className="rounded-full border border-violet-200 bg-violet-50 px-3 py-2 text-violet-800">Booked</span>
                        </div>
                    </div>
                    <div className="mt-4 rounded-xl border border-secondary-100 bg-white p-3">
                        <div className="flex flex-wrap items-start gap-5">
                            <div>
                                <p className="input-header">Weekdays</p>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {WEEKDAYS.map((day) => (
                                        <button
                                            key={day.id}
                                            type="button"
                                            onClick={() => toggleWeekday(day.id)}
                                            className={`rounded-lg border px-3 py-2 text-xs font-semibold ${selectedWeekdays.includes(day.id) ? 'border-[#3863C6] bg-[#3863C6] text-white' : 'border-secondary-100 bg-white text-gray-600 hover:bg-gray-50'}`}
                                        >
                                            {day.short}
                                        </button>
                                    ))}
                                </div>
                                <div className="mt-2 flex gap-2">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setSelectedWeekdays([1, 2, 3, 4, 5]);
                                            setSelectedCells([]);
                                        }}
                                        className="text-xs font-semibold text-[#3863C6] hover:text-black"
                                    >
                                        Weekdays
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setSelectedWeekdays([6, 7]);
                                            setSelectedCells([]);
                                        }}
                                        className="text-xs font-semibold text-[#3863C6] hover:text-black"
                                    >
                                        Weekends
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setSelectedWeekdays(WEEKDAYS.map((day) => day.id));
                                            setSelectedCells([]);
                                        }}
                                        className="text-xs font-semibold text-[#3863C6] hover:text-black"
                                    >
                                        All days
                                    </button>
                                </div>
                            </div>
                            <div className="max-w-[520px]">
                                <p className="input-header">Time Slots</p>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {allSlots.map((slot) => (
                                        <button
                                            key={slot.id}
                                            type="button"
                                            onClick={() => toggleSlotFilter(Number(slot.id))}
                                            className={`rounded-lg border px-3 py-2 text-xs font-semibold ${selectedSlotIds.length === 0 || selectedSlotIds.includes(Number(slot.id)) ? 'border-[#3863C6] bg-blue-50 text-[#3863C6]' : 'border-secondary-100 bg-white text-gray-600 hover:bg-gray-50'}`}
                                        >
                                            {slot.label}
                                        </button>
                                    ))}
                                </div>
                                {selectedSlotIds.length > 0 && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setSelectedSlotIds([]);
                                            setSelectedCells([]);
                                        }}
                                        className="mt-2 text-xs font-semibold text-[#3863C6] hover:text-black"
                                    >
                                        Show all slots
                                    </button>
                                )}
                            </div>
                            <div>
                                <p className="input-header">Focus</p>
                                <div className="mt-2 flex flex-wrap gap-3">
                                    <label className="flex items-center gap-2 text-xs font-semibold text-gray-700">
                                        <input
                                            type="checkbox"
                                            checked={showAdjustedOnly}
                                            onChange={(e) => {
                                                setShowAdjustedOnly(e.target.checked);
                                                setSelectedCells([]);
                                            }}
                                        />
                                        Adjusted only
                                    </label>
                                    <label className="flex items-center gap-2 text-xs font-semibold text-gray-700">
                                        <input
                                            type="checkbox"
                                            checked={showBookedOnly}
                                            onChange={(e) => {
                                                setShowBookedOnly(e.target.checked);
                                                setSelectedCells([]);
                                            }}
                                        />
                                        Booked only
                                    </label>
                                </div>
                                <button
                                    type="button"
                                    onClick={clearFilters}
                                    className="mt-2 text-xs font-semibold text-[#3863C6] hover:text-black"
                                >
                                    Clear filters
                                </button>
                            </div>
                        </div>
                    </div>
                    <div className="mt-4 rounded-xl border border-secondary-100 bg-gray-50 p-3">
                        <div className="flex flex-wrap items-end gap-3">
                            <div>
                                <label className="block input-header">Bulk Action</label>
                                <select
                                    value={bulkAction}
                                    onChange={(e) => setBulkAction(e.target.value)}
                                    className="mt-1 h-[40px] rounded-xl border border-gray-300 bg-white px-3 text-sm focus:border-black focus:outline-none focus:ring-0"
                                >
                                    <option value="close">Close selected slots</option>
                                    <option value="open">Open selected slots</option>
                                    <option value="capacity">Set capacity override</option>
                                    <option value="clear">Clear scheduler adjustment</option>
                                </select>
                            </div>
                            {['open', 'capacity'].includes(bulkAction) && (
                                <label className="flex max-w-[280px] items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                                    <input
                                        type="checkbox"
                                        checked={internalOnly}
                                        onChange={(e) => setInternalOnly(e.target.checked)}
                                        className="mt-1"
                                    />
                                    <span>
                                        <span className="block font-semibold">Admin only</span>
                                        <span className="block text-xs">Available to staff, hidden from the public calendar.</span>
                                    </span>
                                </label>
                            )}
                            {bulkAction === 'capacity' && (
                                <div>
                                    <label className="block input-header">Capacity</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={capacityOverride}
                                        onChange={(e) => setCapacityOverride(e.target.value)}
                                        className="mt-1 h-[40px] w-[140px] rounded-xl border border-gray-300 px-3 text-sm focus:border-black focus:outline-none focus:ring-0"
                                    />
                                </div>
                            )}
                            <OutlineButton
                                title="Clear Selection"
                                width="w-[160px]"
                                onClick={() => setSelectedCells([])}
                            />
                            <p className="text-sm text-gray-500">
                                {selectedSummary.total} selected
                                {selectedSummary.bookedCells > 0 && ` / ${selectedSummary.bookedCells} booked cells (${selectedSummary.bookedPax} pax)`}
                            </p>
                        </div>
                        <div className="mt-3 flex flex-wrap gap-2">
                            <button
                                type="button"
                                onClick={() => selectVisibleCells('all')}
                                className="rounded-lg border border-secondary-100 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                            >
                                Select visible
                            </button>
                            <button
                                type="button"
                                onClick={() => selectVisibleCells('open')}
                                className="rounded-lg border border-emerald-200 bg-white px-3 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-50"
                            >
                                Select open
                            </button>
                            <button
                                type="button"
                                onClick={() => selectVisibleCells('closed')}
                                className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                            >
                                Select closed
                            </button>
                            <button
                                type="button"
                                onClick={() => selectVisibleCells('adjusted')}
                                className="rounded-lg border border-sky-200 bg-white px-3 py-2 text-xs font-semibold text-sky-800 hover:bg-sky-50"
                            >
                                Select adjusted
                            </button>
                            <button
                                type="button"
                                onClick={() => selectVisibleCells('booked')}
                                className="rounded-lg border border-violet-200 bg-white px-3 py-2 text-xs font-semibold text-violet-800 hover:bg-violet-50"
                            >
                                Select booked
                            </button>
                        </div>
                        {selectedSummary.bookedCells > 0 && (
                            <label className="mt-3 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                                <input
                                    type="checkbox"
                                    checked={confirmBooked}
                                    onChange={(e) => setConfirmBooked(e.target.checked)}
                                    className="mt-1"
                                />
                                <span>
                                    I understand this affects slots that already have bookings. Existing bookings will remain visible, but future availability may change.
                                </span>
                            </label>
                        )}
                        {bulkAction === 'capacity' && ((selectedSummary.statuses.closed || 0) > 0 || (selectedSummary.statuses.closeout || 0) > 0) && (
                            <p className="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                                Capacity can only be changed on open slots. Use "Open selected slots" first if the slot is currently closed.
                            </p>
                        )}
                    </div>
                    {pendingChanges.length > 0 && (
                        <div className="mt-4 rounded-xl border border-blue-100 bg-blue-50 p-4">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 className="text-sm font-semibold text-gray-900">Pending Changes Preview</h3>
                                    <p className="mt-1 text-xs text-gray-600">
                                        Review these changes before saving.
                                        {bulkAction !== 'close' && bulkAction !== 'clear' && ' Date override actions may create companion rows to preserve the rest of the base day schedule.'}
                                        {bulkAction === 'clear' && ' Clear only removes scheduler-created adjustments for the selected date/time. Manual records are left alone.'}
                                    </p>
                                </div>
                                <div className="flex flex-wrap gap-2 text-xs">
                                    {Object.entries(pendingSummary).map(([record, count]) => (
                                        <span key={record} className="rounded-full border border-blue-200 bg-white px-3 py-1 text-blue-800">
                                            {count} {record}{count === 1 ? '' : 's'}
                                        </span>
                                    ))}
                                </div>
                            </div>
                            <div className="mt-3 max-h-[160px] overflow-auto rounded-lg border border-blue-100 bg-white">
                                <table className="min-w-full text-xs">
                                    <thead className="bg-blue-50 text-left text-gray-500">
                                        <tr>
                                            <th className="p-2">Date</th>
                                            <th className="p-2">Time</th>
                                            <th className="p-2">Action</th>
                                            <th className="p-2">Record</th>
                                            <th className="p-2">Current</th>
                                            <th className="p-2">Booked</th>
                                            <th className="p-2">Note</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-blue-50">
                                        {pendingChanges.slice(0, 25).map((change) => (
                                            <tr key={change.key}>
                                                <td className="p-2">{change.date}</td>
                                                <td className="p-2">{change.time}</td>
                                                <td className="p-2 font-semibold text-gray-800">{change.action}</td>
                                                <td className="p-2">{change.record}</td>
                                                <td className="p-2">{change.currentStatus}</td>
                                                <td className="p-2">
                                                    {change.booked ? `${change.booked.pax} pax / ${change.booked.bookingsCount}` : '-'}
                                                </td>
                                                <td className="p-2">{change.note}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                                {pendingChanges.length > 25 && (
                                    <p className="border-t border-blue-50 p-2 text-xs text-gray-500">
                                        Showing first 25 of {pendingChanges.length} pending changes.
                                    </p>
                                )}
                            </div>
                        </div>
                    )}
                </div>

                <div className="h-[30vh] min-h-[220px] overflow-auto px-6 py-4 ">
                    <table className="min-w-full border-separate border-spacing-2 text-sm">
                        <thead>
                            <tr>
                                <th className="sticky left-0 z-10 w-[110px] bg-white p-2 text-left text-xs font-semibold uppercase text-gray-500">
                                    Time
                                </th>
                                {visibleDays.map((date) => (
                                    <th key={date.toISOString()} className="min-w-[120px] rounded-xl bg-gray-50 p-3 text-center">
                                        <span className="block text-sm font-semibold text-gray-800">{format(date, 'EEE')}</span>
                                        <span className="block text-xs text-gray-500">{format(date, 'dd MMM')}</span>
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {filteredSlots.map((slot) => (
                                <tr key={slot.id}>
                                    <td className="sticky left-0 z-10 rounded-xl bg-white p-3 font-semibold text-gray-800 shadow-sm">
                                        {slot.label}
                                    </td>
                                    {visibleDays.map((date) => {
                                        const cell = getCell(date, slot);
                                        const passesFilters = cellPassesDisplayFilters(cell);
                                        const cellKey = makeCellKey(date, slot.id);
                                        const isSelected = selectedCellSet.has(cellKey);
                                        return (
                                            <td key={`${slot.id}-${date.toISOString()}`} className="p-0">
                                                {!passesFilters ? (
                                                    <div className="flex h-[74px] w-full items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 text-xs text-slate-300">
                                                        -
                                                    </div>
                                                ) : (
                                                    <button
                                                        type="button"
                                                        onClick={() => toggleCellSelection(date, slot, cell)}
                                                        title={`${cell.note} / ${cell.source || 'Unknown'}${cell.booked ? ` / ${cell.booked.pax} booked pax` : ''}`}
                                                        className={`relative h-[74px] w-full rounded-xl border px-3 pt-5 text-center text-xs font-semibold transition ${statusStyles[cell.status] || statusStyles.closed} ${isSelected ? 'ring-2 ring-[#3863C6] ring-offset-2' : 'hover:ring-1 hover:ring-gray-300'}`}
                                                    >
                                                        {cell.capacity !== null && cell.capacity !== undefined && cell.capacity !== '' && (
                                                            <span className={`absolute left-2 top-2 rounded-full px-2 py-[1px] text-[10px] font-semibold ${cell.status === 'capacity' ? 'bg-fuchsia-100 text-fuchsia-900' : 'bg-white/80 text-gray-700'}`}>
                                                                Cap {cell.capacity}
                                                            </span>
                                                        )}
                                                        <span className="block">{cell.label}</span>
                                                        <span className="block truncate text-[10px] font-normal opacity-75">{cell.note}</span>
                                                        <span className="block truncate text-[10px] font-semibold opacity-70">{cell.source}</span>
                                                        {cell.booked && (
                                                            <span className="mt-1 inline-flex rounded-full border border-violet-200 bg-violet-50 px-2 py-[1px] text-[10px] text-violet-800">
                                                                {cell.booked.pax} pax / {cell.booked.bookingsCount} booking{cell.booked.bookingsCount === 1 ? '' : 's'}
                                                            </span>
                                                        )}
                                                    </button>
                                                )}
                                            </td>
                                        );
                                    })}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="sticky bottom-0 z-20 flex items-center justify-between gap-4 border-t border-secondary-100 bg-white px-6 py-4">
                    <p className="text-sm text-gray-500">
                        {selectedSummary.total} cell{selectedSummary.total === 1 ? '' : 's'} selected for <span className="font-semibold">{bulkAction.replace('_', ' ')}</span>.
                        {selectedSummary.bookedCells > 0 && ' Booked-slot changes will require strong confirmation before saving.'}
                        {bulkAction === 'clear' && ' Clear removes scheduler-created adjustments only.'}
                    </p>
                    <div className="flex gap-3">
                        <OutlineButton title="Close" onClick={onClose} width="w-[160px]" />
                        <BlueButton title={isSaving ? 'Saving...' : 'Save Changes'} disabled={saveDisabled} width="w-[180px]" onClick={handleSave} />
                    </div>
                </div>
            </div>
        </div>
    );
}
