import React, { use, useEffect, useState } from 'react';
import axios from 'axios';

export default function BuyGiftModal({ isOpen, onClose, pricePerAdult, pricePerChild, activityId }) {
  const [total, setTotal] = useState(0.00);
  const [form, setForm] = useState({
    adults: 0, 
    children: 0,
    name: '',
    surname: '',
    email: '',
    phone: '',
    date: new Date().toISOString().split('T')[0],
    time: new Date().toISOString().split('T')[1],
    reference: '',
    activity_id: activityId,
  });

  useEffect(() => {

    const sum = (Number(form.adults) * pricePerAdult + Number(form.children) * pricePerChild);
    if(sum > 0){
      setTotal(sum.toFixed(2));
    }
    // setTotal((Number(form.adults) * pricePerAdult + Number(form.children) * pricePerChild).toFixed(2));
  }, [form.adults, form.children, pricePerAdult, pricePerChild]);

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleConfirm = async () => {
    setLoading(true);
    setError(null);

    try {
      const response = await axios.post(route('front.booking.gift-store'), form);

      console.log('Gift response:', response.data);

      // Redirect to PayFast or show success
      window.location.href = response.data.redirect_url;

    } catch (err) {
      console.error('Gift failed:', err.response?.data || err);
      setError(err.response?.data?.error || 'An error occurred.');
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
      <div className="bg-white p-6 rounded-xl max-w-md w-full">
        <h2 className="text-xl font-semibold mb-4">Gift Summary</h2>
        <p><strong>Adults:</strong> {form.adults}</p>
        <p><strong>Children:</strong> {form.children}</p>
        <p><strong>Total:</strong> R {total}</p>

        <div className="mt-4 space-y-2">
          <input name="adults" onChange={handleChange} placeholder="Adults" className="border p-2 w-full" />
          <input name="children" onChange={handleChange} placeholder="Children" className="border p-2 w-full" />
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
