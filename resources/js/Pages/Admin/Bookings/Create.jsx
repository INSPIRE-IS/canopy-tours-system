
import CustomerToolsModal from '@/Components/CustomerToolsModal';
import Header from '@/Components/Header';
import Calendar from '@/Components/ui/calendar';
import Button from '@/components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';
import { format, addDays, addWeeks, addMonths, set } from 'date-fns';
import CustomSelect from '@/Components/CustomSelect';
import InputText from '@/Components/InputText';
import CustomInputText from '@/Components/CustomInputText';
import CustomCheckbox from '@/Components/CustomCheckbox';
import { icons } from '../../../icons';
import WhiteButton from '@/Components/WhiteButton';
import BlueButton from '@/Components/BlueButton';
import CustomTextarea from '@/Components/CustomTextarea';

export default function Dashboard({ auth, wholesalers = [], services = [], schedule, start_date, view_mode, days, type_affiliates, vouchers, discounts, wholesaler_rates }) {
//seasons = [],, adults, children, guides
    const page = usePage();
    const mode = 'create';
    const [date, setDate] = useState(() => new Date(start_date));
    const [viewMode, setViewMode] = useState(view_mode); // 'day', 'week', or 'month'
    const [activityId, setActivityId] = useState('');
    const [scheduleData, setScheduleData] = useState({ schedule, days });
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [seasons, setSeasons] = useState([]);
    const [timeslots, setTimeslots] = useState([]);
    const [toggleCalendar, setToggleCalendar] = useState(true);
    const [toggleRate, setToggleRate] = useState(true);
    const [selectedService, setSelectedService] = useState(null);
    const [selectedSeason, setSelectedSeason] = useState(null);
    const [selectedWholesaler, setSelectedWholesaler] = useState(null);
    const [adults, setAdults] = useState('0');
    const [children, setChildren] = useState('0');
    const [guides, setGuides] = useState('0');
    const [selectedSlot, setSelectedSlot] = useState(null); // stores {date: string, day: string, time: string }
    const [wholesalerRateList, setWholesalerRateList] = useState([]);
    const [discountList, setDiscountList] = useState([]);
    const [promoCode, setPromoCode] = useState(null);
    const [discountCode, setDiscountCode] = useState(null);
    const [selectedDate, setSelectedDate] = useState(null);
    const [pricing, setPricing] = useState(null);
    console.log('schedule ',schedule);
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
        web_booking: false,
        needs_pickup: false,
        override_timeslots: false,
        exclusive_resource: false,
        //rates
        include_media: false,        
        selected_slot: [],        
        override_rates: false,
        override_reason: '',        
        adult: 0,
        child: 0,
        guide: 0,
        rate_per_adult: 0,
        rate_per_child: 0,
        rate_per_guide: 0,
        //redeem
        redeem_voucher: '',
        redeem_discount: '',
        redeem_wholesaler_rate: '',
        invoice_note: '', 
        email_reminder: '', 
        booking_note: '',
        // booking details
        corporate_customer: false, 
        send_sms: false,
        is_lead: true,
        lead_person: '', 
        lead_email: '', 
        lead_mobile: '', 
        referral_source: '',
        // booking payment
        booking_total: 0,
        deposit_due: 0,
        due_today: 0,
        rack_value: 0,
        bill_back: 0,
    });


    useEffect(() => {
        console.log('activityId', activityId);
        console.log('date', date);
        console.log('view_mode', view_mode);
        console.log('adults', adults);
        console.log('children', children);
        loadCalendar();
    }, [activityId,date, view_mode, adults, children]);


    useEffect(() => {
        console.log('adults', adults);
        console.log('children', children);
        console.log('children', guides);

        if(adults > 0 || children > 0 || guides > 0){
            const adult_total = (parseFloat(adults || 0) * parseFloat(data?.rate_per_adult || 0));
            const child_total = (parseFloat(children || 0) * parseFloat(data?.rate_per_child || 0));
            const guide_total = (parseFloat(guides || 0) * parseFloat(data?.rate_per_guide || 0));
            //console.log('data--------------------', data);
            console.log('adult_total', adult_total);
            console.log('child_total', child_total);
            console.log('guide_total', guide_total);
            const booking_total = (parseFloat(adult_total || 0) + parseFloat(child_total || 0) + parseFloat(guide_total || 0)).toFixed(2);
            setData({
                ...data,
                booking_total: booking_total || 0,
            });
        }
    }, [adults, children, guides]);

    const loadCalendar = async () => {
        console.log('-----------------loadCalendar-------------');
        if (activityId) {
            setLoading(true);
            try {
            const response = await axios.get(route('admin.booking.activity.calendar'), {
                params: {
                activity_id: activityId,
                view_mode: view_mode,
                start_date: format(date, 'yyyy-MM-dd'),
                adults,
                children
                }
            });
            console.log('ScheduleData ', response);
            setScheduleData(response.data);
            } catch (error) {
            console.error('Failed to load calendar:', error);
            } finally {
            setLoading(false);
            }
        }
    };

    useEffect(() => {
    if (selectedService && selectedSeason) {
        fetchCalculatedPrice();
    }
    }, [selectedService, selectedSeason, selectedWholesaler, promoCode, adults, children, guides, selectedDate]);

    const fetchCalculatedPrice = async () => {
        console.log('selectedService', selectedService);
        console.log('selectedSeason', selectedSeason);
        console.log('selectedWholesaler', selectedWholesaler);
        console.log('promoCode', promoCode);
        console.log('discountCode', discountCode);
        console.log('adults', adults);
        console.log('children', children);
        console.log('guides', guides);
        console.log('selectedDate', selectedDate);
        if(!selectedService || !selectedSeason || !adults || !children || !guides || !selectedDate) return; // || !selectedWholesaler || !promoCode || !discountCode 
        try {
            const res = await axios.post('/admin/bookings/calculate-price', {
            service_id: selectedService,
            season_id: selectedSeason,
            wholesaler_id: selectedWholesaler,
            promo_code: promoCode,
            discount_code: discountCode, // optional
            pax: { adults, children, guides },
            booking_date: selectedDate,
            });
            setPricing(res.data);
        } catch (e) {
            console.error(e);
        }
    };


    useEffect(() => {
        // console.log('selectedWholesaler', selectedWholesaler);
        // console.log('wholesaler_rates', wholesaler_rates);
        if(selectedWholesaler){
            const wholesaler_rates_list = wholesaler_rates.filter(s => s.id === parseInt(selectedWholesaler));
            //console.log('wholesaler_rates_list', wholesaler_rates_list);
            let wholesalerList = [];
            wholesaler_rates_list.forEach(rate=>{
                //console.log('rate', rate);
                const filtered = {id: rate.id, name: rate.season?.name, price_adult: rate.price_adult, price_child: rate.price_child, price_guide: rate.price_guide};
                //console.log('filtered', filtered);
                //setWholesalerRateList(prev => [...prev, filtered]);
                wholesalerList.push(filtered);
            });
            setWholesalerRateList(wholesalerList);

        }
    },[selectedWholesaler]);


    useEffect(() => {
        if(selectedService){
            console.log('discounts', discounts);
            const discount_rates_list = discounts.filter(s => s.service_id === parseInt(selectedService));
            let discountList = [];
            discount_rates_list.forEach(rate=>{
                const filtered = {id: rate.id, name: rate.name, percent_off: rate.percent_off, applies_to: rate.applies_to};
                discountList.push(filtered);
            });
            setDiscountList(discountList);
        }
    },[selectedService]);

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

    useEffect(() => {
        const service = services.find(s => {
            console.log('service', s);
            console.log('service_id ', data.service_id);
            const selected = s.id === parseInt(data.service_id);
            console.log(selected);
            setSeasons([]);
            return selected;
        });
        console.log(service);
        setSelectedService(service?.id ?? null);
        const seasons = service ? setSeasons(service.seasons) : [];
        console.log('seasons', service?.seasons);

    }, [data.service_id]);

    useEffect(() => {
        const season = seasons.find(s => {
            console.log('season', s);
            console.log('season_id ', data.season_id);
            const selected = s.id === parseInt(data.season_id);
            console.log(selected);

            return selected;
        });
        console.log('selectedSeason', season);
        setSelectedSeason(season?.id ?? null);
        //const time_slots = selectedSeason ? setTimeslots(selectedSeason?.inventory?.time_slots) : [];
        //console.log('time_slots', selectedSeason?.inventory?.time_slots);
        if (season) {
            const formattedTimeSlots = season?.inventory?.inventory_time_slots?.map(slot => ({
                id: slot.id,
                name: slot.start_time.slice(0, 5), // e.g., '08:00' from '08:00:00'
            })) || [];
            console.log('formattedTimeSlots--------------------', formattedTimeSlots);
            // setData('adult', selectedSeason?.inventory?.rake_rate_adult || 0);
            // setData('child', selectedSeason?.inventory?.rake_rate_child || 0);
            // setData('guide', selectedSeason?.inventory?.rake_rate_guide || 0);
            setData({
                ...data,
                adult: season?.inventory?.rake_rate_adult || 0,
                child: season?.inventory?.rake_rate_child || 0,
                guide: season?.inventory?.rake_rate_guide || 0,
                rate_per_adult: season?.inventory?.rake_rate_adult || 0,
                rate_per_child: season?.inventory?.rake_rate_child || 0,
                rate_per_guide: season?.inventory?.rake_rate_guide || 0,
            });
            setTimeslots(formattedTimeSlots);
        } else {
            setTimeslots([]); // fallback
        }

    }, [data.season_id]);

    // useEffect(() => {
    //     if (!data.override_rates) {
    //         setData({
    //             ...data,
    //             rate_per_adult: data.adult,
    //             rate_per_child: data.child,
    //             rate_per_guide: data.guide,
    //         });
    //     }
    // }, [data.override_rates]);

    const calculateTotal = (count, rate) => {
        return (parseFloat(count || 0) * parseFloat(rate || 0)).toFixed(2);
    };
    const calculateBookingTotal = () => {
        if(data?.adults > 0 || data?.children > 0 || data?.guides > 0){
        const adult_total = (parseFloat(data?.adults || 0) * parseFloat(data?.rate_per_adult || 0));
        const child_total = (parseFloat(data?.children || 0) * parseFloat(data?.rate_per_child || 0));
        const guide_total = (parseFloat(data?.guides || 0) * parseFloat(data?.rate_per_guide || 0));
        //console.log('data--------------------', data);
        console.log('adult_total', adult_total);
        console.log('child_total', child_total);
        console.log('guide_total', guide_total);
            return (parseFloat(adult_total || 0) + parseFloat(child_total || 0) + parseFloat(guide_total || 0)).toFixed(2);
        }

        return 0;

        // if(data.override_rates){
        //     setData({
        //         ...data,
        //         booking_total: booking_total || 0,
        //     });
        // }
    };
    // const serviceChange = (e) => {
    //     console.log('season change', e.target.value);
    //     //setData('season_id', e.target.value);
    //     filteredSeasons = seasons.filter(s => s.service_id == e.target.value);
    //     setData('seasons', filteredSeasons);
    // }
    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
        console.log('data----- ', data);
    };

    const handleCheveronDateChange = (direction) => {
        let newDate;

        if (view_mode === 'day') {
            newDate = addDays(date, direction);
        } else if (view_mode === 'week') {
            newDate = addWeeks(date, direction);
        } else if (view_mode === 'month') {
            newDate = addMonths(date, direction);
        }

        setDate(newDate);
    };

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


            <div className="bg-secondary-100 border border-secondary-200">
                <div className="space-y-6 max-w-full overflow-hidden mx-auto p-6 bg-white shadow rounded">
                    <h1 className="text-booking-heading">New Booking</h1>
                        {page.props.error && <div className="text-red-600">{page.props.error}</div>}
                        {/* Wholesaler Booking */}
                            <CustomCheckbox
                                label="Wholesaler Booking"
                                side="left"
                                name="is_wholesaler"
                                checked={data.is_wholesaler}
                                
                                onChange={handleChange}
                                errors={errors}
                            />
                        {data.is_wholesaler && (
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <CustomSelect
                            name="wholesaler_id"
                            value={data.wholesaler_id}
                            
                            onChange={(e) => {setData(e.target.name, e.target.value); setSelectedWholesaler(e.target.value);}}
                            error={errors.wholesaler_id}
                            options={wholesalers}
                        /> 
                            <CustomCheckbox
                                label="Add Consultant"
                                name="add_consultant"
                                checked={data.add_consultant}
                                
                                onChange={handleChange}
                                errors={errors}
                            />
                        </div>
                        )}
                        {data.add_consultant && (
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <CustomInputText
                                    title="Consultant Name"
                                    name="consultant_name"
                                    value={data.consultant_name}
                                    
                                    onChange={(e) => setData('consultant_name', e.target.value)}
                                    errors={errors}
                                />
                                <CustomInputText
                                    title="Consultant Email"
                                    name="consultant_email"
                                    value={data.consultant_email}
                                    
                                    type="email"                            
                                    onChange={(e) => setData('consultant_email', e.target.value)}
                                    errors={errors}
                                />
                                <CustomInputText
                                    title="Consultant Mobile"
                                    name="consultant_mobile"
                                    value={data.consultant_mobile}
                                    
                                    onChange={(e) => setData('consultant_mobile', e.target.value)}
                                    errors={errors}
                                />
                            </div>
                        )}

                        {/* Service + Date */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <CustomSelect
                            label="Service"
                            name="service_id"
                            value={data.service_id}
                            
                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            error={errors.service_id}
                            options={services}
                        /> 
                        <CustomSelect
                            label="Season"
                            name="season_id"
                            value={data.season_id}
                            
                            onChange={(e) => {handleChange(e.target.name, e.target.value); setActivityId(e.target.value)}}
                            error={errors.season_id}
                            options={seasons}
                        /> 
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {/* Time slots */}
                        {/* <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <input
                                type="date"
                                value={data.booking_date}
                                onChange={(e) => {setData('booking_date', e.target.value); setDate(new Date(e.target.value))}}
                                className="border rounded p-2"
                            /> */}
                        <CustomInputText
                            title="Booking Date"
                            name="booking_date"
                            type='date'
                            value={data.booking_date}
                            
                            onChange={(e) => {handleChange(e.target.name, e.target.value); setDate(new Date(e.target.value))}}
                            errors={errors}
                        />
                        <CustomSelect
                            label="Start Time"
                            name="start_time"
                            value={data.start_time}
                            
                            onChange={(e) => {setData('start_time', e.target.value);}}
                            error={errors.start_time}
                            options={timeslots}
                        /> 

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
                        <div className="grid grid-cols-1 sm:grid-cols-4 gap-4 items-center">
                        <CustomInputText
                            title="Number of Adults"
                            id="adults"
                            name="adults"
                            type='number'
                            min="0"
                            value={data.adults}
                            
                            onChange={(e) => { let val = e.target.value > 0 ? e.target.value : e.target.value * -1;console.log('eeeeeeeeeeeeeeeee', val); handleChange(e.target.name, val); setAdults(val)}}
                            errors={errors}
                        />
                        <CustomInputText
                            title="Number of Children"
                            id="children"
                            name="children"
                            type='number'
                            min="0"
                            value={data.children}
                            
                            onChange={(e) => {let val = e.target.value > 0 ? e.target.value : e.target.value * -1; handleChange(e.target.name, val); setChildren(val)}}
                            errors={errors}
                        />
                        <CustomInputText
                            title="Number of Guides"
                            id="guides"
                            name="guides"
                            type='number'
                            min="0"
                            value={data.guides}
                            
                            onChange={(e) => {let val = e.target.value > 0 ? e.target.value : e.target.value * -1; handleChange(e.target.name, val); setGuides(val)}}
                            errors={errors}
                        />
                        <div className="mt-2">
                            <CustomCheckbox
                                label="Include Photos and Videos"
                                name="include_media"
                                checked={data.include_media}
                                
                                onChange={handleChange}
                                errors={errors}
                            />

                        </div>

                        </div>

                        {/* Inventory Calendar Table */}
                        <section className="md:col-span-3 bg-white p-6">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="text-booking-subheading">Available Inventory</h3>
                                <div className='flex items-center gap-3'>
                                    <div className="flex items-center justify-center w-[208px] h-[36px] bg-secondary-100 rounded-[10px]">
                                        {['day', 'week', 'month'].map((mode) => (
                                        <Button
                                            key={mode}
                                            variant="ghost"
                                            onClick={() => router.reload({ only: ['view_mode'], data: { view_mode: mode } })}
                                            className={view_mode === mode ? 'bg-white text-btn-cal text-[#161618] w-[68px] h-[32px] rounded-[10px] shadow hover:bg-white border-[1px] border-secondary-200' : 'bg-secondary-100 text-btn-cal text-[#161618] w-[68px] h-[32px] hover:bg-white'}
                                        >
                                            {mode.charAt(0).toUpperCase() + mode.slice(1)}
                                        </Button>
                                        ))}
                                    </div>
                                    <div className="flex items-center justify-center bg-white w-[200px] h-[40px] rounded-[10px] px-6 py-5 text-center border border-secondary-200 gap-2">
                                        {/* <Calendar selected={date} onSelect={handleDateChange} />       */}
                                        <span className="text-black">
                                            {icons.calendar}
                                        </span> 
                                        <span>{date.toLocaleDateString()}</span>
                                    </div>                                    
                                </div>

                                <div onClick={() => setToggleCalendar(!toggleCalendar)}>
                                    {toggleCalendar ? icons.cheveron_down : icons.cheveron_up}
                                </div>

                            </div>
                            {loading ? (
                            <div className={`${toggleCalendar ? 'block' : 'hidden'} text-center py-20 text-gray-500`}>Loading calendar...</div>
                            ) : (
                            <div className="overflow-x-auto">
                                {scheduleData.days.length === 0 ? (
                                <div className="text-center py-10 text-red-500">No availability for this date range.</div>
                                ) : (
                                <div className={`${toggleCalendar ? 'block' : 'hidden'} rounded-[10px] overflow-hidden border border-secondary-200`}>
                                    <table className="w-auto text-sm text-left rounded-[10px] border border-secondary-200 overflow-y-auto">
                                        <thead className="rounded-[10px] border border-secondary-200 ">
                                        <tr className="text-gray-600">
                                            <th className="w-[100px] h-[52px] p-2">
                                                <div className="flex items-center justify-between">
                                                    <button onClick={() => handleCheveronDateChange(-1)}>{icons.cheveron_left}</button>
                                                    <button onClick={() => handleCheveronDateChange(1)}>{icons.cheveron_right}</button>
                                                </div>
                                            </th>
                                            {scheduleData.days.map(day => (
                                            <th key={day} className=" w-[133px] h-[52px] bg-gray-100 border border-gray-200 text-center">{day}</th>
                                            ))}
                                        </tr>
                                        </thead>
                                        <tbody>
                                        {scheduleData.schedule.map(row => {
                                            console.log('row', row);
                                        return (
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
                                                        setData('selected_slot', { date: cell.date, day: day, time: row.time });
                                                        setSelectedDate(cell.date);
                                                    }
                                                    }}
                                                >
                                                    <div className="w-[133px] h-full flex items-center justify-center">
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
                                        )})}
                                        </tbody>
                                    </table>
                                </div>
                                )}
                            </div>
                            )}

                            {errors?.['selected_slot'] && (
                                <p className="text-red-500 text-sm mt-2">
                                    {errors['selected_slot']}
                                </p>
                            )}
                        </section>

                        <div className="mt-10 p-6 bg-white space-y-4">


                            {/* Top-level toggles */}
                            <div className="flex flex-wrap gap-4 items-center justify-between w-full ">
                                <div className="flex flex-wrap gap-4 items-center justify-start ">
                                    <h2 className="text-booking-subheading">Rates</h2> 
                                </div>

                                <div className="flex flex-wrap gap-4 items-center justify-end ">
                                                                        {[
                                        { key: 'web_booking', label: 'Web Booking' },
                                        { key: 'needs_pickup', label: 'Needs Pickup' },
                                        { key: 'override_timeslots', label: 'Override Time Slots' },
                                        { key: 'exclusive_resource', label: 'Exclusive Resource use' },
                                    ].map(({ key, label }) => (
                                        <CustomCheckbox
                                            key={key}
                                            label={label}
                                            name={key}
                                            checked={data[key] || false}
                                            onChange={handleChange}
                                            errors={errors}
                                        />
                                    ))}
                                    <WhiteButton 
                                        title="Edit Manifest" 
                                        icon={true}
                                        iconName={icons.plus}
                                        width="w-[152px]"
                                        className="mt-4"
                                        onClick={() => handleSubmit(e)}
                                    />
                                    <div onClick={() => setToggleRate(!toggleRate)}>
                                        {toggleRate ? icons.cheveron_down : icons.cheveron_up}
                                    </div>   
                                </div>
                            </div>
                            {/* Rates */}
                            <div className={`${toggleRate ? 'block' : 'hidden'} mt-10 p-6 bg-white rounded-[10px] border border-secondary-100 shadow space-y-4`}>

                                <div className="flex items-center gap-[66px] mt-4">
                                    <CustomCheckbox
                                        label="Override Rates"
                                        name="override_rates"
                                        checked={data.override_rates || false}
                                        
                                        onChange={handleChange}
                                        errors={errors}
                                    />
                                    <div className="mb-4 flex items-center gap-4">
                                        <label
                                            htmlFor='override_reason'
                                            className="text-booking-lbl-overide mt-2"
                                        >
                                            Reason for Override
                                        </label>
                                        <input
                                            type="text"
                                            id='override_reason'
                                            name="Reason for Override"
                                            value={data.override_reason || ''}
                                            onChange={(e) => setData('override_reason', e.target.value)}
                                            className={`mt-1 block w-[639px] h-12 ${errors.override_reason ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-xl
                                            hover:border-black  active:border-blue-600 
                                            focus:outline-none focus:ring-0 focus:border-black
                                            `}
                                            required
                                        />
                                        {errors.override_reason && (
                                            <p className="text-red-500 text-sm mt-2">
                                                {errors.override_reason}
                                            </p>
                                        )}
                                    </div>
                                </div>
                                {/* Rate cards */}
                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                                    {[
                                        { label: 'Adult', key: 'adult', count: adults, rateKey: 'rate_per_adult', iconCol:'#2D99FE' },
                                        { label: 'Child', key: 'child', count: children, rateKey: 'rate_per_child', iconCol:'#A7D3FD' },
                                        { label: 'Guide', key: 'guide', count: guides, rateKey: 'rate_per_guide', iconCol:'#D6D6D6' },
                                    ].map(({ label, key, count, rateKey, iconCol }) => (
                                        <div key={key} className="p-4 bg-white rounded-[10px] border border-secondary-100 shadow ">
                                            <div className="flex items-center justify-start mb-2 gap-[18px]">
                                                <div className={`flex items-center justify-center w-[36px] h-[38px] gap-2 bg-[${iconCol}] rounded-full p-2`}>
                                                    {icons[key]}
                                                </div>
                                                <div className="flex flex-col items-start ">
                                                    <span className="text-booking-card-title">{label}</span>
                                                    <span className="text-booking-card-title-sub">Rack Rate per: <span className="text-booking-card-body ml-2">{data[key].toFixed(2)}</span></span>
                                                </div>
                                            </div>
                                            <div className="space-y-2">
                                                <div className="mb-4 flex items-center justify-between gap-2">
                                                    <label
                                                        htmlFor='override_reason'
                                                        className="text-booking-card-lbl w-[128px] h-[34px] mt-2"
                                                    >
                                                        Rate per
                                                    </label>
                                                    <input
                                                        type="number"
                                                        id={rateKey}
                                                        name={rateKey}
                                                        disabled={!data.override_rates}
                                                        value={data[rateKey] || ''}
                                                        onChange={(e) => setData(rateKey, e.target.value)}
                                                        className={`mt-1 block w-full h-[34px] ${errors.rateKey ? "border-red-500" : "border-gray-300"} shadow-sm text-booking-card-body rounded-xl
                                                        hover:border-black  active:border-blue-600 
                                                        focus:outline-none focus:ring-0 focus:border-black
                                                        `}
                                                        required
                                                    />
                                                    {errors.override_reason && (
                                                        <p className="text-red-500 text-sm mt-2">
                                                            {errors.override_reason}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="mb-4 flex items-center justify-between gap-2">
                                                    <label
                                                        htmlFor='override_reason'
                                                        className="text-booking-lbl-overide w-[128px] h-[34px] mt-2"
                                                    >
                                                        Total Price
                                                    </label>
                                                    <input
                                                        type="text"
                                                        disabled='disabled'
                                                        value={calculateTotal(count, data[rateKey])}
                                                        className={`mt-1 block w-full h-[34px] ${errors.override_reason ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-xl
                                                        hover:border-black  active:border-blue-600 
                                                        focus:outline-none focus:ring-0 focus:border-black
                                                        `}
                                                        required
                                                    />
                                                    {errors.override_reason && (
                                                        <p className="text-red-500 text-sm mt-2">
                                                            {errors.override_reason}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <div className="flex items-center space-x-4">
                                    <label className="text-booking-lbl-main">Booking Total</label>
                                    <div className="flex items-center justify-between w-[417px] h-[43px] bg-[#DBECFD4D] pl-[7px] pr-[7px] py-[7px] rounded-[6px]">
                                        <span className="text-booking-lbl-main-sub">R {data.booking_total || '0.00'}</span>
                                        <WhiteButton 
                                            title="Recalucate from Rack" 
                                            icon={true}
                                            type="button"
                                            iconName={icons.spanners}
                                            textColor='text-[#4D81E7]'
                                            width="w-[189px]"
                                            className="mt-4"
                                            onClick={()=>{}}
                                        />                                                    
                                    </div>
                                </div>
                            </div>
                            
                            <div className="mt-10 p-6 bg-white rounded-[10px] border border-secondary-100 shadow space-y-4 ">
                                {/*  */}
                                {/* <div className="flex w-full items-center justify-start space-x-4 ">
                                    <span className="text-booking-lbl-main w-[129px]">Redeem Discount</span>
                                    <span className="w-[572px] mt-2">
                                        <CustomInputText
                                            id="redeem_discount"
                                            name="redeem_discount"
                                            type='text'
                                            value={data.redeem_discount}
                                            placeholder="Redeem Discount"
                                            onChange={(e) => setData('redeem_discount', e.target.value)}
                                            errors={errors}
                                        />                                        
                                    </span>

                                    <BlueButton
                                    title="Redeem" 
                                    onClick={()=>{}} 
                                    disabled={processing} 
                                    type="button" />
                                    <WhiteButton 
                                        title="Remove" 
                                        textColor='text-[#7C7C7C]'
                                        width="w-[108px]"
                                        className="mt-4"
                                        disabled={processing}
                                        onClick={()=>{}}
                                    />
                                </div> */}
                                {/* <div className="flex w-full items-center justify-start space-x-4 ">
                                    <span className="text-booking-lbl-main w-[129px]">Redeem Wholesaver Rate</span>
                                    <span className="w-[572px] mt-2">
                                        <CustomInputText
                                            id="redeem_promotion"
                                            name="redeem_promotion"
                                            type='text'
                                            value={data.redeem_promotion}
                                            placeholder="Redeem Promotion"
                                            onChange={(e) => setData('redeem_promotion', e.target.value)}
                                            errors={errors}
                                        />                                        
                                    </span>

                                    <BlueButton
                                    title="Redeem" 
                                    onClick={()=>{}} 
                                    disabled={processing} 
                                    type="button" />
                                    <WhiteButton 
                                        title="Remove" 
                                        textColor='text-[#7C7C7C]'
                                        width="w-[108px]"
                                        className="mt-4"
                                        type="button"
                                        disabled={processing}
                                        onClick={()=>{}}
                                    />
                                </div> */}
                                <div className="flex w-full items-center justify-start space-x-4 ">
                                    <div className="flex w-full items-center justify-start space-x-4 ">
                                        <CustomInputText
                                            title="Redeem Voucher"
                                            id="redeem_voucher"
                                            name="redeem_voucher"
                                            type='text'
                                            value={data.redeem_voucher}
                                            placeholder="Redeem Voucher"
                                            onChange={(e) => setData('redeem_voucher', e.target.value)}
                                            errors={errors}
                                        />
                                        <div className="flex items-center gap-2 mt-2">
                                            <BlueButton
                                            title="Redeem" 
                                            onClick={()=>{}} 
                                            disabled={processing} 
                                            type="button" />
                                            <WhiteButton 
                                            title="Remove" 
                                            type="button"
                                            textColor='text-[#7C7C7C]'
                                            width="w-[108px]"
                                            className="mt-4"
                                            disabled={processing}
                                            onClick={()=>{}}
                                            />    
                                        </div>
                                </div>
                                <div className="flex w-full items-center justify-start space-x-4 ">
                                    
                                        <div className="flex w-full items-center justify-start space-x-4 ">
                                            <CustomSelect
                                                label="Redeem Discount"
                                                name="redeem_discount"
                                                value={data.redeem_discount}
                                                
                                                onChange={(e) => {setData('redeem_discount', e.target.value);}}
                                                error={errors.redeem_discount}
                                                options={discountList}
                                            />
                                            <div className="flex items-center gap-2 mt-2">
                                            <BlueButton
                                            title="Apply" 
                                            onClick={()=>{}} 
                                            disabled={processing} 
                                            type="button" />
                                            <WhiteButton 
                                                title="Remove" 
                                                textColor='text-[#7C7C7C]'
                                                width="w-[108px]"
                                                className="mt-4"
                                                type="button"
                                                disabled={processing}
                                                onClick={()=>{}}
                                            />
                                            </div>
                                        </div>
                                           
                                </div>
                                <div className="flex w-full items-center justify-start space-x-4 ">
                                    {wholesalerRateList.length > 0 && (
                                    <div className="flex w-full items-center justify-start space-x-4 ">
                                    <CustomSelect
                                        label="Wholesaler Rate"
                                        name="redeem_wholesaler_rate"
                                        value={data.redeem_wholesaler_rate}
                                        
                                        onChange={(e) => {setData('redeem_wholesaler_rate', e.target.value);}}
                                        error={errors.redeem_wholesaler_rate}
                                        options={wholesalerRateList}
                                    />
                                    <div className="flex items-center gap-2 mt-2">
                                    <BlueButton
                                    title="Apply" 
                                    onClick={()=>{}} 
                                    disabled={processing} 
                                    type="button" />
                                    <WhiteButton 
                                        title="Remove" 
                                        textColor='text-[#7C7C7C]'
                                        width="w-[108px]"
                                        className="mt-4"
                                        type="button"
                                        disabled={processing}
                                        onClick={()=>{}}
                                    />
                                    </div>
                                    </div>
                                    )}    
                                </div>

                            </div>




                        {pricing && (
                        <div className="mt-4 p-4 border rounded bg-gray-50">
                            <h3 className="font-bold">Pricing</h3>
                            <p>Adult: {pricing.applied_rates.adult}</p>
                            <p>Child: {pricing.applied_rates.child}</p>
                            <p>Guide: {pricing.applied_rates.guide}</p>
                            <p className="font-semibold">Total: {pricing.total_price}</p>
                            {pricing.applied_promotion && <p>Promotion Applied: {pricing.applied_promotion.name}</p>}
                            {pricing.applied_discount && <p>Discount Applied: {pricing.applied_discount.name}</p>}
                        </div>
                        )}



                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div className="flex flex-col items-start  rounded-2xl p-6 w-full border border-secondary-100 shadow">
                                            <div className="flex items-center justify-start mb-2 gap-[18px]">
                                                <div className={`flex items-center justify-center w-[36px] h-[38px] gap-2 bg-[#2D99FE] rounded-full p-2`}>
                                                    {icons.notes}
                                                </div>
                                                <div className="flex flex-col items-start ">
                                                    <span className="text-booking-card-title">Add Invoice Note</span>
                                                </div>
                                            </div>
                                        <CustomTextarea
                                            name="invoice_note"
                                            value={data.invoice_note}
                                            onChange={(e) => setData('invoice_note', e.target.value)}
                                            rows={6}
                                        />                               
                                    </div>
                                    <div className="flex flex-col items-start rounded-2xl p-6 w-full border border-secondary-100 bg-white shadow">
                                        <div className="flex items-center justify-start mb-2 gap-[18px]">
                                            <div className={`flex items-center justify-center w-[36px] h-[38px] gap-2 bg-[#2D99FE] rounded-full p-2`}>
                                                {icons.emails}
                                            </div>
                                            <div className="flex flex-col items-start ">
                                                <span className="text-booking-card-title">Add Email Reminder</span>
                                            </div>
                                        </div>
                                        <CustomTextarea
                                            name="email_reminder"
                                            value={data.email_reminder}
                                            onChange={(e) => setData('email_reminder', e.target.value)}
                                            className="border rounded p-2"
                                            rows={6}
                                        />                               
                                    </div>
                                    <div className="flex flex-col items-start rounded-2xl p-6 w-full border border-secondary-100 bg-white shadow">
                                        <div className="flex items-center justify-start mb-2 gap-[18px]">
                                            <div className={`flex items-center justify-center w-[36px] h-[38px] gap-2 bg-[#2D99FE] rounded-full p-2`}>
                                                {icons.calendars}
                                            </div>
                                            <div className="flex flex-col items-start ">
                                                <span className="text-booking-card-title">Add Booking Note</span>
                                            </div>
                                        </div>
                                        <CustomTextarea
                                            name="booking_note"
                                            value={data.booking_note}
                                            onChange={(e) => setData('booking_note', e.target.value)}
                                            className="border rounded p-2"
                                            rows={6}
                                        />                                
                                    </div>
                                </div>
                            </div>
                            {/* add another activity 
                            <div className="flex items-center justify-between">
                                <label className="text-booking-subheading">Add Another Activity to this Booking</label>
                                <BlueButton
                                    title="Add Another Activity" 
                                    icon={true}
                                    width={"w-[243px]"}
                                    onClick={handleSubmit} 
                                    disabled={processing} 
                                    type="button" 
                                />
                            </div>*/}
                        </div>
                        {/* Booking Details */}
                        <div className="mt-10 p-6 bg-white rounded shadow space-y-4">

                            <div className="flex items-center justify-start">
                                <div className="flex items-center mr-[128px]">
                                    <label className="text-booking-subheading">Booking Details</label>
                                </div>
                                <div className="flex items-center mt-2 space-x-4">
                                    <CustomCheckbox
                                        label="Corporate Customer"
                                        name="corporate_customer"
                                        checked={data.corporate_customer}
                                        onChange={handleChange}
                                        errors={errors}
                                    />
                                    <CustomCheckbox
                                        label="Send SMS Notification Immediately"
                                        name="send_sms"
                                        checked={data.corporate_custsend_smsomer}
                                        onChange={handleChange}
                                        errors={errors}
                                    />                                    
                                </div>

                            </div>


                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <CustomInputText
                                    title="Lead Person"
                                    id="lead_person"
                                    name="lead_person"
                                    value={data.lead_person}
                                    
                                    onChange={(e) => setData('lead_person', e.target.value)}
                                    errors={errors}
                                />
                                <CustomInputText
                                    title="Lead Email"
                                    id="lead_email"
                                    name="lead_email"
                                    value={data.lead_email}
                                    
                                    onChange={(e) => setData('lead_email', e.target.value)}
                                    errors={errors}
                                />
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <CustomInputText
                                    title="Lead Mobile"
                                    id="lead_mobile"
                                    name="lead_mobile"
                                    value={data.lead_mobile}
                                    
                                    onChange={(e) => setData('lead_mobile', e.target.value)}
                                    errors={errors}
                                />
                                <CustomSelect
                                    label="Referral Source"
                                    name="referral_source"
                                    value={data.referral_source}
                                    
                                    onChange={(e) => {setData('referral_source', e.target.value);}}
                                    error={errors.referral_source}
                                    options={type_affiliates}
                                />
                            </div>

                        </div>


                        {/* Booking Payment */}
                        <div className="mt-10 bg-white w-full ml-[20%] lg:ml-[25%] ">
                            <div className="flex flex-col items-center justify-center px-6 py-[65px] w-[60%] lg:w-[50%] rounded-[10px] border border-secondary-100 shadow ">
                                <div className="flex flex-col items-center justify-center space-y-4">
                                    <div className="flex items-center space-x-[37px]">
                                        <span className="text-booking-lbl-main w-[160px] font-normal text-right">Total value of bookings</span>
                                        <div className="flex items-center justify-between w-[417px] h-[43px] bg-[#DBECFD4D] pl-[17px] py-[12.5px] rounded-[6px]">
                                            <span className="text-booking-lbl-main-sub text-[#4D81E7]">R{data?.booking_total || 0}</span>                                                  
                                        </div> 
                                    </div>
                                    <div className="flex items-center space-x-[37px]">
                                        <span className="text-booking-lbl-main w-[160px] font-normal text-right">Deposit Due Today</span>
                                        <div className="flex items-center justify-between w-[417px] h-[43px] bg-[#DBECFD4D] pl-[17px] py-[12.5px] rounded-[6px]">
                                            <span className="text-booking-lbl-main-sub text-[#AFAFAF]">R{(data?.deposit_due * .1).toFixed() || 0}</span>                                                  
                                        </div>
                                    </div>
                                    <div className="flex items-center space-x-[37px]">
                                        <span className="text-booking-lbl-main w-[160px] text-right">Total Due Today</span>
                                        <div className="flex items-center justify-between w-[417px] h-[43px] bg-[#DBECFD4D] pl-[17px] py-[12.5px] rounded-[6px]">
                                            <span className="text-booking-lbl-main-sub text-[#FF0000]">R{(data?.due_today * .1).toFixed() || 0}</span>                                                  
                                        </div>
                                    </div>
                                    <div className="flex items-center space-x-[37px]">
                                        <span className="text-booking-lbl-main w-[160px] text-right text-[#4D81E7]">Total Rack Value</span>
                                        <div className="flex items-center justify-between w-[417px] h-[43px] bg-[#DBECFD4D] pl-[17px] py-[12.5px] rounded-[6px]">
                                            <span className="text-booking-lbl-main-sub text-[#4D81E7]">R{data?.rack_value || 0}</span>                                                  
                                        </div>
                                    </div>                                    
                                </div>

                                <span className="text-booking-lbl-main w-full text-center font-normal my-7">
                                    Bill Back - The Wholesaler will be invoiced for R{data?.bill_back || 0} after the service is delivered.
                                </span>
                                <BlueButton
                                    title="Submit Booking"
                                    width={"w-[169px]"}
                                    type="button"
                                    onClick={(e) =>handleSubmit(e)}
                                    disabled={processing}
                                />                                
                            </div>
           
                        </div>
                </div>
            </div>


        </AuthenticatedLayout>
    );
}
