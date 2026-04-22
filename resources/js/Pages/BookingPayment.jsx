
import React, { useEffect, useRef, useState } from 'react';
import { Link, Head, router, usePage } from '@inertiajs/react';
import CustomSelect from '@/Components/CustomSelect';
import NavSelect from '@/Components/NavSelect';
import TopNav from '@/Components/TopNav';

export default function ReviewPayment({ auth, booking, lookups, payfastStatus }) {
  //const { props, booking } = usePage();
  console.log('auth ', auth);
  console.log('booking ', booking);
  const [submitting, setSubmitting] = useState(false);
  const [teamId, setTeamId] = useState(booking.team_id);
  const totalDue = Number(booking?.grand_tot_due || 0);

  // VAT lookup has had multiple historical shapes:
  // - `rate` sometimes stored as percent (15) and sometimes as fraction (0.15)
  // - `multiplier` sometimes stored as full multiplier (1.15) and sometimes as fraction (0.15)
  // We normalize here so the payment page always matches the invoice breakdown.
  const vatRateRaw = Number(lookups?.rate ?? 0);
  const vatMultiplierRaw = Number(lookups?.multiplier ?? 0);

  let vatMultiplier = 1.15;
  let vatPercent = 15;

  if (vatMultiplierRaw > 1 && vatMultiplierRaw < 3) {
    vatMultiplier = vatMultiplierRaw;
    vatPercent = Math.max(0, (vatMultiplierRaw - 1) * 100);
  } else if (vatRateRaw > 0 && vatRateRaw < 1) {
    vatPercent = vatRateRaw * 100;
    vatMultiplier = 1 + vatRateRaw;
  } else if (vatRateRaw >= 1 && vatRateRaw <= 100) {
    vatPercent = vatRateRaw;
    vatMultiplier = 1 + (vatRateRaw / 100);
  } else if (vatMultiplierRaw > 0 && vatMultiplierRaw < 1) {
    // legacy "multiplier" stored as delta/fraction (0.15)
    vatPercent = vatMultiplierRaw * 100;
    vatMultiplier = 1 + vatMultiplierRaw;
  }

  const amountExVat = totalDue > 0 ? (totalDue / vatMultiplier) : 0;
  const vatAmount = totalDue > 0 ? Math.max(0, totalDue - amountExVat) : 0;



  const [showServices, setShowServices] = useState(false);
  const [showLocation, setShowLocation] = useState(false);
  const [showLocationModal, setShowLocationModal] = useState(false);

  const locationsRef = useRef();
  const servicesRef = useRef();


  useEffect(() => {
    console.log('team_id', booking.team_id);
    if (booking.teamId != 0) {
      setTeamId(booking.team_id);
      localStorage.setItem('preferred_team_id', booking.team_id);
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


  useEffect(() => {
    const handleClickOutside = (event) => {
      if (servicesRef.current && !servicesRef.current.contains(event.target)) {
        setShowServices(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);


  const handleDownload = async () => {
    setSubmitting(true);
    axios.post(route("front.invoices.download"), {
      team_id: value,
    })
      .then((response) => {
        console.log("Team changed successfully", response.data);
        // Optional: trigger a page reload or flash message
        window.location.reload(); // or Inertia.reload()
      })
      .catch((error) => {
        console.error("Error changing team", error);
      })
      .finally(() => {
        setSubmitting(false);
      });

  };


  const handlePayFast = () => {
    window.location.href = route('front.payment.payfast.booking', booking.id);
  };

  return (
    <div className="bg-gray-50 min-h-screen">
      <Head title="Welcome" />
      <TopNav auth={auth} />


      <div className="max-w-2xl mx-auto p-6">
        <h1 className="text-2xl font-bold mb-4">Complete Your Booking</h1>

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
            <h2 className="font-semibold">Booking Details</h2>
            <p className='flex w-auto'><strong className='w-[200px] capitalize'>Name:</strong> {booking.lead_name}</p>
            <p className='flex w-auto'><strong className='w-[200px] capitalize'>Due Date:</strong> {booking.due_date}</p>
            <p className='flex w-auto'><strong className='w-[200px] capitalize'>Refererence:</strong> {booking.reference}</p>
            <p className='flex w-auto'><strong className='w-[200px] capitalize'>Amount (Excl VAT):</strong> R{amountExVat.toFixed(2)}</p>
            <p className='flex w-auto'><strong className='w-[200px] capitalize'>VAT:</strong> {vatPercent.toFixed(2)}%</p>
            <p className='flex w-auto'><strong className='w-[200px] capitalize'>VAT Amount:</strong> R{vatAmount.toFixed(2)}</p>
            <p className='flex w-auto'><strong className='w-[200px] capitalize'>Total:</strong> R{totalDue.toFixed(2)}</p>
          </div>

          <div>
            <h2 className="font-semibold mb-2">Payment Method</h2>
            <p>Pay Online (PayFast)</p>
          </div>

          <div className="flex justify-center gap-4">
            <button
              onClick={handlePayFast}
              disabled={payfastStatus && !payfastStatus.enabled}
              className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
            >
              Continue to Payment
            </button>
            <a
              href={route('front.invoices.download', booking.id)}
              target="_blank"
              rel="noopener noreferrer"
              className="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700"
            >
              Proforma Invoice
            </a>
            <a
              href={route('front.details.download', booking.id)}
              target="_blank"
              rel="noopener noreferrer"
              className="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700"
            >
              Activity
            </a>

            {/* <button
              onClick={handleDownload}
              className="mt-4 px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700"
              disabled={submitting}
            >
              {submitting ? 'Downloading...' : 'Download Invoice PDF'}
            </button> */}

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
