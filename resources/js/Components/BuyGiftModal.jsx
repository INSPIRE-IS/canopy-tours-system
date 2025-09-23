import React, { use, useEffect, useState } from 'react';
import axios from 'axios';
import InputText from './InputText';
import { useForm } from '@inertiajs/react';

export default function BuyGiftModal({ isOpen, onClose, activityName, pricePerAdult, pricePerChild, activityId, auth }) {
  console.log("gift activity", activityName);
  const [total, setTotal] = useState(0.00);
  const mode = 'create';
  const { data, setData, post, put, errors, reset, processing } = useForm({
    adults: null,
    children: null,
    full_name: '',
    email: '',
    phone: '',
    date: new Date().toISOString().split('T')[0],
    time: new Date().toISOString().split('T')[1],
    reference: '',
    activity_id: activityId,
  });

  useEffect(() => {

    if (auth && auth?.customer) {
      console.log('add customer ', auth.customer);
      setData({
        ...data,
        full_name: auth.customer.full_name,
        email: auth.customer.email_address,
        phone: auth.customer.contact_number,
      });
    }
  }, [auth]);

  useEffect(() => {

    const sum = (Number(data.adults) * pricePerAdult + Number(data.children) * pricePerChild);
    if (sum > 0) {
      setTotal(sum.toFixed(2));
    }
    // setTotal((Number(data.adults) * pricePerAdult + Number(data.children) * pricePerChild).toFixed(2));
  }, [data.adults, data.children, pricePerAdult, pricePerChild]);

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // const handleChange = (e) => {
  //   setData({ ...data, [e.target.name]: e.target.value });
  // };
  const handleChange = (target, value) => {
    setData(target, value);
    //setData("name", e.target.value);
  };
  const handleConfirm = async () => {
    setLoading(true);
    setError(null);

    try {
      const response = await axios.post(route('front.booking.gift-store'), data);

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
        <h2 className="text-xl font-semibold mb-4">{activityName}</h2>
        <p><strong>Adults:</strong> {data.adults}</p>
        <p><strong>Children:</strong> {data.children}</p>
        <p><strong>Total:</strong> R {total}</p>

        <div className="mt-4 space-y-2">
          <InputText
            title="Number of Adults"
            field="adults"
            value={data.adults}
            type="number"
            width="w-full"
            height="h-[42px]"
            handleChange={handleChange}
            errors={errors}
            mode={mode}
            required
            min="0"
          />
          <InputText
            title="Number of Children"
            field="children"
            value={data.children}
            type="number"
            width="w-full"
            height="h-[42px]"
            handleChange={handleChange}
            errors={errors}
            mode={mode}
            required
            min="0"
          />
          <InputText
            title="Full Name"
            field="full_name"
            value={data.full_name}
            type="text"
            width="w-full"
            height="h-[42px]"
            handleChange={handleChange}
            errors={errors}
            mode={mode}
            required
          />
          <InputText
            title="Email"
            field="email"
            value={data.email}
            type="email"
            width="w-full"
            height="h-[42px]"
            handleChange={handleChange}
            errors={errors}
            mode={mode}
            required
          />
          <InputText
            title="Phone Number"
            field="phone"
            value={data.phone}
            type="text"
            width="w-full"
            height="h-[42px]"
            handleChange={handleChange}
            errors={errors}
            mode={mode}
            required
          />
        </div>

        <div className="flex justify-between mt-6">
          <button onClick={onClose} className="px-4 py-2 bg-gray-200 rounded">Cancel</button>
          <button
            onClick={handleConfirm}
            disabled={processing}
            className="px-4 py-2 bg-green-600 text-white rounded"
          >
            {processing ? 'Processing...' : 'Confirm & Pay'}
          </button>
        </div>
      </div>
    </div>
  );
}
