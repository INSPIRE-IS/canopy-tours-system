import React, { useState } from 'react';
import {
    eachDayOfInterval,
    endOfMonth,
    format,
    getDay,
    startOfMonth,
} from 'date-fns';
import RichTextDisplay from '@/Components/RichTextDisplay';

export const money = (value) => `R${new Intl.NumberFormat('en-ZA', {
    maximumFractionDigits: 0,
    minimumFractionDigits: 0,
}).format(Number(value || 0))}`;

const dateKey = (date) => format(date, 'yyyy-MM-dd');
const mondayOffset = (date) => (getDay(date) + 6) % 7;

function MinusIcon() {
    return (
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="M3.5 8h9" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
        </svg>
    );
}

function PlusIcon() {
    return (
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="M3.5 8h9M8 3.5v9" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
        </svg>
    );
}

export function ChevronIcon({ direction }) {
    const path = direction === 'left' ? 'M11 4 6 9l5 5' : 'M7 4l5 5-5 5';

    return (
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
            <path d={path} stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

export function TagIcon() {
    return (
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="M8.6 2H14v5.4L7.4 14 2 8.6 8.6 2z" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round" />
            <circle cx="11" cy="5" r="1" fill="currentColor" />
        </svg>
    );
}

export function GiftIcon() {
    return (
        <svg width="22" height="22" viewBox="0 0 22 22" fill="none" aria-hidden="true">
            <rect x="3" y="8" width="16" height="11" rx="2" stroke="currentColor" strokeWidth="1.6" />
            <path d="M2 8h18M11 8v11" stroke="currentColor" strokeWidth="1.6" />
            <path d="M11 8C9.5 4.5 6 4.3 6 6.3S9 8 11 8zm0 0c1.5-3.5 5-3.7 5-1.7S13 8 11 8z" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" />
        </svg>
    );
}

export function Stepper({ label, value, onChange, maximum }) {
    const canDecrease = value > 0;
    const canIncrease = value < maximum;

    return (
        <div className="kct-pax-control">
            <span>{label}</span>
            <div className="kct-stepper" role="group" aria-label={label}>
                <button type="button" disabled={!canDecrease} onClick={() => onChange(value - 1)} aria-label={`Fewer ${label.toLowerCase()}`}><MinusIcon /></button>
                <strong aria-live="polite">{value}</strong>
                <button type="button" disabled={!canIncrease} onClick={() => onChange(value + 1)} aria-label={`More ${label.toLowerCase()}`}><PlusIcon /></button>
            </div>
        </div>
    );
}

export function MobilePaxRow({ label, detail, value, onChange, maximum }) {
    const canDecrease = value > 0;
    const canIncrease = value < maximum;

    return (
        <div className="kct-mobile-pax-row">
            <div>
                <strong>{label}</strong>
                <span>{detail}</span>
            </div>
            <div className="kct-stepper" role="group" aria-label={label}>
                <button type="button" disabled={!canDecrease} onClick={() => onChange(value - 1)} aria-label={`Fewer ${label.toLowerCase()}`}><MinusIcon /></button>
                <strong aria-live="polite">{value}</strong>
                <button type="button" disabled={!canIncrease} onClick={() => onChange(value + 1)} aria-label={`More ${label.toLowerCase()}`}><PlusIcon /></button>
            </div>
        </div>
    );
}

export function CalendarMonth({ month, availability, selectedDate, onSelect, compact = false, hideHeading = false, loading = false, disabled = false }) {
    const days = eachDayOfInterval({ start: startOfMonth(month), end: endOfMonth(month) });
    const blanks = Array.from({ length: mondayOffset(days[0]) });

    return (
        <div className={`kct-month ${compact ? 'is-compact' : ''} ${loading ? 'is-loading' : ''}`} aria-busy={loading}>
            {!hideHeading && <h3>{format(month, 'MMMM yyyy')}</h3>}
            <div className="kct-calendar-grid kct-calendar-weekdays" aria-hidden="true">
                {['M', 'T', 'W', 'T', 'F', 'S', 'S'].map((day, index) => <span key={`${day}-${index}`}>{day}</span>)}
            </div>
            <div className="kct-calendar-grid">
                {blanks.map((_, index) => <span key={`blank-${index}`} />)}
                {days.map((day) => {
                    const key = dateKey(day);
                    const info = availability[key];
                    const selected = selectedDate === key;

                    if (!info?.is_open) {
                        if (info?.status === 'full') {
                            return (
                                <button
                                    type="button"
                                    disabled
                                    className="kct-calendar-day kct-calendar-day--full"
                                    key={key}
                                    aria-label={`${format(day, 'd MMMM yyyy')}, sold out`}
                                >
                                    <strong>{format(day, 'd')}</strong>
                                    <small>Full</small>
                                </button>
                            );
                        }

                        return (
                            <button
                                type="button"
                                disabled
                                className="kct-calendar-day kct-calendar-day--closed"
                                key={key}
                                aria-label={`${format(day, 'd MMMM yyyy')}, unavailable`}
                            >
                                {format(day, 'd')}
                            </button>
                        );
                    }

                    return (
                        <button
                            type="button"
                            key={key}
                            disabled={disabled}
                            className={`kct-calendar-day kct-calendar-day--open ${selected ? 'is-selected' : ''}`}
                            onClick={() => onSelect(key)}
                            aria-pressed={selected}
                            aria-label={`${format(day, 'd MMMM yyyy')}, from ${money(info.from_price)}`}
                        >
                            <strong>{format(day, 'd')}</strong>
                            {!compact && <small>{money(info.from_price)}</small>}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

export function InfoCard({ title, content, moreLabel = null }) {
    const [expanded, setExpanded] = useState(false);

    return (
        <article className="kct-info-card">
            <h3>{title}</h3>
            <div className={`kct-info-content ${moreLabel && !expanded ? 'is-clamped' : ''}`}>
                {content && <RichTextDisplay content={content} />}
            </div>
            {moreLabel && content && (
                <button type="button" className="kct-info-more" onClick={() => setExpanded((value) => !value)}>
                    {expanded ? 'Show less' : moreLabel}
                </button>
            )}
        </article>
    );
}

function subtractMinutes(time, minutes) {
    if (!time || !minutes) return null;

    const [hours, minute] = time.split(':').map(Number);
    const totalMinutes = ((hours * 60) + minute - minutes + 1440) % 1440;

    return `${String(Math.floor(totalMinutes / 60)).padStart(2, '0')}:${String(totalMinutes % 60).padStart(2, '0')}`;
}

export function TimeOptions({ slots, selectedSlotId, onSelect, arrivalMinutes, mobile = false, disabled = false }) {
    if (mobile) {
        return slots.map((slot) => {
            const selected = Number(slot.slot_id) === Number(selectedSlotId);

            return (
                <button
                    type="button"
                    key={`mobile-${slot.season_id}-${slot.slot_id}`}
                    disabled={disabled}
                    className={selected ? 'is-selected' : ''}
                    onClick={() => onSelect(slot.slot_id)}
                    aria-pressed={selected}
                >
                    {slot.time.slice(0, 5)}
                </button>
            );
        });
    }

    return slots.map((slot) => {
        const selected = Number(slot.slot_id) === Number(selectedSlotId);
        const arriveBy = slot.arrival_by || subtractMinutes(slot.time, arrivalMinutes);
        const availabilityLabel = `${slot.spaces_remaining} ${Number(slot.spaces_remaining) === 1 ? 'space' : 'spaces'}${Number(slot.spaces_remaining) <= 3 ? ' left' : ''}`;

        return (
            <button
                type="button"
                key={`${slot.season_id}-${slot.slot_id}`}
                disabled={disabled}
                className={`kct-time-option ${selected ? 'is-selected' : ''}`}
                onClick={() => onSelect(slot.slot_id)}
                aria-pressed={selected}
            >
                <span>
                    <strong>{slot.time.slice(0, 5)}</strong>
                    <small className={Number(slot.spaces_remaining) <= 3 ? 'is-warning' : ''}>
                        {availabilityLabel}{arriveBy ? ` \u00b7 arrive by ${arriveBy}` : ''}
                    </small>
                </span>
                <b>{selected ? 'Selected' : 'Select'}</b>
            </button>
        );
    });
}

export function BookingSummary({ tourSummary, total, canContinue, onContinue }) {
    return (
        <footer className="kct-sticky-summary">
            <div className="kct-summary-selection">
                <div>
                    <small>Your tour</small>
                    <strong>{tourSummary}</strong>
                </div>
                <button type="button" className="kct-promo-trigger"><TagIcon />Add promo code</button>
            </div>
            <div className="kct-mobile-summary-copy">
                <strong>{money(total)}</strong>
                <small>{tourSummary}</small>
            </div>
            <div className="kct-summary-total">
                <span><small>Total</small><strong>{money(total)}</strong></span>
                <button type="button" disabled={!canContinue} onClick={onContinue}>Continue</button>
            </div>
        </footer>
    );
}

export function AvailabilityFeedback({ error, notice, onRetry }) {
    return (
        <>
            {error && (
                <div className="kct-availability-error" role="alert">
                    <span>{error}</span>
                    <button type="button" onClick={onRetry}>Try again</button>
                </div>
            )}
            {!error && notice && <p className="kct-selection-notice" role="status">{notice}</p>}
        </>
    );
}
