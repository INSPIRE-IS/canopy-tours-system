import axios from 'axios';
import { router } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';

const valueOrDash = (value) => {
    if (value === null || value === undefined || value === '') return '-';
    return String(value);
};

export default function AlertContextModal({ alertId, isOpen, onClose, onResolved, reloadOnly = null }) {
    const [context, setContext] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState('');
    const [isResolving, setIsResolving] = useState(false);
    const [addNote, setAddNote] = useState(true);
    const [noteText, setNoteText] = useState('');

    useEffect(() => {
        if (!isOpen || !alertId) return;

        setIsLoading(true);
        setError('');
        setContext(null);

        axios.get(route('admin.user-alerts.context', alertId))
            .then((response) => {
                setContext(response.data);
                setAddNote(Boolean(response.data?.manifest?.id));
                setNoteText(`Notification acknowledged: ${response.data?.alert?.text || 'Alert reviewed'}`);
            })
            .catch(() => setError('Unable to load alert details.'))
            .finally(() => setIsLoading(false));
    }, [alertId, isOpen]);

    if (!isOpen) return null;

    const alert = context?.alert;
    const booking = context?.booking;
    const bookingItem = context?.booking_item;
    const customer = context?.customer;
    const manifest = context?.manifest;
    const answers = Array.isArray(context?.answers) ? context.answers : [];

    const handleResolve = () => {
        if (!alertId || isResolving) return;

        setIsResolving(true);
        axios.post(route('admin.user-alerts.resolve', alertId), {
            add_note: addNote,
            note: noteText,
        })
            .then(() => {
                onResolved?.(alertId);
                onClose();
                reloadOnly ? router.reload({ only: reloadOnly }) : router.reload();
            })
            .catch(() => setError('Unable to resolve alert.'))
            .finally(() => setIsResolving(false));
    };

    const handleViewBooking = () => {
        const href = booking?.view_url || alert?.link;
        if (href) {
            window.location.href = href;
        }
    };

    return (
        <div className="fixed inset-0 z-[70] flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-lg">
                <div className="flex items-start justify-between gap-4 border-b px-6 py-4">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900">Notification Detail</h2>
                        <p className="mt-1 text-sm text-gray-500">{alert?.text || 'Loading notification...'}</p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="text-2xl leading-none text-gray-400 hover:text-gray-600"
                    >
                        &times;
                    </button>
                </div>

                <div className="max-h-[72vh] space-y-5 overflow-y-auto px-6 py-4">
                    {isLoading && <div className="text-sm text-gray-500">Loading alert details...</div>}
                    {error && <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{error}</div>}

                    {!isLoading && context && (
                        <>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                    <div className="text-xs font-semibold uppercase tracking-wide text-gray-500">Guest</div>
                                    <div className="mt-2 text-sm font-semibold text-gray-900">{valueOrDash(customer?.full_name)}</div>
                                    <div className="mt-1 text-sm text-gray-600">{valueOrDash(customer?.email_address)}</div>
                                    <div className="mt-1 text-sm text-gray-600">{valueOrDash(customer?.contact_number)}</div>
                                </div>

                                <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                    <div className="text-xs font-semibold uppercase tracking-wide text-gray-500">Booking</div>
                                    <div className="mt-2 text-sm font-semibold text-gray-900">{valueOrDash(booking?.reference)}</div>
                                    <div className="mt-1 text-sm text-gray-600">{valueOrDash(bookingItem?.service)}</div>
                                    <div className="mt-1 text-sm text-gray-600">{valueOrDash(bookingItem?.date)} {valueOrDash(bookingItem?.time)}</div>
                                </div>

                                <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                    <div className="text-xs font-semibold uppercase tracking-wide text-gray-500">Manifest</div>
                                    <div className="mt-2 text-sm text-gray-700">Indemnity: {manifest?.is_indemnity ? 'Yes' : 'No'}</div>
                                    <div className="mt-1 text-sm text-gray-700">Arrived: {valueOrDash(manifest?.attendance_status)}</div>
                                    <div className="mt-1 text-sm text-gray-700">Type: {valueOrDash(manifest?.type)}</div>
                                    <div className="mt-1 text-sm text-gray-700">Notes: {valueOrDash(manifest?.notes)}</div>
                                </div>
                            </div>

                            <div className="rounded-xl border border-gray-200 bg-white p-4">
                                <h3 className="text-sm font-semibold text-gray-800">Check-in answers</h3>
                                {answers.length === 0 ? (
                                    <div className="mt-3 text-sm text-gray-500">No check-in answers found for this alert.</div>
                                ) : (
                                    <div className="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                                        {answers.map((entry) => (
                                            <div key={entry.id} className="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                                <div className="text-xs font-semibold text-gray-600">
                                                    {entry?.question?.name || entry?.question?.slug || 'Question'}
                                                </div>
                                                <div className="mt-2 text-sm text-gray-900">
                                                    {valueOrDash(entry?.resolved_answer ?? entry?.answer)}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>

                            <div className="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                <label className="flex items-start gap-3 text-sm text-gray-800">
                                    <input
                                        type="checkbox"
                                        checked={addNote}
                                        disabled={!manifest?.id}
                                        onChange={(event) => setAddNote(event.target.checked)}
                                        className="mt-1"
                                    />
                                    <span>
                                        <span className="font-semibold">Add acknowledgement to Checkin Detail notes</span>
                                        <span className="mt-1 block text-xs text-gray-600">
                                            Acknowledging hides this notification for you. It does not change the guest's answers or participation status.
                                        </span>
                                    </span>
                                </label>
                                {addNote && (
                                    <textarea
                                        value={noteText}
                                        onChange={(event) => setNoteText(event.target.value)}
                                        rows={3}
                                        className="mt-3 w-full rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm text-gray-800 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-200"
                                    />
                                )}
                            </div>
                        </>
                    )}
                </div>

                <div className="flex flex-wrap items-center justify-end gap-3 border-t px-6 py-4">
                    <button
                        type="button"
                        onClick={handleViewBooking}
                        disabled={!booking?.view_url && !alert?.link}
                        className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        View Booking
                    </button>
                    <button
                        type="button"
                        onClick={handleResolve}
                        disabled={isResolving || isLoading}
                        className="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-black hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {isResolving ? 'Acknowledging...' : 'Acknowledge'}
                    </button>
                </div>
            </div>
        </div>
    );
}
