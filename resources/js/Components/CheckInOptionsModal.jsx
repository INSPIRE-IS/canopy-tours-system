import React from 'react';
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

export default function CheckInOptionsModal({ isOpen, onClose, booking, contextLabel = 'booking', onEmail = null }) {
    const { showToast } = useToast();

    if (!isOpen || !booking) return null;

    const { booking_item_id, reference, lead_name, lead_phone, lead_email } = booking;
    const baseUrl = route('front.booking-questionnaire.signed', {
        booking_item_id,
        reference,
    });

    const handleCopy = async () => {
        try {
            await navigator.clipboard.writeText(baseUrl);
            showToast('Check-in link copied.', 'success');
        } catch (err) {
            showToast('Copy failed. Please try again.', 'error');
        }
    };

    const handleOpen = () => {
        window.open(baseUrl, '_blank', 'noopener,noreferrer');
    };

    const handleWhatsApp = () => {
        const normalized = normalizeWhatsAppNumber(lead_phone);
        if (!normalized) {
            showToast('Please use an international phone number (e.g., +27...).', 'error');
            return;
        }
        const message = `Hi${lead_name ? ` ${lead_name}` : ''}, please complete your check-in here: ${baseUrl}`;
        const waUrl = `https://wa.me/${normalized}?text=${encodeURIComponent(message)}`;
        window.open(waUrl, '_blank', 'noopener,noreferrer');
    };

    const handleEmail = () => {
        if (typeof onEmail === 'function') {
            onEmail(booking);
        }
        onClose();
    };

    return (
        <CModal title="Check-in options" onClose={onClose}>
            <div className="flex flex-col gap-3">
                <p className="text-sm text-gray-600">
                    {lead_name ? `${lead_name}` : 'Guest'} ({contextLabel})
                </p>
                {lead_email && (
                    <p className="text-sm text-gray-500">
                        Email: {lead_email}
                    </p>
                )}
                {lead_phone && (
                    <p className="text-sm text-gray-500">
                        Phone: {lead_phone}
                    </p>
                )}
                <div className="flex flex-col gap-2 mt-2">
                    <button
                        type="button"
                        onClick={handleOpen}
                        className="w-full px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700"
                    >
                        Open website
                    </button>
                    <button
                        type="button"
                        onClick={handleWhatsApp}
                        className="w-full px-4 py-2 rounded-md bg-green-600 text-white hover:bg-green-700"
                    >
                        Send via WhatsApp
                    </button>
                    <button
                        type="button"
                        onClick={handleCopy}
                        className="w-full px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50"
                    >
                        Copy link
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
            </div>
        </CModal>
    );
}
