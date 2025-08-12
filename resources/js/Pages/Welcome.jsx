import React, { useEffect, useRef, useState } from 'react';
import { Link, Head, router } from '@inertiajs/react';
import Card from '@/components/ui/card';
import Button from '@/components/ui/button';
import Calendar from '@/components/ui/calendar';
import Input from '@/components/ui/input';
import Select from '@/components/ui/select';
import CustomSelect from '@/Components/CustomSelect';
import NavSelect from '@/Components/NavSelect';

// const activities = [
//   {
//     id: 1,
//     category: 'Canopy Tour',
//     title: 'Tsitsikamma Canopy Tour',
//     price: 'R895.00',
//     image: '/images/card1.jpg',
//     badge: '1 Adult',
//     gift: true,
//     minPeople: 1,
//   },
//   {
//     id: 2,
//     category: 'Woodcutters',
//     title: 'Tsitsikamma Woodcutters Journey',
//     price: 'R540.00',
//     image: '/images/card2.jpg',
//     badge: 'Requires a minimum of 2 People',
//     gift: true,
//     minPeople: 2,
//   },
//   {
//     id: 3,
//     category: 'Guided Hikes',
//     title: 'Tsitsikamma Guided Hikes Green route: 5.09km',
//     price: 'R270.00',
//     image: '/images/card3.jpg',
//     badge: 'Book Now From 21 February 2025',
//     gift: true,
//     minPeople: 2,
//   },
//     {
//     id: 4,
//     category: 'Guided Hikes',
//     title: 'Tsitsikamma Guided Hikes Red route: 7.78km',
//     price: 'R390.00',
//     image: '/images/card4.jpg',
//     badge: 'Requires a minimum of 2 People',
//     gift: true,
//     minPeople: 2,
//   },
//     {
//     id: 5,
//     category: 'Guided Hikes',
//     title: 'Tsitsikamma Guided Hikes Yellow route 8.09km',
//     price: 'R450.00',
//     image: '/images/card5.jpg',
//     badge: 'Requires a minimum of 2 People',
//     gift: true,
//     minPeople: 2,
//   },
//     {
//     id: 6,
//     category: 'Guided Hikes',
//     title: 'Tsitsikamma Canopy Tour Photographic Scavenger Hunt',
//     price: 'R1183.00',
//     image: '/images/card6.jpg',
//     badge: 'Book Now From 21 February 2025',
//     gift: true,
//     minPeople: 2,
//   },
// ];

export default function HomePage({activities, teams, team_id}) {
  console.log('activities', activities);
  const [date, setDate] = useState(new Date());
  const [teamId, setTeamId] = useState(team_id);
  const [showServices, setShowServices] = useState(false);
  const [showLocation, setShowLocation] = useState(false);
  const [showLocationModal, setShowLocationModal] = useState(false);
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



  return (
    <>
    <Head title="Welcome" />
    <header className="bg-white shadow sticky top-0 z-50">
    <div className="max-w-screen-xl mx-auto px-4 py-4 flex justify-between items-center">
      <Link href="/" className="text-xl font-bold text-blue-700">
        <img src="/images/logo.png" alt="Stormriver Adventures" className="h-10" />
      </Link>
      <nav className="flex gap-6 text-sm text-gray-700">
        <Link href="/" className="hover:text-blue-600">Home</Link>
        {/* <Link href="/services" className="hover:text-blue-600">Services</Link> */}
{/* Services with dropdown modal */}
            <div className="relative" ref={locationsRef}>
              <button
                onClick={() => setShowLocation(!showLocation)}
                className="hover:text-blue-600"
              >
                Locations
              </button>

              {showLocation && (
                <div className="absolute top-full mt-2 w-64 bg-white border rounded shadow-lg p-4 z-50">
                  <h3 className="text-sm font-semibold text-gray-700 mb-2">Choose a Location</h3>
                  {/* <NavSelect name="All Locations" onChange={() => router.get('/locations')} /> */}
                  {teams.map((team) => (              
                    <NavSelect name={team.name} onChange={(e) => {
                      localStorage.setItem('preferred_team_id',team.id);
                      setShowLocationModal(false);
                      router.get(route('front.welcome', { team_id: team.id }));          
                      //setData(e.target.name, e.target.value);
                    }} />
                  ))}
                </div>
              )}
            </div>


            <div className="relative" ref={servicesRef}>
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
                    console.log('activity', activity);
                    return(
                    <NavSelect name={activity.service} onChange={() => router.get(`/services/${activity.service_id}`)} />
                  )})}
                </div>
              )}
            </div>
        <Link href="/vouchers" className="hover:text-blue-600">Gift Vouchers</Link>
        <Link href="/cart" className="hover:text-blue-600">Cart</Link>
        <Link href="/questionnaire" className="hover:text-blue-600">Questionnaire</Link>
        <Link href="/login" className="hover:text-blue-600">Login</Link>
      </nav>
    </div>
  </header>
      <div className="max-w-screen-xl mx-auto px-4 py-8">
        <div className="grid md:grid-cols-2 gap-6 items-start">
          <div className="relative rounded-xl overflow-hidden shadow-lg h-[400px]">
            <img
              src="/images/main1.jpg"
              alt="Stormriver Adventures"
              className="w-full h-full object-cover"
            />
            <div className="absolute inset-0 bg-black/50 flex flex-col justify-center p-6 text-white">
              <h1 className="text-2xl md:text-4xl font-bold">Stormriver Adventures</h1>
              <p className="mt-2 text-lg md:text-xl">
                Come and Experience a World of Diversity in One Forest
              </p>
              <p className="mt-1 text-sm md:text-base">
                A Unique Tsitsikamma Experience You’ll Never Forget!
              </p>
            </div>
          </div>

          <div className="rounded-xl bg-white shadow p-6">
            <h2 className="text-xl font-semibold mb-4">I'm looking for</h2>
            <Select options={[{ value: 'all', label: 'Show me everything you can do!' }]} />
            <div className="grid grid-cols-2 gap-4 mt-4">
              <Input placeholder="Number of Adults" />
              <Input placeholder="Number of Children" />
            </div>
            <div className="mt-4">
              <Calendar selected={date} onSelect={setDate} />
            </div>
            <Button className="mt-4 w-full">Find Activities</Button>
          </div>
        </div>

      <div className="mt-10 grid md:grid-cols-3 sm:grid-cols-2 grid-cols-1 gap-6">
        {activities.length >= 1 ? (
          activities?.map((activity) => {
            console.log('activity', activity);
            return(
            <Card 
            key={activity?.id} 
            image={activity?.image} 
            service={activity?.service} 
            season={activity?.season}
            price={activity?.price} 
            badge={activity?.badge} 
            gift={activity?.gift} 
            onBook={() => router.get(route('front.activity',{activity_id: activity?.id}))}
            onGift={() => router.get(route('front.voucher',{activity_id:activity?.id}))}
            />
          )})        
        ) :(
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
        mode = "create"
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

    </>

  );
}