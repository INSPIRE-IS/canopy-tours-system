
import CustomerToolsModal from '@/Components/CustomerToolsModal';
import Header from '@/Components/Header';
import Calendar from '@/Components/ui/calendar';
import Button from '@/components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';
import { format, set } from 'date-fns';

export default function Dashboard({ auth, wholesalers = [], services = [],  timeslots = [], schedule, start_date, view_mode, days,availableTimes }) {
//seasons = [],, adults, children, guides
  const [date, setDate] = useState(() => new Date(start_date));
  const [activityId, setActivityId] = useState('');
  const [adults, setAdults] = useState('0');
  const [children, setChildren] = useState('0');
  const [guides, setGuides] = useState('0');
  const [selectedSlot, setSelectedSlot] = useState(null); // stores {date: string, day: string, time: string }
  const [scheduleData, setScheduleData] = useState({ schedule, days });
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);

  useEffect(() => {
    console.log('activityId', activityId);
    console.log('date', date);
    console.log('view_mode', view_mode);
    console.log('adults', adults);
    console.log('children', children);
    loadCalendar();
  }, [activityId,date, view_mode, adults, children]);

  const loadCalendar = async () => {
    if (activityId) {
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
   // setDate(newDate);
    router.get(route('admin.bookings.create'), {
      activity_id: activityId,
      view_mode: 'week',
      start_date: format(newDate, 'yyyy-MM-dd')
    });
    setSelectedSlot(null); // reset selected slotdatedate.toDateString() // 
  };





const [seasons, setSeasons] = useState([]);
    const { data, setData, post, processing, errors } = useForm({
        is_wholesaler: false,
        wholesaler_id: '',
        add_consultant: false,
        consultant_name: '',
        consultant_email: '',
        consultant_mobile: '',
        service_id: '',
        season_id: '',
        booking_date: '',
        start_time: '',
        end_time: '',
        adults: '',
        children: '',
        guides: '',
        include_media: true,
        adult: 895,
        child: 795,
        guide: 0,
        booking_total: 0,
        redeem_voucher: '',
        invoice_note: '', 
        email_reminder: '', 
        booking_note: '',
        corporate_customer: false, 
        send_sms: false,
        lead_person: '', 
        lead_email: '', 
        lead_mobile: '', 
        referral_source: '',
    });
    useEffect(() => {
        const selectedService = services.find(s => {
            console.log('service', s);
            console.log('service_id ', data.service_id);
            const selected = s.id === parseInt(data.service_id);
            console.log(selected);

            return selected;
        });
        console.log(selectedService);
        const seasons = selectedService ? setSeasons(selectedService.seasons) : [];
    }, [data.service_id]);


    // useEffect(() => {
    //     if (!data.override_rates) {
    //         setData({
    //             ...data,
    //             rate_per_adult: rackRates.adult,
    //             rate_per_child: rackRates.child,
    //             rate_per_guide: rackRates.guide,
    //         });
    //     }
    // }, [data.override_rates]);

    const calculateTotal = (count, rate) => (parseFloat(count || 0) * parseFloat(rate || 0)).toFixed(2);

    // const serviceChange = (e) => {
    //     console.log('season change', e.target.value);
    //     //setData('season_id', e.target.value);
    //     filteredSeasons = seasons.filter(s => s.service_id == e.target.value);
    //     setData('seasons', filteredSeasons);
    // }


    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('admin.bookings.store'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Bookings</h2>}
        >
            {/* <Head title="Dashboard" /> */}


            <div className="bg-white border border-secondary-100">
                <div className="space-y-6 max-w-full mx-auto p-6 bg-white shadow rounded">
                    <h1 className="text-2xl font-bold text-green-600">New Booking</h1>

                        {/* Wholesaler Booking */}
                        <div className="flex items-center space-x-4">
                            <label className="font-semibold">Wholesaler Booking</label>
                            <input
                                type="checkbox"
                                checked={data.is_wholesaler}
                                onChange={(e) => setData('is_wholesaler', e.target.checked)}
                                className="h-4 w-4"
                            />
                        </div>
                        {data.is_wholesaler && (
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <select
                                value={data.wholesaler_id}
                                onChange={(e) => setData('wholesaler_id', e.target.value)}
                                className="border rounded p-2"
                            >
                                <option value="">Please select a wholesaler</option>
                                {wholesalers.map((wholesaler) => (
                                    <option key={wholesaler.id} value={wholesaler.id}>
                                        {wholesaler.name}
                                    </option>
                                ))}
                            </select>

                            <div className="flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    checked={data.add_consultant}
                                    onChange={(e) => setData('add_consultant', e.target.checked)}
                                    className="h-4 w-4"
                                />
                                <label>Add Consultant</label>
                            </div>
                        </div>
                        )}
                        {data.add_consultant && (
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <input
                                    type="text"
                                    placeholder="Consultant Name"
                                    value={data.consultant_name}
                                    onChange={(e) => setData('consultant_name', e.target.value)}
                                    className="border rounded p-2"
                                />
                                <input
                                    type="email"
                                    placeholder="Consultant Email"
                                    value={data.consultant_email}
                                    onChange={(e) => setData('consultant_email', e.target.value)}
                                    className="border rounded p-2"
                                />
                                <input
                                    type="text"
                                    placeholder="Consultant Mobile"
                                    value={data.consultant_mobile}
                                    onChange={(e) => setData('consultant_mobile', e.target.value)}
                                    className="border rounded p-2"
                                />
                            </div>
                        )}

                        {/* Service + Date */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <select
                                value={data.service_id}
                                onChange={(e) => setData('service_id', e.target.value)}
                                className="border border-secondary-100 rounded-[10px] p-2 text-red-500"
                            >
                                <option value="" className='text-red-500'>Select a Service</option>
                                {services.map((s) => (
                                    <option key={s.id} value={s.id} className='text-red-500'>
                                        {s.name}
                                    </option>
                                ))}
                            </select>
                            <select
                                value={data.season_id}
                                onChange={(e) => {setData('season_id', e.target.value); setActivityId(e.target.value)}}
                                className="border rounded p-2"
                            >
                                <option value="">Select a Season</option>
                                {seasons.map((s) => (
                                    <option key={s.id} value={s.id}>
                                        {s.name}
                                    </option>
                                ))}
                            </select>

                        </div>

                        {/* Time slots */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <input
                                type="date"
                                value={data.booking_date}
                                onChange={(e) => {setData('booking_date', e.target.value); setDate(new Date(e.target.value))}}
                                className="border rounded p-2"
                            />

                            <select
                                value={data.start_time}
                                onChange={(e) => setData('start_time', e.target.value)}
                                className="border rounded p-2"
                            >
                                <option value="">Starting at</option>
                                {timeslots.map((slot) => (
                                    <option key={slot} value={slot}>
                                        {slot}
                                    </option>
                                ))}
                            </select>

                            {/* <select
                                value={data.end_time}
                                onChange={(e) => setData('end_time', e.target.value)}
                                className="border rounded p-2"
                            >
                                <option value="">Finishing at</option>
                                {timeslots.map((slot) => (
                                    <option key={slot} value={slot}>
                                        {slot}
                                    </option>
                                ))}
                            </select> */}
                        </div>

                        {/* Counts 
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
                        
                        
                        
                        */}
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <input
                                type="number"
                                min="0"
                                value={data.adults}
                                onChange={(e) => {setData('adults', e.target.value); setAdults(e.target.value)}}
                                placeholder="Number of Adults"
                                className="border rounded p-2"
                            />
                            <input
                                type="number"
                                min="0"
                                value={data.children}
                                onChange={(e) => {setData('children', e.target.value); setChildren(e.target.value)}}
                                placeholder="Number of Children"
                                className="border rounded p-2"
                            />
                            <input
                                type="number"
                                min="0"
                                value={data.guides}
                                onChange={(e) => {setData('guides', e.target.value); setGuides(e.target.value)}}
                                placeholder="Number of Guides"
                                className="border rounded p-2"
                            />
                        </div>

                        <div className="flex items-center space-x-2">
                            <input
                                type="checkbox"
                                checked={data.include_media}
                                onChange={(e) => setData('include_media', e.target.checked)}
                                className="h-4 w-4"
                            />
                            <label>Include Photos and Videos</label>
                        </div>

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
                                                <div  className={`p-[6px] text-start w-[135px] h-[99px] cursor-pointer rounded-[10px] space-y-[10px] transition ${
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
                                                        {cell?.rake_rate_adult && <div className="text-xs">From R{cell.rake_rate_adult}</div>}                           
                                                    </>
                                                    )}
                                                    {cell?.status === 'blocked' && (
                                                    <div>No Timeslot Yet</div>
                                                    )}
                                                    {cell?.status === 'full' &&(
                                                    <div>Fully Booked</div>
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

                        <div className="mt-10 p-6 bg-white rounded shadow space-y-4">
                            <h2 className="text-xl font-bold">Rates</h2>

                            {/* Top-level toggles */}
                            <div className="flex flex-wrap gap-4 items-center">
                                {[
                                    { key: 'web_booking', label: 'Web Booking' },
                                    { key: 'needs_pickup', label: 'Needs Pickup' },
                                    { key: 'override_timeslots', label: 'Override Time Slots' },
                                    { key: 'exclusive_resource', label: 'Exclusive Resource use' },
                                ].map(({ key, label }) => (
                                    <label key={key} className="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            checked={data[key] || false}
                                            onChange={(e) => setData(key, e.target.checked)}
                                            className="h-4 w-4"
                                        />
                                        <span>{label}</span>
                                    </label>
                                ))}
                            </div>

                            {/* Override rates toggle + reason */}
                            <div className="flex items-center gap-4 mt-4">
                                <label className="flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        checked={data.override_rates || false}
                                        onChange={(e) => setData('override_rates', e.target.checked)}
                                        className="h-4 w-4"
                                    />
                                    <span className="font-semibold">Override Rates</span>
                                </label>

                                <input
                                    type="text"
                                    placeholder="Reason for Override"
                                    value={data.override_reason || ''}
                                    onChange={(e) => setData('override_reason', e.target.value)}
                                    className="border rounded p-2 flex-1"
                                />
                            </div>

                            {/* Rate cards */}
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                                {[
                                    { label: 'Adult', key: 'adult', count: adults, rateKey: 'rate_per_adult' },
                                    { label: 'Child', key: 'child', count: children, rateKey: 'rate_per_child' },
                                    { label: 'Guide', key: 'guide', count: guides, rateKey: 'rate_per_guide' },
                                ].map(({ label, key, count, rateKey }) => (
                                    <div key={key} className="p-4 rounded shadow bg-gray-50">
                                        <div className="flex items-center justify-between mb-2">
                                            <h3 className="text-lg font-bold">{label}</h3>
                                            <span className="text-sm text-gray-500">Rack Rate per: {data[key].toFixed(2)}</span>
                                        </div>
                                        <div className="space-y-2">
                                            <input
                                                type="number"
                                                disabled={!data.override_rates}
                                                value={data[rateKey] || ''}
                                                onChange={(e) => setData(rateKey, e.target.value)}
                                                className="w-full border p-2 rounded"
                                                placeholder="Rate per"
                                            />
                                            <input
                                                type="text"
                                                className="w-full border p-2 rounded bg-gray-100"
                                                value={calculateTotal(count, data[rateKey])}
                                                disabled
                                                placeholder="Total Price"
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <div className="flex items-center space-x-4">
                                            <label className="font-semibold">Booking Total</label>
                                            <input
                                                type="text"
                                                checked={data.booking_total}
                                                onChange={(e) => setData('booking_total', e.target.checked)}
                                                placeholder="Booking Total"
                                                className="border rounded p-2"
                                                disabled
                                            />
                                        <button
                                            type="button"
                                            disabled={processing}
                                            className="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700"
                                        >
                                            Recalucate from Rack
                                        </button>

                            </div>
                <           div className="flex items-center space-x-4">
                                            <label className="font-semibold">Redeem Voucher</label>
                                            <input
                                                type="text"
                                                checked={data.redeem_voucher}
                                                onChange={(e) => setData('redeem_voucher', e.target.checked)}
                                                placeholder="Redeem Voucher"
                                                className="border rounded p-2"
                                                disabled
                                            />
                                        <button
                                            type="button"
                                            disabled={processing}
                                            className="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700"
                                        >
                                            Redeem
                                        </button>
                                        <button
                                            type="button"
                                            disabled={processing}
                                            className="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700"
                                        >
                                            Remove
                                        </button>
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div className="flex flex-col items-center space-x-2">
                                    <label className="font-semibold">Add Invoice Note</label>
                                    <input
                                        type="text"
                                        value={data.invoice_note}
                                        onChange={(e) => setData('invoice_note', e.target.value)}
                                        placeholder="Add Invoice Note"
                                        className="border rounded p-2"
                                    />                                 
                                </div>
                                <div className="flex flex-col items-center space-x-2">
                                    <label className="font-semibold">Add Email Reminder</label>
                                    <input
                                        type="text"
                                        value={data.email_reminder}
                                        onChange={(e) => setData('email_reminder', e.target.value)}
                                        placeholder="Add Email Reminder"
                                        className="border rounded p-2"
                                    />                                 
                                </div>
                                <div className="flex flex-col items-center space-x-2">
                                    <label className="font-semibold">Add Booking Note</label>
                                    <input
                                        type="text"
                                        value={data.booking_note}
                                        onChange={(e) => setData('booking_note', e.target.value)}
                                        placeholder="Add Booking Note"
                                        className="border rounded p-2"
                                    />                                 
                                </div>
                            </div>
                            <div className="flex items-center justify-between">
                                <label className="font-semibold">Add Another Activity to this Booking</label>
                                <button
                                    type="button"
                                    disabled={processing}
                                    className="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700"
                                >
                                    Add Another Activity
                                </button>
                            </div>
                        </div>
                        <div className="mt-10 p-6 bg-white rounded shadow space-y-4">

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div className="flex items-center space-x-4">
                                    <label className="font-semibold">Booking Details</label>
                                </div>
                                <div className="flex items-center space-x-4">
                                    
                                    <input
                                        type="checkbox"
                                        checked={data.corporate_customer} 
                                        onChange={(e) => setData('corporate_customer', e.target.checked)}
                                        className="h-4 w-4"
                                    />
                                    <label className="font-semibold">Corporate Customer</label>
                                </div>
                                <div className="flex items-center space-x-4">
                                    
                                    <input
                                        type="checkbox"
                                        checked={data.send_sms}
                                        onChange={(e) => setData('send_sms', e.target.checked)}
                                        className="h-4 w-4"
                                    />
                                    <label className="font-semibold">Send SMS Notification Immediately</label>
                                </div>
                            </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div className="flex flex-col justify-start space-y-4">

                                            <label className="font-semibold">Lead Person</label> 
                                            <input
                                                type="text"
                                                placeholder="Lead Person"
                                                value={data.lead_person}
                                                onChange={(e) => setData('lead_person', e.target.value)}
                                                className="border rounded p-2 w-full"
                                            />
                                        </div>
                                        <div className="flex flex-col justify-start space-y-4">

                                            <label className="font-semibold">Lead Email</label>
                                            <input
                                                type="text"
                                                placeholder="Lead Email"
                                                value={data.lead_email}
                                                onChange={(e) => setData('lead_email', e.target.value)}
                                                className="border rounded p-2 w-full"
                                            />
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div className="flex flex-col justify-start space-y-4">

                                            <label className="font-semibold">Lead Mobile</label>
                                            <input
                                                type="text"
                                                placeholder="Lead Mobile"
                                                value={data.lead_mobile}
                                                onChange={(e) => setData('lead_mobile', e.target.value)}
                                                className="border rounded p-2 w-full"
                                            />
                                        </div>
                                        <div className="flex flex-col justify-start space-y-4">

                                            <label className="font-semibold">Referral Source</label>
                                            <select
                                                value={data.referral_source}
                                                onChange={(e) => setData('referral_source', e.target.value)}
                                                className="border rounded p-2"
                                            >
                                                <option value="">Select an Option</option>
                                                {timeslots.map((slot) => (
                                                    <option key={slot} value={slot}>
                                                        {slot}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>

                        </div>
                        <div className="mt-10 bg-white w-full ml-[20%] lg:ml-[25%] ">
                            <div className="p-6 bg-white w-[60%] lg:w-[50%] rounded shadow space-y-4">
                                <div className="grid grid-cols-1 gap-4">
                                <div className="flex flex-col justify-start space-y-4">

                                <label className="font-semibold">Lead Person</label> 
                                <input
                                type="text"
                                placeholder="Lead Person"
                                value={data.lead_person}
                                onChange={(e) => setData('lead_person', e.target.value)}
                                className="border rounded p-2 w-full"
                                />
                                </div>
                                <div className="flex flex-col justify-start space-y-4">

                                <label className="font-semibold">Lead Person</label> 
                                <input
                                type="text"
                                placeholder="Lead Person"
                                value={data.lead_person}
                                onChange={(e) => setData('lead_person', e.target.value)}
                                className="border rounded p-2 w-full"
                                />
                                </div>
                                <div className="flex flex-col justify-start space-y-4">

                                <label className="font-semibold">Lead Person</label> 
                                <input
                                type="text"
                                placeholder="Lead Person"
                                value={data.lead_person}
                                onChange={(e) => setData('lead_person', e.target.value)}
                                className="border rounded p-2 w-full"
                                />
                                </div>
                                <div className="flex flex-col justify-start space-y-4">

                                <label className="font-semibold">Lead Person</label> 
                                <input
                                type="text"
                                placeholder="Lead Person"
                                value={data.lead_person}
                                onChange={(e) => setData('lead_person', e.target.value)}
                                className="border rounded p-2 w-full"
                                />
                                </div>
                                </div>
                            </div>
                            <button
                                type="button"
                                onClick={(e) =>handleSubmit(e)}
                                disabled={processing}
                                className="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700"
                            >
                                Submit Booking
                            </button>            
                        </div>
                </div>
            </div>


        </AuthenticatedLayout>
    );
}
