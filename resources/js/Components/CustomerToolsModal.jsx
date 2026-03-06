import { icons } from '@/icons';
import React, { useEffect, useLayoutEffect, useRef, useState } from 'react';

export default function CustomerToolsModal({ index, booking, customer, position, onClose, CustomerToolClick }) {
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
                    <button type='button' onClick={() => CustomerToolClick('check_in', index, booking, customer)} className="btn-tool">{icons.check_in} Check-In</button>
                    <button type='button' onClick={() => CustomerToolClick('feedback', index, booking, customer)} className="btn-tool">{icons.message} Feedback</button>
                    <button type='button' onClick={() => CustomerToolClick('customer_invoice', index, booking, customer)} className="btn-tool">{icons.customer_invoice} Customer Invoice</button>
                    <button type='button' onClick={() => CustomerToolClick('resend_invoice', index, booking, customer)} className="btn-tool">{icons.resend_invoice} Resend Invoice</button>
                    {/* <button type='button' onClick={() => CustomerToolClick('manifest', index, booking, customer)} className="btn-tool">{icons.manifest} Manifest</button> */}
                    <button type='button' onClick={() => CustomerToolClick('edit_timeslot', index, booking, customer)} className="btn-tool">{icons.edit_timeslot} Edit Timeslot</button>
                    <button type='button' onClick={() => CustomerToolClick('edit_booking', index, booking, customer)} className="btn-tool">{icons.edit_timeslot} Edit Booking</button>

                    <button type='button' onClick={() => CustomerToolClick('message_guest', index, booking, customer)} className="btn-tool">{icons.message} Message Guests</button>
                    <button type='button' onClick={() => CustomerToolClick('booking_notes', index, booking, customer)} className="btn-tool">{icons.notes} Booking Note</button>
                    {/* <button type="button" onClick={() => router.get(route('admin.messenger.createTopic'))} className="btn-tool">{icons.message} Message Staff</button> */}
                    {/* <button type="button" onClick={() => BookingToolClick('message_staff', booking)} className="btn-tool">{icons.message} Message Staff</button> */}
                    {/* <button type="button" onClick={() => BookingToolClick('check_in_progress', booking)} className="btn-tool">{icons.check_in_progress} Check-In Progress</button> */}
                    {/* <button type="button" onClick={() => BookingToolClick('manifest_edit', booking)} className="btn-tool">{icons.manifest_edit} Edit Manifest</button> */}
                    <button type="button" onClick={() => CustomerToolClick('cancel_booking', index, booking, customer)} className="btn-tool-red">{icons.cancel_all_bookings} Cancel Booking</button>
                    {/* <button type="button" onClick={() => BookingToolClick('remove_resource', booking)} className="btn-tool">{icons.remove_resource} Remove Resources</button> */}

                    {/* <button type='button' onClick={() => CustomerToolClick('customer_invoice', index, booking, customer)} className="btn-tool">{icons.customer_invoice} Customer Invoice</button>
                     <button type='button' onClick={() => CustomerToolClick('print_ticket', index, booking, customer)} className="btn-tool">{icons.print_ticket} Print Ticket</button>
                    <button type='button' onClick={() => CustomerToolClick('voucher', index, booking, customer)} className="btn-tool">{icons.voucher} Voucher</button> */}
                </div>
            </div>
        </div>
    );
}
