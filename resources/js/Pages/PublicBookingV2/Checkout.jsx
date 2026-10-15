import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { format, parseISO } from 'date-fns';
import { ChevronIcon, ConditionsModal, money, TenantImage } from './BookingComponents';
import { formatHoldCountdown, remainingHoldSeconds } from './bookingState';
import './booking-v2.css';

function LockIcon() {
    return (
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
            <rect x="2.5" y="6" width="9" height="6.5" rx="1.5" stroke="currentColor" strokeWidth="1.4" />
            <path d="M4.5 6V4.5a2.5 2.5 0 0 1 5 0V6" stroke="currentColor" strokeWidth="1.4" />
        </svg>
    );
}

function CheckoutStep({ number, title, aside = null, children }) {
    return (
        <section className="kct-checkout-card">
            <div className="kct-checkout-step-row">
                <div className="kct-checkout-step-heading">
                    <span>{number}</span>
                    <h2>{title}</h2>
                </div>
                {aside}
            </div>
            {children}
        </section>
    );
}

function SummaryLine({ label, value }) {
    return (
        <div className="kct-checkout-summary-line">
            <span>{label}</span>
            <strong>{value}</strong>
        </div>
    );
}

function BookingDetails({ service, lines, selectedExtras, formattedDate, time, arrivalBy, changeUrl }) {
    return (
        <>
            <TenantImage
                src={service.image}
                alt={service.name}
                placeholderClassName="kct-image-placeholder--summary"
            />
            <div className="kct-checkout-summary-title">
                <strong>{service.name}</strong>
                <a href={changeUrl}>Change</a>
            </div>
            <div className="kct-checkout-summary-lines">
                <SummaryLine label="Date" value={formattedDate} />
                <SummaryLine label="Time" value={`${time}${arrivalBy ? ` - arrive by ${arrivalBy}` : ''}`} />
                {lines.map((line) => <SummaryLine key={line.label} {...line} />)}
                {selectedExtras.map((extra) => (
                    <SummaryLine
                        key={extra.id}
                        label={extra.is_per_guest ? `${extra.name} x ${extra.quantity}` : extra.name}
                        value={money(extra.total)}
                    />
                ))}
            </div>
        </>
    );
}

function ExtrasSelector({ extras, selectedIds, onToggle }) {
    return (
        <CheckoutStep
            number="2"
            title="Add extras"
            aside={<span className="kct-extras-optional">Optional - shown only when a tour has extras</span>}
        >
            <div className="kct-extra-options">
                {extras.map((extra) => {
                    const selected = selectedIds.includes(extra.id);

                    return (
                        <label key={extra.id} className={`kct-extra-option${selected ? ' is-selected' : ''}`}>
                            <input
                                type="checkbox"
                                checked={selected}
                                onChange={() => onToggle(extra.id)}
                            />
                            <span className="kct-extra-check" aria-hidden="true">{selected ? '\u2713' : ''}</span>
                            <span className="kct-extra-copy">
                                <strong>{extra.name}</strong>
                                <small>{extra.description}</small>
                            </span>
                            <strong className="kct-extra-price">
                                + {money(extra.unit_price)}{extra.is_per_guest ? ' per guest' : ''}
                            </strong>
                        </label>
                    );
                })}
            </div>
        </CheckoutStep>
    );
}

function PaymentSummary({
    total,
    canSubmit,
    holdSeconds,
    promoCode,
    promoBusy,
    promoApplied,
    onPromoChange,
    onApplyPromo,
    submitting,
    onSubmit,
}) {
    return (
        <>
            <div className="kct-promo-row">
                <input
                    type="text"
                    aria-label="Promo code"
                    placeholder="Promo code"
                    value={promoCode}
                    onChange={(event) => onPromoChange(event.target.value)}
                    disabled={promoBusy || submitting}
                />
                <button type="button" disabled={!promoCode.trim() || promoBusy || submitting} onClick={onApplyPromo}>
                    {promoBusy ? 'Checking...' : (promoApplied ? 'Applied' : 'Apply')}
                </button>
            </div>
            <div className="kct-checkout-total">
                <span>Total</span>
                <strong>{money(total)}</strong>
            </div>
            <button
                type="button"
                className="kct-confirm-pay"
                disabled={!canSubmit || submitting}
                onClick={onSubmit}
            >
                {submitting ? 'Preparing payment...' : 'Confirm & pay'}
            </button>
            {holdSeconds !== null && (
                <p className="kct-hold-countdown">
                    Your place is held for {formatHoldCountdown(holdSeconds)}.
                </p>
            )}
        </>
    );
}

function CheckoutTitle({ changeUrl }) {
    return (
        <div className="kct-checkout-title">
            <a href={changeUrl}><ChevronIcon direction="left" />Back to dates</a>
            <h1>Checkout</h1>
            <p>Please review your booking, complete your details, and proceed to payment.</p>
        </div>
    );
}

export default function Checkout({ team, service, selection, quote, extras = [], hold = null, terms, changeUrl }) {
    const [details, setDetails] = useState({ full_name: '', email: '', phone: '' });
    const [accepted, setAccepted] = useState(false);
    const [showConditions, setShowConditions] = useState(false);
    const [selectedExtraIds, setSelectedExtraIds] = useState([]);
    const [promoCode, setPromoCode] = useState('');
    const [appliedPromo, setAppliedPromo] = useState(null);
    const [promoBusy, setPromoBusy] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');
    const [holdSeconds, setHoldSeconds] = useState(() => remainingHoldSeconds(hold?.expires_at));
    const expiryRedirected = useRef(false);
    const submittingRef = useRef(false);
    const formattedDate = format(parseISO(selection.date), 'EEE d MMMM yyyy');
    const time = selection.time.slice(0, 5);
    const canSubmit = Boolean(details.full_name.trim()
        && details.email.trim()
        && details.phone.trim()
        && accepted);
    const lines = useMemo(() => [
        selection.adults > 0 ? {
            label: `${selection.adults} ${selection.adults === 1 ? 'adult' : 'adults'} x ${money(quote.adult_unit_price)}`,
            value: money(quote.adult_total),
        } : null,
        selection.children > 0 ? {
            label: `${selection.children} ${selection.children === 1 ? 'child' : 'children'} x ${money(quote.child_unit_price)}`,
            value: money(quote.child_total),
        } : null,
    ].filter(Boolean), [quote, selection]);
    const selectedExtras = extras.filter((extra) => selectedExtraIds.includes(extra.id));
    const extrasTotal = selectedExtras.reduce((total, extra) => total + Number(extra.total || 0), 0);
    const activityTotal = Number(appliedPromo?.total_price ?? quote.total ?? 0);
    const checkoutTotal = activityTotal + extrasTotal;

    useEffect(() => {
        if (!hold?.expires_at) return undefined;

        const updateRemaining = () => {
            const remaining = remainingHoldSeconds(hold.expires_at);
            setHoldSeconds(remaining);

            if (remaining === 0 && !expiryRedirected.current) {
                expiryRedirected.current = true;
                router.post(route('front.v2.holds.release', {
                    team: team.id,
                    service: service.id,
                    hold: hold.uuid,
                }), {}, { replace: true });
            }
        };
        const interval = window.setInterval(updateRemaining, 1000);
        updateRemaining();

        return () => window.clearInterval(interval);
    }, [hold?.expires_at, hold?.uuid, service.id, team.id]);

    const updateDetail = (field) => (event) => {
        setDetails((current) => ({ ...current, [field]: event.target.value }));
    };
    const toggleExtra = (extraId) => {
        setSelectedExtraIds((current) => current.includes(extraId)
            ? current.filter((id) => id !== extraId)
            : [...current, extraId]);
    };
    const changePromoCode = (value) => {
        setPromoCode(value);
        setAppliedPromo(null);
        setError('');
    };
    const applyPromo = async () => {
        if (!promoCode.trim() || promoBusy) return;
        setPromoBusy(true);
        setError('');
        try {
            const response = await axios.post(route('front.v2.checkout.promo', {
                team: team.id,
                service: service.id,
            }), {
                promo_code: promoCode.trim(),
                date: selection.date,
                slot_id: selection.slot_id,
                adults: selection.adults,
                children: selection.children,
                season_id: selection.season_id,
            });
            setAppliedPromo(response.data.pricing);
        } catch (requestError) {
            const validationErrors = requestError.response?.data?.errors;
            setAppliedPromo(null);
            setError(validationErrors
                ? Object.values(validationErrors).flat()[0]
                : 'That promo code could not be applied.');
        } finally {
            setPromoBusy(false);
        }
    };
    const submit = async () => {
        if (!canSubmit || submittingRef.current) return;
        if (promoCode.trim() && !appliedPromo) {
            setError('Apply or remove the promo code before continuing.');
            return;
        }

        submittingRef.current = true;
        setSubmitting(true);
        setError('');
        try {
            const response = await axios.post(route('front.v2.checkout.confirm', {
                team: team.id,
                service: service.id,
            }), {
                hold_uuid: hold?.uuid || null,
                date: selection.date,
                slot_id: selection.slot_id,
                adults: selection.adults,
                children: selection.children,
                full_name: details.full_name.trim(),
                email: details.email.trim(),
                phone: details.phone.trim(),
                promo_code: appliedPromo ? promoCode.trim() : null,
                extra_ids: selectedExtraIds,
                terms_accepted: accepted,
                terms_hash: terms.hash,
            });
            window.location.assign(response.data.redirect_url);
        } catch (requestError) {
            const validationErrors = requestError.response?.data?.errors;
            if (validationErrors?.hold_uuid) {
                if (hold?.uuid) {
                    router.post(route('front.v2.holds.release', {
                        team: team.id,
                        service: service.id,
                        hold: hold.uuid,
                    }), {}, { replace: true });
                } else {
                    window.location.assign(changeUrl);
                }
                return;
            }
            const message = validationErrors
                ? Object.values(validationErrors).flat()[0]
                : requestError.response?.data?.message;
            setError(message || 'We could not prepare your booking. Please try again.');
            submittingRef.current = false;
            setSubmitting(false);
        }
    };

    const paymentSummaryProps = {
        total: checkoutTotal,
        canSubmit,
        holdSeconds,
        promoCode,
        promoBusy,
        promoApplied: Boolean(appliedPromo),
        onPromoChange: changePromoCode,
        onApplyPromo: applyPromo,
        submitting,
        onSubmit: submit,
    };

    return (
        <div className="kct-book-page kct-checkout-page">
            <Head title={`Checkout - ${service.name}`} />
            <div className="kct-app-shell">
                <header className="kct-header kct-checkout-header">
                    <a href={changeUrl} aria-label={`${team.name} booking home`}>
                        <TenantImage
                            src={team?.file_logo?.path}
                            alt={team.name}
                            placeholderClassName="kct-image-placeholder--logo"
                        />
                    </a>
                    <span className="kct-secure-checkout"><LockIcon />Secure checkout</span>
                </header>

                <main className="kct-checkout-main">
                    <div className="kct-checkout-mobile-title">
                        <CheckoutTitle changeUrl={changeUrl} />
                    </div>

                    <section className="kct-checkout-summary kct-checkout-mobile-booking" aria-label="Booking details">
                        <BookingDetails
                            service={service}
                            lines={lines}
                            selectedExtras={selectedExtras}
                            formattedDate={formattedDate}
                            time={time}
                            arrivalBy={selection.arrival_by}
                            changeUrl={changeUrl}
                        />
                    </section>

                    <div className="kct-checkout-content">
                        <div className="kct-checkout-desktop-title">
                            <CheckoutTitle changeUrl={changeUrl} />
                        </div>

                        <CheckoutStep number="1" title="Your details">
                            <div className="kct-checkout-fields">
                                <label>
                                    <span>Full name <em>*</em></span>
                                    <input type="text" value={details.full_name} onChange={updateDetail('full_name')} autoComplete="name" />
                                </label>
                                <label>
                                    <span>Email <em>*</em></span>
                                    <input type="email" value={details.email} onChange={updateDetail('email')} autoComplete="email" placeholder="name@example.com" />
                                </label>
                                <label>
                                    <span>Mobile number <em>*</em></span>
                                    <input type="tel" value={details.phone} onChange={updateDetail('phone')} autoComplete="tel" placeholder="+27 82 123 4567" />
                                </label>
                            </div>
                        </CheckoutStep>

                        {extras.length > 0 && (
                            <ExtrasSelector extras={extras} selectedIds={selectedExtraIds} onToggle={toggleExtra} />
                        )}

                        <CheckoutStep number={extras.length > 0 ? "3" : "2"} title="Pay">
                            <p className="kct-checkout-muted">You'll complete payment on our secure payment page.</p>
                            <label className="kct-terms-choice">
                                <input type="checkbox" checked={accepted} onChange={(event) => setAccepted(event.target.checked)} />
                                <span>I agree to the{' '}
                                    <button type="button" onClick={() => setShowConditions(true)}>conditions and cancellation policy</button>.
                                </span>
                            </label>
                            {error && <div className="kct-checkout-error" role="alert">{error}</div>}
                        </CheckoutStep>
                    </div>

                    <aside className="kct-checkout-summary kct-checkout-desktop-summary">
                        <BookingDetails
                            service={service}
                            lines={lines}
                            selectedExtras={selectedExtras}
                            formattedDate={formattedDate}
                            time={time}
                            arrivalBy={selection.arrival_by}
                            changeUrl={changeUrl}
                        />
                        <hr />
                        <PaymentSummary {...paymentSummaryProps} />
                    </aside>

                    <section className="kct-checkout-summary kct-checkout-mobile-payment" aria-label="Promo code and payment total">
                        <PaymentSummary {...paymentSummaryProps} />
                    </section>
                </main>
            </div>

            <ConditionsModal
                isOpen={showConditions}
                onClose={() => setShowConditions(false)}
                content={service.condition_html}
                plainText={terms.text}
            />
        </div>
    );
}
