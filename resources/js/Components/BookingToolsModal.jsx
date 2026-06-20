import { icons } from '@/icons';
import { router } from '@inertiajs/react';
import React, { useEffect, useLayoutEffect, useRef, useState } from 'react';

export default function BookingToolsModal({ booking, position, onClose, BookingToolClick }) {
    const modalRef = useRef();
    const [modalStyle, setModalStyle] = useState(null);

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

    if (!booking || !position) return null;
    const isCancelled = !!booking?.is_cancelled;

    return (
        <div
            ref={modalRef}
            className="fixed z-50 w-[220px] bg-white rounded-xl shadow-lg border border-gray-200 p-3 max-h-[70vh] overflow-y-auto"
            style={modalStyle ?? { top: position.bottom + 8, left: position.left }}
        >
            <div className="p-4">
                <div className="flex justify-between items-center mb-2">
                    <h3 className="text-tool-card-title">Tools</h3>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600 text-sm">{icons.close}</button>
                </div>

                <div className="flex flex-col gap-2">

                    {isCancelled ? (
                        <button type="button" onClick={() => BookingToolClick('reinstate_booking', booking)} className="btn-tool">{icons.check_in} Reinstate Booking</button>
                    ) : (
                        <button type="button" onClick={() => BookingToolClick('cancel_bookings', booking)} className="btn-tool-red">{icons.cancel_all_bookings} Cancel Bookings</button>
                    )}
                </div>
            </div>
        </div>
    );
}
