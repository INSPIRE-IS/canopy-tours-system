import React from 'react';
import { usePage } from '@inertiajs/react';

export default function SimulatedPayment() {
  const { booking, vatRate } = usePage().props;

  const handleConfirm = () => {
    window.alert(`Booking #${booking.id} marked as paid (simulated)`);
    window.location.href = '/'; // redirect to home or success page
  };

  return (
    <div className="max-w-xl mx-auto p-6 text-center">
      <h1 className="text-2xl font-bold mb-4">Simulated Payment</h1>
      <p className="mb-4">Pretend you're paying R{Number(booking.grand_tot_due * vatRate.multiplier).toFixed(2)}</p>
      <button
        onClick={handleConfirm}
        className="px-6 py-3 bg-blue-600 text-white rounded hover:bg-blue-700"
      >
        Simulate Success
      </button>
    </div>
  );
}