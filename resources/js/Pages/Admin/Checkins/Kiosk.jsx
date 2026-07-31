import KioskLayout from '@/Layouts/KioskLayout';
import CustomInputText from '@/Components/CustomInputText';
import BlueButton from '@/Components/BlueButton';
import { Head, router } from '@inertiajs/react';
import React, { useEffect, useMemo, useState } from 'react';

const statusClasses = {
    complete: 'bg-green-50 text-green-700 border-green-200',
    partial: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    pending: 'bg-gray-50 text-gray-600 border-gray-200',
};

const formatTime = (time) => {
    if (!time) return '--:--';
    return String(time).slice(0, 5);
};

export default function Kiosk({ auth, date, search, items = [] }) {
    const [filters, setFilters] = useState({
        date: date || '',
        search: search || '',
    });

    const totals = useMemo(() => {
        return items.reduce(
            (carry, item) => {
                carry.expected += Number(item.expected_count || 0);
                carry.completed += Number(item.completed_count || 0);
                return carry;
            },
            { expected: 0, completed: 0 }
        );
    }, [items]);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        localStorage.setItem('checkin_kiosk_url', `${window.location.pathname}${window.location.search}`);
    }, [date, search]);

    const applyFilters = (event) => {
        event.preventDefault();
        router.get(route('admin.checkins.kiosk'), filters, {
            preserveState: true,
            replace: true,
        });
    };

    const openCheckin = (url) => {
        window.location.href = url;
    };

    return (
        <KioskLayout>
            <Head title="Check-in Kiosk" />

            <div className="py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900">Check-in Kiosk</h1>
                            <p className="text-sm text-gray-500 mt-1">
                                Staff tablet view. Open a secure check-in form, hand the tablet to the guest, then return here after submit.
                            </p>
                        </div>
                        <div className="rounded-[14px] border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600 shadow-sm">
                            <span className="font-semibold text-gray-900">{totals.completed}</span> / {totals.expected} completed
                        </div>
                    </div>

                    <form onSubmit={applyFilters} className="bg-white rounded-[16px] border border-gray-200 shadow-sm p-4 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-[220px_1fr_auto] gap-4 md:items-end">
                            <CustomInputText
                                title="Activity Date"
                                name="date"
                                type="date"
                                value={filters.date}
                                onChange={(event) => setFilters((current) => ({ ...current, date: event.target.value }))}
                            />
                            <CustomInputText
                                title="Search"
                                name="search"
                                type="search"
                                value={filters.search}
                                required={false}
                                placeholder="Reference, guest, email, phone, or service"
                                onChange={(event) => setFilters((current) => ({ ...current, search: event.target.value }))}
                            />
                            <div className="flex gap-2 mb-4">
                                <BlueButton title="Filter" type="submit" />
                                <button
                                    title="Today"
                                    type="button"
                                    onClick={() => {
                                        const today = new Date().toISOString().slice(0, 10);
                                        router.get(route('admin.checkins.kiosk'), { date: today }, { replace: true });
                                    }}
                                    className="h-[40px] rounded-[10px] border border-primary-14 bg-white-gradient-overlay px-4 py-2 text-sm font-medium text-black shadow-inset-white hover:border-black"
                                >
                                    Today
                                </button>
                            </div>
                        </div>
                    </form>

                    <div className="space-y-4">
                        {items.length === 0 && (
                            <div className="bg-white rounded-[16px] border border-gray-200 shadow-sm p-8 text-center text-gray-500">
                                No bookings found for this date/search.
                            </div>
                        )}

                        {items.map((item) => (
                            <div key={item.booking_item_id} className="bg-white rounded-[16px] border border-gray-200 shadow-sm overflow-hidden">
                                <div className="p-4 border-b border-gray-100 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="text-lg font-semibold text-gray-900">{formatTime(item.start_time)}</span>
                                            <span className="text-sm text-gray-500">{item.reference}</span>
                                            <span className={`text-xs capitalize border px-2 py-1 rounded-full ${statusClasses[item.status] || statusClasses.pending}`}>
                                                {item.status}
                                            </span>
                                        </div>
                                        <div className="text-sm text-gray-600 mt-1">
                                            {item.service_name} {item.season_name ? `- ${item.season_name}` : ''}
                                        </div>
                                        <div className="text-sm text-gray-500 mt-1">
                                            Lead: {item.lead_name || 'Not captured'} | {item.completed_count}/{item.expected_count} checked in
                                        </div>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        <BlueButton
                                            title="Start Check-in"
                                            type="button"
                                            onClick={() => openCheckin(item.checkin_url)}
                                        />
                                    </div>
                                </div>

                                <div className="divide-y divide-gray-100">
                                    {item.guests.map((guest, index) => (
                                        <div key={`${item.booking_item_id}-${guest.customer_id || index}`} className="p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                            <div>
                                                <div className="font-medium text-gray-900">
                                                    {guest.name}
                                                    {guest.completed && <span className="ml-2 text-xs text-green-700 bg-green-50 border border-green-200 rounded-full px-2 py-1">Complete</span>}
                                                </div>
                                                <div className="text-sm text-gray-500">
                                                    {[guest.email, guest.phone].filter(Boolean).join(' | ') || 'Details can be captured during check-in'}
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                className="h-[40px] rounded-[10px] border border-primary-14 bg-white-gradient-overlay px-4 py-2 text-sm font-medium text-black shadow-inset-white hover:border-black"
                                                onClick={() => openCheckin(guest.checkin_url)}
                                            >
                                                {guest.completed ? 'Review Check-in' : 'Check In Guest'}
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </KioskLayout>
    );
}
