import { router } from '@inertiajs/react';
import React, { useEffect, useMemo, useState } from 'react';

const parseAnswerValue = (value) => {
    if (Array.isArray(value)) return value;
    if (value === null || value === undefined) return value;
    if (typeof value === 'number' || typeof value === 'boolean') return value;
    if (typeof value !== 'string') return value;

    const trimmed = value.trim();
    if (!trimmed) return '';
    if ((trimmed.startsWith('[') && trimmed.endsWith(']')) || (trimmed.startsWith('{') && trimmed.endsWith('}'))) {
        try {
            return JSON.parse(trimmed);
        } catch (err) {
            return trimmed;
        }
    }
    return trimmed;
};

const resolveOptionLabel = (value, options = []) => {
    const match = options.find((opt) => String(opt.id) === String(value));
    return match ? match.name : String(value);
};

const formatAnswer = (rawAnswer, question) => {
    const parsed = parseAnswerValue(rawAnswer);
    if (parsed === null || parsed === undefined || parsed === '') return '-';

    const options = Array.isArray(question?.options) ? question.options : [];
    if (options.length) {
        if (Array.isArray(parsed)) {
            return parsed.map((val) => resolveOptionLabel(val, options)).join(', ');
        }
        return resolveOptionLabel(parsed, options);
    }

    if (typeof parsed === 'boolean') {
        return parsed ? 'Yes' : 'No';
    }

    return String(parsed);
};

const STATUS_OPTIONS = [
    { value: 'no_show', label: 'No' },
    { value: 'arrived', label: 'Yes' },
    { value: 'cancelled', label: 'Cancelled' },
];

const statusButtonClass = (value, active, disabled) => {
    const base = 'px-3 py-1 text-xs rounded border transition';
    if (disabled) {
        return `${base} border-gray-200 text-gray-400 bg-gray-100 cursor-not-allowed`;
    }
    if (!active) {
        return `${base} border-gray-300 text-gray-600 bg-white hover:bg-gray-50`;
    }
    if (value === 'arrived') {
        return `${base} border-green-400 text-green-700 bg-green-50`;
    }
    if (value === 'cancelled') {
        return `${base} border-red-400 text-red-700 bg-red-50`;
    }
    return `${base} border-yellow-400 text-yellow-700 bg-yellow-50`;
};

export default function CheckInAnswersModal({ isOpen, onClose, manifest, answers }) {
    if (!isOpen) return null;

    const customer = manifest?.customer ?? manifest ?? {};
    const rawFullName = (customer?.full_name || customer?.name || '').trim();
    const rawEmail = (customer?.email_address || manifest?.email_address || '').trim();
    const phone = customer?.contact_number || '';
    const resolvedAnswers = Array.isArray(answers) ? answers : [];

    const splitFullName = (value) => {
        const cleaned = (value || '').trim();
        if (!cleaned) return { first: '', last: '' };
        const parts = cleaned.split(/\s+/);
        if (parts.length === 1) return { first: parts[0], last: '' };
        return { first: parts[0], last: parts.slice(1).join(' ') };
    };

    const lunchAnswer = useMemo(
        () => resolvedAnswers.find((entry) => entry?.question?.slug === 'lunch_option'),
        [resolvedAnswers]
    );
    const lunchOptions = useMemo(() => {
        const options = Array.isArray(lunchAnswer?.question?.options) ? lunchAnswer.question.options : [];
        return options
            .map((option, index) => ({
                id: option?.id ?? option?.value ?? option?.slug ?? option?.name,
                name: option?.name ?? option?.label ?? option?.id ?? '',
                sort: option?.sort ?? option?.order ?? null,
                show: option?.show,
                index,
            }))
            .filter((option) => option.id !== null && option.id !== undefined && option.name)
            .filter((option) => option.show !== 0)
            .sort((a, b) => {
                const aSort = a.sort ?? a.index;
                const bSort = b.sort ?? b.index;
                return aSort - bSort;
            });
    }, [lunchAnswer?.question?.options]);

    const resolveLunchValue = (value) => {
        if (!lunchOptions.length) return value ?? '';
        const byId = lunchOptions.find((option) => String(option.id) === String(value));
        if (byId) return byId.id;
        const byName = lunchOptions.find((option) => String(option.name) === String(value));
        return byName ? byName.id : (value ?? '');
    };

    const initialName = splitFullName(rawFullName);

    const [firstName, setFirstName] = useState(initialName.first);
    const [lastName, setLastName] = useState(initialName.last);
    const [emailAddress, setEmailAddress] = useState(rawEmail);
    const [notes, setNotes] = useState(manifest?.notes ?? '');
    const [lunchOption, setLunchOption] = useState(resolveLunchValue(lunchAnswer?.answer));
    const [attendanceStatus, setAttendanceStatus] = useState(manifest?.attendance_status ?? 'no_show');
    const [localAnswers, setLocalAnswers] = useState(resolvedAnswers);
    const [isSaving, setIsSaving] = useState(false);
    const [saveMessage, setSaveMessage] = useState('');

    useEffect(() => {
        const nameParts = splitFullName(rawFullName);
        setFirstName(nameParts.first);
        setLastName(nameParts.last);
        setEmailAddress(rawEmail);
        setNotes(manifest?.notes ?? '');
        setLunchOption(resolveLunchValue(lunchAnswer?.answer));
        setAttendanceStatus(manifest?.attendance_status ?? 'no_show');
        setLocalAnswers(resolvedAnswers);
        setSaveMessage('');
    }, [manifest, lunchAnswer?.answer, lunchOptions]);

    const customerId = customer?.id ?? manifest?.customer_id ?? null;
    const bookingItemId = manifest?.booking_item_id ?? null;
    const paxType = manifest?.type ?? 'adult';
    const combinedFullName = `${firstName} ${lastName}`.trim();
    const canSave = Boolean(bookingItemId) && (Boolean(customerId) || combinedFullName.length > 0);
    const canEdit = Boolean(bookingItemId);

    const handleSave = () => {
        if (!canSave || isSaving) return;

        const shouldCloseAfterSave = !customerId;
        setIsSaving(true);
        setSaveMessage('');

        router.put(
            route('admin.customer-manifests.status'),
            {
                booking_item_id: bookingItemId,
                customer_id: customerId,
                type: paxType,
                full_name: combinedFullName,
                email_address: emailAddress?.trim() ? emailAddress.trim() : null,
                attendance_status: attendanceStatus,
                notes,
                lunch_option: lunchOption,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    const lunchLabel = lunchOption ? resolveOptionLabel(lunchOption, lunchOptions) : '';
                    setLocalAnswers((prev) => {
                        let found = false;
                        const updated = prev.map((entry) => {
                            if (entry?.question?.slug !== 'lunch_option') {
                                return entry;
                            }
                            found = true;
                            return {
                                ...entry,
                                answer: lunchOption ?? '',
                                resolved_answer: lunchLabel,
                                question: {
                                    ...entry.question,
                                    options: lunchOptions,
                                },
                            };
                        });
                        if (!found && lunchOption) {
                            updated.push({
                                id: `local-lunch-${Date.now()}`,
                                answer: lunchOption,
                                resolved_answer: lunchLabel,
                                question: lunchAnswer?.question ?? {
                                    name: 'Lunch option',
                                    slug: 'lunch_option',
                                    options: lunchOptions,
                                },
                                sort_order: 999,
                            });
                        }
                        return updated;
                    });
                    setIsSaving(false);
                    setSaveMessage('Saved.');
                    if (shouldCloseAfterSave) {
                        onClose();
                    }
                },
                onError: () => {
                    setIsSaving(false);
                    setSaveMessage('Unable to save changes.');
                },
            }
        );
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-lg">
                <div className="flex items-start justify-between gap-4 border-b px-6 py-4">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900">Checkin Detail</h2>
                        <p className="text-sm text-gray-500">
                            {(combinedFullName || rawFullName || 'Guest')}{(emailAddress?.trim() || rawEmail) ? ` | ${emailAddress?.trim() || rawEmail}` : ''}{phone ? ` | ${phone}` : ''}
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="text-2xl leading-none text-gray-400 hover:text-gray-600"
                    >
                        &times;
                    </button>
                </div>

                <div className="max-h-[70vh] space-y-6 overflow-y-auto px-6 py-4">
                    <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        {localAnswers.length === 0 ? (
                            <div className="text-sm text-gray-600">
                                No check-in answers found for this guest.
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                {localAnswers.map((entry) => {
                                    const question = entry?.question || {};
                                    const isSignature = question?.slug === 'signature' || question?.type === 'signature';
                                    const answerValue = entry?.answer;
                                    const signaturePath = isSignature && answerValue
                                        ? (String(answerValue).includes('/') ? answerValue : `/storage/signatures/${answerValue}`)
                                        : null;
                                    const resolvedAnswer = entry?.resolved_answer;
                                    const showResolved = resolvedAnswer !== null && resolvedAnswer !== undefined && `${resolvedAnswer}` !== '';

                                    return (
                                        <div
                                            key={entry.id}
                                            className={`rounded-lg border border-gray-200 bg-white p-3 shadow-sm ${isSignature ? 'md:col-span-2' : ''}`}
                                        >
                                            <div className="text-xs font-semibold text-gray-600">
                                                {question?.name || question?.slug || 'Question'}
                                            </div>
                                            {question?.description && (
                                                <div className="mt-1 text-xs text-gray-400">{question.description}</div>
                                            )}
                                            <div className="mt-2 text-sm text-gray-900">
                                                {isSignature && signaturePath ? (
                                                    <div className="flex flex-col gap-2">
                                                        <span className="text-xs text-gray-500">Signature</span>
                                                        <img
                                                            src={signaturePath}
                                                            alt="Signature"
                                                            className="h-32 w-auto rounded-lg border border-gray-200 bg-white object-contain"
                                                        />
                                                    </div>
                                                ) : showResolved ? (
                                                    <span>{resolvedAnswer}</span>
                                                ) : (
                                                    <span>{formatAnswer(answerValue, question)}</span>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>

                    <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div className="flex items-center justify-between gap-4">
                            <h3 className="text-sm font-semibold text-gray-800">Update details</h3>
                            {saveMessage && (
                                <span className="text-xs text-gray-500">{saveMessage}</span>
                            )}
                        </div>
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <label className="flex flex-col gap-2 text-sm text-gray-700">
                                First name
                                <input
                                    type="text"
                                    value={firstName}
                                    onChange={(event) => setFirstName(event.target.value)}
                                    disabled={!canEdit || isSaving}
                                    className="h-10 rounded-md border border-gray-300 bg-white px-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-200"
                                />
                            </label>

                            <label className="flex flex-col gap-2 text-sm text-gray-700">
                                Last name
                                <input
                                    type="text"
                                    value={lastName}
                                    onChange={(event) => setLastName(event.target.value)}
                                    disabled={!canEdit || isSaving}
                                    className="h-10 rounded-md border border-gray-300 bg-white px-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-200"
                                />
                            </label>

                            <label className="flex flex-col gap-2 text-sm text-gray-700 md:col-span-2">
                                Email
                                <input
                                    type="email"
                                    value={emailAddress}
                                    onChange={(event) => setEmailAddress(event.target.value)}
                                    disabled={!canEdit || isSaving}
                                    className="h-10 rounded-md border border-gray-300 bg-white px-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-200"
                                />
                            </label>

                            <label className="flex flex-col gap-2 text-sm text-gray-700">
                                Lunch option
                                <select
                                    value={lunchOption ?? ''}
                                    onChange={(event) => setLunchOption(event.target.value)}
                                    disabled={!canEdit || isSaving || lunchOptions.length === 0}
                                    className="h-10 rounded-md border border-gray-300 bg-white px-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-200 disabled:cursor-not-allowed disabled:bg-gray-100"
                                >
                                    <option value="">
                                        {lunchOptions.length === 0 ? 'No lunch options configured' : 'Select an option'}
                                    </option>
                                    {lunchOptions.map((option) => (
                                        <option key={option.id} value={option.id}>
                                            {option.name}
                                        </option>
                                    ))}
                                </select>
                            </label>

                            <div className="flex flex-col gap-2 text-sm text-gray-700">
                                <span>Arrived</span>
                                <div className="flex flex-wrap gap-2">
                                    {STATUS_OPTIONS.map((option) => (
                                        <button
                                            key={option.value}
                                            type="button"
                                            disabled={!canEdit || isSaving}
                                            className={statusButtonClass(option.value, attendanceStatus === option.value, !canEdit || isSaving)}
                                            onClick={() => setAttendanceStatus(option.value)}
                                        >
                                            {option.label}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <label className="flex flex-col gap-2 text-sm text-gray-700 md:col-span-2">
                                Notes
                                <textarea
                                    value={notes}
                                    onChange={(event) => setNotes(event.target.value)}
                                    disabled={!canEdit || isSaving}
                                    rows={3}
                                    className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-200 disabled:cursor-not-allowed disabled:bg-gray-100"
                                />
                            </label>
                        </div>
                        <div className="mt-4 flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={onClose}
                                className="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                Close
                            </button>
                            <button
                                type="button"
                                onClick={handleSave}
                                disabled={!canSave || isSaving}
                                className="rounded-lg border border-blue-500 bg-blue-500 px-4 py-2 text-sm text-white hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {isSaving ? 'Saving...' : 'Save'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
