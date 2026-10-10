import React, { useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import { ChevronIcon, money } from './BookingComponents';
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

function BookingDetails({ service, lines, selectedExtras, formattedDate, time, arrivalBy, changeUrl, heroFallback }) {
    return (
        <>
            <img
                src={service.image || heroFallback}
                onError={(event) => { event.currentTarget.src = heroFallback; }}
                alt=""
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

function PaymentSummary({ total, canSubmit, submissionEnabled }) {
    return (
        <>
            <div className="kct-promo-row">
                <input type="text" aria-label="Promo code" placeholder="Promo code" disabled />
                <button type="button" disabled>Apply</button>
            </div>
            <div className="kct-checkout-total">
                <span>Total</span>
                <strong>{money(total)}</strong>
            </div>
            <button
                type="button"
                className="kct-confirm-pay"
                disabled={!canSubmit || !submissionEnabled}
                title="Payment submission is connected in the next implementation checkpoint."
            >
                Confirm &amp; pay
            </button>
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

export default function Checkout({ team, service, selection, quote, extras = [], changeUrl }) {
    const [details, setDetails] = useState({ full_name: '', email: '', phone: '' });
    const [accepted, setAccepted] = useState(false);
    const [showConditions, setShowConditions] = useState(false);
    const [selectedExtraIds, setSelectedExtraIds] = useState([]);
    const logoFallback = '/images/kct-booking-v2/logo-karkloof.png';
    const heroFallback = '/images/kct-booking-v2/hero-zipline.jpg';
    const formattedDate = format(parseISO(selection.date), 'EEE d MMMM yyyy');
    const time = selection.time.slice(0, 5);
    // Booking creation is enabled only after the hold and final pricing transaction are wired.
    const submissionEnabled = false;
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
    const checkoutTotal = Number(quote.total || 0) + extrasTotal;

    const updateDetail = (field) => (event) => {
        setDetails((current) => ({ ...current, [field]: event.target.value }));
    };
    const toggleExtra = (extraId) => {
        setSelectedExtraIds((current) => current.includes(extraId)
            ? current.filter((id) => id !== extraId)
            : [...current, extraId]);
    };

    return (
        <div className="kct-book-page kct-checkout-page">
            <Head title={`Checkout - ${service.name}`} />
            <div className="kct-app-shell">
                <header className="kct-header kct-checkout-header">
                    <a href={changeUrl} aria-label={`${team.name} booking home`}>
                        <img
                            src={team?.file_logo?.path || logoFallback}
                            onError={(event) => { event.currentTarget.src = logoFallback; }}
                            alt={team.name}
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
                            heroFallback={heroFallback}
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
                            heroFallback={heroFallback}
                        />
                        <hr />
                        <PaymentSummary total={checkoutTotal} canSubmit={canSubmit} submissionEnabled={submissionEnabled} />
                    </aside>

                    <section className="kct-checkout-summary kct-checkout-mobile-payment" aria-label="Promo code and payment total">
                        <PaymentSummary total={checkoutTotal} canSubmit={canSubmit} submissionEnabled={submissionEnabled} />
                    </section>
                </main>
            </div>

            {showConditions && (
                <div className="kct-conditions-backdrop" role="presentation" onMouseDown={() => setShowConditions(false)}>
                    <section className="kct-conditions-dialog" role="dialog" aria-modal="true" aria-labelledby="conditions-title" onMouseDown={(event) => event.stopPropagation()}>
                        <div>
                            <h2 id="conditions-title">Conditions and cancellation policy</h2>
                            <button type="button" onClick={() => setShowConditions(false)} aria-label="Close conditions">x</button>
                        </div>
                        <p>{service.condition_text || 'Please contact us if you need a copy of the activity conditions and cancellation policy.'}</p>
                    </section>
                </div>
            )}
        </div>
    );
}
