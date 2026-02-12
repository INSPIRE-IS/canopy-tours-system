import React from 'react';
import OutlineButton from '@/Components/OutlineButton';

export default function BookingNotesModal({ isOpen, onClose, booking }) {
    if (!isOpen || !booking) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[40%] max-h-[80%] overflow-y-auto">
                <div className="flex justify-between items-center mb-4">
                    <h2 className="text-xl font-bold">Booking Notes</h2>
                    <button type="button" onClick={onClose} className="text-gray-400 hover:text-gray-600 text-sm">✕</button>
                </div>
                <div className="text-sm text-gray-600 mb-4">
                    <div><span className="font-semibold">Reference:</span> {booking.reference}</div>
                    <div><span className="font-semibold">Lead:</span> {booking.lead_name || 'Unknown'}</div>
                </div>
                <div className="bg-gray-50 border border-gray-200 rounded-md p-4 text-gray-800 whitespace-pre-wrap">
                    {booking.booking_note || 'No booking notes found.'}
                </div>
                <div className="flex justify-end mt-6">
                    <OutlineButton title="Close" onClick={onClose} />
                </div>
            </div>
        </div>
    );
}
