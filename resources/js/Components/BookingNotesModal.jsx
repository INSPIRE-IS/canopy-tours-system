import React from 'react';
import OutlineButton from '@/Components/OutlineButton';
import BlueButton from '@/Components/BlueButton';
import CustomTextarea from '@/Components/CustomTextarea';
import { useForm, router } from '@inertiajs/react';
import { useToast } from '@/Context/ToastContext';

export default function BookingNotesModal({ isOpen, onClose, booking }) {
    if (!isOpen || !booking) return null;
    const notes = Array.isArray(booking.booking_notes) ? booking.booking_notes : [];
    const { showToast } = useToast();
    const { data, setData, post, processing, errors, reset } = useForm({
        booking_id: booking.id,
        booking_item_id: booking.booking_item_id,
        note: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('admin.bookings.notes.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset('note');
                showToast('Booking note added.', 'success');
                onClose();
            },
        });
    };

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
                <form onSubmit={handleSubmit} className="mb-6">
                    <CustomTextarea
                        name="note"
                        value={data.note}
                        onChange={(e) => setData('note', e.target.value)}
                        rows={4}
                    />
                    {errors.note && <div className="text-xs text-red-500 mt-1">{errors.note}</div>}
                    <div className="flex justify-end mt-3">
                        <BlueButton
                            title="Add Note"
                            width="w-[140px]"
                            onClick={handleSubmit}
                            disabled={processing}
                            type="submit"
                        />
                    </div>
                </form>
                {notes.length === 0 ? (
                    <div className="bg-gray-50 border border-gray-200 rounded-md p-4 text-gray-800 whitespace-pre-wrap">
                        No booking notes found.
                    </div>
                ) : (
                    <div className="space-y-3">
                        {notes.map((note) => (
                            <div key={note.id} className="bg-gray-50 border border-gray-200 rounded-md p-4 text-gray-800 whitespace-pre-wrap">
                                <div className="text-xs text-gray-500 mb-2">
                                    {note.created_at || 'Unknown date'}
                                    {note.created_by ? ` • ${note.created_by}` : ''}
                                </div>
                                <div>{note.note}</div>
                            </div>
                        ))}
                    </div>
                )}
                <div className="flex justify-end mt-6">
                    <OutlineButton title="Close" onClick={onClose} />
                </div>
            </div>
        </div>
    );
}
