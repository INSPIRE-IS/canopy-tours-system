import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { set } from 'date-fns';
import { useForm } from '@inertiajs/react';
import InputText from './InputText';

export default function BookingModal({ isOpen, onClose, activity, slot, adults, children, pricePerAdult, pricePerChild, activityId, auth }) {
  const [total, setTotal] = useState(0.00);
  const mode = 'create';
  const { data, setData, post, put, errors, reset, processing } = useForm({
    adults: adults,
    children: children,
    name: '',
    surname: '',
    email: '',
    phone: '',
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

  // const handleChange = (e) => {
  //   setData({ ...data, [e.target.name]: e.target.value });
  // };
  const handleChange = (target, value) => {
    setData(target, value);
    //setData("name", e.target.value);
  };

  const handleConfirm = async () => {
    try {
      const response = await axios.post(route('front.booking.store'), {
        ...data,
        date: slot.date,
        time: slot.time,
        activity_id: activityId,
      });

      console.log('Booking response:', response.data);

      // Redirect to PayFast or show success
      window.location.href = response.data.redirect_url;

    } catch (err) {
      console.error('Booking failed:', err.response?.data || err);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
      <div className="bg-white p-6 rounded-xl max-w-md w-full">
        <h2 className="text-xl font-semibold mb-4">Booking Summary</h2>
        <h2 className="text-xl font-semibold mb-4">{activity?.service} {activity?.season}</h2>
        <p><strong>Date:</strong> {slot.date}</p>
        <p><strong>Time:</strong> {slot.time}</p>
        <p><strong>Adults:</strong> {adults}</p>
        <p><strong>Children:</strong> {children}</p>
        <p><strong>Total:</strong> R{total}</p>

        <div className="mt-4 space-y-2">
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
