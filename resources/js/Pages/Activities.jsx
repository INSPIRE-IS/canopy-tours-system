import React, { useEffect, useState } from 'react';
import Header from '@/components/ui/header';
import Button from '@/components/ui/button';
import Input from '@/components/ui/input';
import Calendar from '@/components/ui/calendar';
import InfoCard from '@/components/ui/InfoCard';
import { router } from '@inertiajs/react';
import { format, set } from 'date-fns';
import BookingModal from '@/Components/BookingModal';
import BuyGiftModal from '@/Components/BuyGiftModal';

export default function ActivitySchedulePage({ activities,serviceId, seasonId, activityId, schedule, start_date, view_mode, days,availableTimes }) {
console.log('activities', activities);

  const [date, setDate] = useState(() => new Date(start_date));
  const [adults, setAdults] = useState('');
  const [children, setChildren] = useState('');
  const [selectedSlot, setSelectedSlot] = useState(null); // stores {date: string, day: string, time: string }
  const [scheduleData, setScheduleData] = useState({ schedule, days });
  const [loading, setLoading] = useState(true);
  const [bookModal, setBookModal] = useState(false);
  const [giftModal, setGiftModal] = useState(false);

  useEffect(() => {
    loadCalendar();
  }, [date, view_mode, adults, children]);

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
      <Header />

      <div className="max-w-screen-xl mx-auto px-4 py-8">
        {/* Jumbo Hero Section */}
        <div className="relative rounded-xl overflow-hidden shadow-lg h-[400px]">
          <img
            src="/images/hero.jpg"
            alt="Tsitsikamma Canopy Tour"
            className="w-full h-full object-cover"
          />
          <div className="absolute inset-0 bg-black/10 flex flex-col justify-center p-6 text-white">
            <h2 className="text-lg font-light">{activities?.service}</h2>
            <h1 className="text-3xl md:text-4xl font-bold">{activities?.season}</h1>
            <p className="mt-2 max-w-md">{activities?.description}</p>
            <div className="mt-4 space-x-4">
              <Button variant="outline" className="bg-white text-blue-600">View Gallery</Button>
              <Button variant="outline" className="bg-white text-blue-600">Watch Video</Button>
            </div>
          </div>
        </div>

        {/* Jumbo aside + Calendar */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mt-10">
          <aside className="md:col-span-1 bg-white shadow rounded-xl p-6">
            <h3 className="text-green-700 font-semibold mb-4">I am looking for:</h3>
            <div className="space-y-4">
              <Input placeholder="Number of Adults" value={adults} onChange={(e) => setAdults(e.target.value)} />
              <Input placeholder="Number of Children" value={children} onChange={(e) => setChildren(e.target.value)} />
              <Calendar selected={date} onSelect={handleDateChange} />
              {availableTimes.length > 0 && (
                  <Select onValueChange={(value) => setSelectedSlot({ date: date.toISOString().split('T')[0], time: value })}>
                    {availableTimes.map(time => (
                      <SelectItem key={time} value={time}>{time}</SelectItem>
                    ))}
                  </Select>
                )}
              <Button className="w-full">Find Activities</Button>
              <div className="mt-4">
                <div className="text-lg font-semibold text-green-700">R{Number(adults) * 895}.00</div>
                <div className="text-sm text-gray-600">{adults} Adults</div>
                <div className="text-sm text-gray-600">on {date.toLocaleDateString()}</div>
                <Button
                  className="mt-2 bg-green-500 text-white w-full"
                  disabled={!selectedSlot}
                  onClick={() => {
                    // Submit booking form or route with selected data
                    // router.post(route('booking.create'), {
                    //   adults,
                    //   children,
                    //   date: selectedSlot.date,
                    //   time: selectedSlot.time,
                    // });
                    setBookModal(true);
                  }}
                >
                  Book Now
                </Button>
                <Button onClick={() => {setGiftModal(true);}} variant="outline" className="mt-2 w-full">Buy as a Gift</Button>
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
                        <div  className={` p-[6px] text-start w-[135px] h-[99px] cursor-pointer rounded-[10px] space-y-[10px] transition ${
                            selectedSlot?.day === day && selectedSlot?.time === row.time
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
        </div>
      </div>





    <section className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8 max-w-6xl mx-auto px-4">
      <InfoCard
        title="Activity Description"
        color="green"
        content={activities?.activity}
      />

      <InfoCard
        title="Arrangements"
        color="gray"
        content={activities?.arrangements}
      />

      <InfoCard
        title="Requirements"
        color="green"
        content={activities?.requirements}
      />

      <InfoCard
        title="Inclusions and Exclusions"
        color="blue"
        content={activities?.inclusions}
      />
      <InfoCard
        title="Notes"
        color="blue"
        content={activities?.notes}
      />

    </section>

      {bookModal && (
        <BookingModal
          isOpen={bookModal}
          onClose={() => setBookModal(false)}
          slot={selectedSlot}
          adults={adults}
          children={children}
          pricePerAdult={activities.rake_rate_adult}
          activityId={activityId}
        />
      )}
      {giftModal && (
        <BuyGiftModal
          isOpen={giftModal}
          onClose={() => setGiftModal(false)}
          slot={selectedSlot}
          adults={adults}
          children={children}
          pricePerAdult={activities.rake_rate_adult}
          pricePerChild={activities.rake_rate_child}
          activityId={activityId}
        />
      )}
    </div>
  );
}