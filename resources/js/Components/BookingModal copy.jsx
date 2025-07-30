import React, { useState } from 'react';
import { router } from '@inertiajs/react';

export default function BookingModal({ isOpen, onClose, slot, adults, children, pricePerAdult, activityId }) {
  const [form, setForm] = useState({
    name: '',
    surname: '',
    email: '',
    phone: '',
  });

  const total = (Number(adults) * pricePerAdult); // + logic for children

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleConfirm = () => {
    router.post(route('front.booking.store'), {
      ...form,
      date: slot.date,
      time: slot.time,
      adults,
      children,
      activity_id: activityId,
    }, {
      onSuccess: (page) => {
        const bookingId = page.props.booking_id;
        // Redirect to PayFast or show next modal
        console.log('Booking successful:', bookingId);
        onClose();
      },
      onError: (errors) => {
        console.error(errors);
      },
    });
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
      <div className="bg-white p-6 rounded-xl max-w-md w-full">
        <h2 className="text-xl font-semibold mb-4">Booking Summary</h2>
        <p><strong>Date:</strong> {slot.date}</p>
        <p><strong>Time:</strong> {slot.time}</p>
        <p><strong>Adults:</strong> {adults}</p>
        <p><strong>Children:</strong> {children}</p>
        <p><strong>Total:</strong> R{total.toFixed(2)}</p>

        <div className="mt-4 space-y-2">
          <input name="name" onChange={handleChange} placeholder="Name" className="border p-2 w-full" />
          <input name="surname" onChange={handleChange} placeholder="Surname" className="border p-2 w-full" />
          <input name="email" onChange={handleChange} placeholder="Email" className="border p-2 w-full" />
          <input name="phone" onChange={handleChange} placeholder="Phone Number" className="border p-2 w-full" />
        </div>

        <div className="flex justify-between mt-6">
          <button onClick={onClose} className="px-4 py-2 bg-gray-200 rounded">Cancel</button>
          <button onClick={handleConfirm} className="px-4 py-2 bg-green-600 text-white rounded">Confirm & Pay</button>
        </div>
      </div>
    </div>
  );
}
