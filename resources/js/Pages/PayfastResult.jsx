import React, { useEffect, useRef, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
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

export default function PayfastResult({
    status,
    message,
    purchase = null,
    purchaseState = null,
    purchaseStatusUrl = null,
    purchaseAcknowledgementUrl = null,
    purchaseContext = null,
}) {
    const isSuccess = status === 'success';
    const [paymentConfirmed, setPaymentConfirmed] = useState(purchaseState === 'paid');
    const purchasePushed = useRef(false);
    const title = isSuccess ? 'Payment received' : 'Payment cancelled';
    const supportingMessage = isSuccess && paymentConfirmed
        ? 'Your payment has been confirmed. Thank you for your booking.'
        : isSuccess
            ? 'Your payment is being processed. The booking will be confirmed once PayFast completes the payment notification.'
        : 'No payment was completed. You can return to the booking home and try again when you are ready.';

    useEffect(() => {
        if (!isSuccess || !purchaseContext) {
            return undefined;
        }

        let cancelled = false;
        let pollTimer = null;

        const pushPurchase = async (purchaseEvent) => {
            const transactionId = purchaseEvent?.ecommerce?.transaction_id;
            if (!transactionId || purchasePushed.current) {
                return;
            }

            const storageKey = `ga4_purchase_${transactionId}`;
            try {
                if (window.localStorage.getItem(storageKey)) {
                    purchasePushed.current = true;
                    setPaymentConfirmed(true);
                    return;
                }
            } catch {
                // Browser storage can be unavailable in strict privacy modes.
            }

            purchasePushed.current = true;
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push(purchaseEvent);

            try {
                window.localStorage.setItem(storageKey, new Date().toISOString());
            } catch {
                // GA4 also deduplicates purchases by transaction_id.
            }

            setPaymentConfirmed(true);
            console.info('[analytics] purchase pushed to dataLayer', {
                transaction_id: transactionId,
                value: purchaseEvent.ecommerce?.value,
                currency: purchaseEvent.ecommerce?.currency,
                item_count: purchaseEvent.ecommerce?.items?.length ?? 0,
            });

            if (purchaseAcknowledgementUrl) {
                try {
                    await axios.post(purchaseAcknowledgementUrl, {
                        purchase_context: purchaseContext,
                        transaction_id: transactionId,
                    });
                } catch (error) {
                    console.warn('[analytics] purchase acknowledgement failed', error);
                }
            }
        };

        if (purchase) {
            pushPurchase(purchase);
            return () => {
                cancelled = true;
            };
        }

        if (!purchaseStatusUrl) {
            return undefined;
        }

        const checkPayment = async () => {
            try {
                const response = await axios.get(purchaseStatusUrl);
                if (cancelled) {
                    return;
                }

                if (response.data?.state === 'paid' && response.data?.purchase) {
                    if (pollTimer) {
                        window.clearInterval(pollTimer);
                    }
                    await pushPurchase(response.data.purchase);
                }
            } catch (error) {
                if (!cancelled) {
                    console.warn('[analytics] payment confirmation check failed', error);
                }
            }
        };

        checkPayment();
        pollTimer = window.setInterval(checkPayment, 2500);

        return () => {
            cancelled = true;
            if (pollTimer) {
                window.clearInterval(pollTimer);
            }
        };
    }, [isSuccess, purchase, purchaseAcknowledgementUrl, purchaseContext, purchaseStatusUrl]);

    return (
        <div className="kct-book-page kct-checkout-page kct-payment-result-page">
            <Head title={title} />

            <div className="kct-app-shell">
                <header className="kct-header kct-checkout-header">
                    <Link href={route('front.welcome')} aria-label="Choose a booking site">
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
                        <Link href={route('front.welcome')} className="kct-payment-result-action">
                            Back to home
                        </Link>
                    </section>
                </main>
            </div>
        </div>
    );
}
