import React from 'react';

const money = (value) => `R${Number(value || 0).toFixed(2)}`;

export default function BookingRateBreakdown({ breakdown, compact = false }) {
    if (!breakdown || !Array.isArray(breakdown.lines) || breakdown.lines.length === 0) {
        return null;
    }

    const sourceTone = {
        promotion: 'bg-emerald-50 text-emerald-700 border-emerald-100',
        discount: 'bg-amber-50 text-amber-700 border-amber-100',
        wholesaler: 'bg-sky-50 text-sky-700 border-sky-100',
        rack: 'bg-gray-50 text-gray-700 border-gray-100',
    }[breakdown.source_type] || 'bg-gray-50 text-gray-700 border-gray-100';

    return (
        <div className={`rounded-xl border border-gray-200 bg-white ${compact ? 'p-2 text-xs' : 'p-4 text-sm'}`}>
            <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                <div className="font-semibold text-gray-800">Rate breakdown</div>
                <span className={`inline-flex rounded-full border px-2 py-0.5 text-xs font-medium ${sourceTone}`}>
                    {breakdown.source_label || 'Rack rate'}
                </span>
            </div>

            <div className="space-y-1">
                {breakdown.lines.map((line, index) => (
                    <div key={`${line.type}-${index}`} className="flex items-start justify-between gap-3 border-b border-gray-100 pb-1 last:border-0 last:pb-0">
                        <div>
                            <div className="font-medium text-gray-800">
                                {line.label} x {line.count}
                            </div>
                            <div className="text-gray-500">
                                {line.adjustment_label ? (
                                    <>
                                        {line.adjustment_label}: {money(line.base_rate)} to {money(line.final_rate)}
                                    </>
                                ) : (
                                    <>Rate: {money(line.rate)}</>
                                )}
                            </div>
                        </div>
                        <div className="text-right font-semibold text-gray-900">{money(line.total)}</div>
                    </div>
                ))}
            </div>

            <div className="mt-2 flex items-center justify-between border-t border-gray-200 pt-2 font-semibold text-gray-900">
                <span>Booking total</span>
                <span>{money(breakdown.booking_total)}</span>
            </div>
        </div>
    );
}
