import React, { useState } from 'react';
import Header from '@/components/ui/header';
import Button from '@/components/ui/button';
import Input from '@/components/ui/input';
import Calendar from '@/components/ui/calendar';
import InfoCard from '@/components/ui/InfoCard';


export default function ActivitySchedulePage() {
  const [date, setDate] = useState(new Date());
  const [adults, setAdults] = useState('2');
  const [children, setChildren] = useState('');

  const inventory = [
    {
      time: '08:00 - 10:00',
      slots: '2 Slots Open',
      price: 'From R895',
      status: 'available',
      day: 'Mon 24'
    },
    {
      time: '08:00 - 10:00',
      slots: 'Fully Booked',
      price: '',
      status: 'full',
      day: 'Tue 25'
    },
    {
      time: '08:00 - 10:00',
      slots: '8 Slots Open',
      price: 'From R895',
      status: 'available',
      day: 'Fri 28'
    }
    // More items...
  ];

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
            <h2 className="text-lg font-light">Tsitsikamma</h2>
            <h1 className="text-3xl md:text-4xl font-bold">Tsitsikamma Canopy Tour</h1>
            <p className="mt-2 max-w-md">
              Come and Experience a World of Diversity in One Forest
              <br />
              A Unique Tsitsikamma Experience You’ll Never Forget!
            </p>
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
              <Calendar selected={date} onSelect={setDate} />
              <Button className="w-full">Find Activities</Button>
              <div className="mt-4">
                <div className="text-lg font-semibold text-green-700">R{Number(adults) * 895}.00</div>
                <div className="text-sm text-gray-600">{adults} Adults</div>
                <div className="text-sm text-gray-600">on {date.toLocaleDateString()}</div>
                <Button className="mt-2 bg-green-500 text-white w-full">Book Now</Button>
                <Button variant="outline" className="mt-2 w-full">Buy as a Gift</Button>
              </div>
            </div>
          </aside>

          {/* Inventory Calendar Table */}
          <section className="md:col-span-3 bg-white shadow rounded-xl p-6">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-semibold">Available Inventory</h3>
              <div className="flex gap-2">
                <Button variant="ghost">Day</Button>
                <Button variant="ghost" className="font-bold border-b-2 border-blue-600">Week</Button>
                <Button variant="ghost">Month</Button>
              </div>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead>
                  <tr className="text-gray-600">
                    <th className="p-2">Time</th>
                    <th className="p-2">Mon 24</th>
                    <th className="p-2">Tue 25</th>
                    <th className="p-2">Wed 26</th>
                    <th className="p-2">Thu 27</th>
                    <th className="p-2">Fri 28</th>
                  </tr>
                </thead>
                <tbody>
                  {[
                    '08:00 - 10:00',
                    '08:30 - 10:30',
                    '09:00 - 11:00',
                    '10:00 - 12:00',
                  ].map((time) => (
                    <tr key={time}>
                      <td className="p-2 font-semibold text-gray-700">{time}</td>
                      {[0, 1, 2, 3, 4].map((col) => {
                        const cell = inventory.find(
                          (i) => i.time === time && i.day === ['Mon 24', 'Tue 25', 'Wed 26', 'Thu 27', 'Fri 28'][col]
                        );
                        return (
                          <td
                            key={col}
                            className={
                              'p-2 rounded text-center ' +
                              (cell?.status === 'available'
                                ? 'bg-green-100 text-green-700'
                                : cell?.status === 'full'
                                ? 'bg-red-100 text-red-700'
                                : 'bg-yellow-100 text-yellow-700')
                            }
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
        content={
          <>
            <p>
              Experience a World of Diversity in One Forest – Situated in the Garden Route...
            </p>
            <p>
              Tsitsikamma Canopy Tour is an award-winning eco-adventure...
            </p>
          </>
        }
      />

      <InfoCard
        title="Arrangements"
        color="gray"
        content={
          <ul className="list-disc ml-5 space-y-1">
            <li><strong>Duration:</strong> Open Ended</li>
            <li><strong>When:</strong> All Year Round</li>
            <li><strong>Where:</strong> Stormsriver Village, Tsitsikamma</li>
            <li><strong>Weather:</strong> All-weather activity. Bring appropriate clothing.</li>
            <li><strong>Things to Bring:</strong> All additional items</li>
          </ul>
        }
      />

      <InfoCard
        title="Requirements"
        color="green"
        content={
          <ul className="list-disc ml-5 space-y-1">
            <li>Minimum Age: 5 years</li>
            <li>Minimum Height: 0.0m</li>
            <li>Fitness Level: Average</li>
            <li>Certification: Not Applicable</li>
          </ul>
        }
      />

      <InfoCard
        title="Inclusions and Exclusions"
        color="blue"
        content={
          <>
            <p><strong>Inclusions:</strong> Bottled water, safety gear, guide, certificate</p>
            <p><strong>Exclusions:</strong> All additional items</p>
          </>
        }
      />
    </section>




    </div>
  );
}