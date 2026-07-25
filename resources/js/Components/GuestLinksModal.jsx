import React, { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import CModal from '@/Components/CModal';
import { useToast } from '@/Context/ToastContext';

const normalizeWhatsAppNumber = (raw) => {
    if (!raw) return null;
    let cleaned = raw.trim();
    if (cleaned.startsWith('+')) {
        cleaned = cleaned.slice(1);
    }
    cleaned = cleaned.replace(/\D/g, '');
    if (cleaned.startsWith('00')) {
        cleaned = cleaned.slice(2);
    }
    if (!cleaned || cleaned.startsWith('0')) {
        return null;
    }
    return cleaned;
};

export default function GuestLinksModal({ isOpen, onClose, booking = null, customer = null, onEmail = null }) {
    const { showToast } = useToast();
    const [loading, setLoading] = useState(false);
    const [payload, setPayload] = useState(null);

    useEffect(() => {
        if (!isOpen || !booking?.id) return;

        setLoading(true);
        setPayload(null);

        axios.get(route('admin.bookings.guest-links', booking.id), {
            params: {
                customer_id: customer?.id || booking?.customer_id || booking?.lead_id || '',
                booking_item_id: booking?.booking_item_id || '',
            },
        })
            .then((response) => {
                setPayload(response.data);
            })
            .catch(() => showToast('Could not load booking links.', 'error'))
            .finally(() => setLoading(false));
    }, [isOpen, booking?.id, booking?.booking_item_id, customer?.id]);

    const links = payload?.links || [];
    const invoiceLink = useMemo(() => links.find((link) => link.key === 'invoice') || null, [links]);
    const paymentLink = useMemo(() => links.find((link) => link.key === 'payment') || null, [links]);

    if (!isOpen || !booking) return null;

    const guestName = payload?.customer_name || customer?.full_name || booking?.lead_name || 'Guest';
    const guestEmail = payload?.recipient || customer?.email_address || booking?.lead_email || '';
    const guestPhone = customer?.contact_number || customer?.phone || booking?.lead_phone || booking?.lead_mobile || '';

    const handleCopy = async (link, label = 'Link') => {
        if (!link?.url) return;
        try {
            await navigator.clipboard.writeText(link.url);
            showToast(`${label} copied.`, 'success');
        } catch (err) {
            showToast('Copy failed. Please try again.', 'error');
        }
    };

    const handleOpen = (link) => {
        if (!link?.url) return;
        window.open(link.url, '_blank', 'noopener,noreferrer');
    };

    const handleWhatsApp = (link, type) => {
        if (!link?.url) return;
        const normalized = normalizeWhatsAppNumber(guestPhone);
        if (!normalized) {
            showToast('Please use an international phone number (e.g., +27...).', 'error');
            return;
        }

        const message = type === 'payment'
            ? `Hi${guestName ? ` ${guestName}` : ''}, you can make payment for your booking here: ${link.url}`
            : `Hi${guestName ? ` ${guestName}` : ''}, you can view your booking invoice here: ${link.url}`;
        const waUrl = `https://wa.me/${normalized}?text=${encodeURIComponent(message)}`;
        window.open(waUrl, '_blank', 'noopener,noreferrer');
    };

    const handleEmail = () => {
        if (typeof onEmail === 'function') {
            onEmail({ booking, customer });
        }
        onClose();
    };

    return (
        <CModal title="Customer invoice options" onClose={onClose}>
            <div className="flex flex-col gap-3">
                <p className="text-sm text-gray-600">
                    {guestName} ({booking?.reference || 'booking'})
                </p>
                {guestEmail && (
                    <p className="text-sm text-gray-500">
                        Email: {guestEmail}
                    </p>
                )}
                {guestPhone && (
                    <p className="text-sm text-gray-500">
                        Phone: {guestPhone}
                    </p>
                )}

                {loading ? (
                    <p className="text-sm text-gray-400">Loading links...</p>
                ) : (
                    <>
                        <div className="flex flex-col gap-2 mt-2">
                            <p className="text-sm font-semibold text-gray-700">Invoice</p>
                            <button
                                type="button"
                                onClick={() => handleOpen(invoiceLink)}
                                className="w-full px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700"
                            >
                                Open invoice
                            </button>
                            <button
                                type="button"
                                onClick={() => handleWhatsApp(invoiceLink, 'invoice')}
                                className="w-full px-4 py-2 rounded-md bg-green-600 text-white hover:bg-green-700"
                            >
                                WhatsApp invoice
                            </button>
                            <button
                                type="button"
                                onClick={() => handleCopy(invoiceLink, 'Invoice link')}
                                className="w-full px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50"
                            >
                                Copy invoice link
                            </button>

                            <p className="text-sm font-semibold text-gray-700 mt-3">Payment</p>
                            <button
                                type="button"
                                onClick={() => handleOpen(paymentLink)}
                                className="w-full px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700"
                            >
                                Open payment page
                            </button>
                            <button
                                type="button"
                                onClick={() => handleWhatsApp(paymentLink, 'payment')}
                                className="w-full px-4 py-2 rounded-md bg-green-600 text-white hover:bg-green-700"
                            >
                                WhatsApp payment link
                            </button>
                            <button
                                type="button"
                                onClick={() => handleCopy(paymentLink, 'Payment link')}
                                className="w-full px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50"
                            >
                                Copy payment link
                            </button>
                            <button
                                type="button"
                                onClick={handleEmail}
                                className="w-full px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50"
                            >
                                Open email options
                            </button>
                        </div>
                        <p className="text-xs text-gray-400">
                            WhatsApp opens in a new tab and requires WhatsApp Web to be logged in.
                        </p>
                    </>
                )}
            </div>
        </CModal>
    );
}
