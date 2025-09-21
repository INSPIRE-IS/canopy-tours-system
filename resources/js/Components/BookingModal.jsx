import React, { useEffect, useState } from 'react';
import axios from 'axios';

export default function BookingModal({ isOpen, onClose, slot, adults, children, pricePerAdult, pricePerChild, activityId }) {
  const [total, setTotal] = useState(0.00);
  const [form, setForm] = useState({
    adults: adults,
    children: children,
    name: '',
    surname: '',
    email: '',
    phone: '',
    activity_id: activityId,
  });

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {

    const sum = (Number(form.adults) * pricePerAdult + Number(form.children) * pricePerChild);
    if (sum > 0) {
      setTotal(sum.toFixed(2));
    }
    // setTotal((Number(form.adults) * pricePerAdult + Number(form.children) * pricePerChild).toFixed(2));
  }, [form.adults, form.children, pricePerAdult, pricePerChild]);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleConfirm = async () => {
    setLoading(true);
    setError(null);

    try {
      const response = await axios.post(route('front.booking.store'), {
        ...form,
        date: slot.date,
        time: slot.time,
        activity_id: activityId,
      });

      console.log('Booking response:', response.data);

      // Redirect to PayFast or show success
      window.location.href = response.data.redirect_url;

    } catch (err) {
      console.error('Booking failed:', err.response?.data || err);
      setError(err.response?.data?.error || 'An error occurred.');
    } finally {
      setLoading(false);
    }
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
        <p><strong>Total:</strong> R{total}</p>

        <div className="mt-4 space-y-2">
          <input name="name" onChange={handleChange} placeholder="Name" className="border p-2 w-full" />
          <input name="surname" onChange={handleChange} placeholder="Surname" className="border p-2 w-full" />
          <input name="email" onChange={handleChange} placeholder="Email" className="border p-2 w-full" />
          <input name="phone" onChange={handleChange} placeholder="Phone Number" className="border p-2 w-full" />
        </div>

        {error && <p className="text-red-500 mt-2">{error}</p>}

        <div className="flex justify-between mt-6">
          <button onClick={onClose} className="px-4 py-2 bg-gray-200 rounded">Cancel</button>
          <button
            onClick={handleConfirm}
            disabled={loading}
            className="px-4 py-2 bg-green-600 text-white rounded"
          >
            {loading ? 'Processing...' : 'Confirm & Pay'}
          </button>
        </div>
      </div>
    </div>
  );
}
