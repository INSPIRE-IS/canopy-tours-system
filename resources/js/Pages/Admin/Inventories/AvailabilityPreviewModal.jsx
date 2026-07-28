import React, { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import { addDays, format, isAfter, isBefore, isValid, parseISO, startOfDay } from 'date-fns';
import BlueButton from '@/Components/BlueButton';
import OutlineButton from '@/Components/OutlineButton';

const VIEW_DAYS = {
    week: 7,
    month: 30,
};

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
const normalizeTime = (time) => String(time || '').slice(0, 5);
const closeoutSourceLabel = (rule) => {
    if (!rule) return '';

    const priority = rule.priority ?? 50;
    const dates = rule.start_date && rule.end_date ? ` • ${rule.start_date} to ${rule.end_date}` : '';
    return `${rule.name || 'Closeout'} (priority ${priority})${dates}`;
};

const statusMeta = (cell) => {
    if (!cell) {
        return {
            label: 'Not Available',
            className: 'border-slate-200 bg-slate-50 text-slate-500',
        };
    }

    if (cell.status === 'available' && cell.internal_only) {
        return {
            label: cell.is_date_override ? 'Admin-only override' : 'Admin-only',
            className: 'border-amber-200 bg-amber-50 text-amber-800',
        };
    }

    if (cell.status === 'available') {
        return {
            label: cell.is_date_override ? 'Open by override' : 'Open',
            className: 'border-emerald-200 bg-emerald-50 text-emerald-800',
        };
    }

    if (cell.status === 'full') {
        return {
            label: 'Full',
            className: 'border-orange-200 bg-orange-50 text-orange-800',
        };
    }

    if (['closed', 'blocked_day', 'blocked_slot'].includes(cell.status)) {
        return {
            label: 'Closed',
            className: 'border-rose-200 bg-rose-50 text-rose-800',
        };
    }

    return {
        label: 'Not Available',
        className: 'border-slate-200 bg-slate-50 text-slate-500',
    };
};

export default function AvailabilityPreviewModal({ isOpen, onClose, inventory, inventoryOptions = [] }) {
    const [selectedInventoryId, setSelectedInventoryId] = useState(inventory?.id || inventoryOptions?.[0]?.id || '');
    const activeInventory = useMemo(() => {
        return inventoryOptions.find((item) => Number(item.id) === Number(selectedInventoryId)) || inventory || null;
    }, [inventoryOptions, selectedInventoryId, inventory]);

    const seasonStart = parseDate(activeInventory?.season?.start_date);
    const seasonEnd = parseDate(activeInventory?.season?.end_date);
    const initialStart = seasonStart && seasonEnd
        ? toDateInput(clampDate(startOfDay(new Date()), seasonStart, seasonEnd))
        : toDateInput(startOfDay(new Date()));

    const [viewMode, setViewMode] = useState('week');
    const [startDate, setStartDate] = useState(initialStart);
    const [payload, setPayload] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        if (!isOpen) return;

        setSelectedInventoryId(inventory?.id || inventoryOptions?.[0]?.id || '');
        setViewMode('week');
        setPayload(null);
        setError('');
    }, [isOpen, inventory?.id, inventoryOptions]);

    useEffect(() => {
        if (!isOpen || !activeInventory?.id) return;
        setStartDate(initialStart);
        setPayload(null);
    }, [isOpen, activeInventory?.id, initialStart]);

    const endDate = useMemo(() => {
        const parsedStart = parseDate(startDate) || startOfDay(new Date());
        return toDateInput(addDays(parsedStart, VIEW_DAYS[viewMode] - 1));
    }, [startDate, viewMode]);

    const loadPreview = async () => {
        if (!activeInventory?.id) return;

        setIsLoading(true);
        setError('');

        try {
            const response = await axios.get(route('admin.inventories.availability-preview', activeInventory.id), {
                params: {
                    from: startDate,
                    to: endDate,
                },
            });
            setPayload(response.data);
        } catch (err) {
            setError(err?.response?.data?.message || 'Could not load availability preview.');
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        if (!isOpen || !activeInventory?.id) return;
        loadPreview();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isOpen, activeInventory?.id, startDate, viewMode]);

    if (!isOpen) return null;

    const days = payload?.days || [];
    const schedule = payload?.schedule || [];

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 py-6">
            <div className="flex max-h-[92vh] w-full max-w-[1500px] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div className="flex items-start justify-between border-b px-6 py-4">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900">Preview Availability</h2>
                        <p className="mt-1 text-sm text-gray-500">
                            Read-only view of the effective calendar after inventory, closeouts, date overrides, capacity, and bookings.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-full px-3 py-1 text-2xl leading-none text-gray-500 hover:bg-gray-100 hover:text-gray-900"
                    >
                        &times;
                    </button>
                </div>

                <div className="space-y-4 overflow-y-auto px-6 py-5">
                    <div className="grid gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 md:grid-cols-5">
                        <label className="block">
                            <span className="text-xs font-semibold uppercase tracking-wide text-gray-500">Inventory</span>
                            <select
                                value={selectedInventoryId}
                                onChange={(event) => setSelectedInventoryId(event.target.value)}
                                className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            >
                                {inventoryOptions.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {(item.service?.name || 'Service')} - {(item.season?.name || 'Season')}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wide text-gray-500">Service</div>
                            <div className="mt-1 text-sm font-semibold text-gray-900">{payload?.inventory?.service || activeInventory?.service?.name || '-'}</div>
                        </div>
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wide text-gray-500">Season</div>
                            <div className="mt-1 text-sm font-semibold text-gray-900">{payload?.inventory?.season || activeInventory?.season?.name || '-'}</div>
                        </div>
                        <label className="block">
                            <span className="text-xs font-semibold uppercase tracking-wide text-gray-500">Start date</span>
                            <input
                                type="date"
                                value={startDate}
                                min={activeInventory?.season?.start_date || undefined}
                                max={activeInventory?.season?.end_date || undefined}
                                onChange={(event) => setStartDate(event.target.value)}
                                className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            />
                        </label>
                        <div className="flex flex-wrap items-end gap-2">
                            <button
                                type="button"
                                onClick={() => setViewMode('week')}
                                className={`rounded-lg border px-4 py-2 text-sm font-semibold ${viewMode === 'week' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-300 bg-white text-gray-700'}`}
                            >
                                Week
                            </button>
                            <button
                                type="button"
                                onClick={() => setViewMode('month')}
                                className={`rounded-lg border px-4 py-2 text-sm font-semibold ${viewMode === 'month' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-300 bg-white text-gray-700'}`}
                            >
                                Month
                            </button>
                            <OutlineButton title={isLoading ? 'Loading...' : 'Refresh'} onClick={loadPreview} />
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2 text-xs">
                        <span className="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-emerald-800">Open</span>
                        <span className="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-amber-800">Admin-only</span>
                        <span className="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-rose-800">Closed</span>
                        <span className="rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-orange-800">Full</span>
                        <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-slate-500">Not Available</span>
                    </div>

                    {error && (
                        <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>
                    )}

                    {payload?.message && (
                        <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{payload.message}</div>
                    )}

                    <div className="overflow-x-auto rounded-xl border border-gray-200">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th className="sticky left-0 z-10 w-24 bg-gray-50 px-3 py-3">Time</th>
                                    {days.map((day) => (
                                        <th key={day.date} className="min-w-[150px] px-3 py-3">
                                            <div className="font-semibold text-gray-700">{day.display}</div>
                                            <div className="font-normal normal-case text-gray-400">{day.date}</div>
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {schedule.map((row) => (
                                    <tr key={`${row.slot_id}-${row.time}`}>
                                        <td className="sticky left-0 z-10 bg-white px-3 py-3 font-semibold text-gray-900">
                                            {normalizeTime(row.time)}
                                        </td>
                                        {days.map((day) => {
                                            const cell = row.days?.[day.label];
                                            const meta = statusMeta(cell);
                                            return (
                                                <td key={`${row.slot_id}-${day.date}`} className="px-2 py-2 align-top">
                                                    <div className={`min-h-[88px] rounded-xl border p-3 ${meta.className}`}>
                                                        <div className="font-semibold">{meta.label}</div>
                                                        {cell?.capacity !== undefined && (
                                                            <div className="mt-1 text-xs">
                                                                {Number(cell.slots ?? 0)} / {Number(cell.capacity ?? 0)} slots
                                                            </div>
                                                        )}
                                                        {cell?.note && (
                                                            <div className="mt-1 text-xs opacity-80">{cell.note}</div>
                                                        )}
                                                        {cell?.is_date_override && (
                                                            <div className="mt-2 inline-flex rounded-full bg-white/70 px-2 py-0.5 text-[11px] font-semibold">
                                                                Date override
                                                            </div>
                                                        )}
                                                        {Array.isArray(cell?.rule_sources) && cell.rule_sources.length > 0 && (
                                                            <div className="mt-2 space-y-1 text-[11px]">
                                                                <div className="font-semibold">Rules:</div>
                                                                {cell.rule_sources.slice(0, 3).map((rule, index) => (
                                                                    <div key={`${rule.type || 'rule'}-${rule.id || index}`} className="rounded bg-white/70 px-2 py-1">
                                                                        {closeoutSourceLabel(rule)}
                                                                    </div>
                                                                ))}
                                                                {cell.rule_sources.length > 3 && (
                                                                    <div className="opacity-70">+{cell.rule_sources.length - 3} more rules</div>
                                                                )}
                                                            </div>
                                                        )}
                                                        {Array.isArray(cell?.suppressed_closeout_rules) && cell.suppressed_closeout_rules.length > 0 && (
                                                            <div className="mt-2 text-[11px] opacity-80">
                                                                Overrides closeout: {cell.suppressed_closeout_rules.map((rule) => rule.name).filter(Boolean).join(', ')}
                                                            </div>
                                                        )}
                                                    </div>
                                                </td>
                                            );
                                        })}
                                    </tr>
                                ))}
                                {!isLoading && schedule.length === 0 && (
                                    <tr>
                                        <td colSpan={days.length + 1} className="px-4 py-10 text-center text-sm text-gray-500">
                                            No availability rows found for this range.
                                        </td>
                                    </tr>
                                )}
                                {isLoading && (
                                    <tr>
                                        <td colSpan={Math.max(days.length + 1, 2)} className="px-4 py-10 text-center text-sm text-gray-500">
                                            Loading availability preview...
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="flex justify-end border-t px-6 py-4">
                    <BlueButton title="Close" onClick={onClose} />
                </div>
            </div>
        </div>
    );
}
