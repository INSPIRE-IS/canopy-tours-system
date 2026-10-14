import React from 'react';
import { Head, Link } from '@inertiajs/react';
import './PublicBookingV2/booking-v2.css';

function LockIcon() {
    return (
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
            <rect x="2.5" y="6" width="9" height="6.5" rx="1.5" stroke="currentColor" strokeWidth="1.4" />
            <path d="M4.5 6V4.5a2.5 2.5 0 0 1 5 0V6" stroke="currentColor" strokeWidth="1.4" />
        </svg>
    );
}

function StatusIcon({ success }) {
    if (success) {
        return (
            <svg width="34" height="34" viewBox="0 0 34 34" fill="none" aria-hidden="true">
                <path d="m9 17 5.2 5.2L25 11.5" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        );
    }

    return (
        <svg width="34" height="34" viewBox="0 0 34 34" fill="none" aria-hidden="true">
            <path d="m11.5 11.5 11 11m0-11-11 11" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
        </svg>
    );
}

export default function PayfastResult({ status, message }) {
    const isSuccess = status === 'success';
    const title = isSuccess ? 'Payment received' : 'Payment cancelled';
    const supportingMessage = isSuccess
        ? 'Your payment is being processed. The booking will be confirmed once PayFast completes the payment notification.'
        : 'No payment was completed. You can return to the booking home and try again when you are ready.';

    return (
        <div className="kct-book-page kct-checkout-page kct-payment-result-page">
            <Head title={title} />

            <div className="kct-app-shell">
                <header className="kct-header kct-checkout-header">
                    <Link href="/" aria-label="Booking home">
                        <img src="/images/kct-booking-v2/logo-karkloof.png" alt="Karkloof Canopy Tour" />
                    </Link>
                    <span className="kct-secure-checkout"><LockIcon />Secure payment</span>
                </header>

                <main className="kct-payment-result-main">
                    <section className={`kct-payment-result-card ${isSuccess ? 'is-success' : 'is-cancelled'}`}>
                        <span className="kct-payment-result-icon">
                            <StatusIcon success={isSuccess} />
                        </span>
                        <span className="kct-payment-result-eyebrow">Payment status</span>
                        <h1>{title}</h1>
                        <p className="kct-payment-result-message">{message}</p>
                        <p className="kct-payment-result-support">{supportingMessage}</p>
                        <Link href="/" className="kct-payment-result-action">
                            Back to home
                        </Link>
                    </section>
                </main>
            </div>
        </div>
    );
}
