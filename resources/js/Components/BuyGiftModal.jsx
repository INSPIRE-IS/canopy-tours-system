import React, { useEffect, useRef, useState } from 'react';
import axios from 'axios';
import InputText from './InputText';
import PhoneInput from '@/Components/PhoneInput';
import { useForm, usePage } from '@inertiajs/react';
import { useToast } from '../Context/ToastContext';

export default function BuyGiftModal({ isOpen, onClose, activityName, pricePerAdult, pricePerChild, activityId, auth, countries = [] }) {
  console.log("gift activity", activityName);
  const { showToast } = useToast();
  const { props } = usePage();
  const { flash } = props;
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
    if (flash?.success) {
      showToast(flash.success, "success");
    }
    if (flash?.error) {
      showToast(flash.error, "error");
    }
  }, [flash, showToast]);

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
  const submittingRef = useRef(false);

  // const handleChange = (e) => {
  //   setData({ ...data, [e.target.name]: e.target.value });
  // };
  const handleChange = (target, value) => {
    setData(target, value);
    //setData("name", e.target.value);
  };
  const handleConfirm = async () => {
    if (submittingRef.current) return;

    if (!data.full_name || !data.email || !data.phone) {
      showToast("Please fill in all the required fields.", "info");
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
      showToast("Please enter a valid email address.", "warning");
      return;
    }
    submittingRef.current = true;
    setLoading(true);
    setError(null);

    try {
      const response = await axios.post(route('front.booking.gift-store'), data);

      console.log('Gift response:', response.data);

      // Redirect to PayFast or show success
      window.location.href = response.data.redirect_url;

    } catch (err) {
      console.error('Gift failed:', err.response?.data || err);
      const message = err.response?.data?.error || err.response?.data?.message || 'An error occurred.';
      setError(message);
      showToast(message, 'error');
      submittingRef.current = false;
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
            min="1"
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
          <PhoneInput
            label="Phone Number*"
            name="phone"
            value={data.phone}
            onChange={(val) => setData('phone', val)}
            countries={countries}
            error={errors.phone}
            defaultCountryCode="27"
          />
        </div>

        <div className="flex justify-between mt-6">
          <button onClick={onClose} className="px-4 py-2 bg-gray-200 rounded">Cancel</button>
          <button
            onClick={handleConfirm}
            disabled={loading || processing}
            className="px-4 py-2 bg-green-600 text-white rounded"
          >
            {loading || processing ? 'Processing...' : 'Confirm & Pay'}
          </button>
        </div>
      </div>
    </div>
  );
}
