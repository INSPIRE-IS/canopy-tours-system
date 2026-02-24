import React from 'react';

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
    if (parsed === null || parsed === undefined || parsed === '') return '—';

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

export default function CheckInAnswersModal({ isOpen, onClose, manifest, answers }) {
    if (!isOpen) return null;

    const customer = manifest?.customer ?? manifest ?? {};
    const fullName = customer?.full_name || customer?.name || 'Guest';
    const email = customer?.email_address || manifest?.email_address || '';
    const phone = customer?.contact_number || '';
    const resolvedAnswers = Array.isArray(answers) ? answers : [];

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-lg">
                <div className="flex items-start justify-between gap-4 border-b px-6 py-4">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900">Check-in answers</h2>
                        <p className="text-sm text-gray-500">
                            {fullName}{email ? ` • ${email}` : ''}{phone ? ` • ${phone}` : ''}
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

                <div className="max-h-[70vh] overflow-y-auto px-6 py-4">
                    {resolvedAnswers.length === 0 ? (
                        <div className="rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                            No check-in answers found for this guest.
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {resolvedAnswers.map((entry) => {
                                const question = entry?.question || {};
                                const isSignature = question?.slug === 'signature' || question?.type === 'signature';
                                const answerValue = entry?.answer;
                                const signaturePath = isSignature && answerValue
                                    ? (String(answerValue).includes('/') ? answerValue : `/storage/signatures/${answerValue}`)
                                    : null;

                                const resolvedAnswer = entry?.resolved_answer;
                                const showResolved = resolvedAnswer !== null && resolvedAnswer !== undefined && `${resolvedAnswer}` !== '';
                                return (
                                    <div key={entry.id} className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                        <div className="text-sm font-semibold text-gray-900">
                                            {question?.name || question?.slug || 'Question'}
                                        </div>
                                        {question?.description && (
                                            <div className="mt-1 text-xs text-gray-500">{question.description}</div>
                                        )}
                                        <div className="mt-3 text-sm text-gray-700">
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

                <div className="flex justify-end border-t px-6 py-4">
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    );
}
