import React, { useEffect, useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';
import {
    addMonths,
    eachDayOfInterval,
    endOfMonth,
    format,
    getDay,
    parseISO,
    startOfMonth,
    subMonths,
} from 'date-fns';
import RichTextDisplay from '@/Components/RichTextDisplay';
import './booking-v2.css';

const money = (value) => `R${new Intl.NumberFormat('en-ZA', {
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

function ChevronIcon({ direction }) {
    const path = direction === 'left' ? 'M11 4 6 9l5 5' : 'M7 4l5 5-5 5';

    return (
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
            <path d={path} stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function TagIcon() {
    return (
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="M8.6 2H14v5.4L7.4 14 2 8.6 8.6 2z" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round" />
            <circle cx="11" cy="5" r="1" fill="currentColor" />
        </svg>
    );
}

function subtractMinutes(time, minutes) {
    if (!time || !minutes) return null;

    const [hours, minute] = time.split(':').map(Number);
    const totalMinutes = ((hours * 60) + minute - minutes + 1440) % 1440;

    return `${String(Math.floor(totalMinutes / 60)).padStart(2, '0')}:${String(totalMinutes % 60).padStart(2, '0')}`;
}

function Stepper({ label, value, onChange, maximum }) {
    return (
        <div className="kct-pax-control">
            <span>{label}</span>
            <div className="kct-stepper" role="group" aria-label={label}>
                <button type="button" onClick={() => onChange(Math.max(0, value - 1))} aria-label={`Fewer ${label.toLowerCase()}`}><MinusIcon /></button>
                <strong aria-live="polite">{value}</strong>
                <button type="button" onClick={() => onChange(Math.min(maximum, value + 1))} aria-label={`More ${label.toLowerCase()}`}><PlusIcon /></button>
            </div>
        </div>
    );
}

function MobilePaxRow({ label, detail, value, onChange, maximum }) {
    return (
        <div className="kct-mobile-pax-row">
            <div>
                <strong>{label}</strong>
                <span>{detail}</span>
            </div>
            <div className="kct-stepper" role="group" aria-label={label}>
                <button type="button" onClick={() => onChange(Math.max(0, value - 1))} aria-label={`Fewer ${label.toLowerCase()}`}><MinusIcon /></button>
                <strong aria-live="polite">{value}</strong>
                <button type="button" onClick={() => onChange(Math.min(maximum, value + 1))} aria-label={`More ${label.toLowerCase()}`}><PlusIcon /></button>
            </div>
        </div>
    );
}

function CalendarMonth({ month, availability, selectedDate, onSelect, compact = false, hideHeading = false }) {
    const days = eachDayOfInterval({ start: startOfMonth(month), end: endOfMonth(month) });
    const blanks = Array.from({ length: mondayOffset(days[0]) });

    return (
        <div className={`kct-month ${compact ? 'is-compact' : ''}`}>
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
                        return <span className="kct-calendar-day kct-calendar-day--closed" key={key}>{format(day, 'd')}</span>;
                    }

                    return (
                        <button
                            type="button"
                            key={key}
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

function InfoCard({ title, content, moreLabel = null }) {
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

function GiftIcon() {
    return (
        <svg width="22" height="22" viewBox="0 0 22 22" fill="none" aria-hidden="true">
            <rect x="3" y="8" width="16" height="11" rx="2" stroke="currentColor" strokeWidth="1.6" />
            <path d="M2 8h18M11 8v11" stroke="currentColor" strokeWidth="1.6" />
            <path d="M11 8C9.5 4.5 6 4.3 6 6.3S9 8 11 8zm0 0c1.5-3.5 5-3.7 5-1.7S13 8 11 8z" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" />
        </svg>
    );
}

export default function Book({ team, services = [], selectedServiceId }) {
    const [serviceId, setServiceId] = useState(Number(selectedServiceId || services[0]?.id || 0));
    const service = useMemo(
        () => services.find((item) => Number(item.id) === Number(serviceId)) || services[0] || null,
        [serviceId, services],
    );
    const [adults, setAdults] = useState(2);
    const [children, setChildren] = useState(0);
    const [viewMonth, setViewMonth] = useState(() => startOfMonth(parseISO(service?.initial_date || format(new Date(), 'yyyy-MM-dd'))));
    const [availability, setAvailability] = useState({});
    const [slotsByDate, setSlotsByDate] = useState({});
    const [selectedDate, setSelectedDate] = useState(null);
    const [selectedSlotId, setSelectedSlotId] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [showMobileDetails, setShowMobileDetails] = useState(false);

    useEffect(() => {
        if (!service) return;
        setViewMonth(startOfMonth(parseISO(service.initial_date)));
        setSelectedDate(null);
        setSelectedSlotId(null);
    }, [service?.id]);

    useEffect(() => {
        if (!service) return undefined;

        const controller = new AbortController();
        const start = startOfMonth(viewMonth);
        const end = endOfMonth(addMonths(viewMonth, 1));
        setLoading(true);
        setError('');

        axios.get(route('front.v2.services.availability', { service: service.id }), {
            params: {
                start_date: dateKey(start),
                end_date: dateKey(end),
                adults,
                children,
            },
            signal: controller.signal,
        }).then(({ data }) => {
            const days = Object.fromEntries((data.days || []).map((day) => [day.date, day]));
            setAvailability(days);
            setSlotsByDate(data.slots_by_date || {});

            if (!selectedDate || !days[selectedDate]?.is_open) {
                const firstOpen = (data.days || []).find((day) => day.is_open)?.date || null;
                setSelectedDate(firstOpen);
                setSelectedSlotId(null);
            }
        }).catch((requestError) => {
            if (requestError.code !== 'ERR_CANCELED') {
                setAvailability({});
                setSlotsByDate({});
                setError(requestError.response?.data?.message || 'Availability could not be loaded. Please try again.');
            }
        }).finally(() => {
            if (!controller.signal.aborted) setLoading(false);
        });

        return () => controller.abort();
    }, [service?.id, viewMonth, adults, children]);

    const slots = selectedDate ? (slotsByDate[selectedDate] || []) : [];
    const selectedSlot = slots.find((slot) => Number(slot.slot_id) === Number(selectedSlotId)) || null;
    const total = selectedSlot
        ? (adults * Number(selectedSlot.adult_price)) + (children * Number(selectedSlot.child_price))
        : 0;
    const heroImage = service?.image || team?.file_image?.path;
    const serviceCapacity = Number(service?.capacity || 0);
    const arrivalMinutes = Number(service?.arrival_minutes || 0);
    const guestSummary = [
        adults > 0 ? `${adults} ${adults === 1 ? 'adult' : 'adults'}` : null,
        children > 0 ? `${children} ${children === 1 ? 'child' : 'children'}` : null,
    ].filter(Boolean).join(', ');
    const tourSummary = selectedDate && selectedSlot
        ? `${format(parseISO(selectedDate), 'EEE d MMMM')} \u00b7 ${selectedSlot.time.slice(0, 5)} \u00b7 ${guestSummary}`
        : 'Select a date and time';
    const selectDate = (date) => {
        setSelectedDate(date);
        setSelectedSlotId(null);
    };
    const logoFallback = '/images/kct-booking-v2/logo-karkloof.png';
    const heroFallback = '/images/kct-booking-v2/hero-zipline.jpg';
    const childMinimumAge = service.age_label?.match(/\d+/)?.[0] || null;

    if (!service) {
        return (
            <main className="kct-empty-page">
                <Head title="Book" />
                <h1>No online activities are available.</h1>
            </main>
        );
    }

    return (
        <div className="kct-book-page">
            <Head title={`Book ${service.name}`} />
            <div className="kct-app-shell">
                <header className="kct-header">
                    <a href={route('front.v2.welcome', { team: team.id })} aria-label={`${team.name} booking home`}>
                        <img
                            src={team?.file_logo?.path || logoFallback}
                            onError={(event) => { event.currentTarget.src = logoFallback; }}
                            alt={team.name}
                        />
                    </a>
                    <div className="kct-header-actions">
                        {services.length > 1 && (
                            <label className="kct-service-select">
                                <span>Activity</span>
                                <select value={service.id} onChange={(event) => setServiceId(Number(event.target.value))}>
                                    {services.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                                </select>
                            </label>
                        )}
                        {service.gift_activity_id && (
                            <a className="kct-voucher-button" href={route('front.voucher', { activity_id: service.gift_activity_id })}>
                                <GiftIcon />
                                <span className="kct-voucher-label-desktop">Buy a Gift Voucher</span>
                                <span className="kct-voucher-label-mobile">Gift voucher</span>
                            </a>
                        )}
                    </div>
                </header>

                <main className="kct-main kct-desktop-book">
                    <section className="kct-hero">
                        <img
                            src={heroImage || heroFallback}
                            onError={(event) => { event.currentTarget.src = heroFallback; }}
                            alt="Guest ziplining through the forest canopy"
                        />
                        <div className="kct-hero-shade" />
                        <div className="kct-hero-copy">
                            <h1>{services.length === 1 ? team.name : service.name}</h1>
                            {service.hero_summary && <p className="kct-hero-summary">{service.hero_summary}</p>}
                            <div className="kct-pills">
                                {service.from_price !== null && <span>From {money(service.from_price)} pp</span>}
                                {service.age_label && <span>{service.age_label}</span>}
                                {service.arrival_label && <span>{service.arrival_label}</span>}
                            </div>
                        </div>
                    </section>

                    <section className="kct-booking-card" aria-label="Book your tour">
                        <div className="kct-booking-heading">
                            <h2>Pick a date and time</h2>
                            <div className="kct-pax-controls">
                                <Stepper label="Adults" value={adults} onChange={(value) => { setAdults(value); setSelectedSlotId(null); }} maximum={Math.max(0, serviceCapacity - children)} />
                                <Stepper label="Children" value={children} onChange={(value) => { setChildren(value); setSelectedSlotId(null); }} maximum={Math.max(0, serviceCapacity - adults)} />
                            </div>
                        </div>

                        <div className="kct-booking-layout">
                            <div className="kct-calendar-panel">
                                <div className="kct-calendar-nav">
                                    <button type="button" onClick={() => setViewMonth((month) => subMonths(month, 1))} aria-label="Previous months"><ChevronIcon direction="left" /></button>
                                    <span>{loading ? 'Checking availability...' : 'Prices shown per person'}</span>
                                    <button type="button" onClick={() => setViewMonth((month) => addMonths(month, 1))} aria-label="Next months"><ChevronIcon direction="right" /></button>
                                </div>
                                {error && <p className="kct-error" role="alert">{error}</p>}
                                <div className="kct-months">
                                    <CalendarMonth month={viewMonth} availability={availability} selectedDate={selectedDate} onSelect={selectDate} />
                                    <CalendarMonth month={addMonths(viewMonth, 1)} availability={availability} selectedDate={selectedDate} onSelect={selectDate} />
                                </div>
                            </div>

                            <aside className="kct-times-panel">
                                <div>
                                    <h3>{selectedDate ? format(parseISO(selectedDate), 'EEEE d MMMM') : 'Choose an available date'}</h3>
                                    <span>{selectedDate ? `${slots.length} ${slots.length === 1 ? 'time' : 'times'} available` : 'Available times will appear here'}</span>
                                </div>
                                <div className="kct-times-scroll">
                                    {slots.map((slot) => {
                                        const selected = Number(slot.slot_id) === Number(selectedSlotId);
                                        const arriveBy = subtractMinutes(slot.time, arrivalMinutes);
                                        const availabilityLabel = `${slot.spaces_remaining} ${Number(slot.spaces_remaining) === 1 ? 'space' : 'spaces'}${Number(slot.spaces_remaining) <= 3 ? ' left' : ''}`;
                                        return (
                                            <button
                                                type="button"
                                                key={`${slot.season_id}-${slot.slot_id}`}
                                                className={`kct-time-option ${selected ? 'is-selected' : ''}`}
                                                onClick={() => setSelectedSlotId(slot.slot_id)}
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
                                    })}
                                    {selectedDate && !loading && slots.length === 0 && <p className="kct-times-empty">No times are available for this date.</p>}
                                </div>
                            </aside>
                        </div>
                    </section>

                    <section className="kct-info-grid">
                        <InfoCard title="Before you go" content={service.requirements || service.arrangements} />
                        <InfoCard title="What's included" content={service.inclusions} />
                        <InfoCard title="Cancellation" content={service.condition_text} moreLabel="All conditions" />
                    </section>
                </main>

                <main className="kct-mobile-book">
                    <section className="kct-mobile-hero">
                        <img
                            src={heroImage || heroFallback}
                            onError={(event) => { event.currentTarget.src = heroFallback; }}
                            alt="Guest ziplining through the forest canopy"
                        />
                        <div className="kct-mobile-hero-shade" />
                        <div className="kct-mobile-hero-copy">
                            <h1>{services.length === 1 ? team.name : service.name}</h1>
                            <p>{[
                                service.from_price !== null ? `From ${money(service.from_price)} pp` : null,
                                service.age_label,
                                service.arrival_label,
                            ].filter(Boolean).join(' \u00b7 ')}</p>
                        </div>
                    </section>

                    <div className="kct-mobile-content">
                        <section className="kct-mobile-card kct-mobile-date-card">
                            <div className="kct-mobile-card-heading">
                                <h2>Choose a date</h2>
                                <div className="kct-mobile-month-nav">
                                    <button type="button" onClick={() => setViewMonth((month) => subMonths(month, 1))} aria-label="Previous month"><ChevronIcon direction="left" /></button>
                                    <span>{format(viewMonth, 'MMM yyyy')}</span>
                                    <button type="button" onClick={() => setViewMonth((month) => addMonths(month, 1))} aria-label="Next month"><ChevronIcon direction="right" /></button>
                                </div>
                            </div>
                            {error && <p className="kct-error" role="alert">{error}</p>}
                            <CalendarMonth month={viewMonth} availability={availability} selectedDate={selectedDate} onSelect={selectDate} compact hideHeading />
                        </section>

                        <section className="kct-mobile-card kct-mobile-time-card">
                            <h2>Choose a time {selectedDate && <span>\u00b7 {format(parseISO(selectedDate), 'EEE d MMM')}</span>}</h2>
                            <div className="kct-mobile-time-grid" role="group" aria-label={selectedDate ? `Times on ${format(parseISO(selectedDate), 'EEEE d MMMM')}` : 'Available times'}>
                                {slots.map((slot) => {
                                    const selected = Number(slot.slot_id) === Number(selectedSlotId);
                                    return (
                                        <button
                                            type="button"
                                            key={`mobile-${slot.season_id}-${slot.slot_id}`}
                                            className={selected ? 'is-selected' : ''}
                                            onClick={() => setSelectedSlotId(slot.slot_id)}
                                            aria-pressed={selected}
                                        >
                                            {slot.time.slice(0, 5)}
                                        </button>
                                    );
                                })}
                                {selectedDate && !loading && slots.length === 0 && <p className="kct-times-empty">No tours on this date. Try another date.</p>}
                            </div>
                        </section>

                        <section className="kct-mobile-card kct-mobile-pax-card">
                            <h2>Who's coming?</h2>
                            <MobilePaxRow label="Adults" detail={`${money(selectedSlot?.adult_price || service.from_price)} per person`} value={adults} onChange={(value) => { setAdults(value); setSelectedSlotId(null); }} maximum={Math.max(0, serviceCapacity - children)} />
                            <MobilePaxRow label="Children" detail={childMinimumAge ? `${childMinimumAge} years and older` : 'Children'} value={children} onChange={(value) => { setChildren(value); setSelectedSlotId(null); }} maximum={Math.max(0, serviceCapacity - adults)} />
                        </section>

                        <button type="button" className="kct-mobile-details-trigger" onClick={() => setShowMobileDetails((value) => !value)}>
                            Tour details &amp; conditions <ChevronIcon direction={showMobileDetails ? 'left' : 'right'} />
                        </button>
                        {showMobileDetails && (
                            <section className="kct-mobile-details">
                                <InfoCard title="Before you go" content={service.requirements || service.arrangements} />
                                <InfoCard title="What's included" content={service.inclusions} />
                                <InfoCard title="Cancellation" content={service.condition_text} moreLabel="All conditions" />
                            </section>
                        )}
                    </div>
                </main>

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
                        <button type="button" disabled={!selectedSlot || adults + children === 0}>Continue</button>
                    </div>
                </footer>
            </div>
        </div>
    );
}
