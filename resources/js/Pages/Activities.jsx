import React, { useEffect, useState } from 'react';
import { icons } from '@/icons';
import TopNav from '@/components/TopNav';
import Button from '@/components/ui/button';
import Input from '@/components/ui/input';
import Calendar from '@/components/ui/calendar';
import InfoCard from '@/components/ui/InfoCard';
import { Head, router } from '@inertiajs/react';
import { format, set } from 'date-fns';
import BookingModal from '@/Components/BookingModal';
import BuyGiftModal from '@/Components/BuyGiftModal';
import CustomInputText from '@/Components/CustomInputText';
import BlueButton from '@/Components/BlueButton';
import GreenButton from '@/Components/GreenButton';
import WhiteButton from '@/Components/WhiteButton';

export default function ActivitySchedulePage({ activity, auth, team, activityId, schedule, start_date, i_adults, i_children, view_mode, days, availableTimes }) {
  console.log('activity', activity);
  console.log('activityId', activityId);
  console.log('start_date', start_date);
  console.log('i_adults', i_adults);
  console.log('i_children', i_children);
  console.log('auth ', auth);
  const formatDate = new Date(start_date);
  console.log('formatDate', formatDate);
  const [total, setTotal] = useState(0.00);
  const [date, setDate] = useState(formatDate);
  const [adults, setAdults] = useState(i_adults);
  const [children, setChildren] = useState(i_children);
  const [selectedSlot, setSelectedSlot] = useState(null); // stores {date: string, day: string, time: string }
  const [scheduleData, setScheduleData] = useState({ schedule, days });
  const [loading, setLoading] = useState(true);
  const [bookModal, setBookModal] = useState(false);
  const [giftModal, setGiftModal] = useState(false);
  const [showContent, setShowContent] = useState('activityContent');

  useEffect(() => {
    loadCalendar();
  }, [date, view_mode, adults, children]);

  useEffect(() => {
    const sum = (Number(adults) * activity.rake_rate_adult + Number(children) * activity.rake_rate_child);
    if (sum > 0) {
      setTotal(sum.toFixed(2));
    }
  }, [adults, children, activity.rake_rate_adult, activity.rake_rate_child]);

  const loadCalendar = async () => {
    setLoading(true);
    try {
      const response = await axios.get(route('front.activity.calendar'), {
        params: {
          activity_id: activityId,
          view_mode: view_mode,
          start_date: format(date, 'yyyy-MM-dd'),
          adults,
          children
        }
      });
      console.log('response', response);
      setScheduleData(response.data);
    } catch (error) {
      console.error('Failed to load calendar:', error);
    } finally {
      setLoading(false);
    }
  };

  // useEffect(() => {
  //   // reload calendar from Laravel with view_mode=day and date +/- 2 days
  //   router.get(route('front.activity'), {
  //     activity_id: activityId,
  //     view_mode: 'week',
  //     start_date: format(date, 'yyyy-MM-dd')
  //   });
  //   setSelectedSlot(null); // reset selected slotdatedate.toDateString() // 
  // }, [date]);

  const handleDateChange = (newDate) => {
    setDate(newDate);
    router.get(route('front.activity'), {
      activity_id: activityId,
      view_mode: 'week',
      start_date: format(newDate, 'yyyy-MM-dd')
    });
    setSelectedSlot(null); // reset selected slotdatedate.toDateString() // 
  };


  return (
    <div className="bg-gray-50 min-h-screen">
      <Head title="Activity" />
      <TopNav auth={auth} team={team} />

      <div className="max-w-screen-xl mx-auto px-4 py-8">
        {/* Jumbo Hero Section */}
        <div className="relative rounded-xl overflow-hidden shadow-lg h-[400px]">
          {team?.file_logo?.path != null ? (
            <img
              src={`/storage/${team?.file_image?.path}`}
              alt="Canopy Tours"
              className="w-full h-full object-cover"
            />
          ) : (
            <img
              src="/images/hero.jpg"
              alt="Tsitsikamma Canopy Tour"
              className="w-full h-full object-cover"
            />
          )}
          <div className="absolute inset-0 bg-black/10 flex flex-col justify-center p-6 text-white">
            <h2 className="text-lg font-light">{activity?.service}</h2>
            <h1 className="text-3xl md:text-4xl font-bold">{activity?.season}</h1>
            <p className="mt-2 max-w-md">{activity?.description}</p>
            <div className="mt-4 flex gap-2 items-center justify-start">
              {/* <WhiteButton
                title="View Gallery"
                className="mt-2"
                textColor='text-white'
                onClick={() => setShowContent('galleryContent')}
              />
              <WhiteButton
                title="Watch Video"
                className="mt-2"
                textColor='text-white'
                onClick={() => setShowContent('videoContent')}
              /> */}
              {/* <Button onClick={() => setShowContent('galleryContent')} variant="outline" className="bg-white text-blue-600">View Gallery</Button>
              <Button onClick={() => setShowContent('videoContent')} variant="outline" className="bg-white text-blue-600">Watch Video</Button> */}
            </div>
          </div>
        </div>

        {/* Jumbo aside + Calendar */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mt-10">
          {showContent === 'activityContent' && (
            <>
              <aside className="md:col-span-1 bg-white shadow rounded-xl p-6">
                <h3 className="text-green-700 font-semibold mb-4">I am looking for:</h3>
                <div className="space-y-4">
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
                  <CustomInputText
                    title="Desired Date of Booking"
                    id="date"
                    name="date"
                    type='date'
                    value={date ? format(date, 'yyyy-MM-dd') : ''}
                    onChange={(e) => {
                      const newDate = new Date(e.target.value);
                      setDate(newDate);
                      router.get(route('front.activity'), {
                        activity_id: activityId,
                        view_mode: 'week',
                        start_date: format(newDate, 'yyyy-MM-dd'),
                        adults: adults,
                        children: children
                      });
                      setSelectedSlot(null); // reset selected slotdatedate.toDateString() // 

                    }}
                  />

                  {/* <Input placeholder="Number of Adults" value={adults} onChange={(e) => setAdults(e.target.value)} />
                <Input placeholder="Number of Children" value={children} onChange={(e) => setChildren(e.target.value)} />
                <Calendar selected={date} onSelect={handleDateChange} /> */}
                  {availableTimes.length > 0 && (
                    <Select onValueChange={(value) => setSelectedSlot({ date: date.toISOString().split('T')[0], time: value })}>
                      {availableTimes.map(time => (
                        <SelectItem key={time} value={time}>{time}</SelectItem>
                      ))}
                    </Select>
                  )}
                  <BlueButton
                    title="Find Activities"
                    onClick={() => router.get(route('front.welcome'), {
                      activity_id: selectedService,
                      adults: adults,
                      children: children,
                      start_date: date,
                    })}
                  />
                  <div className="mt-4">
                    <div className="text-lg font-semibold text-green-700">R {total}</div>
                    <div className="text-sm text-gray-600">{adults} Adults</div>
                    <div className="text-sm text-gray-600">{children} Children</div>
                    <div className="text-sm text-gray-600">on {date.toLocaleDateString()}</div>
                    <GreenButton
                      title="Book Now"
                      icon={true}
                      iconName={icons.credit_card}
                      width="w-full"
                      className="mt-2"
                      disabled={!selectedSlot}
                      onClick={() => {
                        setBookModal(true);
                      }}
                    />
                    <WhiteButton
                      title="Buy as a Gift"
                      icon={true}
                      iconName={icons.gift}
                      width="w-full"
                      className="mt-2"
                      onClick={() => { setGiftModal(true); }}
                    />
                  </div>
                </div>
              </aside>

              {/* Inventory Calendar Table */}
              <section className="md:col-span-3 bg-white shadow rounded-xl p-6">
                <div className="flex justify-between items-center mb-4">
                  <h3 className="text-lg font-semibold">Available Inventory</h3>
                  <div className="flex gap-2">
                    {['day', 'week', 'month'].map((mode) => (
                      <Button
                        key={mode}
                        variant="ghost"
                        onClick={() => router.reload({ only: ['view_mode'], data: { view_mode: mode } })}
                        className={view_mode === mode ? 'font-bold border-b-2 border-blue-600' : ''}
                      >
                        {mode.charAt(0).toUpperCase() + mode.slice(1)}
                      </Button>
                    ))}
                  </div>
                  <div className="flex gap-2">
                    <Calendar selected={date} onSelect={handleDateChange} />
                  </div>

                </div>
                {loading ? (
                  <div className="text-center py-20 text-gray-500">Loading calendar...</div>
                ) : (
                  <div className="overflow-x-auto">
                    {scheduleData.days.length === 0 ? (
                      <div className="text-center py-10 text-red-500">No availability for this date range.</div>
                    ) : (
                      <table className="w-full text-sm text-left">
                        <thead>
                          <tr className="text-gray-600">
                            <th className="p-2">Time</th>
                            {scheduleData.days.map(day => (
                              <th key={day} className=" w-[133px] h-[52px] bg-gray-100 border border-gray-200 text-center">{day}</th>
                            ))}
                          </tr>
                        </thead>
                        <tbody>
                          {scheduleData.schedule.map(row => (
                            <tr key={row.time} className='w-full border-b border-gray-200 '>
                              <td className="flex-1 p-2 font-semibold w-[100px] h-[114px] text-gray-700  ">{row.time.slice(0, 5)}</td>
                              {scheduleData.days.map(day => {
                                const cell = row.days[day];
                                return (
                                  <td
                                    key={day}
                                    className={`flex-1 items-center justify-center w-[133px] h-[111px] border border-gray-200 cursor-pointer transition `}
                                    onClick={() => {
                                      if (cell?.status === 'available') {
                                        setSelectedSlot({ date: cell.date, day: day, time: row.time });
                                      }
                                    }}
                                  >
                                    <div className="w-full h-full flex items-center justify-center">
                                      <div className={` p-[6px] text-start w-[135px] h-[99px] cursor-pointer rounded-[10px] space-y-[10px] transition ${selectedSlot?.day === day && selectedSlot?.time === row.time
                                        ? 'bg-[#55FF9E] text-black'
                                        : cell?.status === 'available'
                                          ? 'bg-[#D6FBE6] border border-[#E5E5EC] text-green-700 hover:bg-green-200'
                                          : 'bg-[#FCE4DF] border border-[#E5E5EC] text-gray-400 cursor-not-allowed'
                                        }`}>

                                        {cell?.status === 'available' && (
                                          <>
                                            <div className="text-xs">{row.time.slice(0, 5)}</div>
                                            <div className="text-xs">{cell?.slots} Slots Open</div>

                                            {/* Price with discount handling */}
                                            {cell?.rake_rate_adult && (
                                              <div className="text-xs">
                                                <span>From R{cell.rake_rate_adult}</span>
                                              </div>
                                            )}
                                          </>
                                        )}

                                        {/* 
                            {cell?.status === 'available' && (
                              <>
                                <div className="text-xs">{row.time.slice(0, 5)}</div>
                                <div className="text-xs">{cell?.slots} Slots Open</div>
                                {cell?.rake_rate_adult && <div className="text-xs">From R{cell.rake_rate_adult}</div>}                           
                              </>
                            )} */}
                                        {cell?.status === 'full' && (
                                          <div className="text-xs text-red-500">Fully Booked</div>
                                        )}

                                        {cell?.status === 'blocked_day' && (
                                          <div className="text-xs text-black">Day Closed</div>
                                        )}

                                        {cell?.status === 'blocked_slot' && (
                                          <div className="text-xs text-black">Slot Closed</div>
                                        )}
                                        {cell?.status === 'unavailable' && (
                                          <div className="text-xs text-black">Not Available</div>
                                        )}
                                      </div>
                                    </div>

                                  </td>
                                );
                              })}
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    )}
                  </div>
                )}
              </section>
            </>
          )}
          {showContent === 'galleryContent' && (
            <div className="bg-white shadow rounded-xl p-6">
              <div className="flex gap-2 items-center justify-between  mb-4">
                <span className="text-green-700 font-semibold ">Gallery Content</span>
                <WhiteButton
                  title="View Activity"
                  className="mt-2"
                  onClick={() => setShowContent('activityContent')}
                />
              </div>
              <div className="space-y-4">
                <span>No content available</span>
              </div>
            </div>
          )}
          {showContent === 'videoContent' && (
            <div className="bg-white shadow rounded-xl p-6">
              <div className="flex gap-2 items-center justify-between  mb-4">
                <span className="text-green-700 font-semibold ">Video Content</span>
                <WhiteButton
                  title="View Activity"
                  className="mt-2"
                  onClick={() => setShowContent('activityContent')}
                />
              </div>
              <div className="space-y-4">
                <span>No content available</span>
              </div>
            </div>
          )}
        </div>
      </div>

      <section className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8 max-w-6xl mx-auto px-4">
        <InfoCard
          title="Activity Description"
          color="green"
          content={activity?.activity}
        />

        <InfoCard
          title="Arrangements"
          color="gray"
          content={activity?.arrangements}
        />

        <InfoCard
          title="Requirements"
          color="green"
          content={activity?.requirements}
        />

        <InfoCard
          title="Inclusions and Exclusions"
          color="blue"
          content={activity?.inclusions}
        />
        <InfoCard
          title="Notes"
          color="blue"
          content={activity?.notes}
        />

      </section>

      {bookModal && (
        <BookingModal
          isOpen={bookModal}
          onClose={() => setBookModal(false)}
          slot={selectedSlot}
          activity={activity}
          adults={adults}
          children={children}
          pricePerAdult={activity.rake_rate_adult}
          pricePerChild={activity.rake_rate_child}
          activityId={activityId}
          auth={auth}
        />
      )}
      {giftModal && (
        <BuyGiftModal
          isOpen={giftModal}
          onClose={() => setGiftModal(false)}
          activityName={activity.service}
          adults={adults}
          children={children}
          pricePerAdult={activity.rake_rate_adult}
          pricePerChild={activity.rake_rate_child}
          activityId={activityId}
          auth={auth}
        />
      )}
    </div>
  );
}