
import React, { useEffect, useRef, useState } from 'react';
import { usePage, Link, Head, router } from '@inertiajs/react';
import CustomSelect from '@/Components/CustomSelect';
import NavSelect from '@/Components/NavSelect';

export default function ReviewPayment({ auth, gift }) {
  //const { props, booking } = usePage();
  console.log('auth ', auth);
  console.log('gift ', gift);
  const [file, setFile] = useState(null);
  const [method, setMethod] = useState('payfast'); // 'payfast' or 'offline'
  const [submitting, setSubmitting] = useState(false);
  const [offlineNote, setOfflineNote] = useState('');
  const [date, setDate] = useState(new Date());
  const [teamId, setTeamId] = useState(gift.team_id);
  const [showServices, setShowServices] = useState(false);
  const [showLocation, setShowLocation] = useState(false);
  const [showLocationModal, setShowLocationModal] = useState(false);
  const locationsRef = useRef();
  const servicesRef = useRef();


  useEffect(() => {
    console.log('team_id', gift.team_id);
    if (teamId != 0) {
      setTeamId(gift.team_id);
      localStorage.setItem('preferred_team_id', gift.team_id);
      return;
    }
    const savedTeamId = localStorage.getItem('preferred_team_id');
    console.log('savedTeamId', savedTeamId);
    if (savedTeamId == null || savedTeamId == "undefined" || savedTeamId == 0) {
      // Ask for location or show modal
      // For now, show modal with team select
      setShowLocationModal(true);
    } else {
      // Redirect to page with team_id
      //localStorage.setItem('preferred_team_id', savedTeamId);
      router.get(route('front.welcome', { team_id: savedTeamId }));
    }
  }, []);


  const handleOfflineSubmit = async () => {
    setSubmitting(true);
    const formData = new FormData();

    formData.append('gift_id', gift.id);
    formData.append('note', offlineNote);
    formData.append('method', 'offline');
    formData.append('type', 'voucher');
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
      gift_id: gift.id, type: 'voucher',
    });
  };

  return (
    <div className="bg-gray-50 min-h-screen">
      <Head title="Welcome" />
      <TopNav auth={auth} />


      <div className="max-w-2xl mx-auto p-6">
        <h1 className="text-2xl font-bold mb-4">Complete Your Purchase</h1>

        <div className="bg-white shadow p-6 rounded-xl space-y-6">
          <div>
            <h2 className="font-semibold">Gift Voucher Details</h2>
            <p><strong>Name:</strong> {gift.full_name}</p>
            <p><strong>Date:</strong> {gift.created_at.split('T')[0]}</p>
            <p><strong>Time:</strong> {gift.created_at.split('T')[1].slice(0, 5)}</p>
            <p><strong>Amount:</strong> R{Number(gift.grand_tot_due).toFixed(2)}</p>
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
      {showLocationModal && (
        <div className="fixed inset-0 bg-black/50 flex justify-center items-center z-50">
          <div className="bg-white p-6 rounded-lg shadow w-full max-w-md">
            <CustomSelect
              label="Select Location"
              name="location"
              value={teamId}
              mode="create"
              onChange={(e) => {
                localStorage.setItem('preferred_team_id', e.target.value);
                setShowLocationModal(false);
                router.get(route('front.welcome', { team_id: e.target.value }));
                //setData(e.target.name, e.target.value);
              }}
              options={teams}
            />


            {/* <Select
            options={teams.map(team => ({ label: team.name, value: team.id }))}
            onChange={(val) => {
              console.log('val', val);

            }}
          /> */}
          </div>
        </div>
      )}

    </div>

  );
}
