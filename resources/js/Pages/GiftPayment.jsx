import React, { useRef, useState } from 'react';
import { Head } from '@inertiajs/react';
import { ChevronIcon, GiftIcon, money } from './PublicBookingV2/BookingComponents';
import './PublicBookingV2/booking-v2.css';

function LockIcon() {
    return (
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
            <rect x="2.5" y="6" width="9" height="6.5" rx="1.5" stroke="currentColor" strokeWidth="1.4" />
            <path d="M4.5 6V4.5a2.5 2.5 0 0 1 5 0V6" stroke="currentColor" strokeWidth="1.4" />
        </svg>
    );
}

function VoucherSummary({ gift, service, season, voucherItem, backUrl, heroFallback }) {
    return (
        <>
            <img
                src={service.image || heroFallback}
                onError={(event) => { event.currentTarget.src = heroFallback; }}
                alt=""
            />
            <div className="kct-checkout-summary-title">
                <strong>{service.name}</strong>
                <a href={backUrl}>Change</a>
            </div>
            <div className="kct-gift-summary-label"><GiftIcon />Gift voucher</div>
            <div className="kct-checkout-summary-lines">
                {season?.name && <div className="kct-checkout-summary-line"><span>Season</span><strong>{season.name}</strong></div>}
                <div className="kct-checkout-summary-line"><span>Buyer</span><strong>{gift.full_name}</strong></div>
                <div className="kct-checkout-summary-line"><span>Email</span><strong>{gift.email_address}</strong></div>
                {voucherItem?.pax_adults > 0 && (
                    <div className="kct-checkout-summary-line">
                        <span>{voucherItem.pax_adults} {voucherItem.pax_adults === 1 ? 'adult' : 'adults'}</span>
                        <strong>{money(voucherItem.price_adult)}</strong>
                    </div>
                )}
                {voucherItem?.pax_children > 0 && (
                    <div className="kct-checkout-summary-line">
                        <span>{voucherItem.pax_children} {voucherItem.pax_children === 1 ? 'child' : 'children'}</span>
                        <strong>{money(voucherItem.price_child)}</strong>
                    </div>
                )}
            </div>
            <hr />
            <div className="kct-checkout-total"><span>Total</span><strong>{money(gift.grand_tot_due)}</strong></div>
        </>
    );
}

export default function GiftPayment({ gift, team, service, season, voucherItem, backUrl, payfastStatus }) {
    const [submitting, setSubmitting] = useState(false);
    const submittingRef = useRef(false);
    const logoFallback = '/images/kct-booking-v2/logo-karkloof.png';
    const heroFallback = '/images/kct-booking-v2/hero-zipline.jpg';
    const paymentAvailable = !payfastStatus || payfastStatus.enabled;

    const proceedToPayFast = () => {
        if (!paymentAvailable || submittingRef.current) return;
        submittingRef.current = true;
        setSubmitting(true);
        window.location.assign(route('front.payment.payfast.gift', { gift: gift.id }));
    };

    const summary = (
        <VoucherSummary
            gift={gift}
            service={service}
            season={season}
            voucherItem={voucherItem}
            backUrl={backUrl}
            heroFallback={heroFallback}
        />
    );

    return (
        <div className="kct-book-page kct-checkout-page">
            <Head title={`Secure Payment - ${service.name}`} />
            <div className="kct-app-shell">
                <header className="kct-header kct-checkout-header">
                    <a href={backUrl} aria-label={`${team.name} gift voucher checkout`}>
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
                        <div className="kct-checkout-title">
                            <a href={backUrl}><ChevronIcon direction="left" />Back to voucher details</a>
                            <h1>Complete your purchase</h1>
                            <p>Review your gift voucher before continuing to secure payment.</p>
                        </div>
                    </div>

                    <section className="kct-checkout-summary kct-checkout-mobile-booking" aria-label="Gift voucher summary">
                        {summary}
                    </section>

                    <div className="kct-checkout-content">
                        <div className="kct-checkout-desktop-title">
                            <div className="kct-checkout-title">
                                <a href={backUrl}><ChevronIcon direction="left" />Back to voucher details</a>
                                <h1>Complete your purchase</h1>
                                <p>Review your gift voucher before continuing to secure payment.</p>
                            </div>
                        </div>

                        <section className="kct-checkout-card">
                            <div className="kct-checkout-step-heading"><span>1</span><h2>Gift voucher details</h2></div>
                            <div className="kct-checkout-summary-lines">
                                <div className="kct-checkout-summary-line"><span>Voucher code</span><strong>{gift.voucher_code}</strong></div>
                                <div className="kct-checkout-summary-line"><span>Full name</span><strong>{gift.full_name}</strong></div>
                                <div className="kct-checkout-summary-line"><span>Email</span><strong>{gift.email_address}</strong></div>
                                <div className="kct-checkout-summary-line"><span>Mobile number</span><strong>{gift.contact_number}</strong></div>
                            </div>
                        </section>

                        <section className="kct-checkout-card">
                            <div className="kct-checkout-step-heading"><span>2</span><h2>Pay securely</h2></div>
                            <p className="kct-checkout-muted">You'll complete payment on PayFast's secure payment page.</p>
                            <p className="kct-checkout-muted">After payment, your voucher will be prepared and sent to {gift.email_address}. Please retain your PayFast confirmation for payment verification.</p>
                            {!paymentAvailable && (
                                <div className="kct-checkout-error" role="alert">Online payment is temporarily unavailable. Please contact {team.name} for assistance.</div>
                            )}
                        </section>
                    </div>

                    <aside className="kct-checkout-summary kct-checkout-desktop-summary">
                        {summary}
                        <button type="button" className="kct-confirm-pay" disabled={!paymentAvailable || submitting} onClick={proceedToPayFast}>
                            {submitting ? 'Opening PayFast...' : 'Continue to PayFast'}
                        </button>
                        <p className="kct-hold-countdown">Payment is verified before the voucher is redeemed.</p>
                    </aside>

                    <section className="kct-checkout-summary kct-checkout-mobile-payment" aria-label="Gift voucher payment">
                        <div className="kct-checkout-total"><span>Total</span><strong>{money(gift.grand_tot_due)}</strong></div>
                        <button type="button" className="kct-confirm-pay" disabled={!paymentAvailable || submitting} onClick={proceedToPayFast}>
                            {submitting ? 'Opening PayFast...' : 'Continue to PayFast'}
                        </button>
                        <p className="kct-hold-countdown">Payment is verified before the voucher is redeemed.</p>
                    </section>
                </main>
            </div>
        </div>
    );
}
