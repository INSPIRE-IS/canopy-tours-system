import { icons } from '@/icons';
import { router } from '@inertiajs/react';
import React, { useEffect, useRef } from 'react';

export default function BookingToolsModal({ booking, position, onClose, BookingToolClick }) {
    const modalRef = useRef();

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

    if (!booking || !position) return null;

    return (
        <div
            ref={modalRef}
            className="absolute z-50 w-[220px] bg-white rounded-xl shadow-lg border border-gray-200 p-3"
            style={{
                top: position.top,
                left: position.left,
            }}
        >
            <div className="p-4">
                <div className="flex justify-between items-center mb-2">
                    <h3 className="text-tool-card-title">Tools</h3>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600 text-sm">{icons.close}</button>
                </div>

                <div className="flex flex-col gap-2">

                    <button type="button" onClick={() => BookingToolClick('cancel_bookings', booking)} className="btn-tool-red">{icons.cancel_all_bookings} Cancel Bookings</button>
                </div>
            </div>
        </div>
    );
}
