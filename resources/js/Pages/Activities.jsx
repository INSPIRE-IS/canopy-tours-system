import React, { useEffect, useState } from 'react';
import Header from '@/components/ui/header';
import Button from '@/components/ui/button';
import Input from '@/components/ui/input';
import Calendar from '@/components/ui/calendar';
import InfoCard from '@/components/ui/InfoCard';
import { router } from '@inertiajs/react';
import { format } from 'date-fns';

export default function ActivitySchedulePage({ activities,activityId, schedule, start_date, view_mode, days,availableTimes }) {
console.log('activities', activities);

  const [date, setDate] = useState(() => new Date(start_date));
  const [adults, setAdults] = useState('');
  const [children, setChildren] = useState('');
  const [selectedSlot, setSelectedSlot] = useState(null); // stores { date: string, time: string }
const [scheduleData, setScheduleData] = useState({ schedule, days });
const [loading, setLoading] = useState(false);

  useEffect(() => {
    loadCalendar();
  }, [date, view_mode]);

  const loadCalendar = async () => {
    setLoading(true);
    try {
      const response = await axios.get(route('front.activity.calendar'), {
        params: {
          activity_id: activityId,
          view_mode: view_mode,
          start_date: format(date, 'yyyy-MM-dd')
        }
      });
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
          <div className="absolute inset-0 bg-black/50 flex flex-col justify-center p-6 text-white">
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
                    router.post(route('booking.create'), {
                      adults,
                      children,
                      date: selectedSlot.date,
                      time: selectedSlot.time,
                    });
                  }}
                >
                  Book Now
                </Button>
                <Button variant="outline" className="mt-2 w-full">Buy as a Gift</Button>
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
                    onClick={() => router.get(route('front.activity'), { activity_id: activityId, view_mode: mode, start_date })}
                    className={view_mode === mode ? 'font-bold border-b-2 border-blue-600' : ''}
                  >
                    {mode.charAt(0).toUpperCase() + mode.slice(1)}
                  </Button>
                ))}
              </div>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead>
                  <tr className="text-gray-600">
                    <th className="p-2">Time</th>
                    {days.map(day => <th key={day} className="p-2">{day}</th>)}
                  </tr>
                </thead>
                <tbody>
                  {schedule.map(row  => (
                    <tr key={row.time}>
                      <td className="p-2 font-semibold text-gray-700">{row.time}</td>
                      {days.map(day => {
                        const cell = row.days[day];
                        console.log('cell', cell);
                        return (
                          <td
                            key={day}
                            className={`p-2 text-center cursor-pointer rounded transition ${
                                selectedSlot?.date === day && selectedSlot?.time === row.time
                                  ? 'bg-blue-100 text-blue-700'
                                  : cell?.status === 'available'
                                    ? 'bg-green-100 text-green-700 hover:bg-green-200'
                                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                              }`}
                              onClick={() => {
                                if (cell?.status === 'available') {
                                  setSelectedSlot({ date: day, time: row.time });
                                }
                              }}
                          >
                            <div>{cell?.slots || 'No Timeslot Yet'}</div>
                            {cell?.price && <div className="text-xs">{cell.price}</div>}
                          </td>
                        );
                      })}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
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




    </div>
  );
}