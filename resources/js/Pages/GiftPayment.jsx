
import React, { useEffect, useRef, useState } from 'react';
import { usePage, Link, Head, router } from '@inertiajs/react';
import CustomSelect from '@/Components/CustomSelect';
import NavSelect from '@/Components/NavSelect';
import TopNav from '@/Components/TopNav';

export default function ReviewPayment({ auth, gift, payfastStatus }) {
  //const { props, booking } = usePage();
  console.log('auth ', auth);
  console.log('gift ', gift);
  const [submitting, setSubmitting] = useState(false);
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


  const handlePayFast = () => {
    window.location.href = route('front.payment.payfast.gift', gift.id);
  };

  return (
    <div className="bg-gray-50 min-h-screen">
      <Head title="Welcome" />
      <TopNav auth={auth} />


      <div className="max-w-2xl mx-auto p-6">
        <h1 className="text-2xl font-bold mb-4">Complete Your Purchase</h1>

        <div className="bg-white shadow p-6 rounded-xl space-y-6">
          {payfastStatus && (
            <div className="text-xs text-gray-600">
              <div>PayFast status: {payfastStatus.enabled ? 'enabled' : 'disabled'} | {payfastStatus.is_sandbox ? 'sandbox' : 'live'} | source: {payfastStatus.source}</div>
              <div>Endpoint: {payfastStatus.endpoint_url}</div>
              <div>Return: {payfastStatus.return_url}</div>
              <div>Cancel: {payfastStatus.cancel_url}</div>
              <div>Notify: {payfastStatus.notify_url}</div>
              <div>Trust return: {payfastStatus.trust_return ? 'yes' : 'no'}</div>
            </div>
          )}
          <div>
            <h2 className="font-semibold">Gift Voucher Details</h2>
            <p><strong>Name:</strong> {gift.full_name}</p>
            <p><strong>Date:</strong> {gift.created_at.split('T')[0]}</p>
            <p><strong>Time:</strong> {gift.created_at.split('T')[1].slice(0, 5)}</p>
            <p><strong>Amount:</strong> R{Number(gift.grand_tot_due).toFixed(2)}</p>
          </div>

          <div>
            <h2 className="font-semibold mb-2">Payment Method</h2>
            <p>Pay Online (PayFast)</p>
          </div>

          <div>
            <button
              onClick={handlePayFast}
              disabled={payfastStatus && !payfastStatus.enabled}
              className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
            >
              Continue to Payment
            </button>
          </div>
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
