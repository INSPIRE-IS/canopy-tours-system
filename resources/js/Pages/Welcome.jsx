import React, { useEffect, useRef, useState } from 'react';
import { Link, Head, router } from '@inertiajs/react';
import Card from '@/Components/ui/card';
import CustomSelect from '@/Components/CustomSelect';
import NavSelect from '@/Components/NavSelect';
import BuyGiftModal from '@/Components/BuyGiftModal';
import CustomInputText from '@/Components/CustomInputText';
import BlueButton from '@/Components/BlueButton';
import RichTextDisplay from '@/Components/RichTextDisplay';

export default function HomePage({ activities, auth, teams, team_id, team }) {
  console.log('activities', activities);
  console.log('team', team);
  const [date, setDate] = useState(new Date());
  const [teamId, setTeamId] = useState(team_id);
  const [selectedService, setSelectedService] = useState(null);
  const [adults, setAdults] = useState(0);
  const [children, setChildren] = useState(0);
  const [showServices, setShowServices] = useState(false);
  const [showLocation, setShowLocation] = useState(false);
  const [showLocationModal, setShowLocationModal] = useState(false);
  const [giftModal, setGiftModal] = useState(false);
  const [selectedActivity, setSelectedActivity] = useState(null);
  const locationsRef = useRef();
  const servicesRef = useRef();


  useEffect(() => {
    console.log('team_id', team_id);
    if (teamId != 0) {
      setTeamId(team_id);
      localStorage.setItem('preferred_team_id', team_id);
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

  const handleGift = (event, activity) => {
    event.preventDefault();
    console.log('event', event);
    setSelectedActivity(activity);
    setGiftModal(true);
  };



  return (
    <div className="bg-gray-50 min-h-screen">
      <Head title="Welcome" />
      <div className="bg-gray-50 sticky top-0 z-50">
        <div className="max-w-screen-xl mx-auto px-4 pt-12 flex justify-between items-center">
          <Link href="/" className="text-xl font-bold text-blue-700">
            <img src={`${team?.file_logo?.path}`} alt="Canopy Tours" className="h-10" />
          </Link>
          <nav className="flex gap-6 text-sm text-gray-700">
            <Link href="/" className="hover:text-blue-600">Home</Link>
            {/* <Link href="/services" className="hover:text-blue-600">Services</Link> */}
            {/* Services with dropdown modal */}
            <div className="relative hidden sm:block" ref={locationsRef}>
              <button
                onClick={() => setShowLocation(!showLocation)}
                className="hover:text-blue-600"
              >
                Venue
              </button>

              {showLocation && (
                <div className="absolute top-full mt-2 w-64 bg-white border rounded shadow-lg p-4 z-50 ">
                  <h3 className="text-sm font-semibold text-gray-700 mb-2">Choose a Location</h3>
                  {/* <NavSelect name="All Locations" onChange={() => router.get('/locations')} /> */}
                  {teams.map((team) => (
                    <NavSelect name={team.name} onChange={(e) => {
                      localStorage.setItem('preferred_team_id', team.id);
                      setShowLocationModal(false);
                      router.get(route('front.welcome', { team_id: team.id }));
                      //setData(e.target.name, e.target.value);
                    }} />
                  ))}
                </div>
              )}
            </div>


            <div className="relative hidden sm:block" ref={servicesRef}>
              <button
                onClick={() => setShowServices(!showServices)}
                className="hover:text-blue-600"
              >
                Services
              </button>

              {showServices && (
                <div className="absolute top-full mt-2 w-64 bg-white border rounded shadow-lg p-4 z-50">
                  <h3 className="text-sm font-semibold text-gray-700 mb-2">Choose a Service</h3>
                  {activities?.map((activity) => {
                    return (
                      <NavSelect name={activity.name} onChange={() => router.get(route('front.activity', { activity_id: activity.id }))} />
                    )
                  })}
                </div>
              )}
            </div>
            {/* <Link href="/vouchers" className="hover:text-blue-600">Gift Vouchers</Link> route('front.payment.booking', ['booking' => $booking->id]) */}
            {auth?.bookingId && <Link href={route('front.payment.booking', { booking: auth?.bookingId })} className="hover:text-blue-600">Cart {auth?.count > 0 ? '(' + auth?.count + ')' : ''}</Link>}
            {/* <Link href="/questionnaire" className="hover:text-blue-600">Questionnaire</Link> */}
            <Link href="/login" className="hover:text-blue-600">Login</Link>
          </nav>
        </div>
      </div>
      <div className="max-w-screen-xl mx-auto px-4 py-8">
        <div className="grid md:grid-cols-2 gap-6 items-start">
          <div className="relative rounded-[20px] overflow-hidden shadow-lg h-[400px]">
            <img
              src={`${team?.file_image?.path}`}
              alt="Canopy Tours"
              className="w-full h-full object-cover"
            />
            <div className="absolute inset-0 bg-black/30 flex flex-col justify-center p-6 text-white">
              {/* <h1 className="text-2xl md:text-4xl font-bold">Stormriver Adventures</h1>
              <p className="mt-2 text-lg md:text-xl">
                Come and Experience a World of Diversity in One Forest
              </p>
              <p className="mt-1 text-sm md:text-base">
                A Unique Tsitsikamma Experience You’ll Never Forget!
              </p> */}
              <div className="text-sm leading-relaxed text-white space-y-2">
                <div className="prose prose-invert max-w-none" dangerouslySetInnerHTML={{ __html: team?.description }} />
              </div>
            </div>
          </div>

          <div className="rounded-[20px] bg-white shadow h-[400px] ">
            <div className='space-y-[12px]  p-6 mt-3'>
              <span className="text-welcome-card-header ">I'm looking for</span>
              {/* <Select options={[{ value: 'all', label: 'Show me everything you can do!' }]} /> */}
              <CustomSelect
                name="service"
                optionText='Show me everything you can do!'
                value={selectedService}
                mode="create"
                onChange={(e) => { setSelectedService(e.target.value); }}
                options={activities}
              //options={services.map(service => ({ label: service.name, value: service.id }))}
              />

              <div className="grid grid-cols-2 pt-1 gap-4">
                <CustomInputText
                  title="Number of Adults"
                  id="adults"
                  name="adults"
                  type='number'
                  min="0"
                  value={adults}
                  onChange={(e) => setAdults(e.target.value)}
                />
                <CustomInputText
                  title="Number of Children"
                  id="children"
                  name="children"
                  type='number'
                  min="0"
                  value={children}
                  onChange={(e) => setChildren(e.target.value)}
                />
              </div>
              <CustomInputText
                title="Desired Date of Booking"
                id="date"
                name="date"
                type='date'
                value={date}
                onChange={(e) => setDate(e.target.value)}
              />
              <div className='pt-3'>
                <BlueButton
                  title="Find Activities"
                  onClick={() => router.get(route('front.activity'), {
                    activity_id: selectedService,
                    adults: adults,
                    children: children,
                    start_date: date,
                  })}
                />
              </div>
            </div>

          </div>
        </div>

        <div className="mt-10 grid md:grid-cols-3 sm:grid-cols-2 grid-cols-1 gap-6">
          {activities.length >= 1 ? (
            activities?.map((activity) => {

              return (
                <Card
                  key={activity?.id + activity?.inventory_id}
                  image={activity?.image}
                  tag={activity?.tag}
                  service={activity?.name}
                  season={activity?.season}
                  price={activity?.price}
                  badge={activity?.pillbox_conditions}
                  promo={activity?.promo}
                  gift={activity?.gift}
                  onBook={() => router.get(route('front.activity', { activity_id: activity?.season_id }))}
                  onGift={(e) => handleGift(e, activity)}
                />
              )
            })
          ) : (
            <div className="flex justify-center items-center">
              <div className="text-center">
                <h1 className="text-2xl md:text-4xl font-bold">No activities found</h1>
                <p className="mt-2 text-lg md:text-xl">
                  Please try again later or contact us for more information.
                </p>
              </div>
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
      {giftModal && (
        <BuyGiftModal
          isOpen={giftModal}
          onClose={() => { setGiftModal(false); setSelectedActivity(null); }}
          pricePerAdult={selectedActivity.rake_rate_adult}
          pricePerChild={selectedActivity.rake_rate_child}
          activityName={selectedActivity.name}
          activityId={selectedActivity.id}
          auth={auth}
        />
      )}

    </div>

  );
}