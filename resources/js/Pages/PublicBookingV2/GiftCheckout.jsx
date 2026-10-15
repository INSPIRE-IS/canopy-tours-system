import React, { useMemo, useRef, useState } from 'react';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { format, parseISO } from 'date-fns';
import { ChevronIcon, GiftIcon, money, Stepper, TenantImage } from './BookingComponents';
import './booking-v2.css';

function LockIcon() {
    return (
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
            <rect x="2.5" y="6" width="9" height="6.5" rx="1.5" stroke="currentColor" strokeWidth="1.4" />
            <path d="M4.5 6V4.5a2.5 2.5 0 0 1 5 0V6" stroke="currentColor" strokeWidth="1.4" />
        </svg>
    );
}

function GiftSummary({ service, season, adults, children, total, backUrl }) {
    return (
        <>
            <TenantImage
                src={service.image}
                alt={service.name}
                placeholderClassName="kct-image-placeholder--summary"
            />
            <div className="kct-checkout-summary-title">
                <strong>{service.name}</strong>
                <a href={backUrl}>Change</a>
            </div>
            <div className="kct-gift-summary-label"><GiftIcon />Gift voucher</div>
            <div className="kct-checkout-summary-lines">
                <div className="kct-checkout-summary-line">
                    <span>Season</span>
                    <strong>{season.name}</strong>
                </div>
                <div className="kct-checkout-summary-line">
                    <span>Valid pricing period</span>
                    <strong>{format(parseISO(season.start_date), 'd MMM yyyy')} - {format(parseISO(season.end_date), 'd MMM yyyy')}</strong>
                </div>
                {adults > 0 && (
                    <div className="kct-checkout-summary-line">
                        <span>{adults} {adults === 1 ? 'adult' : 'adults'} x {money(season.adult_price)}</span>
                        <strong>{money(adults * season.adult_price)}</strong>
                    </div>
                )}
                {children > 0 && (
                    <div className="kct-checkout-summary-line">
                        <span>{children} {children === 1 ? 'child' : 'children'} x {money(season.child_price)}</span>
                        <strong>{money(children * season.child_price)}</strong>
                    </div>
                )}
            </div>
            <hr />
            <div className="kct-checkout-total">
                <span>Total</span>
                <strong>{money(total)}</strong>
            </div>
        </>
    );
}

export default function GiftCheckout({ team, service, season, countries = [], backUrl }) {
    const defaultCountry = countries.find((country) => country.name === 'South Africa') || countries[0];
    const [adults, setAdults] = useState(1);
    const [children, setChildren] = useState(0);
    const [details, setDetails] = useState({ full_name: '', email: '', local_phone: '' });
    const [countryId, setCountryId] = useState(defaultCountry?.id ? String(defaultCountry.id) : '');
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');
    const submittingRef = useRef(false);
    const selectedCountry = countries.find((country) => String(country.id) === countryId) || defaultCountry;
    const total = useMemo(
        () => (adults * Number(season.adult_price)) + (children * Number(season.child_price)),
        [adults, children, season.adult_price, season.child_price],
    );
    const normalizedLocalPhone = details.local_phone.replace(/\D/g, '').replace(/^0+/, '');
    const phone = selectedCountry?.phonecode && normalizedLocalPhone
        ? `+${String(selectedCountry.phonecode).replace(/\D/g, '')}${normalizedLocalPhone}`
        : '';
    const canSubmit = details.full_name.trim().length > 1
        && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(details.email.trim())
        && normalizedLocalPhone.length >= 5
        && adults + children >= 1;

    const updateDetail = (field) => (event) => {
        setDetails((current) => ({ ...current, [field]: event.target.value }));
    };

    const submit = async () => {
        if (!canSubmit || submittingRef.current) return;

        submittingRef.current = true;
        setSubmitting(true);
        setError('');

        try {
            const response = await axios.post(route('front.booking.gift-store'), {
                full_name: details.full_name.trim(),
                email: details.email.trim(),
                phone,
                adults,
                children,
                activity_id: season.id,
            });
            window.location.assign(response.data.redirect_url);
        } catch (requestError) {
            const validationErrors = requestError.response?.data?.errors;
            const firstValidationError = validationErrors
                ? Object.values(validationErrors).flat()[0]
                : null;
            setError(firstValidationError || requestError.response?.data?.error || 'We could not prepare the gift voucher. Please try again.');
            submittingRef.current = false;
            setSubmitting(false);
        }
    };

    const summary = (
        <GiftSummary
            service={service}
            season={season}
            adults={adults}
            children={children}
            total={total}
            backUrl={backUrl}
        />
    );

    return (
        <div className="kct-book-page kct-checkout-page">
            <Head title={`Gift Voucher - ${service.name}`} />
            <div className="kct-app-shell">
                <header className="kct-header kct-checkout-header">
                    <a href={backUrl} aria-label={`${team.name} booking home`}>
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
                        <div className="kct-checkout-title">
                            <a href={backUrl}><ChevronIcon direction="left" />Back to booking</a>
                            <h1>Buy a gift voucher</h1>
                            <p>Choose your voucher, complete your details, and proceed to secure payment.</p>
                        </div>
                    </div>

                    <section className="kct-checkout-summary kct-checkout-mobile-booking" aria-label="Gift voucher summary">
                        {summary}
                    </section>

                    <div className="kct-checkout-content">
                        <div className="kct-checkout-desktop-title">
                            <div className="kct-checkout-title">
                                <a href={backUrl}><ChevronIcon direction="left" />Back to booking</a>
                                <h1>Buy a gift voucher</h1>
                                <p>Choose your voucher, complete your details, and proceed to secure payment.</p>
                            </div>
                        </div>

                        <section className="kct-checkout-card">
                            <div className="kct-checkout-step-heading"><span>1</span><h2>Choose your voucher</h2></div>
                            <div className="kct-gift-pax-grid">
                                <Stepper label="Adults" value={adults} maximum={99} onChange={setAdults} />
                                <Stepper label="Children" value={children} maximum={99} onChange={setChildren} />
                            </div>
                            <p className="kct-checkout-muted">Adult {money(season.adult_price)} each - Child {money(season.child_price)} each</p>
                        </section>

                        <section className="kct-checkout-card">
                            <div className="kct-checkout-step-heading"><span>2</span><h2>Your details</h2></div>
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
                                    <span>Country code <em>*</em></span>
                                    <select value={countryId} onChange={(event) => setCountryId(event.target.value)}>
                                        {countries.map((country) => (
                                            <option key={country.id} value={country.id}>+{country.phonecode} - {country.name}</option>
                                        ))}
                                    </select>
                                </label>
                                <label>
                                    <span>Mobile number <em>*</em></span>
                                    <input
                                        type="tel"
                                        inputMode="numeric"
                                        value={details.local_phone}
                                        onChange={(event) => setDetails((current) => ({
                                            ...current,
                                            local_phone: event.target.value.replace(/\D/g, ''),
                                        }))}
                                        autoComplete="tel-national"
                                        placeholder="82 123 4567"
                                    />
                                </label>
                            </div>
                            {phone && <p className="kct-phone-preview">Saved as {phone}</p>}
                        </section>

                        <section className="kct-checkout-card">
                            <div className="kct-checkout-step-heading"><span>3</span><h2>Pay</h2></div>
                            <p className="kct-checkout-muted">You'll complete payment on our secure PayFast payment page.</p>
                            {error && <div className="kct-checkout-error" role="alert">{error}</div>}
                        </section>
                    </div>

                    <aside className="kct-checkout-summary kct-checkout-desktop-summary">
                        {summary}
                        <button type="button" className="kct-confirm-pay" disabled={!canSubmit || submitting} onClick={submit}>
                            {submitting ? 'Preparing payment...' : 'Confirm & pay'}
                        </button>
                        <p className="kct-hold-countdown">The voucher is issued after payment notification and remains subject to payment verification.</p>
                    </aside>

                    <section className="kct-checkout-summary kct-checkout-mobile-payment" aria-label="Gift voucher payment total">
                        <div className="kct-checkout-total"><span>Total</span><strong>{money(total)}</strong></div>
                        {error && <div className="kct-checkout-error" role="alert">{error}</div>}
                        <button type="button" className="kct-confirm-pay" disabled={!canSubmit || submitting} onClick={submit}>
                            {submitting ? 'Preparing payment...' : 'Confirm & pay'}
                        </button>
                        <p className="kct-hold-countdown">The voucher is issued after payment notification and remains subject to payment verification.</p>
                    </section>
                </main>
            </div>
        </div>
    );
}
