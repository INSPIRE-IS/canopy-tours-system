import { icons } from '@/icons';
import React, { useEffect, useLayoutEffect, useRef, useState } from 'react';

export default function CustomerToolsModal({ index, booking, customer, position, onClose, CustomerToolClick, allowedTools = null }) {
    const modalRef = useRef();
    const [modalStyle, setModalStyle] = useState(null);
    console.log('-----customer-----', customer);

    // Close when clicking outside the modal
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (modalRef.current && !modalRef.current.contains(event.target)) {
                onClose();
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, [modalRef, onClose]);

    useLayoutEffect(() => {
        if (!position || !modalRef.current) return;
        const rect = modalRef.current.getBoundingClientRect();
        const padding = 12;
        const viewportWidth = window.innerWidth || 0;
        const viewportHeight = window.innerHeight || 0;

        let left = position.right - rect.width;
        left = Math.max(padding, Math.min(left, viewportWidth - rect.width - padding));

        let top = position.bottom + 8;
        if (top + rect.height > viewportHeight - padding) {
            top = position.top - rect.height - 8;
        }
        top = Math.max(padding, Math.min(top, viewportHeight - rect.height - padding));

        setModalStyle({ top, left });
    }, [position]);

    if (!customer || !position) return null;

    const toolButtons = [
        { id: 'check_in', label: 'Check-In', icon: icons.check_in, className: 'btn-tool' },
        { id: 'feedback', label: 'Feedback', icon: icons.message, className: 'btn-tool' },
        { id: 'customer_invoice', label: 'Customer Invoice', icon: icons.customer_invoice, className: 'btn-tool' },
        { id: 'resend_invoice', label: 'Resend Invoice', icon: icons.resend_invoice, className: 'btn-tool' },
        { id: 'edit_timeslot', label: 'Edit Timeslot', icon: icons.edit_timeslot, className: 'btn-tool' },
        { id: 'edit_booking', label: 'Edit Booking', icon: icons.edit_timeslot, className: 'btn-tool' },
        { id: 'message_guest', label: 'Message Guests', icon: icons.message, className: 'btn-tool' },
        { id: 'booking_notes', label: 'Booking Note', icon: icons.notes, className: 'btn-tool' },
        { id: 'cancel_booking', label: 'Cancel Booking', icon: icons.cancel_all_bookings, className: 'btn-tool-red' },
    ];

    const visibleButtons = Array.isArray(allowedTools) && allowedTools.length > 0
        ? toolButtons.filter((btn) => allowedTools.includes(btn.id))
        : toolButtons;

    return (
        <div
            ref={modalRef}
            className="fixed z-50 w-[220px] bg-white rounded-xl shadow-lg border border-gray-200 max-h-[70vh] overflow-y-auto"
            style={modalStyle ?? { top: position.bottom + 8, left: position.left }}
        >
            <div className="p-4">
                <div className="flex justify-between items-center mb-2">
                    <h3 className="text-tool-card-title">Customer Tools</h3>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600 text-sm">{icons.close}</button>
                </div>

                <div className="flex flex-col gap-2">
                    {visibleButtons.map((btn) => (
                        <button
                            key={btn.id}
                            type="button"
                            onClick={() => CustomerToolClick(btn.id, index, booking, customer)}
                            className={btn.className}
                        >
                            {btn.icon} {btn.label}
                        </button>
                    ))}
                    {/* <button type="button" onClick={() => BookingToolClick('remove_resource', booking)} className="btn-tool">{icons.remove_resource} Remove Resources</button> */}

                    {/* <button type='button' onClick={() => CustomerToolClick('customer_invoice', index, booking, customer)} className="btn-tool">{icons.customer_invoice} Customer Invoice</button>
                     <button type='button' onClick={() => CustomerToolClick('print_ticket', index, booking, customer)} className="btn-tool">{icons.print_ticket} Print Ticket</button>
                    <button type='button' onClick={() => CustomerToolClick('voucher', index, booking, customer)} className="btn-tool">{icons.voucher} Voucher</button> */}
                </div>
            </div>
        </div>
    );
}
