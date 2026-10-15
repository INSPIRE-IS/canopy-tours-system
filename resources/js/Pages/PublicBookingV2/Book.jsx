import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useToast } from '@/Context/ToastContext';
import {
    addMonths,
    endOfMonth,
    format,
    parseISO,
    startOfMonth,
    subMonths,
} from 'date-fns';
import {
    AvailabilityFeedback,
    BookingSummary,
    CalendarMonth,
    ChevronIcon,
    ConditionsModal,
    GiftIcon,
    InfoCard,
    MobilePaxRow,
    money,
    Stepper,
    TimeOptions,
} from './BookingComponents';
import {
    availabilityState,
    calculateBookingTotal,
    findFirstOpenDate,
    findSelectedSlot,
    hasSelectedGuests,
} from './bookingState';
import SitePickerModal from './SitePickerModal';
import './booking-v2.css';

const dateKey = (date) => format(date, 'yyyy-MM-dd');
// Switch to true only after the client approves persistent inline reset copy.
const SHOW_INLINE_SELECTION_NOTICE = false;

export default function Book({ team, teams = [], services = [], selectedServiceId, notice = null }) {
    const { showToast } = useToast();
    const [serviceId, setServiceId] = useState(Number(selectedServiceId || services[0]?.id || 0));
    const service = useMemo(
        () => services.find((item) => Number(item.id) === Number(serviceId)) || services[0] || null,
        [serviceId, services],
    );
    const [adults, setAdults] = useState(2);
    const [children, setChildren] = useState(0);
    const [availabilityPax, setAvailabilityPax] = useState({ adults: 2, children: 0 });
    const [viewMonth, setViewMonth] = useState(() => startOfMonth(parseISO(service?.initial_date || format(new Date(), 'yyyy-MM-dd'))));
    const [availability, setAvailability] = useState({});
    const [slotsByDate, setSlotsByDate] = useState({});
    const [selectedDate, setSelectedDate] = useState(null);
    const [selectedSlotId, setSelectedSlotId] = useState(null);
    const [loading, setLoading] = useState(Boolean(service));
    const [refreshing, setRefreshing] = useState(false);
    const [error, setError] = useState('');
    const [selectionNotice, setSelectionNotice] = useState('');
    const [availabilityRequestVersion, setAvailabilityRequestVersion] = useState(0);
    const [showMobileDetails, setShowMobileDetails] = useState(false);
    const [showSitePicker, setShowSitePicker] = useState(false);
    const [showConditions, setShowConditions] = useState(false);
    const availabilityCache = useRef(null);
    const shownNotice = useRef(null);
    const availabilityKey = `${service?.id || 0}:${availabilityPax.adults}:${availabilityPax.children}`;

    useEffect(() => {
        if (!notice || shownNotice.current === notice) return;
        shownNotice.current = notice;
        showToast(notice, 'warning', 7000);
    }, [notice, showToast]);

    useEffect(() => {
        if (!service) return;
        setViewMonth(startOfMonth(parseISO(service.initial_date)));
        setSelectedDate(null);
        setSelectedSlotId(null);
        setSelectionNotice('');
    }, [service?.id]);

    useEffect(() => {
        if (adults === availabilityPax.adults && children === availabilityPax.children) return undefined;

        const timeout = setTimeout(() => {
            setAvailabilityPax({ adults, children });
        }, 250);

        return () => clearTimeout(timeout);
    }, [adults, children, availabilityPax.adults, availabilityPax.children]);

    useEffect(() => {
        const previousCache = availabilityCache.current;
        previousCache?.controllers.forEach((controller) => controller.abort());
        const keepDisplayedAvailability = previousCache?.serviceId === service?.id
            && Object.keys(availability).length > 0;

        const cache = {
            key: availabilityKey,
            serviceId: service?.id,
            days: {},
            slotsByDate: {},
            loadedMonths: new Set(),
            pendingMonths: new Map(),
            controllers: new Set(),
            staleDisplay: keepDisplayedAvailability,
        };

        availabilityCache.current = cache;
        if (!keepDisplayedAvailability) {
            setAvailability({});
            setSlotsByDate({});
        }
        setLoading(!keepDisplayedAvailability);
        setRefreshing(keepDisplayedAvailability);
        setError('');

        return () => {
            if (availabilityCache.current === cache) {
                cache.controllers.forEach((controller) => controller.abort());
            }
        };
    }, [availabilityKey]);

    useEffect(() => {
        if (!service) return undefined;

        const cache = availabilityCache.current;
        if (!cache || cache.key !== availabilityKey) return undefined;

        let viewChanged = false;
        const visibleMonths = [startOfMonth(viewMonth), startOfMonth(addMonths(viewMonth, 1))];
        const lookAheadMonths = [startOfMonth(addMonths(viewMonth, 2)), startOfMonth(addMonths(viewMonth, 3))];
        const visibleStart = dateKey(visibleMonths[0]);
        const visibleEnd = dateKey(endOfMonth(visibleMonths[1]));
        const primaryMonthEnd = dateKey(endOfMonth(visibleMonths[0]));

        const ensureMonths = async (months) => {
            const monthKeys = months.map((month) => format(month, 'yyyy-MM'));
            const pending = [...new Set(monthKeys
                .map((key) => cache.pendingMonths.get(key))
                .filter(Boolean))];

            if (pending.length > 0) {
                await Promise.all(pending);
            }

            const missingMonths = months.filter((month) => {
                const key = format(month, 'yyyy-MM');
                return !cache.loadedMonths.has(key) && !cache.pendingMonths.has(key);
            });

            if (missingMonths.length === 0) return;

            const start = startOfMonth(missingMonths[0]);
            const end = endOfMonth(missingMonths[missingMonths.length - 1]);
            const missingKeys = missingMonths.map((month) => format(month, 'yyyy-MM'));
            const controller = new AbortController();
            cache.controllers.add(controller);

            let request;
            request = axios.get(route('front.v2.services.availability', { service: service.id }), {
                params: {
                    start_date: dateKey(start),
                    end_date: dateKey(end),
                    adults: availabilityPax.adults,
                    children: availabilityPax.children,
                },
                signal: controller.signal,
            }).then(({ data }) => {
                if (availabilityCache.current !== cache || controller.signal.aborted) return;

                Object.assign(cache.days, Object.fromEntries((data.days || []).map((day) => [day.date, day])));
                Object.assign(cache.slotsByDate, data.slots_by_date || {});
                missingKeys.forEach((key) => cache.loadedMonths.add(key));
                setAvailability({ ...cache.days });
                setSlotsByDate({ ...cache.slotsByDate });
            }).finally(() => {
                cache.controllers.delete(controller);
                missingKeys.forEach((key) => {
                    if (cache.pendingMonths.get(key) === request) {
                        cache.pendingMonths.delete(key);
                    }
                });
            });

            missingKeys.forEach((key) => cache.pendingMonths.set(key, request));
            await request;
        };

        const visibleAlreadyLoaded = visibleMonths.every((month) => cache.loadedMonths.has(format(month, 'yyyy-MM')));
        setLoading(!visibleAlreadyLoaded && !cache.staleDisplay);
        setRefreshing(!visibleAlreadyLoaded && cache.staleDisplay);
        setError('');

        ensureMonths(visibleMonths).then(() => {
            if (viewChanged || availabilityCache.current !== cache) return;

            cache.staleDisplay = false;
            setLoading(false);
            setRefreshing(false);
            setSelectedDate((currentDate) => {
                const currentDateIsVisible = currentDate
                    && currentDate >= visibleStart
                    && currentDate <= visibleEnd
                    && cache.days[currentDate]?.is_open;

                if (currentDateIsVisible) return currentDate;

                const firstOpen = findFirstOpenDate(Object.values(cache.days), visibleStart, primaryMonthEnd);
                setSelectedSlotId(null);

                return firstOpen;
            });

            ensureMonths(lookAheadMonths).catch(() => {
                // Prefetch failures stay silent and are retried if the customer navigates forward.
            });
        }).catch((requestError) => {
            if (viewChanged || requestError.code === 'ERR_CANCELED' || availabilityCache.current !== cache) return;

            setLoading(false);
            setRefreshing(false);
            setError(requestError.response?.data?.message || 'Availability could not be loaded. Please try again.');
        });

        return () => {
            viewChanged = true;
        };
    }, [service?.id, availabilityKey, viewMonth, availabilityRequestVersion]);

    const slots = selectedDate ? (slotsByDate[selectedDate] || []) : [];
    const selectedSlot = findSelectedSlot(slots, selectedSlotId);
    const total = calculateBookingTotal(selectedSlot, adults, children);
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
        setSelectionNotice('');
    };
    const logoFallback = '/images/kct-booking-v2/logo-karkloof.png';
    const heroFallback = '/images/kct-booking-v2/hero-zipline.jpg';
    const childMinimumAge = service.age_label?.match(/\d+/)?.[0] || null;
    const visibleStart = dateKey(startOfMonth(viewMonth));
    const visibleEnd = dateKey(endOfMonth(addMonths(viewMonth, 1)));
    const currentMonthEnd = dateKey(endOfMonth(viewMonth));
    const availabilityDays = Object.values(availability);
    const noVisibleAvailability = availabilityState(availabilityDays, visibleStart, visibleEnd, loading, error) === 'empty';
    const noCurrentMonthAvailability = availabilityState(availabilityDays, visibleStart, currentMonthEnd, loading, error) === 'empty';
    const paxRefreshPending = adults !== availabilityPax.adults || children !== availabilityPax.children;
    const availabilityUpdating = refreshing || paxRefreshPending;
    const changePax = (setter, value) => {
        if (selectedSlotId !== null) {
            const message = 'Guest numbers changed. Please choose an available time again.';
            setSelectionNotice(message);
            if (!SHOW_INLINE_SELECTION_NOTICE) showToast(message, 'info', 5000);
        }
        setSelectedSlotId(null);
        setter(value);
    };
    const selectSlot = (slotId) => {
        setSelectedSlotId(slotId);
        setSelectionNotice('');
    };
    const retryAvailability = () => {
        setError('');
        setLoading(true);
        setAvailabilityRequestVersion((version) => version + 1);
    };
    const continueToCheckout = () => {
        if (!selectedDate || !selectedSlot || availabilityUpdating) return;

        router.post(route('front.v2.holds.store', {
            team: team.id,
            service: service.id,
        }), {
            date: selectedDate,
            slot_id: selectedSlot.slot_id,
            adults,
            children,
        }, {
            preserveState: false,
            onError: (errors) => {
                const message = Object.values(errors || {})[0]
                    || 'That selection could not be opened. Please choose the date and time again.';
                setSelectionNotice(message);
                showToast(message, 'error', 7000);
            },
        });
    };

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
                    <button
                        type="button"
                        className="kct-header-logo"
                        onClick={() => setShowSitePicker(true)}
                        aria-label="Choose a booking site"
                    >
                        <img
                            src={team?.file_logo?.path || logoFallback}
                            onError={(event) => { event.currentTarget.src = logoFallback; }}
                            alt={team.name}
                        />
                    </button>
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
                            <a className="kct-voucher-button" href={route('front.v2.gift', { team: team.id, service: service.id })}>
                                <GiftIcon />
                                <span className="kct-voucher-label-desktop">Buy a Gift Voucher</span>
                                <span className="kct-voucher-label-mobile">Gift voucher</span>
                            </a>
                        )}
                    </div>
                </header>

                <SitePickerModal
                    teams={teams}
                    isOpen={showSitePicker}
                    onClose={() => setShowSitePicker(false)}
                />

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
                                <Stepper label="Adults" value={adults} onChange={(value) => changePax(setAdults, value)} maximum={Math.max(0, serviceCapacity - children)} />
                                <Stepper label="Children" value={children} onChange={(value) => changePax(setChildren, value)} maximum={Math.max(0, serviceCapacity - adults)} />
                            </div>
                        </div>

                        <div className="kct-booking-layout" aria-busy={loading || availabilityUpdating}>
                            <div className="kct-calendar-panel">
                                <div className="kct-calendar-nav">
                                    <button type="button" onClick={() => setViewMonth((month) => subMonths(month, 1))} aria-label="Previous months"><ChevronIcon direction="left" /></button>
                                    <span role="status" aria-live="polite">{loading ? 'Checking availability...' : availabilityUpdating ? 'Updating availability...' : 'Prices shown per person'}</span>
                                    <button type="button" onClick={() => setViewMonth((month) => addMonths(month, 1))} aria-label="Next months"><ChevronIcon direction="right" /></button>
                                </div>
                                <AvailabilityFeedback error={error} notice={SHOW_INLINE_SELECTION_NOTICE ? selectionNotice : ''} onRetry={retryAvailability} />
                                <div className="kct-months">
                                    <CalendarMonth month={viewMonth} availability={availability} selectedDate={selectedDate} onSelect={selectDate} loading={loading} disabled={availabilityUpdating} />
                                    <CalendarMonth month={addMonths(viewMonth, 1)} availability={availability} selectedDate={selectedDate} onSelect={selectDate} loading={loading} disabled={availabilityUpdating} />
                                </div>
                            </div>

                            <aside className="kct-times-panel">
                                <div>
                                    <h3>{selectedDate ? format(parseISO(selectedDate), 'EEEE d MMMM') : 'Choose an available date'}</h3>
                                    <span aria-live="polite">{selectedDate ? `${slots.length} ${slots.length === 1 ? 'time' : 'times'} available` : 'Available times will appear here'}</span>
                                </div>
                                <div className="kct-times-scroll">
                                    <TimeOptions
                                        slots={slots}
                                        selectedSlotId={selectedSlotId}
                                        onSelect={selectSlot}
                                        arrivalMinutes={arrivalMinutes}
                                        disabled={availabilityUpdating}
                                    />
                                    {loading && slots.length === 0 && <p className="kct-times-empty" role="status">Checking available times...</p>}
                                    {noVisibleAvailability && <p className="kct-times-empty" role="status">No tours are available in these months. Try the next available dates.</p>}
                                    {selectedDate && !loading && slots.length === 0 && <p className="kct-times-empty">No times are available for this date.</p>}
                                </div>
                            </aside>
                        </div>
                    </section>

                    <section className="kct-info-grid">
                        <InfoCard title="Before you go" content={service.requirements || service.arrangements} />
                        <InfoCard title="What's included" content={service.inclusions} />
                        <InfoCard title="Cancellation" content={service.condition_text} moreLabel="All conditions" onMore={() => setShowConditions(true)} />
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
                            <AvailabilityFeedback error={error} notice={SHOW_INLINE_SELECTION_NOTICE ? selectionNotice : ''} onRetry={retryAvailability} />
                            <CalendarMonth month={viewMonth} availability={availability} selectedDate={selectedDate} onSelect={selectDate} compact hideHeading loading={loading} disabled={availabilityUpdating} />
                            <span className="sr-only" role="status" aria-live="polite">{availabilityUpdating ? 'Updating availability' : ''}</span>
                        </section>

                        <section className="kct-mobile-card kct-mobile-time-card">
                            <h2>Choose a time {selectedDate && <span>{'\u00b7'} {format(parseISO(selectedDate), 'EEE d MMM')}</span>}</h2>
                            <div className="kct-mobile-time-grid" role="group" aria-label={selectedDate ? `Times on ${format(parseISO(selectedDate), 'EEEE d MMMM')}` : 'Available times'}>
                                <TimeOptions
                                    slots={slots}
                                    selectedSlotId={selectedSlotId}
                                    onSelect={selectSlot}
                                    arrivalMinutes={arrivalMinutes}
                                    mobile
                                    disabled={availabilityUpdating}
                                />
                                {loading && slots.length === 0 && <p className="kct-times-empty" role="status">Checking available times...</p>}
                                {noCurrentMonthAvailability && <p className="kct-times-empty" role="status">No tours are available this month. Try the next available dates.</p>}
                                {selectedDate && !loading && slots.length === 0 && <p className="kct-times-empty">No tours on this date. Try another date.</p>}
                            </div>
                        </section>

                        <section className="kct-mobile-card kct-mobile-pax-card">
                            <h2>Who's coming?</h2>
                            <MobilePaxRow label="Adults" detail={`${money(selectedSlot?.adult_price || service.from_price)} per person`} value={adults} onChange={(value) => changePax(setAdults, value)} maximum={Math.max(0, serviceCapacity - children)} />
                            <MobilePaxRow label="Children" detail={childMinimumAge ? `${childMinimumAge} years and older` : 'Children'} value={children} onChange={(value) => changePax(setChildren, value)} maximum={Math.max(0, serviceCapacity - adults)} />
                        </section>

                        <button type="button" className="kct-mobile-details-trigger" onClick={() => setShowMobileDetails((value) => !value)}>
                            Tour details &amp; conditions <ChevronIcon direction={showMobileDetails ? 'left' : 'right'} />
                        </button>
                        {showMobileDetails && (
                            <section className="kct-mobile-details">
                                <InfoCard title="Before you go" content={service.requirements || service.arrangements} />
                                <InfoCard title="What's included" content={service.inclusions} />
                                <InfoCard title="Cancellation" content={service.condition_text} moreLabel="All conditions" onMore={() => setShowConditions(true)} />
                            </section>
                        )}
                    </div>
                </main>

                <BookingSummary
                    tourSummary={tourSummary}
                    total={total}
                    canContinue={Boolean(selectedSlot && hasSelectedGuests(adults, children) && !availabilityUpdating)}
                    onContinue={continueToCheckout}
                    onPromoInfo={() => showToast('You can enter your promo code on the checkout screen.', 'info', 5000)}
                />

                <ConditionsModal
                    isOpen={showConditions}
                    onClose={() => setShowConditions(false)}
                    content={service.condition_text}
                />
            </div>
        </div>
    );
}
