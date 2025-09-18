import React, { useState } from 'react';
import { usePage, router } from '@inertiajs/react';

export default function ReviewPayment() {
  const { booking } = usePage().props;
  const [file, setFile] = useState(null);
  const [method, setMethod] = useState('payfast'); // 'payfast' or 'offline'
  const [submitting, setSubmitting] = useState(false);
  const [offlineNote, setOfflineNote] = useState('');

  const handleOfflineSubmit = async () => {
    setSubmitting(true);
    const formData = new FormData();

    formData.append('booking_id', booking.id);
    formData.append('note', offlineNote);
    formData.append('method', 'offline');
    formData.append('type', 'booking');
    if (file) formData.append('proof', file);

    try {
      await axios.post(route('front.payment.offline'), formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      window.location.reload(); // or redirect to thank-you
    } catch (error) {
      console.error('Upload failed:', error);
    } finally {
      setSubmitting(false);
    }
  };

  const handlePayFast = () => {
    // Fake redirect to a placeholder "PayFast" screen
    router.get(route('front.payment.simulated'), {
      booking: booking.id, type: 'booking',
    });
  };

  return (
    <div className="max-w-2xl mx-auto p-6">
      <h1 className="text-2xl font-bold mb-4">Complete Your Booking</h1>

      <div className="bg-white shadow p-6 rounded-xl space-y-6">
        <div>
          <h2 className="font-semibold">Booking Details</h2>
          <p><strong>Name:</strong> {booking.lead_name}</p>
          <p><strong>Date:</strong> {booking.date}</p>
          <p><strong>Time:</strong> {booking.time}</p>
          <p><strong>Amount:</strong> R{Number(booking.grand_tot_due).toFixed(2)}</p>
        </div>

        <div>
          <h2 className="font-semibold mb-2">Select Payment Method</h2>
          <div className="space-x-4">
            <label>
              <input
                type="radio"
                name="method"
                value="payfast"
                checked={method === 'payfast'}
                onChange={() => setMethod('payfast')}
              />{' '}
              Pay Online (Simulated)
            </label>

            <label>
              <input
                type="radio"
                name="method"
                value="offline"
                checked={method === 'offline'}
                onChange={() => setMethod('offline')}
              />{' '}
              Pay Offline (e.g. Cash / EFT)
            </label>
          </div>
        </div>

        {method === 'offline' && (
          <div>
            <h3 className="font-medium mb-1">Payment Note</h3>
            <textarea
              className="border p-2 w-full rounded"
              rows="3"
              value={offlineNote}
              onChange={(e) => setOfflineNote(e.target.value)}
            ></textarea>

            <h3 className="font-medium mt-4 mb-1">Upload Proof of Payment</h3>
            <input
              type="file"
              onChange={(e) => setFile(e.target.files[0])}
              accept=".jpg,.jpeg,.png,.pdf"
              className="border p-2 rounded w-full"
            />

            <button
              onClick={handleOfflineSubmit}
              className="mt-4 px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700"
              disabled={submitting}
            >
              {submitting ? 'Submitting...' : 'Submit Offline Payment'}
            </button>
          </div>
        )}

        {method === 'payfast' && (
          <div>
            <button
              onClick={handlePayFast}
              className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
            >
              Continue to Payment
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
