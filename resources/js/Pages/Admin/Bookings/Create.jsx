
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
import OutlineButton from '@/Components/OutlineButton';
import PromoModal from './PromoModal';


export default function Dashboard({ auth, wholesalers = [], services = [], schedule, start_date, view_mode, days, type_affiliates, vouchers, discounts, wholesaler_rates, promos, lookups }) {
    //seasons = [],, adults, children, guides
    console.log('---------------auth----create------------ ', auth);
    const page = usePage();
    const mode = 'create';
    //const currentRoutePath = page.props.currentRoutePath;

    //// console.log('=======================currentRoutePath============== ',currentRoutePath); // outputs: "services"
    const [viewMode, setViewMode] = useState(view_mode); // 'day', 'week', or 'month'
    const [activityId, setActivityId] = useState('');
    const [scheduleData, setScheduleData] = useState({ schedule, days });
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [seasons, setSeasons] = useState([]);
    const [timeslots, setTimeslots] = useState([]);
    const [toggleCalendar, setToggleCalendar] = useState(true);
    const [toggleRate, setToggleRate] = useState(true);
    const [selectedWholesaler, setSelectedWholesaler] = useState(null);
    const [selectedService, setSelectedService] = useState(null);
    const [selectedSeason, setSelectedSeason] = useState(null);
    const [activityDate, setActivityDate] = useState(() => new Date(start_date));
    const [selectedTime, setSelectedTime] = useState(null);
    const [adults, setAdults] = useState('0');
    const [children, setChildren] = useState('0');
    const [guides, setGuides] = useState('0');
    const [selectedSlot, setSelectedSlot] = useState(null); // stores {date: string, day: string, time: string }
    const [wholesalerRateList, setWholesalerRateList] = useState([]);
    const [discountList, setDiscountList] = useState(null);
    const [showDiscount, setShowDiscount] = useState(false);
    const [discountId, setDiscountId] = useState(null);
    const [discountPax, setDiscountPax] = useState(null);
    const [paxGroups, setPaxGroups] = useState([]);
    const [discount, setDiscount] = useState(false);
    const [showPromo, setShowPromo] = useState(false);
    const [promoList, setPromoList] = useState([]);
    const [promotionId, setPromotionId] = useState(null);
    const [promoCode, setPromoCode] = useState(null);
    const [promoModalOpen, setPromoModalOpen] = useState(false);


    const [selectedDate, setSelectedDate] = useState(null);
    const [pricing, setPricing] = useState(null);
    // console.log('schedule ',schedule);
    const { data, setData, post, processing, errors } = useForm({
        is_wholesaler: false,
        wholesaler_id: '',
        add_consultant: false,
        consultant_name: '',
        consultant_email: '',
        consultant_mobile: '',
        service_id: '',
        override_capacity: false,
        capacity: 0,
        duration: 0,
        season_id: '',
        override_price: false,
        base_per_adult: 0,
        base_per_child: 0,
        base_per_guide: 0,
        rate_per_adult: 0,
        rate_per_child: 0,
        rate_per_guide: 0,
        override_timeslots: false,
        booking_date: '',
        start_time: '',
        end_time: '',
        adults: '',
        children: '',
        guides: '',
        adult: 0,
        child: 0,
        guide: 0,
        include_media: false,
        web_booking: false,
        needs_pickup: false,
        exclusive_resource: false,
        //rates
        selected_slot: [],
        override_rates: false,
        override_reason: '',
        discount_lines: [],
        promoCode: null,
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
        deposit_due: lookups.find(l => l.slug == 'deposit_due')?.multiplier || 1,
        due_today: lookups.find(l => l.slug == 'due_today')?.multiplier || 1,
        rack_value: 1,
        bill_back: false,
        team_id: auth.user.team_id,
    });

    useEffect(() => {
        // console.log('activityId', activityId);
        // console.log('activityDate', activityDate);
        // console.log('start_time', selectedTime);
        // console.log('view_mode', view_mode);
        // console.log('adults', adults);
        // console.log('children', children);
        loadCalendar();
    }, [activityId, activityDate, view_mode, selectedTime, adults, children]);
    useEffect(() => {
        console.log('-----------------------Adults----------------------', adults);
        console.log('-----------------------Children----------------------', children);
        console.log('-----------------------Guides----------------------', guides);
        console.log('-----------------------data----------------------', data);
    }, [data]);


    const loadCalendar = async () => {
        // console.log('-----------------loadCalendar-------------');
        // console.log('-----------------data------------- ', data);
        if (activityId) {
            setLoading(true);
            try {
                const response = await axios.get(route('admin.booking.activity.calendar'), {
                    params: {
                        activity_id: activityId,
                        view_mode: view_mode,
                        start_date: format(activityDate, 'yyyy-MM-dd'),
                        start_time: selectedTime,
                        adults,
                        children
                    }
                });
                // console.log('ScheduleData ', response);
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
        console.log('selectedService', selectedService);
        console.log('selectedSeason', selectedSeason);
        console.log('selectedWholesaler', selectedWholesaler);
        console.log('promoCode', promoCode);
        console.log('discount', discount);
        console.log('adults', adults);
        console.log('children', children);
        console.log('guides', guides);
        console.log('selectedDate', selectedDate);
        console.log('selectedTime', selectedTime);
        let reset = false;
        if (data.adults != adults || data.children != children || data.guides != guides) {
            reset = true;
            setPromoCode(null);
            setDiscount(true);
        }
        console.log('reset', reset);

        if (adults > 0 || children > 0 || guides > 0) {
            let rate_per_adult = data?.rate_per_adult || 0;
            let rate_per_child = data?.rate_per_child || 0;
            let rate_per_guide = data?.rate_per_guide || 0;
            if (reset) {
                rate_per_adult = data?.base_per_adult || 0;
                rate_per_child = data?.base_per_child || 0;
                rate_per_guide = data?.base_per_guide || 0;
            }

            const adult_total = (parseFloat(adults || 0) * parseFloat(rate_per_adult));
            const child_total = (parseFloat(children || 0) * parseFloat(rate_per_child));
            const guide_total = (parseFloat(guides || 0) * parseFloat(rate_per_guide));
            //console.log('data--------------------', data);
            // console.log('adult_total', adult_total);
            // console.log('child_total', child_total);
            // console.log('guide_total', guide_total);
            const booking_total = (parseFloat(adult_total || 0) + parseFloat(child_total || 0) + parseFloat(guide_total || 0)).toFixed(2);
            setData({
                ...data,
                adults: adults || 0,
                children: children || 0,
                guides: guides || 0,
                promoCode: null,
                discount_lines: [],
                rate_per_adult: rate_per_adult,
                rate_per_child: rate_per_child,
                rate_per_guide: rate_per_guide,
                booking_total: booking_total || 0,
            });
            let groups = [];

            if (adults > 0) {
                groups.push({ type: "adult", count: parseInt(adults), discount_id: null, base_rate: data.rate_per_adult, final_rate: data.rate_per_adult });
            }
            if (children > 0) {
                groups.push({ type: "child", count: parseInt(children), discount_id: null, base_rate: data.rate_per_child, final_rate: data.rate_per_child });
            }
            if (guides > 0) {
                groups.push({ type: "guide", count: parseInt(guides), discount_id: null, base_rate: data.rate_per_guide, final_rate: data.rate_per_guide });
            }
            setPaxGroups(groups);
            if (data.discount_lines.length != 0) {
                setData({
                    ...data,
                    discount_lines: []
                });
            }
        }
        if ((selectedService && selectedDate && selectedTime && adults > 0) && !data.is_wholesaler) {
            const discount_rates_list = discounts.filter(discount =>
                discount.services.some(service => service.id === parseInt(selectedService.id))
            );
            const serviceDiscounts = discount_rates_list.map(rate => ({
                id: rate.id,
                name: rate.name,
                percent_off: rate.percent_off,
                applies_to: rate.applies_to
            }));
            if (serviceDiscounts.length > 0 && (data.promoCode == null || reset)) {
                setShowDiscount(true);
                setDiscountList(serviceDiscounts);
            } else {
                setShowDiscount(false);
                setDiscountList([]);
            }
            const servicePromos = promos.filter(promo => promo.service_id === selectedService.id);
            if (servicePromos.length > 0 && (data.discount_lines.length == 0 || reset)) {
                setShowPromo(true);
                setPromoList(servicePromos);
            } else {
                setShowPromo(false);
                setPromoList([]);
            }

        } else {
            setShowDiscount(false);
            setDiscountList([]);
            setShowPromo(false);
            setPromoList([]);
        }
        console.log('data', data);

    }, [selectedService, selectedSeason, adults, children, guides, selectedDate, selectedTime]);//, selectedWholesaler, promoCode

    ////////////////////////////////////////
    const handleCalculate = async () => {
        console.log('selectedService', selectedService);
        console.log('selectedSeason', selectedSeason);
        console.log('selectedWholesaler', selectedWholesaler);
        console.log('promoCode', promoCode);
        console.log('discount', discount);
        console.log('adults', adults);
        console.log('children', children);
        console.log('guides', guides);
        console.log('selectedDate', selectedDate);
        console.log('selectedTime', selectedTime);
        console.log('==============Payload:========== ');

        const service_id = selectedService ? selectedService?.id : (alert('Please select a service'));
        const season_id = selectedSeason ? selectedSeason?.id : (alert('Please select a season'));
        const wholesaler_id = selectedWholesaler || null;
        const booking_date = selectedDate ? selectedDate : (alert('Please select a date'));
        const promo_code = promoCode || null;
        const pax_groups = paxGroups ? paxGroups : (alert('Please select a pax group'));
        if (!selectedService || !selectedSeason || !adults || !children || !guides || !selectedDate || !selectedTime) return;
        console.log('==============Payload:========== ');
        try {
            const payload = {
                service_id: service_id,
                season_id: season_id,
                wholesaler_id: wholesaler_id,
                booking_date: booking_date,
                promo_code: promo_code,
                pax_groups: pax_groups,
            };

            console.log('==============Payload:========== ', payload);
            axios
                .post(route("admin.booking.calculate"), payload)
                .then((res) => {
                    console.log('Calculation result:', res.data);
                    const applied_discounts = res.data.applied_rates;
                    const applied_promotion = res.data.applied_promotion;
                    const applied_rates = res.data.applied_rates;
                    const base_rates = res.data.applied_rates;
                    const total_price = res.data.total_price;
                    setData({
                        ...data,
                        override_price: false,
                        override_rates: false,
                        rate_per_adult: applied_rates.adult,
                        rate_per_child: applied_rates.child,
                        rate_per_guide: applied_rates.guide,
                    });

                    //setPricing(res.data);
                    setShowDiscount(false);
                    setDiscountList([]);
                })
                .catch((err) => {
                    console.error(err)
                });


            // const { data } = await axios.post(route('admin.booking.calculate'), payload);
            // console.log('Calculation result:', data);
            // update state here
        } catch (err) {
            console.error(err.response?.data || err);
        }
    };
    ///////////////////////////////////////////////////////


    const fetchCalculatedPrice = async () => {
        // console.log('selectedService', selectedService);
        // console.log('selectedSeason', selectedSeason);
        // console.log('selectedWholesaler', selectedWholesaler);
        // console.log('promoCode', promoCode);
        // console.log('discount', discount);
        // console.log('adults', adults);
        // console.log('children', children);
        // console.log('guides', guides);
        // console.log('selectedDate', selectedDate);
        return;
        if (!selectedService || !selectedSeason || !adults || !children || !guides || !selectedDate) return; // || !selectedWholesaler || !promoCode || !discount 
        try {
            const res = await axios.post('/admin/bookings/calculate-price', {
                service_id: selectedService.id,
                season_id: selectedSeason.id,
                wholesaler_id: selectedWholesaler,
                promo_code: promoCode,
                discount_code: discount, // optional
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
        if (selectedWholesaler) {
            const wholesaler_rates_list = wholesaler_rates.filter(s => s.id === parseInt(selectedWholesaler));
            //console.log('wholesaler_rates_list', wholesaler_rates_list);
            let wholesalerList = [];
            wholesaler_rates_list.forEach(rate => {
                //console.log('rate', rate);
                const filtered = { id: rate.id, name: rate.season?.name, price_adult: rate.price_adult, price_child: rate.price_child, price_guide: rate.price_guide };
                // console.log('filtered', filtered);
                setData({
                    ...data,
                    override_price: false,
                    override_rates: false,
                    adult: rate.price_adult || 0,
                    child: rate.price_child || 0,
                    guide: rate.price_guide || 0,
                    base_per_adult: rate.price_adult || 0,
                    base_per_child: rate.price_child || 0,
                    base_per_guide: rate.price_guide || 0,
                    rate_per_adult: rate.price_adult || 0,
                    rate_per_child: rate.price_child || 0,
                    rate_per_guide: rate.price_guide || 0,
                });
                wholesalerList.push(filtered);
            });
            setWholesalerRateList(wholesalerList);
        }
        if (!data.is_wholesaler) {
            setWholesalerRateList([]);
            setSelectedWholesaler(null);
            setData({
                ...data,
                wholesaler_id: null,
                discount_lines: [],
                promoCode: null,
                redeem_voucher: null,
            });
            setShowDiscount(false);
            setPaxGroups([]);
            setAdults(0);
            setChildren(0);
            setGuides(0);
        }

    }, [selectedWholesaler, data.is_wholesaler]);

    const handleDateChange = (newDate) => {
        // setActivityDate(newDate);
        router.get(route('admin.bookings.create'), {
            activity_id: activityId,
            view_mode: 'week',
            start_date: format(newDate, 'yyyy-MM-dd')
        });
        setSelectedSlot(null); // reset selected slotdatedate.toDateString() // 
    };

    useEffect(() => {
        const service = services.find(s => {
            // console.log('service', s);
            // console.log('service_id ', data.service_id);
            const selected = s.id === parseInt(data.service_id);
            // console.log(selected);
            setSeasons([]);
            return selected;
        });
        // console.log(service);
        setSelectedService(service ?? null);
        const seasons = service ? setSeasons(service.seasons) : [];
        // console.log('seasons', service?.seasons);
        // const base_per_adult = service?.rate_per_adult || 0;
        // const base_per_child = service?.rate_per_child || 0;
        // const base_per_guide = service?.rate_per_guide || 0;
        // const rate_per_adult = service?.rate_per_adult || 0;
        // const rate_per_child = service?.rate_per_child || 0;
        // const rate_per_guide = service?.rate_per_guide || 0;
        if (!selectedWholesaler && !data.is_wholesaler) {
            setData({
                ...data,
                override_capacity: service?.allow_internal_capacity_override || false,
                capacity: service?.capacity || 0,
                duration: service?.duration || 0,
                season_id: null,
                booking_date: null,
                override_price: false,
                override_rates: false,
                override_timeslots: false,
                adult: 0,
                child: 0,
                guide: 0,
                base_per_adult: 0,
                base_per_child: 0,
                base_per_guide: 0,
                rate_per_adult: 0,
                rate_per_child: 0,
                rate_per_guide: 0,
            });
            setSelectedSeason(null);
            setActivityId(null);
            setActivityDate(() => new Date(start_date));
            setTimeslots([]);
        } else {
            setData({
                ...data,
                override_capacity: service?.allow_internal_capacity_override || false,
                capacity: service?.capacity || 0,
                duration: service?.duration || 0,
                season_id: null,
                booking_date: null,
                override_price: false,
                override_rates: false,
                override_timeslots: false,
            });
            setSelectedSeason(null);
            setActivityId(null);
            setActivityDate(() => new Date(start_date));
            setTimeslots([]);
        }

    }, [data.service_id]);

    useEffect(() => {
        // const season = seasons.find(s => {
        //     console.log('season', s);
        //     console.log('season_id ', data.season_id);
        //     const selected = s.id === parseInt(data.season_id);
        //     console.log(selected);

        //     return selected;
        // });
        // console.log('selectedSeason', season);
        // setSelectedSeason(season?.id ?? null);
        // //const time_slots = selectedSeason ? setTimeslots(selectedSeason?.inventory?.time_slots) : [];
        // //console.log('time_slots', selectedSeason?.inventory?.time_slots);
        // if (season) {
        //     const formattedTimeSlots = season?.inventory?.inventory_time_slots?.map(slot => ({
        //         id: slot.id,
        //         name: slot.start_time.slice(0, 5), // e.g., '08:00' from '08:00:00'
        //     })) || [];
        //     console.log('formattedTimeSlots--------------------', formattedTimeSlots);
        //     // setData('adult', selectedSeason?.inventory?.rake_rate_adult || 0);
        //     // setData('child', selectedSeason?.inventory?.rake_rate_child || 0);
        //     // setData('guide', selectedSeason?.inventory?.rake_rate_guide || 0);
        //     setData({
        //         ...data,
        //         adult: season?.inventory?.rake_rate_adult || 0,
        //         child: season?.inventory?.rake_rate_child || 0,
        //         guide: season?.inventory?.rake_rate_guide || 0,
        //         rate_per_adult: season?.inventory?.rake_rate_adult || 0,
        //         rate_per_child: season?.inventory?.rake_rate_child || 0,
        //         rate_per_guide: season?.inventory?.rake_rate_guide || 0,
        //     });
        //     setTimeslots(formattedTimeSlots);
        // } else {
        //     setTimeslots([]); // fallback
        // }

    }, [data.season_id]);

    function applyPromoCode() {
        if (!promoCode) return;
        const promo = promoList.find(p => p.promo_code === promoCode);
        if (!promo) return;
        setData({
            ...data,
            override_price: false,
            override_rates: false,
            adult: promo.value,
            child: promo.value,
            guide: promo.value,
        });
    }

    function handlePromoModalClose() {
        setPromoModalOpen(false);
    }

    function handlePromoModalSelect(promoCode) {
        setPromoCode(promoCode);
        setData("promoCode", promoCode);
        handlePromoModalClose();
    }

    const calculateTotal = (count, rate) => {
        return (parseFloat(count || 0) * parseFloat(rate || 0)).toFixed(2);
    };

    const calculateBookingTotal = () => {
        if (data?.adults > 0 || data?.children > 0 || data?.guides > 0) {
            const adult_total = (parseFloat(data?.adults || 0) * parseFloat(data?.rate_per_adult || 0));
            const child_total = (parseFloat(data?.children || 0) * parseFloat(data?.rate_per_child || 0));
            const guide_total = (parseFloat(data?.guides || 0) * parseFloat(data?.rate_per_guide || 0));
            // console.log('data--------------------', data);
            // console.log('adult_total', adult_total);
            // console.log('child_total', child_total);
            // console.log('guide_total', guide_total);
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
    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
        // console.log('target----- ', target);
        // console.log('value----- ', value);

    };

    const parseDate = (str) => {
        const [datePart] = str.split(" "); // strip time if present
        const [y, m, d] = datePart.split("-").map(Number);
        return new Date(y, m - 1, d); // local midnight
    };

    useEffect(() => {
        if (data?.booking_date) {
            // console.log('booking_date', data.booking_date);
            //const selectedDate = new Date(value);
            const selectedDate = parseDate(data.booking_date);
            // console.log('selectedDate', selectedDate);
            const season = seasons.find(s => {
                const start = new Date(s.start_date);
                // console.log('start', start);
                const end = new Date(s.end_date);
                // console.log('end', end);
                const selected = selectedDate >= start && selectedDate <= end;
                // console.log('selected----------ooooooooooooooooooooooooooo', selected);
                return selected;
            });

            console.log('-------------selectedSeason---------', season);
            //const time_slots = selectedSeason ? setTimeslots(selectedSeason?.inventory?.time_slots) : [];
            //console.log('time_slots', selectedSeason?.inventory?.time_slots);
            if (season) {
                // console.log('-------------Season-----gggggggggggggggggg----', season);
                const formattedTimeSlots = [
                    { id: 0, name: "All" },
                    ...(season?.inventory?.inventory_time_slots?.map(slot => ({
                        id: slot.id,
                        name: slot.start_time.slice(0, 5), // e.g., '08:00' from '08:00:00'
                    })) || [])
                ];
                // console.log("formattedTimeSlots-----------ffffffffffffffff---------",formattedTimeSlots);

                if (!selectedWholesaler && !data.is_wholesaler) {
                    setData({
                        ...data,
                        season_id: season.id,
                        override_timeslots: season?.inventory?.allow_internal_override == 1 ? true : false || false,
                        override_price: season?.allow_internal_price_override == 1 ? true : false || false,
                        override_rates: season?.allow_internal_price_override == 1 ? true : false || false,
                        adult: season?.rake_rate_adult || 0,
                        child: season?.rake_rate_child || 0,
                        guide: season?.rake_rate_guide || 0,
                        base_per_adult: season?.rake_rate_adult || 0,
                        base_per_child: season?.rake_rate_child || 0,
                        base_per_guide: season?.rake_rate_guide || 0,
                        rate_per_adult: season?.rake_rate_adult || 0,
                        rate_per_child: season?.rake_rate_child || 0,
                        rate_per_guide: season?.rake_rate_guide || 0,
                    });
                } else {
                    setData({
                        ...data,
                        season_id: season.id,
                        override_timeslots: season?.inventory?.allow_internal_override || false,
                    });
                }
                console.log('-------------------------data-------------------------');
                setSelectedSeason(season);
                setActivityId(season.id);
                setActivityDate(selectedDate);
                setTimeslots(formattedTimeSlots);
                console.log('-------------------------data-------------------------', data);
            } else {
                setTimeslots([]); // fallback
                setSelectedSeason(null);
            }
        }

    }, [data.booking_date]);

    const handleCheveronDateChange = (direction) => {
        let newDate;

        if (view_mode === 'day') {
            newDate = addDays(activityDate, direction);
        } else if (view_mode === 'week') {
            newDate = addWeeks(activityDate, direction);
        } else if (view_mode === 'month') {
            newDate = addMonths(activityDate, direction);
        }

        setActivityDate(newDate);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('admin.bookings.store'));
    };

    const addDiscountGroup = () => {
        if (!discountId || !discountPax) return;

        const countToMove = parseInt(discountPax);
        const selectedDiscount = parseInt(discountId);

        setPaxGroups(prevGroups => {
            let updated = [...prevGroups];

            // Find the first group of adults WITHOUT a discount (the pool to split from)
            const idx = updated.findIndex(g => g.type === "adult" && g.discount_id === null);

            if (idx === -1) {
                // No base adult group to split from
                return updated;
            }
            const baseRate = data.rate_per_adult;
            const finalRate = calculateDiscountedRate(baseRate, selectedDiscount);
            const baseGroup = updated[idx];

            if (baseGroup.count <= countToMove) {
                // All base adults are moved to discounted group
                updated[idx] = {
                    type: "adult",
                    count: countToMove,
                    discount_id: selectedDiscount,
                    base_rate: baseRate,
                    final_rate: finalRate,
                };
            } else {
                // Split: reduce base group, add new discounted group
                updated[idx].count -= countToMove;
                updated.push({
                    type: "adult",
                    count: countToMove,
                    discount_id: selectedDiscount,
                    base_rate: baseRate,
                    final_rate: finalRate,
                });
            }
            setData({
                ...data,
                discount_lines: updated
            });
            console.log('updated', updated);
            console.log('data', data);
            return updated;
        });

        // reset UI inputs
        setDiscount(true);
        setShowPromo(false);
        setPromoList([]);
        setDiscountId(null);
        setDiscountPax(0);
    };

    function calculateDiscountedRate(baseRate, discountId) {
        if (!discountId) return baseRate;
        const discount = discountList.find(d => d.id === discountId);
        return baseRate * (1 - discount.percent_off / 100);
    }

    const totalAdult = paxGroups.reduce((sum, g) => {
        if (g.type === "adult") {
            const baseRate = data.rate_per_adult;
            const rate = calculateDiscountedRate(baseRate, g.discount_id);
            //setData("adult", sum + (rate * g.count));
            return sum + (rate * g.count);   // ✅ add to sum
        }
        return sum; // ✅ always return sum if not adult
    }, 0);
    const totalChild = paxGroups.reduce((sum, g) => {
        if (g.type === "child") {
            const baseRate = data.rate_per_child;
            const rate = calculateDiscountedRate(baseRate, g.discount_id);
            //setData("child", sum + (rate * g.count));
            return sum + (rate * g.count);   // ✅ add to sum
        }
        return sum; // ✅ always return sum if not adult
    }, 0);
    const totalGuide = paxGroups.reduce((sum, g) => {
        if (g.type === "guide") {
            const baseRate = data.rate_per_guide;
            const rate = calculateDiscountedRate(baseRate, g.discount_id);
            //setData("guide", sum + (rate * g.count));
            return sum + (rate * g.count);   // ✅ add to sum
        }
        return sum; // ✅ always return sum if not adult
    }, 0);
    const total = paxGroups.reduce((sum, g) => {
        const baseRate = g.type === "adult" ? data.rate_per_adult
            : g.type === "child" ? data.rate_per_child
                : data.rate_per_guide;
        const rate = calculateDiscountedRate(baseRate, g.discount_id);
        //setData("booking_total", sum + (rate * g.count));
        return sum + (rate * g.count);
    }, 0);

    useEffect(() => {
        setData("adult", totalAdult);
        setData("child", totalChild);
        setData("guide", totalGuide);
        setData("booking_total", (total * lookups.find(l => l.slug == 'vat_rate')?.multiplier));
    }, [total]);

    // find currently selected discount from the list
    const selectedDiscount = discountList?.find(d => d.id === parseInt(discountId));
    const selectedPromo = promoList?.find(d => d.id === parseInt(promotionId));
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Bookings</h2>}
        >
            <Head title="Booking" />
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

                                onChange={(e) => { setData(e.target.name, e.target.value); setSelectedWholesaler(e.target.value); }}
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
                            {/* <div className="flex w-full items-center justify-start space-x-4 ">
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
                                </div> */}

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
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <CustomSelect
                            label="Service"
                            name="service_id"
                            value={data.service_id}

                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            error={errors.service_id}
                            options={services}
                        />
                        {/* <CustomSelect
                            label="Season"
                            name="season_id"
                            value={data.season_id}
                            
                            onChange={(e) => {handleChange(e.target.name, e.target.value); setActivityId(e.target.value)}}
                            error={errors.season_id}
                            options={seasons}
                        />  */}
                        <CustomInputText
                            title="Booking Date"
                            name="booking_date"
                            type='date'
                            value={data.booking_date}

                            onChange={(e) => { handleChange(e.target.name, e.target.value); setSelectedDate(e.target.value); }}
                            errors={errors}
                        />
                        <CustomSelect
                            label="Start Time"
                            name="start_time"
                            value={data.start_time}

                            onChange={(e) => {
                                setData('start_time', e.target.value);
                                setSelectedTime(e.target.value);
                                if (e.target.value == 0) {
                                    setSelectedSlot(null);
                                    setData('selected_slot', []);
                                    //setSelectedDate(cell.date);                                    
                                } else {
                                    console.log('--------date--------', activityDate.toLocaleDateString());
                                    console.log('--------scheduleData.days--------', scheduleData.days);
                                    const selectedDay = new Date(activityDate).getDate(); // 11
                                    const day = scheduleData.days.find(d =>
                                        (typeof d === "string" ? d : d.day)?.endsWith(selectedDay.toString().padStart(2, "0"))
                                    );

                                    const slot = timeslots.find(s => s.id === Number(e.target.value));
                                    console.log("value:", e.target.value); // "Thu 11"
                                    console.log("timeslots:", timeslots); // "Thu 11"
                                    console.log("day:", day); // "Thu 11"
                                    console.log("slot:", slot); // "Thu 11"
                                    setSelectedSlot({ activityDate: data?.booking_date, day: day, time: slot.name });
                                    setData('selected_slot', { activityDate: data?.booking_date, day: day, time: slot.name });
                                    console.log("SelectedSlot--------------------:", selectedSlot);
                                    console.log("sDatalot:-----------------------", data);
                                }
                            }}
                            error={errors.start_time}
                            options={timeslots}
                        />
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {/* Time slots */}
                        {/* <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <input
                                type="date"
                                value={data.booking_date}
                                onChange={(e) => {setData('booking_date', e.target.value); setActivityDate(new Date(e.target.value))}}
                                className="border rounded p-2"
                            /> */}



                    </div>

                    {/* Counts 
                                      <Input placeholder="Number of Adults" value={adults} onChange={(e) => setAdults(e.target.value)} />
              <Input placeholder="Number of Children" value={children} onChange={(e) => setChildren(e.target.value)} />
              <Calendar selected={activityDate} onSelect={handleDateChange} />
              {availableTimes.length > 0 && (
                  <Select onValueChange={(value) => setSelectedSlot({ date: activityDate.toISOString().split('T')[0], time: value })}>
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
                            onChange={(e) => { let val = e.target.value > 0 ? e.target.value : e.target.value * -1; setAdults(val) }}
                            errors={errors}
                        />
                        <CustomInputText
                            title="Number of Children"
                            id="children"
                            name="children"
                            type='number'
                            min="0"
                            value={data.children}
                            onChange={(e) => { let val = e.target.value > 0 ? e.target.value : e.target.value * -1; setChildren(val) }}
                            errors={errors}
                        />
                        <CustomInputText
                            title="Number of Guides"
                            id="guides"
                            name="guides"
                            type='number'
                            min="0"
                            value={data.guides}

                            onChange={(e) => { let val = e.target.value > 0 ? e.target.value : e.target.value * -1; handleChange(e.target.name, val); setGuides(val) }}
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
                                    {/* <Calendar selected={activityDate} onSelect={handleDateChange} />       */}
                                    <span className="text-black">
                                        {icons.calendar}
                                    </span>
                                    <span>{activityDate.toLocaleDateString()}</span>
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
                                                    // console.log('row', row);
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
                                                                                // console.log('cell.date', cell.date);
                                                                                // console.log('day', day);
                                                                                // console.log('row.time', row.time);
                                                                                // console.log('-------timeslots----------', timeslots);

                                                                                setSelectedDate(cell.date);
                                                                                const time = row.time.slice(0, 5);
                                                                                const slot = timeslots.find(s => s.name === time);
                                                                                setData('start_time', slot.id);
                                                                                setSelectedTime(slot.id);
                                                                                console.log("SelectedSlot--------------------:", selectedSlot);
                                                                                console.log("sDatalot:-----------------------", data);
                                                                            }
                                                                        }}
                                                                    >
                                                                        <div className="w-[133px] h-full flex items-center justify-center">
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
                                                    )
                                                })}
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
                    {/* Rate cards */}
                    <div className="mt-10 p-6 bg-white space-y-4">
                        {/* Top-level toggles */}
                        <div className="flex flex-wrap gap-4 items-center justify-between w-full ">
                            <div className="flex flex-wrap gap-4 items-center justify-start ">
                                <h2 className="text-booking-subheading">Rates</h2>
                            </div>
                            {/* {pricing && (
                        <div className="mt-4 p-4 border rounded bg-gray-50">
                            <h3 className="font-bold">Pricing</h3>
                            <p>Adult: {pricing.applied_rates.adult}</p>
                            <p>Child: {pricing.applied_rates.child}</p>
                            <p>Guide: {pricing.applied_rates.guide}</p>
                            <p className="font-semibold">Total: {pricing.total_price}</p>
                            {pricing.applied_promotion && <p>Promotion Applied: {pricing.applied_promotion.name}</p>}
                            {pricing.applied_discount && <p>Discount Applied: {pricing.applied_discount.name}</p>}
                        </div>
                        )} */}
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

                                    onClick={() => { alert('todo add manifest modal') }}
                                />
                                <div onClick={() => setToggleRate(!toggleRate)}>
                                    {toggleRate ? icons.cheveron_down : icons.cheveron_up}
                                </div>
                            </div>
                        </div>
                        {/* Rates */}
                        <div className={`${toggleRate ? 'block' : 'hidden'} mt-10 p-6 bg-white rounded-[10px] border border-secondary-100 shadow space-y-4`}>

                            <div className="flex items-center gap-[66px] mt-4">
                                <div className="flex w-[50%] items-center justify-start space-x-4 ">
                                    <CustomCheckbox
                                        label="Override Rates"
                                        name="override_rates"
                                        checked={data.override_rates || false}

                                        onChange={handleChange}
                                        errors={errors}
                                    />
                                    <div className="flex items-center w-full  gap-4">
                                        <CustomInputText
                                            title="Reason for Override"
                                            id="override_reason"
                                            name="override_reason"
                                            type='text'
                                            width='w-[100%]'
                                            required
                                            value={data.override_reason || ''}

                                            onChange={(e) => setData('override_reason', e.target.value)}
                                            errors={errors}
                                        />
                                    </div>
                                </div>
                                <div className="flex w-[50%] items-center justify-start ">
                                    {showDiscount && (
                                        <div className="flex flex-none items-center gap-2">
                                            <CustomSelect
                                                label="Discount"
                                                name="discountId"
                                                value={discountId}
                                                width={"w-full"}
                                                onChange={(e) => { setDiscountId(e.target.value); }}
                                                error={errors.discountId}
                                                options={discountList}
                                            />
                                            {selectedDiscount?.applies_to === 'line_item' && (
                                                <CustomInputText
                                                    title="Discount PAX"
                                                    id="discountPax"
                                                    name="discountPax"
                                                    type="number"
                                                    min="0"
                                                    width="w-auto"
                                                    value={discountPax}
                                                    onChange={(e) => setDiscountPax(e.target.value)}
                                                    errors={errors}
                                                />
                                            )}
                                            <div className="mt-3">
                                                <BlueButton
                                                    title="Apply"
                                                    width='w-[100px]'
                                                    onClick={addDiscountGroup}
                                                    disabled={processing}
                                                    type="button" />
                                            </div>
                                        </div>)}
                                </div>
                                <div className="flex w-[50%] items-center justify-start ">
                                    {showPromo && (
                                        <div className="flex flex-none items-center gap-2">
                                            {/* <CustomSelect
                                                label="Promotion"
                                                name="PromotionId"
                                                value={promotionId}
                                                width={"w-full"}
                                                
                                                onChange={(e) => {setPromotionId(e.target.value);}}
                                                error={errors.PromotionId}
                                                options={promoList}
                                            />
                                            {selectedPromo && (
                                                <span className='flex flex-col w-full items-start justify-start space-y-2 text-sm text-gray-500'>
                                                <span className='text-sm text-gray-500'>Promotion Code:</span>
                                                <span className='text-sm text-gray-500'>{selectedPromo.promo_code}</span>
                                                </span>
                                            )}
                                            
                                            <BlueButton
                                            title="Apply" 
                                            width='w-[100px]'
                                            onClick={()=>{}}
                                            disabled={processing} 
                                            type="button" />    */}
                                            <CustomInputText
                                                title="Promo Code"
                                                name="promo_code"
                                                value={promoCode}
                                                onChange={(e) => setPromoCode(e.target.value)}
                                            />
                                            <div className="flex items-center gap-2 mt-3">
                                                <BlueButton
                                                    title="Apply"
                                                    disabled={processing}
                                                    onClick={handleCalculate}
                                                />
                                                {/* <OutlineButton
                                            title="Promotions"
                                            onClick={() => setPromoModalOpen(true)}
                                            /> */}
                                                <div onClick={() => setPromoModalOpen(true)} className="flex items-center justify-center w-[36px] h-[32px] gap-2 bg-[#2D99FE] rounded-full p-2">
                                                    {icons.info}
                                                </div>
                                            </div>



                                        </div>)}
                                </div>
                                {/* {paxGroups?.map((g, i) => (
                                    <div key={i}>
                                        {g.count} {g.type}(s) with {discountList.find(d => d.id === g.discount_id)?.name}
                                    </div>
                                    ))} */}
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                                {/* Adult */}
                                <div className="p-4 bg-white rounded-[10px] border shadow">
                                    {/* Header */}
                                    <div className="flex items-center gap-4 mb-4">
                                        <div className="flex items-center justify-center w-[36px] h-[32px] gap-2 bg-[#2D99FE] rounded-full p-2">
                                            {icons.adult}
                                        </div>
                                        <div className="flex flex-col">
                                            <span className="text-booking-card-title">Adult</span>
                                            <div className="flex items-center justify-start w-[417px] h-[43px] gap-2">
                                                <span className="text-booking-card-title-sub">
                                                    Rack Rate:
                                                </span>
                                                <span>
                                                    <input
                                                        type="number"
                                                        id={'rate_per_adult'}
                                                        name={'rate_per_adult'}
                                                        disabled={!data.override_rates}
                                                        value={data['rate_per_adult'] || ''}
                                                        onChange={(e) => setData('rate_per_adult', e.target.value)}
                                                        className={`mt-1 block w-full h-[24px] ${errors.rate_per_adult ? "border-red-500" : "border-gray-300"} shadow-sm text-booking-card-body rounded-xl
                                                    hover:border-black  active:border-blue-600 
                                                    focus:outline-none focus:ring-0 focus:border-black
                                                    `}
                                                        required
                                                    />
                                                    {errors.rate_per_adult && (
                                                        <p className="text-red-500 text-sm mt-2">
                                                            {errors.rate_per_adult}
                                                        </p>
                                                    )}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Line items */}
                                    <div className="space-y-2">
                                        {paxGroups
                                            .filter((g) => g.type === "adult")
                                            .map((group, i) => {
                                                const baseAdultRate = data?.rate_per_adult || 0;
                                                const rate = calculateDiscountedRate(baseAdultRate, group.discount_id);
                                                const amount = group.count * rate;

                                                return (
                                                    <div key={i} className="mb-4 flex items-center justify-between gap-2">
                                                        <label
                                                            htmlFor='override_reason'
                                                            className="text-booking-card-lbl w-[128px] h-[34px] mt-2"
                                                        >
                                                            <span>
                                                                {group.discount_id
                                                                    ? discountList.find((d) => d.id === group.discount_id)?.name
                                                                    : "Adult"} {group.count}
                                                            </span>
                                                        </label>
                                                        <input
                                                            type="text"
                                                            disabled='disabled'
                                                            value={amount?.toFixed(2)}
                                                            className={`mt-1 block w-full h-[34px] border-gray-300 shadow-sm text-booking-card-body rounded-xl
                                                            hover:border-black  active:border-blue-600 
                                                            focus:outline-none focus:ring-0 focus:border-black
                                                            `}
                                                        />
                                                    </div>
                                                );
                                            })}
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
                                            value={totalAdult?.toFixed(2)}
                                            className={`mt-1 block w-full h-[34px] border-gray-300 shadow-sm input-text rounded-xl
                                                hover:border-black  active:border-blue-600 
                                                focus:outline-none focus:ring-0 focus:border-black
                                                `}
                                        />
                                    </div>

                                </div>
                                {/* Child */}
                                <div className="p-4 bg-white rounded-[10px] border shadow">
                                    {/* Header */}
                                    <div className="flex items-center gap-4 mb-4">
                                        <div className="flex items-center justify-center w-[36px] h-[32px] gap-2 bg-[#A7D3FD] rounded-full p-2">
                                            {icons.child}
                                        </div>
                                        <div className="flex flex-col">
                                            <span className="text-booking-card-title">Child</span>
                                            <div className="flex items-center justify-start w-[417px] h-[43px] gap-2">
                                                <span className="text-booking-card-title-sub">
                                                    Rack Rate:
                                                </span>
                                                <span>
                                                    <input
                                                        type="number"
                                                        id={'rate_per_child'}
                                                        name={'rate_per_child'}
                                                        disabled={!data.override_rates}
                                                        value={data['rate_per_child'] || ''}
                                                        onChange={(e) => setData('rate_per_child', e.target.value)}
                                                        className={`mt-1 block w-full h-[24px] ${errors.rate_per_child ? "border-red-500" : "border-gray-300"} shadow-sm text-booking-card-body rounded-xl
                                                    hover:border-black  active:border-blue-600 
                                                    focus:outline-none focus:ring-0 focus:border-black
                                                    `}
                                                        required
                                                    />
                                                    {errors.rate_per_child && (
                                                        <p className="text-red-500 text-sm mt-2">
                                                            {errors.rate_per_child}
                                                        </p>
                                                    )}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Line items */}
                                    <div className="space-y-2">
                                        {paxGroups
                                            .filter((g) => g.type === "child")
                                            .map((group, i) => {
                                                const baseChildRate = data?.rate_per_child || 0;
                                                const rate = calculateDiscountedRate(baseChildRate, group.discount_id);
                                                const amount = group.count * rate;

                                                return (
                                                    <div key={i} className="mb-4 flex items-center justify-between gap-2">
                                                        <label
                                                            htmlFor='override_reason'
                                                            className="text-booking-card-lbl w-[128px] h-[34px] mt-2"
                                                        >
                                                            <span>
                                                                {group.discount_id
                                                                    ? discountList.find((d) => d.id === group.discount_id)?.name
                                                                    : "Child"} {group.count}
                                                            </span>
                                                        </label>
                                                        <input
                                                            type="text"
                                                            disabled='disabled'
                                                            value={amount?.toFixed(2)}
                                                            className={`mt-1 block w-full h-[34px] border-gray-300 shadow-sm text-booking-card-body rounded-xl
                                                            hover:border-black  active:border-blue-600 
                                                            focus:outline-none focus:ring-0 focus:border-black
                                                            `}
                                                        />
                                                    </div>
                                                );
                                            })}
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
                                            value={totalChild?.toFixed(2)}
                                            className={`mt-1 block w-full h-[34px] border-gray-300 shadow-sm input-text rounded-xl
                                                hover:border-black  active:border-blue-600 
                                                focus:outline-none focus:ring-0 focus:border-black
                                                `}
                                        />
                                    </div>

                                </div>
                                {/* Guide */}
                                <div className="p-4 bg-white rounded-[10px] border shadow">
                                    {/* Header */}
                                    <div className="flex items-center gap-4 mb-4">
                                        <div className="flex items-center justify-center w-[36px] h-[32px] gap-2 bg-[#D6D6D6] rounded-full p-2">
                                            {icons.guide}
                                        </div>
                                        <div className="flex flex-col">
                                            <span className="text-booking-card-title">Guide</span>
                                            <div className="flex items-center justify-start w-[417px] h-[43px] gap-2">
                                                <span className="text-booking-card-title-sub">
                                                    Rack Rate:
                                                </span>
                                                <span>
                                                    <input
                                                        type="number"
                                                        id={'rate_per_guide'}
                                                        name={'rate_per_guide'}
                                                        disabled={!data.override_rates}
                                                        value={data['rate_per_guide'] || ''}
                                                        onChange={(e) => setData('rate_per_guide', e.target.value)}
                                                        className={`mt-1 block w-full h-[24px] ${errors.rate_per_guide ? "border-red-500" : "border-gray-300"} shadow-sm text-booking-card-body rounded-xl
                                                    hover:border-black  active:border-blue-600 
                                                    focus:outline-none focus:ring-0 focus:border-black
                                                    `}
                                                        required
                                                    />
                                                    {errors.rate_per_guide && (
                                                        <p className="text-red-500 text-sm mt-2">
                                                            {errors.rate_per_guide}
                                                        </p>
                                                    )}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Line items */}
                                    <div className="space-y-2">
                                        {paxGroups
                                            .filter((g) => g.type === "guide")
                                            .map((group, i) => {
                                                const baseGuideRate = data?.rate_per_guide || 0;
                                                const rate = calculateDiscountedRate(baseGuideRate, group.discount_id);
                                                const amount = group.count * rate;

                                                return (
                                                    <div key={i} className="mb-4 flex items-center justify-between gap-2">
                                                        <label
                                                            htmlFor='override_reason'
                                                            className="text-booking-card-lbl w-[128px] h-[34px] mt-2"
                                                        >
                                                            <span>
                                                                {group.discount_id
                                                                    ? discountList.find((d) => d.id === group.discount_id)?.name
                                                                    : "Guide"} {group.count}
                                                            </span>
                                                        </label>
                                                        <input
                                                            type="text"
                                                            disabled='disabled'
                                                            value={amount?.toFixed(2)}
                                                            className={`mt-1 block w-full h-[34px] border-gray-300 shadow-sm text-booking-card-body rounded-xl
                                                            hover:border-black  active:border-blue-600 
                                                            focus:outline-none focus:ring-0 focus:border-black
                                                            `}
                                                        />
                                                    </div>
                                                );
                                            })}
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
                                            value={totalGuide?.toFixed(2)}
                                            className={`mt-1 block w-full h-[34px] border-gray-300 shadow-sm input-text rounded-xl
                                                hover:border-black  active:border-blue-600 
                                                focus:outline-none focus:ring-0 focus:border-black
                                                `}
                                        />
                                    </div>

                                </div>
                            </div>
                            <div className="flex items-center space-x-4">
                                <label className="text-booking-lbl-main">Booking Total</label>
                                <div className="flex items-center justify-between w-[417px] h-[43px] bg-[#DBECFD4D] pl-[7px] pr-[7px] py-[7px] rounded-[6px]">
                                    <span className="text-booking-lbl-main-sub">R {(total * lookups.find(l => l.slug == 'vat_rate')?.multiplier).toFixed(2) || '0.00'}</span>
                                    <WhiteButton
                                        title="Recalucate from Rack"
                                        icon={true}
                                        type="button"
                                        iconName={icons.spanners}
                                        textColor='text-[#4D81E7]'
                                        width="w-[189px]"
                                        onClick={() => { }}
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="mt-10 p-6 bg-white rounded-[10px] border border-secondary-100 shadow space-y-4 ">

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
                                    <span className="text-booking-lbl-main w-[129px]">Promotion Rate</span>
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

                                onChange={(e) => { setData('referral_source', e.target.value); }}
                                error={errors.referral_source}
                                options={type_affiliates}
                            />
                        </div>

                    </div>


                    {/* Booking Payment */}
                    <div className="mt-10 bg-white w-full ml-[20%] lg:ml-[25%] ">
                        <div className="flex flex-col items-center justify-center px-6 py-[65px] w-[60%] lg:w-[50%] rounded-[10px] border border-secondary-100 shadow ">
                            <div className="flex w-full items-center justify-start space-x-4 ">
                                <div className="flex basis-[80%] items-center justify-start space-x-4 ">
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
                                            onClick={() => { }}
                                            disabled={processing}
                                            type="button" />

                                        <WhiteButton
                                            title="Remove"
                                            type="button"
                                            textColor='text-[#7C7C7C]'
                                            width="w-[108px]"
                                            className="mt-4"
                                            disabled={processing}
                                            onClick={() => { }}
                                        />
                                    </div>
                                </div>
                            </div>


                            <div className="flex flex-col items-center justify-center space-y-4 mb-7">
                                <div className="flex items-center space-x-[37px]">
                                    <span className="text-booking-lbl-main w-[160px] font-normal text-right">Total value of bookings</span>
                                    <div className="flex items-center justify-between w-[417px] h-[43px] bg-[#DBECFD4D] pl-[17px] py-[12.5px] rounded-[6px]">
                                        <span className="text-booking-lbl-main-sub text-[#4D81E7]">R {Number(data?.booking_total || 0).toFixed(2)}</span>
                                    </div>
                                </div>
                                <div className="flex items-center space-x-[37px]">
                                    <span className="text-booking-lbl-main w-[160px] font-normal text-right">Deposit Due Today</span>
                                    <div className="flex items-center justify-between w-[417px] h-[43px] bg-[#DBECFD4D] pl-[17px] py-[12.5px] rounded-[6px]">
                                        <span className="text-booking-lbl-main-sub text-[#AFAFAF]">R {((Number(data?.deposit_due) || 0) * (Number(data?.booking_total) || 0)).toFixed(2)}</span>
                                    </div>
                                </div>
                                <div className="flex items-center space-x-[37px]">
                                    <span className="text-booking-lbl-main w-[160px] text-right">Total Due Today</span>
                                    <div className="flex items-center justify-between w-[417px] h-[43px] bg-[#DBECFD4D] pl-[17px] py-[12.5px] rounded-[6px]">
                                        <span className="text-booking-lbl-main-sub text-[#FF0000]">R {((Number(data?.due_today) || 0) * (Number(data?.booking_total) || 0)).toFixed(2)}</span>
                                    </div>
                                </div>
                                <div className="flex items-center space-x-[37px]">
                                    <span className="text-booking-lbl-main w-[160px] text-right text-[#4D81E7]">Total Rack Value</span>
                                    <div className="flex items-center justify-between w-[417px] h-[43px] bg-[#DBECFD4D] pl-[17px] py-[12.5px] rounded-[6px]">
                                        <span className="text-booking-lbl-main-sub text-[#4D81E7]">R {((Number(data?.rack_value) || 0) * (Number(data?.booking_total) || 0)).toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>

                            {data.bill_back && (<span className="text-booking-lbl-main w-full text-center font-normal my-7">
                                Bill Back - The Wholesaler will be invoiced for R{data?.rack_value * data?.booking_total} after the service is delivered.
                            </span>)}
                            <BlueButton
                                title="Submit Booking"
                                width={"w-[169px]"}
                                type="button"
                                onClick={(e) => handleSubmit(e)}
                                disabled={processing}
                            />
                            {/* <button
  type="button"
  className="btn-secondary"
  onClick={() => {
    post(route('admin.bookings.preview-invoice'), data, {
      onSuccess: (res) => {
        // open modal with res.props.html
      }
    })
  }}
>
  Preview Invoice
</button> */}


                        </div>

                    </div>
                </div>
            </div>
            <PromoModal
                isOpen={promoModalOpen}
                serviceId={selectedService?.id}
                bookingDate={data.booking_date}
                onClose={handlePromoModalClose}
                onSelect={handlePromoModalSelect}
            />

        </AuthenticatedLayout>
    );
}
