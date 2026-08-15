import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Link, Head, router } from '@inertiajs/react';
import Card from '@/Components/ui/card';
import CustomSelect from '@/Components/CustomSelect';
import NavSelect from '@/Components/NavSelect';
import BuyGiftModal from '@/Components/BuyGiftModal';
import CustomInputText from '@/Components/CustomInputText';
import BlueButton from '@/Components/BlueButton';
import RichTextDisplay from '@/Components/RichTextDisplay';

export default function HomePage({ activities, auth, teams, team_id, team }) {
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
  const activitiesRef = useRef();

  const serviceOptions = useMemo(() => (activities || []).map((activity) => ({
    ...activity,
    id: activity.season_id,
    name: `${activity.name} ${activity.season || ''}`.trim(),
  })), [activities]);


  useEffect(() => {
    const explicitTeamId = Number(team_id || 0);
    if (explicitTeamId > 0) {
      setTeamId(explicitTeamId);
      localStorage.setItem('preferred_team_id', String(explicitTeamId));
      return;
    }

    // If there are no published teams available, don't trap the user behind the
    // location picker modal (it has no close button).
    if ((teams || []).length === 0) {
      setShowLocationModal(false);
      localStorage.removeItem('preferred_team_id');
      return;
    }

    const allowedTeamIds = new Set((teams || []).map((t) => Number(t.id)));
    const savedTeamId = Number(localStorage.getItem('preferred_team_id') || 0);
    if (savedTeamId > 0) {
      // If the previously selected team is no longer available (e.g. unpublished),
      // clear it so the user can pick a valid team instead of looping on a 404.
      if (allowedTeamIds.has(savedTeamId)) {
        router.get(route('front.welcome', { team_id: savedTeamId }));
        return;
      }

      localStorage.removeItem('preferred_team_id');
    }

    if ((teams || []).length === 1) {
      const onlyTeamId = teams[0]?.id;
      if (onlyTeamId) {
        localStorage.setItem('preferred_team_id', String(onlyTeamId));
        router.get(route('front.welcome', { team_id: onlyTeamId }));
        return;
      }
    }

    setShowLocationModal(true);
  }, [team_id, teams]);


  useEffect(() => {
    if (serviceOptions.length === 0) {
      setSelectedService(null);
      return;
    }

    const hasSelectedService = serviceOptions.some((option) => String(option.id) === String(selectedService || ''));
    if (!hasSelectedService) {
      setSelectedService(String(serviceOptions[0].id));
    }
  }, [serviceOptions, selectedService]);

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



  if ((teams || []).length === 0) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
        <Head title="Under Construction" />
        <div className="w-full max-w-xl bg-white shadow-lg rounded-[20px] p-8 text-center">
          <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Site under construction</h1>
          <p className="mt-3 text-gray-600">
            There are no published venues available yet. If you are staff, please log in to finish setting up your site.
          </p>

          <div className="mt-6 flex items-center justify-center gap-3">
            <Link
              href={route('login')}
              className="inline-flex items-center justify-center h-[44px] px-5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition"
            >
              Staff login
            </Link>
            <button
              type="button"
              onClick={() => router.reload({ preserveScroll: true })}
              className="inline-flex items-center justify-center h-[44px] px-5 rounded-xl border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 transition"
            >
              Refresh
            </button>
          </div>
        </div>
      </div>
    );
  }

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
                      <NavSelect
                        key={`${activity.id}-${activity.season_id}`}
                        name={`${activity.name} ${activity.season || ''}`.trim()}
                        onChange={() => router.get(route('front.activity', { activity_id: activity.season_id }))}
                      />
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
              <CustomSelect
                name="service"
                showOption={false}
                value={selectedService}
                mode="create"
                onChange={(e) => { setSelectedService(e.target.value); }}
                options={serviceOptions}
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
                  onClick={() => {
                    if (!selectedService) {
                      activitiesRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                      return;
                    }

                    router.get(route('front.activity'), {
                      activity_id: selectedService,
                      adults: adults,
                      children: children,
                      start_date: date,
                    });
                  }}
                />
              </div>
            </div>

          </div>
        </div>

        <div ref={activitiesRef} className="mt-10 grid md:grid-cols-3 sm:grid-cols-2 grid-cols-1 gap-6">
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
          countries={countries}
        />
      )}

    </div>

  );
}
