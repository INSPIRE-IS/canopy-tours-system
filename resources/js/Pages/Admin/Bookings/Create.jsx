
import CustomerToolsModal from '@/Components/CustomerToolsModal';
import Header from '@/Components/Header';
import Calendar from '@/Components/ui/calendar';
import Button from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import React, { useEffect, useMemo, useRef, useState } from 'react';
import { format, addDays, addWeeks, addMonths, set } from 'date-fns';
import CustomSelect from '@/Components/CustomSelect';
import InputText from '@/Components/InputText';
import CustomInputText from '@/Components/CustomInputText';
import CustomCheckbox from '@/Components/CustomCheckbox';
import Modal from '@/Components/Modal';
import { icons } from '../../../icons';
import WhiteButton from '@/Components/WhiteButton';
import BlueButton from '@/Components/BlueButton';
import CustomTextarea from '@/Components/CustomTextarea';
import OutlineButton from '@/Components/OutlineButton';
import PromoModal from './PromoModal';
import { useToast } from '@/Context/ToastContext';
import PhoneInput from '@/Components/PhoneInput';

const PRICING_MODES = {
    STANDARD: 'STANDARD',
    DISCOUNT: 'DISCOUNT',
    PROMO: 'PROMO',
    WHOLESALER: 'WHOLESALER',
    OVERRIDE: 'OVERRIDE',
};

export default function Dashboard({ auth, wholesalers = [], services = [], schedule, start_date, view_mode, days, type_affiliates, vouchers, discounts, wholesaler_rates, promos, lookups, countries = [], settings = {} }) {
    //seasons = [],, adults, children, guides
    console.log('---------------auth----create------------ ', auth);
    console.log('---------------auth----vouchers------------ ', vouchers);
    console.log('---------------auth----discounts------------ ', discounts);
    console.log('---------------auth----wholesaler_rates------------ ', wholesaler_rates);
    console.log('---------------auth----promos------------ ', promos);
    const page = usePage();
    const { props } = usePage();
    const { flash } = props;
    const { showToast } = useToast();
    const [pricingMode, setPricingMode] = useState(PRICING_MODES.STANDARD);
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
    const [slotOverrideOpen, setSlotOverrideOpen] = useState(false);
    const [slotOverrideCommitted, setSlotOverrideCommitted] = useState(false);
    const [slotOverrideSaving, setSlotOverrideSaving] = useState(false);
    const [slotOverrideData, setSlotOverrideData] = useState({
        time: '',
        capacity_override: '',
        note: '',
        internal_only: true,
    });
    const [slotOverrideErrors, setSlotOverrideErrors] = useState({});

    const [selectedService, setSelectedService] = useState(null);
    const [selectedSeason, setSelectedSeason] = useState(null);
    const [activityDate, setActivityDate] = useState(() => new Date(start_date));
    const [selectedSlot, setSelectedSlot] = useState(null); // stores {date: string, day: string, time: string }
    const [showWholesaler, setShowWholesaler] = useState(false);
    const [wholesalerApplied, setWholesalerApplied] = useState(false);
    const [wholesalerRateList, setWholesalerRateList] = useState([]);

    const [discountList, setDiscountList] = useState(null);
    const [showDiscount, setShowDiscount] = useState(false);
    const [discountId, setDiscountId] = useState(null);
    const [discountAdultPax, setDiscountAdultPax] = useState('');
    const [discountChildPax, setDiscountChildPax] = useState('');
    const [paxGroups, setPaxGroups] = useState([]);

    const [showPromo, setShowPromo] = useState(false);
    const [promoList, setPromoList] = useState([]);
    const [promotionId, setPromotionId] = useState(null);
    const [promoCode, setPromoCode] = useState(null);
    const [promoModalOpen, setPromoModalOpen] = useState(false);

    const [pricing, setPricing] = useState(null);
    const [activityItems, setActivityItems] = useState([]);
    const lastSlotWarningRef = useRef('');
    const lastCapacityWarningRef = useRef('');
    const lastPricingKeyRef = useRef('');
    const canOverrideCapacity = !!selectedService?.allow_internal_capacity_override;
    const canOverrideRates = !!selectedSeason?.allow_internal_price_override;
    const canOverrideTimeSlots = !!selectedSeason?.inventory?.allow_internal_override;
    const prefillsRef = useRef(null);
    const prevServiceIdRef = useRef(null);
    const pickupEnabled = settings?.enable_pickup_dropoff ?? true;
    const InfoHint = ({ text }) => (
        <span
            title={text}
            aria-label={text}
            className="inline-flex h-4 w-4 items-center justify-center rounded-full border border-gray-400 text-[10px] font-semibold leading-none text-gray-500 hover:border-gray-700 hover:text-gray-800"
        >
            i
        </span>
    );
    const noteHelp = {
        invoice: 'Client-facing note that appears on the invoice/quote PDF.',
        email: 'Extra wording for the email sent to the guest. This is not an automated scheduled reminder.',
        booking: 'Internal operational note for staff, such as tea/coffee/sandwich details. Not used for Franchise Fees.',
        override: 'Manual rate override reason. This is the pricing note that can be used for reporting, including Franchise Fees.',
    };

    // console.log('schedule ',schedule);
    const { data, setData, post, processing, errors, transform } = useForm({
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
        adults: 0,
        children: 0,
        guides: 0,
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
        wholesaler_rate_id: '',
        invoice_note: '',
        email_reminder: '',
        booking_note: '',
        // booking details
        corporate_customer: false,
        send_sms: false,
        send_invoice: false,
        send_quote: false,
        is_lead: true,
        lead_person: '',
        lead_email: '',
        lead_mobile: '',
        nationality_id: '',
        referral_source: '',
        // booking payment
        booking_total: 0,
        is_prepaid_wholesaler_voucher: false,
        invoice_discount_id: null,
        deposit_due: lookups.find(l => l.slug == 'deposit_due')?.multiplier || 1,
        due_today: lookups.find(l => l.slug == 'due_today')?.multiplier || 1,
        rack_value: 1,
        bill_back: false,
        team_id: auth.user.team_id,
        pay_later: false,
    });
    useEffect(() => {
        if (flash?.success) {
            showToast(flash.success, "success");
        }
        if (flash?.error) {
            showToast(flash.error, "error");
        }
    }, [flash, showToast]);

    useEffect(() => {
        loadCalendar();
    }, [activityId, activityDate, view_mode, data.start_time, data.adults, data.children]);

    useEffect(() => {
        if (prefillsRef.current) return;
        const params = new URLSearchParams(window.location.search);
        const serviceId = params.get('service_id');
        const bookingDate = params.get('booking_date');
        const startTime = params.get('start_time');

        prefillsRef.current = { serviceId, bookingDate, startTime };

        if (serviceId) {
            setData('service_id', serviceId);
        }
        if (bookingDate) {
            setData('booking_date', bookingDate);
        }
        if (startTime) {
            setData('start_time', startTime);
        }
    }, []);

    useEffect(() => {
        if (!prefillsRef.current) return;
        if (data.service_id) return;
        const { serviceId } = prefillsRef.current;
        if (!serviceId) return;
        if ((services || []).some(s => String(s.id) === String(serviceId))) {
            setData('service_id', serviceId);
        }
    }, [services, data.service_id]);

    useEffect(() => {
        if (!prefillsRef.current) return;
        if (data.start_time) return;
        if (!timeslots?.length) return;
        const { startTime } = prefillsRef.current;
        if (!startTime) return;
        if (timeslots.some(s => String(s.id) === String(startTime))) {
            setData('start_time', startTime);
        }
    }, [timeslots, data.start_time]);


    const loadCalendar = async () => {
        // console.log('-----------------loadCalendar-------------');
        console.log('----------------- loadCalendar line 159------------- ', data);
        if (activityId) {
            setLoading(true);
            try {
                const response = await axios.get(route('admin.booking.activity.calendar'), {
                    params: {
                        activity_id: activityId,
                        view_mode: view_mode,
                        start_date: format(activityDate, 'yyyy-MM-dd'),
                        start_time: data.start_time,
                        adults: data.adults || 0,
                        children: data.children || 0
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

    const serviceWholesalers = selectedService
        ? Array.from(
            new Map(
                (wholesaler_rates || [])
                    .filter(r => Number(r.service_id) === Number(selectedService.id))
                    .map(r => {
                        const w = r.wholesaler || wholesalers.find(x => x.id === r.wholesaler_id);
                        return [w?.id, w];
                    })
            ).values()
        ).filter(Boolean)
        : (wholesalers || []);
    const isRateValidForDate = (rate, date) => {
        if (!rate) return false;
        if (rate.is_active === false || rate.is_active === 0) return false;
        if (!date) return true;
        const from = rate.effective_from ? String(rate.effective_from).slice(0, 10) : null;
        const to = rate.effective_to ? String(rate.effective_to).slice(0, 10) : null;
        return (!from || from <= date) && (!to || to >= date);
    };
    const rateLabel = (rate) => {
        const dateLabel = `${rate.effective_from ? String(rate.effective_from).slice(0, 10) : 'Any start'} - ${rate.effective_to ? String(rate.effective_to).slice(0, 10) : 'Any end'}`;
        return `${rate.name || rate.season?.name || 'Wholesaler rate'} (${dateLabel})`;
    };
    const selectedWholesalerRates = useMemo(() => (wholesaler_rates || []).filter(rate =>
        Number(rate.wholesaler_id) === Number(data.wholesaler_id)
        && Number(rate.season_id) === Number(selectedSeason?.id || data.season_id)
        && Number(rate.service_id) === Number(selectedService?.id || data.service_id)
        && isRateValidForDate(rate, data.booking_date)
    ), [wholesaler_rates, data.wholesaler_id, data.season_id, data.service_id, data.booking_date, selectedSeason?.id, selectedService?.id]);
    const selectedWholesalerRate = selectedWholesalerRates.find(rate => Number(rate.id) === Number(data.wholesaler_rate_id)) || selectedWholesalerRates[0] || null;
    const isPrepaidWholesalerVoucher = Boolean(data.is_wholesaler && (data.is_prepaid_wholesaler_voucher || (wholesalerApplied && selectedWholesalerRate?.is_prepaid_voucher)));
    const hasWholesalers = Array.isArray(serviceWholesalers) && serviceWholesalers.length > 0;
    const hasPromo = !!promoCode;
    const hasDiscountSelection = !!discountId;
    const hasInvoiceDiscount = !!pricing?.applied_discounts?.some(d => d.applied_to === 'invoice');
    const invoiceDiscountFromPricing = pricing?.applied_discounts?.find(d => d.applied_to === 'invoice')?.id ?? null;
    const hasDiscounts = (data.discount_lines?.length || 0) > 0 || paxGroups.some(g => g.discount_id) || hasDiscountSelection || hasInvoiceDiscount;
    const computedMode = data.override_rates
        ? PRICING_MODES.OVERRIDE
        : (data.is_wholesaler || data.wholesaler_id)
            ? PRICING_MODES.WHOLESALER
            : hasPromo
                ? PRICING_MODES.PROMO
                : hasDiscounts
                    ? PRICING_MODES.DISCOUNT
                    : PRICING_MODES.STANDARD;
    const slotOverrideTimeErrorRaw = slotOverrideErrors?.time || slotOverrideErrors?.time_slots || slotOverrideErrors?.time_slot;
    const slotOverrideTimeError = Array.isArray(slotOverrideTimeErrorRaw) ? slotOverrideTimeErrorRaw[0] : slotOverrideTimeErrorRaw;
    const bookingToggleItems = [
        { key: 'web_booking', label: 'Web Booking' },
        ...(pickupEnabled ? [{ key: 'needs_pickup', label: 'Needs Pickup' }] : []),
        { key: 'override_capacity', label: 'Override Capacity', disabled: !canOverrideCapacity },
        { key: 'override_timeslots', label: 'Override Time Slots', disabled: !canOverrideTimeSlots },
        { key: 'exclusive_resource', label: 'Exclusive Resource use' },
    ];

    useEffect(() => {
        // Keep the submitted invoice_discount_id aligned with what pricing currently shows,
        // so Store/Update applies the same discount used in UI calculation.
        if (invoiceDiscountFromPricing && String(data.invoice_discount_id || '') !== String(invoiceDiscountFromPricing)) {
            setData('invoice_discount_id', invoiceDiscountFromPricing);
        }
    }, [invoiceDiscountFromPricing, data.invoice_discount_id, setData]);

    useEffect(() => {
        // if (selectedService && selectedSeason) {
        //     fetchCalculatedPrice();
        // }
        console.log('selectedService, selectedSeason, adults, children, guides, booking_date, start_time 187', selectedService);
        console.log('selectedService', selectedService);
        console.log('selectedSeason', selectedSeason);
        console.log('wholesaler_id', data.wholesaler_id);
        console.log('promoCode', promoCode);
        console.log('adults', data.adults);
        console.log('children', data.children);
        console.log('guides', data.guides);
        console.log('booking_date', data.booking_date);
        console.log('start_time', data.start_time);
        if (data.adults > 0 || data.children > 0 || data.guides > 0) {
            if (Array.isArray(pricing?.line_items) && pricing.line_items.length > 0) {
                setPaxGroups(pricing.line_items);
                return;
            }

            let rate_per_adult = data?.rate_per_adult || 0;
            let rate_per_child = data?.rate_per_child || 0;
            let rate_per_guide = data?.rate_per_guide || 0;

            const hasPricingOverride = !!pricing?.applied_promotion || (pricing?.applied_discounts?.length || 0) > 0;
            const shouldAutoTotal = !hasPricingOverride && (
                computedMode === PRICING_MODES.STANDARD
                || computedMode === PRICING_MODES.WHOLESALER
                || computedMode === PRICING_MODES.OVERRIDE
            );
            if (shouldAutoTotal) {
                const adult_total = (parseFloat(data.adults || 0) * parseFloat(rate_per_adult));
                const child_total = (parseFloat(data.children || 0) * parseFloat(rate_per_child));
                const guide_total = (parseFloat(data.guides || 0) * parseFloat(rate_per_guide));
                const booking_total = (parseFloat(adult_total || 0) + parseFloat(child_total || 0) + parseFloat(guide_total || 0)).toFixed(2);
                setData(prev => ({
                    ...prev,
                    rate_per_adult: rate_per_adult,
                    rate_per_child: rate_per_child,
                    rate_per_guide: rate_per_guide,
                    booking_total: booking_total || 0,
                }));
            }
            let groups = [];

            if (data.adults > 0) {
                groups.push({ type: "adult", count: parseInt(data.adults), discount_id: null, base_rate: rate_per_adult, final_rate: rate_per_adult });
            }
            if (data.children > 0) {
                groups.push({ type: "child", count: parseInt(data.children), discount_id: null, base_rate: rate_per_child, final_rate: rate_per_child });
            }
            if (data.guides > 0) {
                groups.push({ type: "guide", count: parseInt(data.guides), discount_id: null, base_rate: rate_per_guide, final_rate: rate_per_guide });
            }
            let mergedGroups = data.discount_lines?.length ? mergeDiscountLinesWithBase(groups, data.discount_lines) : groups;
            if (Array.isArray(pricing?.applied_discounts) && pricing.applied_discounts.length > 0) {
                const discountRateMap = new Map(
                    pricing.applied_discounts.map(d => [`${d.applied_to}:${d.id}`, Number(d.final_rate || 0)])
                );
                mergedGroups = mergedGroups.map(group => {
                    if (group.discount_id) {
                        const key = `${group.type}:${group.discount_id}`;
                        if (discountRateMap.has(key)) {
                            return { ...group, final_rate: discountRateMap.get(key) };
                        }
                    }
                    return group;
                });
            }
            setPaxGroups(mergedGroups);
        }
        if (!selectedService) {
            setDiscountList([]);
            setPromoList([]);
        }
        console.log('data', data);

    }, [selectedService, selectedSeason, data.adults, data.children, data.guides, data.booking_date, data.start_time, data.rate_per_adult, data.rate_per_child, data.rate_per_guide, computedMode, pricing]);
    //, promoCode

    useEffect(() => {
        setPricingMode(computedMode);
        console.log('computedMode', computedMode);
        const canShowDiscount = (discountList?.length || 0) > 0;
        const canShowPromo = (promoList?.length || 0) > 0;
        if (!hasWholesalers && data.is_wholesaler) {
            setData('is_wholesaler', false);
            setData('wholesaler_id', '');
            setData('wholesaler_rate_id', '');
            setWholesalerApplied(false);
        }

        switch (computedMode) {
            case PRICING_MODES.OVERRIDE:
                if (data.is_wholesaler) {
                    setData('is_wholesaler', false);
                    setData('wholesaler_id', '');
                    setWholesalerApplied(false);
                }
                setShowDiscount(false);
                setShowPromo(false);
                setShowWholesaler(false);
                break;
            case PRICING_MODES.WHOLESALER:
                if (hasPromo || hasDiscounts) {
                    resetPromoDiscountAndRates();
                }
                setShowDiscount(false);
                setShowPromo(false);
                setShowWholesaler(hasWholesalers);
                break;
            case PRICING_MODES.PROMO:
                if (data.is_wholesaler) {
                    setData('is_wholesaler', false);
                    setData('wholesaler_id', '');
                    setWholesalerApplied(false);
                }
                setShowDiscount(false);
                setShowPromo(canShowPromo);
                setShowWholesaler(false);
                break;
            case PRICING_MODES.DISCOUNT:
                if (data.is_wholesaler) {
                    setData('is_wholesaler', false);
                    setData('wholesaler_id', '');
                    setWholesalerApplied(false);
                }
                setShowPromo(false);
                setShowDiscount(canShowDiscount);
                setShowWholesaler(false);
                break;
            default:
                setShowDiscount(canShowDiscount);
                setShowPromo(canShowPromo);
                setShowWholesaler(hasWholesalers);
                break;
        }
    }, [computedMode, discountList, promoList, hasWholesalers, data.is_wholesaler, setData]);

    useEffect(() => {
        if (data.is_wholesaler) {
            resetPromoDiscountAndRates();
        }
    }, [data.is_wholesaler]);

    // used by wholesaler and promo
    const handleCalculate = async (type, paxGroupsOverride = null, invoiceDiscountId = null) => {
        console.log('==============handleCalculate 303:========== ');

        const service_id = selectedService ? selectedService?.id : (alert('Please select a service'));
        const season_id = selectedSeason ? selectedSeason?.id : (alert('Please select a season'));
        const wholesaler_id = type === 'saler' ? (data.wholesaler_id || null) : null;
        const booking_date = data.booking_date ? data.booking_date : (alert('Please select a date'));
        const promo_code = type === 'promo' ? (promoCode || null) : null;
        const pax_groups = paxGroupsOverride || paxGroups || (alert('Please select a pax group'));
        if (!selectedService || !selectedSeason || !data.booking_date || !data.start_time) return;
        if ((Number(data.adults || 0) + Number(data.children || 0)) < 1) return;

        if (type === 'saler') {
            if (!wholesaler_id) {
                showToast('Please select a wholesaler before applying the wholesaler rate.', 'error');
                return null;
            }
            setWholesalerApplied(true);
        }
        if (type === 'promo') {
            //setPricingMode(PRICING_MODES.PROMO);
            //resetPricingState('promo');
        }
        try {
            const payload = {
                service_id: service_id,
                season_id: season_id,
                wholesaler_id: wholesaler_id,
                wholesaler_rate_id: type === 'saler' ? (data.wholesaler_rate_id || null) : null,
                booking_date: booking_date,
                promo_code: promo_code,
                pax_groups: pax_groups,
                invoice_discount_id: type === 'discount' ? invoiceDiscountId : null,
            };

            console.log('==============Payload:========== ', payload);
            const res = await axios.post(route("admin.booking.calculate"), payload);

            console.log('Calculation result:', res.data);
            const applied_rates = res.data.applied_rates || {};
            const base_rates = res.data.base_rates || {};
            const total_price = res.data.total_price;
            setPricing(res.data);
            setData(prev => ({
                ...prev,
                override_price: false,
                override_rates: false,
                rate_per_adult: applied_rates.adult ?? prev.rate_per_adult,
                rate_per_child: applied_rates.child ?? prev.rate_per_child,
                rate_per_guide: applied_rates.guide ?? prev.rate_per_guide,
                booking_total: total_price || 0,
                is_prepaid_wholesaler_voucher: Boolean(res.data.applied_wholesaler_rate?.is_prepaid_voucher),
                wholesaler_rate_id: res.data.applied_wholesaler_rate?.id || prev.wholesaler_rate_id,
            }));
            const responseGroups = Array.isArray(res.data.line_items) && res.data.line_items.length > 0
                ? res.data.line_items
                : (Array.isArray(pax_groups) ? pax_groups : []);
            const discountRateMap = new Map(
                (res.data.applied_discounts || []).map(d => [`${d.applied_to}:${d.id}`, Number(d.final_rate || 0)])
            );
            setPaxGroups(responseGroups.map(g => {
                if (g.discount_id) {
                    const key = `${g.type}:${g.discount_id}`;
                    if (discountRateMap.has(key)) {
                        return { ...g, final_rate: discountRateMap.get(key) };
                    }
                }
                const fallbackRate = g.type === "adult"
                    ? Number(applied_rates.adult ?? base_rates.adult ?? 0)
                    : g.type === "child"
                        ? Number(applied_rates.child ?? base_rates.child ?? 0)
                        : Number(applied_rates.guide ?? base_rates.guide ?? 0);
                return {
                    ...g,
                    final_rate: Number(g.final_rate ?? fallbackRate),
                    base_rate: Number(g.base_rate ?? fallbackRate),
                };
            }));

            return res.data;
        } catch (err) {
            console.error(err.response?.data || err);
            showToast(err.response?.data?.message || 'Pricing recalculation failed. Please try again.', 'error');
            return null;
        }
    };

    useEffect(() => {
        if (!selectedService || !selectedSeason || !data.booking_date || !data.start_time) return;
        if ((Number(data.adults || 0) + Number(data.children || 0)) < 1) return;

        if (promoCode) {
            const key = `promo|${promoCode}|${data.adults}|${data.children}|${data.guides}|${data.booking_date}|${data.start_time}`;
            if (lastPricingKeyRef.current !== key) {
                lastPricingKeyRef.current = key;
                const promoGroups = buildBasePaxGroups(
                    Number(data.adults || 0),
                    Number(data.children || 0),
                    Number(data.guides || 0),
                    {
                        adult: Number(data.base_per_adult || data.rate_per_adult || 0),
                        child: Number(data.base_per_child || data.rate_per_child || 0),
                        guide: Number(data.base_per_guide || data.rate_per_guide || 0),
                    }
                );
                handleCalculate('promo', promoGroups);
            }
            return;
        }

        if (paxGroups.some(g => g.discount_id)) {
            const discountKey = paxGroups
                .filter(g => g.discount_id)
                .map(g => `${g.type}:${g.discount_id}:${g.count}`)
                .join('|');
            const key = `discount|${discountKey}|${data.adults}|${data.children}|${data.guides}|${data.booking_date}|${data.start_time}`;
            if (lastPricingKeyRef.current !== key) {
                lastPricingKeyRef.current = key;
                handleCalculate('discount', paxGroups);
            }
            return;
        }

        if (hasInvoiceDiscount) {
            const invoiceDiscountId = pricing?.applied_discounts?.find(d => d.applied_to === 'invoice')?.id;
            if (invoiceDiscountId) {
                const baseGroups = buildBasePaxGroups(
                    Number(data.adults || 0),
                    Number(data.children || 0),
                    Number(data.guides || 0),
                    {
                        adult: Number(data.rate_per_adult || 0),
                        child: Number(data.rate_per_child || 0),
                        guide: Number(data.rate_per_guide || 0),
                    }
                );
                const key = `invoice|${invoiceDiscountId}|${data.adults}|${data.children}|${data.guides}|${data.booking_date}|${data.start_time}`;
                if (lastPricingKeyRef.current !== key) {
                    lastPricingKeyRef.current = key;
                    handleCalculate('discount', baseGroups, invoiceDiscountId);
                }
            }
        }
    }, [
        promoCode,
        paxGroups,
        data.adults,
        data.children,
        data.guides,
        data.booking_date,
        data.start_time,
        selectedService,
        selectedSeason,
        hasInvoiceDiscount,
        pricing,
    ]);

    useEffect(() => {
        if (data.wholesaler_id && data.is_wholesaler) {
            const wholesalerList = selectedWholesalerRates.map(rate => ({
                id: rate.id,
                name: rateLabel(rate),
                price_adult: rate.price_adult,
                price_child: rate.price_child,
                price_guide: rate.price_guide,
            }));
            setWholesalerRateList(wholesalerList);

            const nextRate = selectedWholesalerRate;
            if (!nextRate && data.wholesaler_rate_id) {
                setData('wholesaler_rate_id', '');
                return;
            }
            if (nextRate && String(data.wholesaler_rate_id || '') !== String(nextRate.id)) {
                setData('wholesaler_rate_id', nextRate.id);
                return;
            }

            if (wholesalerApplied && nextRate) {
                const adultTotal = Number(data.adults || 0) * Number(nextRate.price_adult || 0);
                const childTotal = Number(data.children || 0) * Number(nextRate.price_child || 0);
                const guideTotal = Number(data.guides || 0) * Number(nextRate.price_guide || 0);
                setData(prev => ({
                    ...prev,
                    override_price: false,
                    override_rates: false,
                    adult: nextRate.price_adult || 0,
                    child: nextRate.price_child || 0,
                    guide: nextRate.price_guide || 0,
                    rate_per_adult: nextRate.price_adult || 0,
                    rate_per_child: nextRate.price_child || 0,
                    rate_per_guide: nextRate.price_guide || 0,
                    booking_total: (adultTotal + childTotal + guideTotal).toFixed(2),
                }));
            }
        }
        if (!data.is_wholesaler) {
            setWholesalerRateList([]);
            setWholesalerApplied(false);
            setData(prev => ({
                ...prev,
                wholesaler_id: null,
                wholesaler_rate_id: null,
                discount_lines: [],
                promoCode: null,
                redeem_voucher: null,
            }));
        }

    }, [data.wholesaler_id, data.is_wholesaler, data.wholesaler_rate_id, data.booking_date, wholesalerApplied, selectedWholesalerRates, selectedWholesalerRate]);
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
        console.log('data.service_id 456');
        setSelectedService(service ?? null);
        const seasons = service ? setSeasons(service.seasons) : [];
        // console.log('seasons', service?.seasons);
        if (service) {
            const serviceDiscounts = discounts
                .filter(discount => discount.services?.some(s => s.id === service.id))
                .map(rate => ({
                    id: rate.id,
                    name: rate.name,
                    percent_off: rate.percent_off,
                    applies_to: rate.applies_to
                }));
            setDiscountList(serviceDiscounts);
            const servicePromos = promos.filter(promo => promo.service_id === service.id);
            setPromoList(servicePromos);
        } else {
            setDiscountList([]);
            setPromoList([]);
        }
        const isServiceCleared = !data.service_id;
        const isServiceChanged = prevServiceIdRef.current && String(prevServiceIdRef.current) !== String(data.service_id);
        if (!data.wholesaler_id && !data.is_wholesaler) {
            setData(prev => ({
                ...prev,
                override_capacity: false,
                capacity: service?.capacity || 0,
                duration: service?.duration || 0,
                season_id: null,
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
            }));
            setSelectedSeason(null);
            if (isServiceCleared) {
                setActivityId(null);
                setActivityDate(() => new Date(start_date));
                setTimeslots([]);
            }
        } else {
            setData(prev => ({
                ...prev,
                override_capacity: false,
                capacity: service?.capacity || 0,
                duration: service?.duration || 0,
                season_id: null,
                override_price: false,
                override_rates: false,
                override_timeslots: false,
            }));
            setSelectedSeason(null);
            if (isServiceCleared) {
                setActivityId(null);
                setActivityDate(() => new Date(start_date));
                setTimeslots([]);
            }
        }
        if (isServiceChanged) {
            setData(prev => ({
                ...prev,
                start_time: '',
                selected_slot: [],
            }));
            setSelectedSlot(null);
        }
        prevServiceIdRef.current = data.service_id || null;

    }, [data.service_id]);


    function handlePromoModalClose() {
        setPromoModalOpen(false);
    }

    function handlePromoModalSelect(promo) {
        const selectedPromoCode = promo?.promo_code || promo;
        const params = typeof promo?.params === 'string'
            ? (() => {
                try { return JSON.parse(promo.params) || {}; } catch (e) { return {}; }
            })()
            : (promo?.params || {});
        const mode = params?.fixed_value_mode;
        const adults = Number(data.adults || 0);
        const children = Number(data.children || 0);

        if (promo?.type === 'fixed_value') {
            if (['one_adult', 'each_adult'].includes(mode) && adults < 1) {
                showToast('This promo applies to adults. Add at least one adult before applying it.', 'error');
                return false;
            }
            if (['one_child', 'each_child'].includes(mode) && children < 1) {
                showToast('This promo applies to children. Add at least one child before applying it.', 'error');
                return false;
            }
            if (mode === 'each_adult_child' && (adults + children) < 1) {
                showToast('This promo applies to adults and children. Add pax before applying it.', 'error');
                return false;
            }
        }

        setPromoCode(selectedPromoCode);
        setData("promoCode", selectedPromoCode);
        handlePromoModalClose();
        return true;
    }

    const calculateTotal = (count, rate) => {
        return (parseFloat(count || 0) * parseFloat(rate || 0)).toFixed(2);
    };

    const calculateBookingTotal = () => {
        if (data?.adults > 0 || data?.children > 0 || data?.guides > 0) {
            const adult_total = (parseFloat(data?.adults || 0) * parseFloat(data?.rate_per_adult || 0));
            const child_total = (parseFloat(data?.children || 0) * parseFloat(data?.rate_per_child || 0));
            const guide_total = (parseFloat(data?.guides || 0) * parseFloat(data?.rate_per_guide || 0));
            return (parseFloat(adult_total || 0) + parseFloat(child_total || 0) + parseFloat(guide_total || 0)).toFixed(2);
        }
        return 0;
    };

    const buildDiscountLinesPayload = (groups) => {
        return (groups || [])
            .filter(g => g.discount_id)
            .map(g => {
                const fallbackBase = g.type === 'adult'
                    ? Number(data.rate_per_adult || 0)
                    : g.type === 'child'
                        ? Number(data.rate_per_child || 0)
                        : Number(data.rate_per_guide || 0);
                const baseRate = Number(g.base_rate ?? fallbackBase);
                const finalRate = Number(g.final_rate ?? baseRate);
                return {
                    type: g.type,
                    count: Number(g.count || 0),
                    discount_id: g.discount_id,
                    base_rate: baseRate,
                    final_rate: finalRate,
                };
            });
    };

    const buildBasePaxGroups = (adultCount, childCount, guideCount, rates) => {
        const groups = [];
        if (adultCount > 0) {
            groups.push({ type: "adult", count: adultCount, discount_id: null, base_rate: rates.adult, final_rate: rates.adult });
        }
        if (childCount > 0) {
            groups.push({ type: "child", count: childCount, discount_id: null, base_rate: rates.child, final_rate: rates.child });
        }
        if (guideCount > 0) {
            groups.push({ type: "guide", count: guideCount, discount_id: null, base_rate: rates.guide, final_rate: rates.guide });
        }
        return groups;
    };

    const mergeDiscountLinesWithBase = (baseGroups, discountLines) => {
        const remainingCounts = {};
        baseGroups.forEach(group => {
            remainingCounts[group.type] = (remainingCounts[group.type] || 0) + Number(group.count || 0);
        });

        const merged = [];
        (discountLines || []).forEach(line => {
            const type = line.type;
            if (!['adult', 'child', 'guide'].includes(type)) return;
            const count = Math.min(Number(line.count || 0), Number(remainingCounts[type] || 0));
            if (count <= 0) return;
            remainingCounts[type] = Math.max(0, (remainingCounts[type] || 0) - count);
            merged.push({
                type,
                count,
                discount_id: line.discount_id ?? null,
                base_rate: Number(line.base_rate ?? 0),
                final_rate: Number(line.final_rate ?? line.base_rate ?? 0),
            });
        });

        baseGroups.forEach(group => {
            const remaining = Number(remainingCounts[group.type] || 0);
            if (remaining > 0) {
                merged.push({
                    ...group,
                    count: remaining,
                });
            }
        });

        return merged;
    };

    const resetPromoDiscountAndRates = () => {
        setPromoCode(null);
        setDiscountId(null);
        setDiscountAdultPax('');
        setDiscountChildPax('');
        setPromoModalOpen(false);
        setPricing(null);

        const adultCount = parseInt(data.adults || 0);
        const childCount = parseInt(data.children || 0);
        const guideCount = parseInt(data.guides || 0);
        const baseRates = {
            adult: data.base_per_adult || 0,
            child: data.base_per_child || 0,
            guide: data.base_per_guide || 0,
        };
        const booking_total = (
            (adultCount * baseRates.adult) +
            (childCount * baseRates.child) +
            (guideCount * baseRates.guide)
        ).toFixed(2);

        setData(prev => ({
            ...prev,
            promoCode: null,
            discount_lines: [],
            invoice_discount_id: null,
            override_rates: false,
            override_price: false,
            rate_per_adult: baseRates.adult,
            rate_per_child: baseRates.child,
            rate_per_guide: baseRates.guide,
            booking_total: booking_total || 0,
            is_prepaid_wholesaler_voucher: false,
        }));

        setPaxGroups(buildBasePaxGroups(adultCount, childCount, guideCount, baseRates));

        if (selectedService) {
            const serviceDiscounts = discounts
                .filter(discount => discount.services?.some(s => s.id === selectedService.id))
                .map(rate => ({
                    id: rate.id,
                    name: rate.name,
                    percent_off: rate.percent_off,
                    applies_to: rate.applies_to
                }));
            setDiscountList(serviceDiscounts);
            const servicePromos = promos.filter(promo => promo.service_id === selectedService.id);
            setPromoList(servicePromos);
        }
    };

    const clearPricingForOverride = () => {
        setPromoCode(null);
        setDiscountId(null);
        setDiscountAdultPax('');
        setDiscountChildPax('');
        setPromoModalOpen(false);
        setPricing(null);
        setShowDiscount(false);
        setShowPromo(false);
        setShowWholesaler(false);
        setWholesalerApplied(false);

        const adultCount = parseInt(data.adults || 0);
        const childCount = parseInt(data.children || 0);
        const guideCount = parseInt(data.guides || 0);
        const baseRates = {
            adult: Number(data.base_per_adult || 0),
            child: Number(data.base_per_child || 0),
            guide: Number(data.base_per_guide || 0),
        };
        const bookingTotal = (
            (adultCount * baseRates.adult) +
            (childCount * baseRates.child) +
            (guideCount * baseRates.guide)
        ).toFixed(2);

        setData(prev => ({
            ...prev,
            promoCode: null,
            discount_lines: [],
            invoice_discount_id: null,
            override_rates: true,
            override_price: false,
            is_wholesaler: false,
            wholesaler_id: '',
            wholesaler_rate_id: '',
            is_prepaid_wholesaler_voucher: false,
            rate_per_adult: baseRates.adult,
            rate_per_child: baseRates.child,
            rate_per_guide: baseRates.guide,
            adult: baseRates.adult,
            child: baseRates.child,
            guide: baseRates.guide,
            booking_total: bookingTotal || 0,
        }));
        setPaxGroups(buildBasePaxGroups(adultCount, childCount, guideCount, baseRates));
    };

    const handleResetToRack = () => {
        if (data.is_wholesaler || data.wholesaler_id) {
            setData('is_wholesaler', false);
            setData('wholesaler_id', '');
            setWholesalerApplied(false);
        }
        resetPromoDiscountAndRates();
    };

    const handleDiscountChange = (value) => {
        if (hasDiscounts || hasPromo || data.is_wholesaler || data.wholesaler_id) {
            handleResetToRack();
        }
        setDiscountId(value || null);
    };

    const handleChange = (target, value) => {
        console.log('target, value', target, value);
        if (target && typeof target === 'object' && target.target) {
            const { name, type, checked, value: inputValue } = target.target;
            return handleChange(name, type === 'checkbox' ? checked : inputValue);
        }
        const getRemainingSlots = (bookingDate, startTimeId) => {
            if (!bookingDate || !startTimeId || Number(startTimeId) === 0) return null;
            const datePart = bookingDate.split(' ')[0].split('T')[0];
            const row = (scheduleData?.schedule || []).find(r => String(r.slot_id) === String(startTimeId))
                || (scheduleData?.schedule || []).find(r => {
                    const slot = (timeslots || []).find(s => String(s.id) === String(startTimeId));
                    const timeLabel = slot?.name;
                    return timeLabel ? r.time?.slice(0, 5) === timeLabel : false;
                });
            if (!row) return null;
            const info = Object.values(row.days || {}).find(dayInfo => dayInfo?.date === datePart);
            if (!info) return null;
            return Number(info.slots ?? 0);
        };
        if (target === 'override_capacity') {
            const nextValue = !!value;
            if (nextValue && !canOverrideCapacity) {
                showToast('Override capacity is not allowed for this service.', 'error');
                setData('override_capacity', false);
                return;
            }
            setData('override_capacity', nextValue);
            return;
        }
        if (target === 'override_timeslots') {
            const nextValue = !!value;
            if (nextValue) {
                if (!canOverrideTimeSlots) {
                    showToast('Override time slots are not allowed for this inventory.', 'error');
                    setData('override_timeslots', false);
                    return;
                }
                if (!selectedService || !selectedSeason || !data.booking_date) {
                    showToast('Select a service, season, and date first.', 'error');
                    setData('override_timeslots', false);
                    return;
                }
                setSlotOverrideCommitted(false);
                const slot = timeslots.find(s => String(s.id) === String(data.start_time));
                const initialTime = slot?.name && slot.name !== 'All' ? slot.name : '';
                setSlotOverrideData({
                    time: initialTime,
                    capacity_override: '',
                    note: '',
                    internal_only: true,
                });
                setSlotOverrideErrors({});
                setSlotOverrideOpen(true);
                setData('override_timeslots', true);
            } else {
                setData('override_timeslots', false);
            }
            return;
        }
        if (target === 'override_rates') {
            const nextValue = !!value;
            if (nextValue && !canOverrideRates) {
                showToast('Override rates are not allowed for this season.', 'error');
                setData('override_rates', false);
                return;
            }
            if (nextValue) {
                clearPricingForOverride();
            } else {
                setData('override_reason', '');
                resetPromoDiscountAndRates();
            }
            return;
        }
        if (target === 'adults') {
            const nextAdults = value === '' ? 0 : Number(value);
            const nextChildren = Number(data.children || 0);
            if (!data.override_capacity) {
                const remaining = getRemainingSlots(data.booking_date, data.start_time);
                if (remaining !== null && (nextAdults + nextChildren) > remaining) {
                    const warningKey = `${data.booking_date}|${data.start_time}|${remaining}`;
                    if (lastCapacityWarningRef.current !== warningKey) {
                        const message = remaining > 0
                            ? `Cannot exceed remaining capacity (${remaining}). Enable Override Capacity to proceed.`
                            : 'This slot is full. Enable Override Capacity to proceed.';
                        showToast(message, 'error');
                        lastCapacityWarningRef.current = warningKey;
                    }
                    return;
                }
            }
            setData('adults', value === '' ? '' : value);
            return;
        }
        if (target === 'children') {
            const nextAdults = Number(data.adults || 0);
            const nextChildren = value === '' ? 0 : Number(value);
            if (!data.override_capacity) {
                const remaining = getRemainingSlots(data.booking_date, data.start_time);
                if (remaining !== null && (nextAdults + nextChildren) > remaining) {
                    const warningKey = `${data.booking_date}|${data.start_time}|${remaining}`;
                    if (lastCapacityWarningRef.current !== warningKey) {
                        const message = remaining > 0
                            ? `Cannot exceed remaining capacity (${remaining}). Enable Override Capacity to proceed.`
                            : 'This slot is full. Enable Override Capacity to proceed.';
                        showToast(message, 'error');
                        lastCapacityWarningRef.current = warningKey;
                    }
                    return;
                }
            }
            setData('children', value === '' ? '' : value);
            return;

        }
        if (target === 'guides') {
            setData('guides', value === '' ? '' : value);
            return;
        }
        setData(target, value);
        if (target === 'wholesaler_id') {
            if (value !== data.wholesaler_id) {
                setWholesalerApplied(false);
                resetPromoDiscountAndRates();
            }
        }
        if (target === 'booking_date') {
            setActivityDate(parseDate(value));
        }

    };

    const parseDate = (str) => {
        const [datePart] = str.split(" "); // strip time if present
        const [y, m, d] = datePart.split("-").map(Number);
        return new Date(y, m - 1, d); // local midnight
    };

    const findScheduleDay = (bookingDate, daysList) => {
        if (!bookingDate || !Array.isArray(daysList)) return null;
        const selectedDay = parseDate(bookingDate).getDate();
        return daysList.find(d =>
            (typeof d === "string" ? d : d.day)?.endsWith(selectedDay.toString().padStart(2, "0"))
        ) || null;
    };

    const resolveTimeLabel = (startTimeId, slotList, scheduleList) => {
        const slot = (slotList || []).find(s => s.id === Number(startTimeId));
        if (!slot) return null;
        const row = (scheduleList || []).find(r => r.time?.slice(0, 5) === slot.name);
        return row?.time || slot.name;
    };

    const getDefaultWeeklySlots = () => {
        return (selectedSeason?.inventory?.inventory_time_slots || [])
            .filter(slot => !slot.is_override_only)
            .map(slot => slot.start_time.slice(0, 5));
    };

    const closeSlotOverrideModal = () => {
        setSlotOverrideOpen(false);
        if (!slotOverrideCommitted) {
            setData('override_timeslots', false);
        }
    };

    const saveSlotOverride = async () => {
        if (!selectedService || !selectedSeason || !data.booking_date) {
            showToast('Select a service, season, and date first.', 'error');
            return;
        }
        const timeValue = (slotOverrideData.time || '').trim();
        if (!timeValue) {
            setSlotOverrideErrors({ time: 'Time is required.' });
            return;
        }
        const timeSlots = Array.from(new Set([
            ...getDefaultWeeklySlots(),
            timeValue,
        ]));
        setSlotOverrideSaving(true);
        try {
            const response = await axios.post(route('admin.inventory-slot-ranges.store'), {
                service_id: selectedService.id,
                season_id: selectedSeason.id,
                start_date: data.booking_date,
                end_date: data.booking_date,
                time_slots: timeSlots,
                internal_only: slotOverrideData.internal_only ? 1 : 0,
                capacity_override: slotOverrideData.capacity_override || null,
                note: slotOverrideData.note || null,
            }, {
                headers: { Accept: 'application/json' },
            });
            const returnedSlots = response?.data?.time_slots || [];
            if (returnedSlots.length) {
                const formatted = [
                    { id: 0, name: 'All' },
                    ...returnedSlots
                        .slice()
                        .sort((a, b) => String(a.start_time || '').localeCompare(String(b.start_time || '')))
                        .map(slot => ({
                            id: slot.id,
                            name: slot.start_time.slice(0, 5),
                        })),
                ];
                setTimeslots(formatted);
                const match = formatted.find(s => s.name === timeValue);
                if (match) {
                    setData('start_time', match.id);
                }
            }
            setSlotOverrideCommitted(true);
            setData('override_timeslots', true);
            setSlotOverrideOpen(false);
            loadCalendar();
        } catch (error) {
            const message = error?.response?.data?.message || 'Failed to save date override.';
            showToast(message, 'error');
            if (error?.response?.data?.errors) {
                setSlotOverrideErrors(error.response.data.errors);
            }
            setData('override_timeslots', false);
        } finally {
            setSlotOverrideSaving(false);
        }
    };

    const selectCalendarSlot = (cell, day, row, slotList = timeslots) => {
        const time = row.time.slice(0, 5);
        const slot = (slotList || []).find(s => String(s.id) === String(row.slot_id))
            || (slotList || []).find(s => s.name === time);

        if (!slot) {
            showToast('Please select a valid slot.', 'error');
            return;
        }

        const nextSlot = { activityDate: cell.date, day, time: row.time };
        setSelectedSlot(nextSlot);
        setData('selected_slot', nextSlot);
        setData('booking_date', cell.date);
        setActivityDate(parseDate(cell.date));
        setData('start_time', slot.id);
    };

    const canStaffOverrideClosedCell = (cell) => {
        if (!cell || cell.status === 'available' || cell.status === 'full') return false;
        if (cell.note === 'Past date') return false;
        return ['blocked_day', 'blocked_slot', 'unavailable'].includes(cell.status);
    };

    const handleCalendarCellClick = async (cell, day, row) => {
        if (!cell) return;

        if (cell.status === 'available') {
            selectCalendarSlot(cell, day, row);
            return;
        }

        if (!canStaffOverrideClosedCell(cell)) return;

        if (!canOverrideTimeSlots) {
            showToast('Override time slots are not allowed for this inventory.', 'error');
            return;
        }

        const confirmed = window.confirm(
            `This slot is currently closed (${cell.note || cell.status}). Create an internal date override and continue with this booking?`
        );
        if (!confirmed) return;

        setSlotOverrideSaving(true);
        try {
            const response = await axios.post(route('admin.inventory-slot-ranges.store'), {
                service_id: selectedService.id,
                season_id: selectedSeason.id,
                start_date: cell.date,
                end_date: cell.date,
                time_slots: [row.time.slice(0, 5)],
                internal_only: 1,
                capacity_override: null,
                note: `Staff booking override: ${cell.note || cell.status}`,
            }, {
                headers: { Accept: 'application/json' },
            });

            const returnedSlots = response?.data?.time_slots || [];
            const formatted = returnedSlots.length
                ? [
                    { id: 0, name: 'All' },
                    ...returnedSlots
                        .slice()
                        .sort((a, b) => String(a.start_time || '').localeCompare(String(b.start_time || '')))
                        .map(slot => ({
                            id: slot.id,
                            name: slot.start_time.slice(0, 5),
                        })),
                ]
                : timeslots;

            if (returnedSlots.length) {
                setTimeslots(formatted);
            }

            setData('override_timeslots', true);
            selectCalendarSlot(cell, day, row, formatted);
            showToast('Internal date override created for this closed slot.', 'success');
            loadCalendar();
        } catch (error) {
            const message = error?.response?.data?.message || 'Failed to create the date override.';
            showToast(message, 'error');
        } finally {
            setSlotOverrideSaving(false);
        }
    };

    useEffect(() => {
        if (data?.booking_date) {
            console.log('data.booking_date 614', data.booking_date);
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

                if (!data.wholesaler_id && !data.is_wholesaler) {
                    setData(prev => ({
                        ...prev,
                        season_id: season.id,
                        override_timeslots: false,
                        override_price: season?.allow_internal_price_override == 1 ? true : false || false,
                        override_rates: false,
                        adult: season?.rake_rate_adult || 0,
                        child: season?.rake_rate_child || 0,
                        guide: season?.rake_rate_guide || 0,
                        base_per_adult: season?.rake_rate_adult || 0,
                        base_per_child: season?.rake_rate_child || 0,
                        base_per_guide: season?.rake_rate_guide || 0,
                        rate_per_adult: season?.rake_rate_adult || 0,
                        rate_per_child: season?.rake_rate_child || 0,
                        rate_per_guide: season?.rake_rate_guide || 0,
                    }));
                } else {
                    setData(prev => ({
                        ...prev,
                        season_id: season.id,
                        override_timeslots: false,
                    }));
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

    }, [data.booking_date, seasons]);

    useEffect(() => {
        if (!data.booking_date || !data.start_time || Number(data.start_time) === 0) {
            if (selectedSlot) {
                setSelectedSlot(null);
                setData('selected_slot', []);
            }
            return;
        }
        if (!scheduleData?.days?.length) return;
        if (!timeslots?.length) return;

        const day = findScheduleDay(data.booking_date, scheduleData.days);
        const resolvedTime = resolveTimeLabel(data.start_time, timeslots, scheduleData.schedule);

        if (!day || !resolvedTime) {
            setSelectedSlot(null);
            setData('selected_slot', []);
            const warningKey = `${data.booking_date}|${data.start_time}|${day ? 1 : 0}|${resolvedTime ? 1 : 0}`;
            if (lastSlotWarningRef.current !== warningKey) {
                showToast('Selected time may not be available for this date. Please confirm or reselect.', 'error');
                lastSlotWarningRef.current = warningKey;
            }
            return;
        }

        const nextSlot = { activityDate: data.booking_date, day: day, time: resolvedTime };
        if (
            !selectedSlot ||
            selectedSlot.day !== day ||
            selectedSlot.time !== resolvedTime ||
            selectedSlot.activityDate !== data.booking_date
        ) {
            setSelectedSlot(nextSlot);
            setData('selected_slot', nextSlot);
        }
    }, [data.booking_date, data.start_time, scheduleData.days, scheduleData.schedule, timeslots]);

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

    const hasCurrentActivityInput = () => Boolean(
        data.service_id ||
        data.season_id ||
        data.booking_date ||
        data.start_time ||
        Number(data.adults || 0) > 0 ||
        Number(data.children || 0) > 0 ||
        Number(data.guides || 0) > 0
    );

    const hasCompleteCurrentActivity = () => Boolean(
        data.service_id &&
        data.season_id &&
        data.booking_date &&
        data.start_time &&
        data.selected_slot?.time &&
        (Number(data.adults || 0) + Number(data.children || 0)) >= 1
    );

    const resetCurrentActivity = () => {
        setSelectedSlot(null);
        setPricing(null);
        setPricingMode(PRICING_MODES.STANDARD);
        setPaxGroups([]);
        lastPricingKeyRef.current = '';
        setPromoCode(null);
        setPromotionId(null);
        setDiscountId(null);
        setDiscountAdultPax('');
        setDiscountChildPax('');
        setWholesalerApplied(false);
        setData(prev => ({
            ...prev,
            is_wholesaler: false,
            wholesaler_id: '',
            start_time: '',
            end_time: '',
            selected_slot: [],
            adults: 0,
            children: 0,
            guides: 0,
            adult: 0,
            child: 0,
            guide: 0,
            rate_per_adult: 0,
            rate_per_child: 0,
            rate_per_guide: 0,
            booking_total: 0,
            is_prepaid_wholesaler_voucher: false,
            discount_lines: [],
            invoice_discount_id: null,
            promoCode: null,
            override_rates: false,
            override_reason: '',
            override_capacity: false,
            override_timeslots: false,
        }));
    };

    const buildCurrentActivityItem = () => ({
        client_id: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
        service_id: data.service_id,
        service_name: selectedService?.name || services.find(s => Number(s.id) === Number(data.service_id))?.name || '',
        season_id: data.season_id,
        season_name: selectedSeason?.name || seasons.find(s => Number(s.id) === Number(data.season_id))?.name || '',
        booking_date: data.booking_date,
        start_time: data.start_time,
        selected_slot: data.selected_slot,
        adults: Number(data.adults || 0),
        children: Number(data.children || 0),
        guides: Number(data.guides || 0),
        base_per_adult: Number(data.base_per_adult || 0),
        base_per_child: Number(data.base_per_child || 0),
        base_per_guide: Number(data.base_per_guide || 0),
        rate_per_adult: Number(data.rate_per_adult || 0),
        rate_per_child: Number(data.rate_per_child || 0),
        rate_per_guide: Number(data.rate_per_guide || 0),
        booking_total: Number(data.booking_total || 0),
        is_wholesaler: Boolean(data.is_wholesaler),
        wholesaler_id: data.wholesaler_id || null,
        wholesaler_rate_id: data.wholesaler_rate_id || null,
        promoCode: promoCode || data.promoCode || null,
        invoice_discount_id: data.invoice_discount_id || null,
        discount_lines: buildDiscountLinesPayload(paxGroups),
        pax_groups: paxGroups,
        pricing,
        pricing_mode: pricingMode,
        override_rates: Boolean(data.override_rates),
        override_reason: data.override_reason || '',
        override_capacity: Boolean(data.override_capacity),
        override_timeslots: Boolean(data.override_timeslots),
        rate_source_label: pricing?.applied_promotion?.name
            ? `Promo: ${pricing.applied_promotion.name}`
            : data.is_wholesaler
                ? 'Wholesaler'
                : (data.invoice_discount_id || paxGroups.some(g => g.discount_id))
                    ? 'Discount'
                    : data.override_rates
                        ? 'Override'
                        : 'Rack rate',
    });

    const activityIdentity = (item) => JSON.stringify({
        service_id: Number(item.service_id || 0),
        season_id: Number(item.season_id || 0),
        booking_date: item.booking_date || '',
        start_time: String(item.start_time || ''),
        selected_time: item.selected_slot?.time || '',
        adults: Number(item.adults || 0),
        children: Number(item.children || 0),
        guides: Number(item.guides || 0),
        booking_total: Number(item.booking_total || 0).toFixed(2),
        pricing_mode: item.pricing_mode || '',
        wholesaler_id: item.wholesaler_id || null,
        promoCode: item.promoCode || null,
        invoice_discount_id: item.invoice_discount_id || null,
        discount_lines: item.discount_lines || [],
    });

    const addCurrentActivity = ({ addAnother = false } = {}) => {
        if (!data.service_id || !data.season_id || !data.booking_date || !data.start_time || !data.selected_slot?.time) {
            showToast('Please select service, date, time slot, and rates before adding the activity.', 'info');
            return;
        }
        if ((Number(data.adults || 0) + Number(data.children || 0)) < 1) {
            showToast('Please add at least 1 adult or 1 child for this activity.', 'info');
            return;
        }

        const item = buildCurrentActivityItem();
        const itemKey = activityIdentity(item);
        if (activityItems.some(existingItem => activityIdentity(existingItem) === itemKey)) {
            showToast('This activity is already in the booking.', 'info');
            if (addAnother) {
                resetCurrentActivity();
            }
            return;
        }

        setActivityItems(prev => [...prev, item]);
        showToast('Activity added to booking.', 'success');
        if (addAnother) {
            resetCurrentActivity();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    const removeActivityItem = (clientId) => {
        setActivityItems(prev => prev.filter(item => item.client_id !== clientId));
    };

    const buildPaxGroupsForActivity = (item) => {
        const baseRates = {
            adult: Number(item.base_per_adult || item.rate_per_adult || 0),
            child: Number(item.base_per_child || item.rate_per_child || 0),
            guide: Number(item.base_per_guide || item.rate_per_guide || 0),
        };

        if (item.promoCode || item.invoice_discount_id || item.is_wholesaler) {
            return buildBasePaxGroups(
                Number(item.adults || 0),
                Number(item.children || 0),
                Number(item.guides || 0),
                baseRates
            );
        }

        return Array.isArray(item.pax_groups) && item.pax_groups.length > 0
            ? item.pax_groups
            : buildBasePaxGroups(
                Number(item.adults || 0),
                Number(item.children || 0),
                Number(item.guides || 0),
                baseRates
            );
    };

    const recalculateActivityItemsForSubmit = async (items) => {
        const recalculatedItems = [];

        for (const item of items) {
            if (item.override_rates) {
                recalculatedItems.push(item);
                continue;
            }

            const response = await axios.post(route("admin.booking.calculate"), {
                service_id: item.service_id,
                season_id: item.season_id,
                wholesaler_id: item.is_wholesaler ? (item.wholesaler_id || null) : null,
                booking_date: item.booking_date,
                promo_code: item.promoCode || null,
                invoice_discount_id: item.invoice_discount_id || null,
                pax_groups: buildPaxGroupsForActivity(item),
            });

            recalculatedItems.push({
                ...item,
                booking_total: Number(response.data?.total_price || 0),
                rate_per_adult: Number(response.data?.applied_rates?.adult ?? item.rate_per_adult ?? 0),
                rate_per_child: Number(response.data?.applied_rates?.child ?? item.rate_per_child ?? 0),
                rate_per_guide: Number(response.data?.applied_rates?.guide ?? item.rate_per_guide ?? 0),
                pax_groups: response.data?.line_items || item.pax_groups || [],
                pricing: response.data || item.pricing || null,
                discount_lines: response.data?.discount_lines || item.discount_lines || [],
            });
        }

        return recalculatedItems;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const currentActivityIsComplete = hasCompleteCurrentActivity();
        const currentActivityHasInput = hasCurrentActivityInput();
        let activitiesForSubmit = [
            ...activityItems,
            ...(currentActivityIsComplete ? [buildCurrentActivityItem()] : []),
        ];
        const hasSavedActivities = activitiesForSubmit.length > 0;
        if (!hasSavedActivities && (Number(data.adults || 0) + Number(data.children || 0)) < 1) {
            showToast('Please add at least 1 adult or 1 child.', 'info');
            return;
        }
        if (activityItems.length > 0 && currentActivityHasInput && !currentActivityIsComplete) {
            showToast('Please complete or clear the current activity before submitting the booking.', 'info');
            return;
        }
        // Avoid "Pricing mismatch" by ensuring server-calculated totals are current for promo/wholesaler/invoice-discount flows.
        let serverCalculatedTotal = null;
        let invoiceDiscountIdToSend = data.invoice_discount_id || null;
        try {
            if (hasSavedActivities) {
                activitiesForSubmit = await recalculateActivityItemsForSubmit(activitiesForSubmit);
                serverCalculatedTotal = activitiesForSubmit.reduce((sum, item) => sum + Number(item.booking_total || 0), 0);
            }
            const invoiceDiscountId = pricing?.applied_discounts?.find(d => d.applied_to === 'invoice')?.id ?? null;
            invoiceDiscountIdToSend = invoiceDiscountId || invoiceDiscountIdToSend;
            const hasLineDiscounts = paxGroups.some(g => g.discount_id);
            const shouldRecalc = !hasSavedActivities && Boolean(data.is_wholesaler || promoCode || invoiceDiscountIdToSend || hasLineDiscounts || pricing?.applied_promotion);
            if (shouldRecalc) {
                const calcType = promoCode ? 'promo' : data.is_wholesaler ? 'saler' : (invoiceDiscountIdToSend || hasLineDiscounts) ? 'discount' : 'base';
                const result = await handleCalculate(calcType, paxGroups, invoiceDiscountIdToSend);
                serverCalculatedTotal = Number(result?.total_price ?? 0) || null;
            }
        } catch (err) {
            // If we can't recalc, still allow submit; backend will return a clear error and we surface it via toast.
            console.error(err);
        }

        const discountLinesPayload = buildDiscountLinesPayload(paxGroups);
        const firstActivity = activitiesForSubmit[0] || null;
        const submitPayload = {
            ...data,
            ...(firstActivity ? {
                service_id: firstActivity.service_id,
                season_id: firstActivity.season_id,
                booking_date: firstActivity.booking_date,
                start_time: firstActivity.start_time,
                selected_slot: firstActivity.selected_slot,
                adults: firstActivity.adults,
                children: firstActivity.children,
                guides: firstActivity.guides,
                rate_per_adult: firstActivity.rate_per_adult,
                rate_per_child: firstActivity.rate_per_child,
                rate_per_guide: firstActivity.rate_per_guide,
                is_wholesaler: firstActivity.is_wholesaler,
                wholesaler_id: firstActivity.wholesaler_id,
                promoCode: firstActivity.promoCode,
                invoice_discount_id: firstActivity.invoice_discount_id,
                discount_lines: firstActivity.discount_lines || [],
            } : {}),
            booking_total: serverCalculatedTotal ?? data.booking_total,
            invoice_discount_id: firstActivity ? firstActivity.invoice_discount_id : invoiceDiscountIdToSend,
            discount_lines: firstActivity ? (firstActivity.discount_lines || []) : discountLinesPayload,
            activity_items: activitiesForSubmit.length > 1 ? activitiesForSubmit : [],
        };

        transform(() => submitPayload);
        post(route('admin.bookings.store'), {
            onSuccess: () => {
                if (submitPayload.pay_later) {
                    router.visit(route('admin.bookings.index'));
                }
            },
            onError: (errs) => {
                const msg = errs?.booking_total || errs?.start_time || errs?.booking_date || 'Please fix the highlighted fields and try again.';
                showToast(msg, 'error');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
        });
    };

    const addDiscountGroup = () => {
        if (!discountId) return;
        if (pricingMode === PRICING_MODES.WHOLESALER) {
            alert('Cannot apply discounts to wholesaler bookings');
            return;
        }
        setPricingMode(PRICING_MODES.DISCOUNT);
        const selectedDiscount = parseInt(discountId);

        const selectedDiscountObj = discountList?.find(d => d.id === selectedDiscount);
        if (selectedDiscountObj?.applies_to === 'invoice') {
            setPaxGroups(prev => prev.map(g => ({
                ...g,
                discount_id: null,
                final_rate: undefined,
            })));
            setData(prev => ({
                ...prev,
                discount_lines: [],
                invoice_discount_id: selectedDiscount,
            }));
            handleCalculate('discount', paxGroups, selectedDiscount);
            setDiscountId(null);
            setDiscountAdultPax('');
            setDiscountChildPax('');
            return;
        }

        const requestedCounts = {
            adult: Math.max(0, parseInt(discountAdultPax || 0, 10) || 0),
            child: Math.max(0, parseInt(discountChildPax || 0, 10) || 0),
        };

        if (requestedCounts.adult < 1 && requestedCounts.child < 1) {
            alert('Add at least one adult or child pax count for this discount.');
            return;
        }

        const availableCounts = {
            adult: paxGroups.filter(g => g.type === 'adult' && g.discount_id === null).reduce((sum, g) => sum + Number(g.count || 0), 0),
            child: paxGroups.filter(g => g.type === 'child' && g.discount_id === null).reduce((sum, g) => sum + Number(g.count || 0), 0),
        };
        const applicableAdult = Math.min(requestedCounts.adult, availableCounts.adult);
        const applicableChild = Math.min(requestedCounts.child, availableCounts.child);

        if (applicableAdult < 1 && applicableChild < 1) {
            alert('There are no undiscounted adult or child pax available for this discount.');
            return;
        }

        setPaxGroups(prevGroups => {
            let updated = [...prevGroups];

            const applyLineDiscount = (type, requestedCount) => {
                if (requestedCount <= 0) return;

                const idx = updated.findIndex(g => g.type === type && g.discount_id === null);
                if (idx === -1) return;

                const baseGroup = updated[idx];
                const countToMove = Math.min(requestedCount, Number(baseGroup.count || 0));
                if (countToMove <= 0) return;

                const baseRate = type === 'adult' ? data.rate_per_adult : data.rate_per_child;
                const discountedGroup = {
                    type,
                    count: countToMove,
                    discount_id: selectedDiscount,
                    base_rate: baseRate,
                    final_rate: baseRate,
                };

                if (Number(baseGroup.count || 0) <= countToMove) {
                    updated[idx] = discountedGroup;
                } else {
                    updated[idx] = {
                        ...baseGroup,
                        count: Number(baseGroup.count || 0) - countToMove,
                    };
                    updated.push(discountedGroup);
                }
            };

            applyLineDiscount('adult', applicableAdult);
            applyLineDiscount('child', applicableChild);

            setData(prev => ({
                ...prev,
                discount_lines: updated,
                invoice_discount_id: null,
            }));
            handleCalculate('discount', updated, null);
            return updated;
        });

        setDiscountId(null);
        setDiscountAdultPax('');
        setDiscountChildPax('');
    };

    const invoiceDiscountPct = pricing?.applied_discounts?.find(d => d.applied_to === 'invoice')?.percent_off;
    const invoiceMultiplier = invoiceDiscountPct ? (1 - (Number(invoiceDiscountPct) / 100)) : 1;
    const totalAdult = paxGroups
        .filter(g => g.type === "adult")
        .reduce((sum, g) => {
            if (g.line_total !== undefined && g.line_total !== null) {
                return sum + Number(g.line_total || 0);
            }
            const baseRate = Number(g.final_rate ?? data.rate_per_adult ?? 0);
            const effectiveRate = invoiceDiscountPct && !g.discount_id ? baseRate * invoiceMultiplier : baseRate;
            return sum + (g.count * effectiveRate);
        }, 0);
    const totalChild = paxGroups
        .filter(g => g.type === "child")
        .reduce((sum, g) => {
            if (g.line_total !== undefined && g.line_total !== null) {
                return sum + Number(g.line_total || 0);
            }
            const baseRate = Number(g.final_rate ?? data.rate_per_child ?? 0);
            const effectiveRate = invoiceDiscountPct && !g.discount_id ? baseRate * invoiceMultiplier : baseRate;
            return sum + (g.count * effectiveRate);
        }, 0);
    const totalGuide = paxGroups
        .filter(g => g.type === "guide")
        .reduce((sum, g) => {
            if (g.line_total !== undefined && g.line_total !== null) {
                return sum + Number(g.line_total || 0);
            }
            const baseRate = Number(g.final_rate ?? data.rate_per_guide ?? 0);
            const effectiveRate = invoiceDiscountPct && !g.discount_id ? baseRate * invoiceMultiplier : baseRate;
            return sum + (g.count * effectiveRate);
        }, 0);
    const total = Number(data.booking_total || 0);
    const currentActivityDisplayItem = hasCompleteCurrentActivity()
        ? {
            booking_total: Number(data.booking_total || 0),
            pricing,
        }
        : null;
    const visibleActivityItems = [
        ...activityItems,
        ...(activityItems.length > 0 && currentActivityDisplayItem ? [currentActivityDisplayItem] : []),
    ];
    const activityItemsTotal = visibleActivityItems.reduce((sum, item) => sum + Number(item.booking_total || 0), 0);
    const bookingTotalForPayment = visibleActivityItems.length > 0 ? activityItemsTotal : total;
    const payableTotalForPayment = visibleActivityItems.length > 0
        ? visibleActivityItems.reduce((sum, item) => {
            const prepaid = Boolean(item.pricing?.applied_wholesaler_rate?.is_prepaid_voucher);
            return sum + (prepaid ? 0 : Number(item.booking_total || 0));
        }, 0)
        : (isPrepaidWholesalerVoucher ? 0 : total);
    const graduatedPromo = pricing?.applied_promotion?.type === 'graduated';
    const promoTotal = Number(pricing?.total_price ?? data.booking_total ?? 0);
    const promoAdjustmentTarget = graduatedPromo
        ? (totalAdult > 0 ? 'adult' : totalChild > 0 ? 'child' : 'guide')
        : null;
    const displayAdjustment = graduatedPromo
        ? Number((promoTotal - (totalAdult + totalChild + totalGuide)).toFixed(2))
        : 0;
    const displayTotalAdult = totalAdult + (graduatedPromo && promoAdjustmentTarget === 'adult' ? displayAdjustment : 0);
    const displayTotalChild = totalChild + (graduatedPromo && promoAdjustmentTarget === 'child' ? displayAdjustment : 0);
    const displayTotalGuide = totalGuide + (graduatedPromo && promoAdjustmentTarget === 'guide' ? displayAdjustment : 0);

    useEffect(() => {
        if (pricing) return;
        const nextTotal = (Number(totalAdult || 0) + Number(totalChild || 0) + Number(totalGuide || 0)).toFixed(2);
        if (Number(data.booking_total || 0).toFixed(2) !== nextTotal) {
            setData('booking_total', nextTotal);
        }
    }, [pricing, totalAdult, totalChild, totalGuide, data.booking_total, setData]);

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
                    {errors?.booking_total && (
                        <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {errors.booking_total}
                        </div>
                    )}


                    {/* Service + Date */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <CustomSelect
                            label="Service"
                            name="service_id"
                            value={data.service_id}

                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            errors={errors}
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

                            onChange={(e) => { handleChange(e.target.name, e.target.value) }}
                            errors={errors}
                        />
                        <CustomSelect
                            label="Start Time"
                            name="start_time"
                            value={data.start_time}

                            onChange={(e) => {
                                setData('start_time', e.target.value);
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
                            errors={errors}
                            options={timeslots}
                        />
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
                                                                        onClick={() => handleCalendarCellClick(cell, day, row)}
                                                                    >
                                                                        <div className="w-[133px] h-full flex items-center justify-center">
                                                                            <div className={` p-[6px] text-start w-[135px] h-[99px] cursor-pointer rounded-[10px] space-y-[10px] transition ${selectedSlot?.day === day && selectedSlot?.time === row.time
                                                                                ? 'bg-[#55FF9E] text-black'
                                                                                : cell?.status === 'available'
                                                                                    ? (cell?.internal_only
                                                                                        ? 'bg-gray-100 border border-[#E5E5EC] text-gray-700 hover:bg-gray-200'
                                                                                        : 'bg-[#D6FBE6] border border-[#E5E5EC] text-green-700 hover:bg-green-200')
                                                                                    : canStaffOverrideClosedCell(cell)
                                                                                        ? 'bg-[#FCE4DF] border border-[#E5E5EC] text-gray-700 hover:bg-orange-100'
                                                                                        : 'bg-[#FCE4DF] border border-[#E5E5EC] text-gray-400 cursor-not-allowed'
                                                                                }`}>
                                                                                {cell?.status === 'available' && (
                                                                                    <>
                                                                                        <div className="text-xs">{row.time.slice(0, 5)}</div>
                                                                                        {cell?.internal_only && (
                                                                                            <div className="text-[10px] inline-flex w-fit px-2 py-0.5 rounded-full bg-gray-200 text-gray-700">
                                                                                                Admin-only
                                                                                            </div>
                                                                                        )}
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
                                                                                    <div className="text-xs text-black">Day Closed{canStaffOverrideClosedCell(cell) ? <span className="block text-[10px] text-orange-700">Click to override</span> : null}</div>
                                                                                )}

                                                                                {cell?.status === 'blocked_slot' && (
                                                                                    <div className="text-xs text-black">Slot Closed{canStaffOverrideClosedCell(cell) ? <span className="block text-[10px] text-orange-700">Click to override</span> : null}</div>
                                                                                )}
                                                                                {cell?.status === 'unavailable' && (
                                                                                    <div className="text-xs text-black">Not Available{canStaffOverrideClosedCell(cell) ? <span className="block text-[10px] text-orange-700">Click to override</span> : null}</div>
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
                    {/* Wholesaler Booking */}
                    {showWholesaler && (
                        <>
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
                                        label="Wholesaler"
                                        name="wholesaler_id"
                                        value={data.wholesaler_id}
                                        onChange={(e) => {
                                            setData('wholesaler_rate_id', '');
                                            setWholesalerApplied(false);
                                            handleChange(e.target.name, e.target.value);
                                        }}
                                        errors={errors}
                                        options={serviceWholesalers}
                                    />
                                    <CustomSelect
                                        label="Wholesaler Rate"
                                        name="wholesaler_rate_id"
                                        value={data.wholesaler_rate_id}
                                        onChange={(e) => {
                                            setWholesalerApplied(false);
                                            setData('wholesaler_rate_id', e.target.value);
                                        }}
                                        errors={errors}
                                        options={wholesalerRateList}
                                        optionText={wholesalerRateList.length ? '-- Select rate --' : 'No valid rates for this date'}
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
                        </>
                    )}
                    {data.add_consultant && (
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <CustomInputText
                                title="Consultant Name"
                                name="consultant_name"
                                value={data.consultant_name}

                                onChange={(e) => handleChange(e.target.name, e.target.value)}
                                errors={errors}
                            />
                            <CustomInputText
                                title="Consultant Email"
                                name="consultant_email"
                                value={data.consultant_email}

                                type="email"
                                onChange={(e) => handleChange(e.target.name, e.target.value)}
                                errors={errors}
                            />
                            <CustomInputText
                                title="Consultant Mobile"
                                name="consultant_mobile"
                                value={data.consultant_mobile}

                                onChange={(e) => handleChange(e.target.name, e.target.value)}
                                errors={errors}
                            />
                        </div>
                    )}
                    <div className="grid grid-cols-1 sm:grid-cols-4 gap-4 items-center">
                        <CustomInputText
                            title="Number of Adults"
                            id="adults"
                            name="adults"
                            type='number'
                            min="0"
                            value={data.adults}
                            onChange={(e) => { handleChange(e.target.name, e.target.value) }}
                            errors={errors}
                        />
                        <CustomInputText
                            title="Number of Children"
                            id="children"
                            name="children"
                            type='number'
                            min="0"
                            value={data.children}
                            onChange={(e) => { handleChange(e.target.name, e.target.value) }}
                            errors={errors}
                        />
                        <CustomInputText
                            title="Number of Guides"
                            id="guides"
                            name="guides"
                            type='number'
                            min="0"
                            value={data.guides}
                            onChange={(e) => { handleChange(e.target.name, e.target.value) }}
                            errors={errors}
                        />
                        <div className="mt-2">
                            <CustomCheckbox
                                label="Include Photos and Videos"
                                name="include_media"
                                checked={data.include_media}

                                onChange={(e) => { handleChange(e.target.name, e.target.value) }}
                                errors={errors}
                            />

                        </div>

                    </div>

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
                                {bookingToggleItems.map(({ key, label, disabled }) => (
                                    <CustomCheckbox
                                        key={key}
                                        label={label}
                                        name={key}
                                        checked={data[key] || false}
                                        onChange={handleChange}
                                        errors={errors}
                                        disabled={disabled || false}
                                    />
                                ))}
                                {/* <WhiteButton
                                    title="Edit Manifest"
                                    icon={true}
                                    iconName={icons.plus}
                                    width="w-[152px]"

                                    onClick={() => { alert('todo add manifest modal') }}
                                /> */}
                                <div onClick={() => setToggleRate(!toggleRate)}>
                                    {toggleRate ? icons.cheveron_down : icons.cheveron_up}
                                </div>
                            </div>
                        </div>
                        {/* Rates */}
                        <div className={`${toggleRate ? 'block' : 'hidden'} mt-10 p-6 bg-white rounded-[10px] border border-secondary-100 shadow space-y-4`}>

                            <div className="flex items-center gap-4 mt-4">
                                <div className="flex  items-center justify-start space-x-4 ">
                                    <CustomCheckbox
                                        label="Override Rates"
                                        name="override_rates"
                                        checked={data.override_rates || false}
                                        disabled={!canOverrideRates}

                                        onChange={handleChange}
                                        errors={errors}
                                    />
                                    <div className="flex items-center w-full  gap-4">
                                        <CustomInputText
                                            title={<span className="inline-flex items-center gap-1">Reason for Override <InfoHint text={noteHelp.override} /></span>}
                                            id="override_reason"
                                            name="override_reason"
                                            type='text'
                                            width='w-[100%]'
                                            required={data.override_rates}
                                            disabled={!data.override_rates}
                                            value={data.override_reason || ''}

                                            onChange={(e) => { handleChange(e.target.name, e.target.value) }}
                                            errors={errors}
                                        />
                                    </div>
                                </div>
                                {showDiscount && (
                                    <div className="flex  items-center justify-start ">

                                        <div className="flex flex-wrap items-end gap-2 w-full">
                                            <div className="w-full sm:w-[220px]">
                                                <CustomSelect
                                                    label="Discount"
                                                    name="discountId"
                                                    value={discountId}
                                                    width={"w-full"}
                                                    onChange={(e) => { handleDiscountChange(e.target.value); }}
                                                    error={errors.discountId}
                                                    options={discountList}
                                                />
                                            </div>
                                            {selectedDiscount?.applies_to === 'line_item' && (
                                                <>
                                                    <div className="w-[130px]">
                                                        <CustomInputText
                                                            title="Discount PAX (adult)"
                                                            id="discountAdultPax"
                                                            name="discountAdultPax"
                                                            type="number"
                                                            min="0"
                                                            width="w-full"
                                                            value={discountAdultPax}
                                                            onChange={(e) => setDiscountAdultPax(e.target.value)}
                                                            errors={errors}
                                                        />
                                                    </div>
                                                    <div className="w-[130px]">
                                                        <CustomInputText
                                                            title="Discount PAX (child)"
                                                            id="discountChildPax"
                                                            name="discountChildPax"
                                                            type="number"
                                                            min="0"
                                                            width="w-full"
                                                            value={discountChildPax}
                                                            onChange={(e) => setDiscountChildPax(e.target.value)}
                                                            errors={errors}
                                                        />
                                                    </div>
                                                </>
                                            )}
                                            <div className="mb-4">
                                                <BlueButton
                                                    title="Apply"
                                                    width='w-[100px]'
                                                    onClick={addDiscountGroup}
                                                    disabled={processing}
                                                    type="button" />
                                            </div>
                                        </div>
                                    </div>)}
                                {showPromo && (
                                    <div className="flex items-center justify-start ">

                                        <div className="flex flex-none items-center gap-2">
                                            <CustomInputText
                                                title="Promo Code"
                                                id="admin_create_promo_lookup"
                                                name="admin_create_promo_lookup"
                                                value={promoCode}
                                                onChange={(e) => setPromoCode(e.target.value)}
                                                autoComplete="new-password"
                                                autoCorrect="off"
                                                autoCapitalize="characters"
                                                spellCheck={false}
                                            />
                                            <div className="flex items-center gap-2 mt-3">
                                                {!pricing?.applied_promotion && (
                                                    <BlueButton
                                                        title="Apply"
                                                        disabled={processing}
                                                        onClick={() => handleCalculate('promo')}
                                                    />
                                                )}
                                                <div onClick={() => setPromoModalOpen(true)} className="flex items-center justify-center w-[36px] h-[32px] gap-2 bg-[#2D99FE] rounded-full p-2">
                                                    {icons.info}
                                                </div>
                                            </div>
                                        </div>
                                    </div>)}
                                {showWholesaler && data.is_wholesaler && (
                                    <div className="flex  items-center justify-start ">

                                        <div className="flex flex-col items-center space-y-2">
                                            <span className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]">
                                                Wholesaler Discount
                                            </span>
                                            <div className="flex items-center gap-2 mt-3">
                                                <BlueButton
                                                    title="Apply"
                                                    disabled={processing || !data.wholesaler_id}
                                                    onClick={() => handleCalculate('saler')}
                                                />
                                            </div>
                                        </div>
                                    </div>)}

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
                                            <div className="flex items-center justify-start w-full h-[43px] gap-2">
                                                <span className="text-booking-card-title-sub">
                                                    Rack Rate:
                                                </span>
                                                <span>
                                                    <input
                                                        type="number"
                                                        id='rate_per_adult'
                                                        name='rate_per_adult'
                                                        disabled={!data.override_rates}
                                                        value={data.override_rates ? (data['rate_per_adult'] || '') : (pricing?.base_rates?.adult ?? data['rate_per_adult'] ?? '')}
                                                        onChange={(e) => { handleChange(e.target.name, e.target.value) }}
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
                                                const baseRate = Number(group.final_rate ?? data?.rate_per_adult ?? 0);
                                                const rate = invoiceDiscountPct && !group.discount_id ? (baseRate * invoiceMultiplier) : baseRate;
                                                const amount = group.line_total !== undefined && group.line_total !== null
                                                    ? Number(group.line_total || 0)
                                                    : group.count * rate;
                                                const adjustedAmount = (graduatedPromo && promoAdjustmentTarget === 'adult' && i === 0)
                                                    ? amount + displayAdjustment
                                                    : amount;

                                                return (
                                                    <div key={i} className="mb-4 flex items-center justify-between gap-2">
                                                        <label
                                                            htmlFor='override_reason'
                                                            className="text-booking-card-lbl w-[128px] h-[34px] mt-2"
                                                        >
                                                            <span>
                                                                <span title={group.adjustment_label || undefined}>
                                                                    {group.adjustment_label ? "Promo" : (group.discount_id
                                                                        ? discountList.find((d) => d.id === group.discount_id)?.name
                                                                        : "Adult")} {group.count}
                                                                </span>
                                                            </span>
                                                        </label>
                                                        <input
                                                            type="text"
                                                            disabled='disabled'
                                                            value={adjustedAmount?.toFixed(2)}
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
                                            value={displayTotalAdult?.toFixed(2)}
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
                                            <div className="flex items-center justify-start w-full h-[43px] gap-2">
                                                <span className="text-booking-card-title-sub">
                                                    Rack Rate:
                                                </span>
                                                <span>
                                                    <input
                                                        type="number"
                                                        id='rate_per_child'
                                                        name='rate_per_child'
                                                        disabled={!data.override_rates}
                                                        value={data.override_rates ? (data['rate_per_child'] || '') : (pricing?.base_rates?.child ?? data['rate_per_child'] ?? '')}
                                                        onChange={(e) => { handleChange(e.target.name, e.target.value) }}
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
                                                const baseRate = Number(group.final_rate ?? data?.rate_per_child ?? 0);
                                                const rate = invoiceDiscountPct && !group.discount_id ? (baseRate * invoiceMultiplier) : baseRate;
                                                const amount = group.line_total !== undefined && group.line_total !== null
                                                    ? Number(group.line_total || 0)
                                                    : group.count * rate;
                                                const adjustedAmount = (graduatedPromo && promoAdjustmentTarget === 'child' && i === 0)
                                                    ? amount + displayAdjustment
                                                    : amount;

                                                return (
                                                    <div key={i} className="mb-4 flex items-center justify-between gap-2">
                                                        <label
                                                            htmlFor='override_reason'
                                                            className="text-booking-card-lbl w-[128px] h-[34px] mt-2"
                                                        >
                                                            <span>
                                                                <span title={group.adjustment_label || undefined}>
                                                                    {group.adjustment_label ? "Promo" : (group.discount_id
                                                                        ? discountList.find((d) => d.id === group.discount_id)?.name
                                                                        : "Child")} {group.count}
                                                                </span>
                                                            </span>
                                                        </label>
                                                        <input
                                                            type="text"
                                                            disabled='disabled'
                                                            value={adjustedAmount?.toFixed(2)}
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
                                            value={displayTotalChild?.toFixed(2)}
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
                                            <div className="flex items-center justify-start w-full h-[43px] gap-2">
                                                <span className="text-booking-card-title-sub">
                                                    Rack Rate:
                                                </span>
                                                <span>
                                                    <input
                                                        type="number"
                                                        id='rate_per_guide'
                                                        name='rate_per_guide'
                                                        disabled={!data.override_rates}
                                                        value={data.override_rates ? (data['rate_per_guide'] || '') : (pricing?.base_rates?.guide ?? data['rate_per_guide'] ?? '')}
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
                                                const baseRate = Number(group.final_rate ?? data?.rate_per_guide ?? 0);
                                                const rate = invoiceDiscountPct && !group.discount_id ? (baseRate * invoiceMultiplier) : baseRate;
                                                const amount = group.line_total !== undefined && group.line_total !== null
                                                    ? Number(group.line_total || 0)
                                                    : group.count * rate;
                                                const adjustedAmount = (graduatedPromo && promoAdjustmentTarget === 'guide' && i === 0)
                                                    ? amount + displayAdjustment
                                                    : amount;

                                                return (
                                                    <div key={i} className="mb-4 flex items-center justify-between gap-2">
                                                        <label
                                                            htmlFor='override_reason'
                                                            className="text-booking-card-lbl w-[128px] h-[34px] mt-2"
                                                        >
                                                            <span>
                                                                <span title={group.adjustment_label || undefined}>
                                                                    {group.adjustment_label ? "Promo" : (group.discount_id
                                                                        ? discountList.find((d) => d.id === group.discount_id)?.name
                                                                        : "Guide")} {group.count}
                                                                </span>
                                                            </span>
                                                        </label>
                                                        <input
                                                            type="text"
                                                            disabled='disabled'
                                                            value={adjustedAmount?.toFixed(2)}
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
                                            value={displayTotalGuide?.toFixed(2)}
                                            className={`mt-1 block w-full h-[34px] border-gray-300 shadow-sm input-text rounded-xl
                                                hover:border-black  active:border-blue-600 
                                                focus:outline-none focus:ring-0 focus:border-black
                                                `}
                                        />
                                    </div>

                                </div>
                            </div>
                            <div className="flex items-center space-x-4">
                                <span className="text-booking-lbl-main">Booking Total</span>
                                <div className="flex items-center justify-between gap-3 w-full max-w-[417px] min-w-[320px] h-[43px] bg-[#DBECFD4D] pl-[7px] pr-[7px] py-[7px] rounded-[6px]">
                                    <span className="text-booking-lbl-main-sub min-w-[115px] whitespace-nowrap">R {total.toFixed(2) || '0.00'}</span>
                                    {(hasPromo || hasDiscounts || data.is_wholesaler || !!invoiceDiscountPct) && (
                                        <WhiteButton
                                            title="Recalculate from Rack"
                                            icon={true}
                                            type="button"
                                            iconName={icons.spanners}
                                            textColor='text-[#4D81E7]'
                                            width="w-[189px] shrink-0"
                                            onClick={handleResetToRack}
                                        />
                                    )}
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
                                            <span className="inline-flex items-center gap-1 text-booking-card-title">Add Invoice Note <InfoHint text={noteHelp.invoice} /></span>
                                        </div>
                                    </div>
                                    <CustomTextarea
                                        name="invoice_note"
                                        value={data.invoice_note}
                                        onChange={(e) => { handleChange(e.target.name, e.target.value) }}
                                        rows={6}
                                    />
                                </div>
                                <div className="flex flex-col items-start rounded-2xl p-6 w-full border border-secondary-100 bg-white shadow">
                                    <div className="flex items-center justify-start mb-2 gap-[18px]">
                                        <div className={`flex items-center justify-center w-[36px] h-[38px] gap-2 bg-[#2D99FE] rounded-full p-2`}>
                                            {icons.emails}
                                        </div>
                                        <div className="flex flex-col items-start ">
                                            <span className="inline-flex items-center gap-1 text-booking-card-title">Add Email Reminder <InfoHint text={noteHelp.email} /></span>
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
                                            <span className="inline-flex items-center gap-1 text-booking-card-title">Add Booking Note <InfoHint text={noteHelp.booking} /></span>
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
                        <div className="flex flex-wrap items-center justify-end gap-3 pt-4">
                            <BlueButton
                                title="Add Another Activity"
                                width={"w-[243px]"}
                                onClick={() => addCurrentActivity({ addAnother: true })}
                                disabled={processing}
                                type="button"
                            />
                        </div>
                    </div>
                    {activityItems.length > 0 && (
                        <div className="mt-10 p-6 bg-white rounded shadow space-y-4">
                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <h2 className="text-booking-subheading">Activities in this booking</h2>
                                    <p className="text-sm text-gray-500">These activities will share the lead details below.</p>
                                </div>
                                <div className="text-booking-lbl-main-sub text-[#4D81E7]">
                                    R {Number(activityItemsTotal || 0).toFixed(2)}
                                </div>
                            </div>
                            <div className="space-y-3">
                                {activityItems.map((item, index) => (
                                    <div key={item.client_id} className="w-full rounded-[10px] border border-secondary-100 bg-white p-4 shadow-sm">
                                        <div className="flex flex-wrap items-start justify-between gap-4">
                                            <div>
                                                <div className="text-sm font-semibold text-gray-500">Activity {index + 1}</div>
                                                <div className="text-lg font-semibold text-gray-900">{item.service_name || 'Selected service'}</div>
                                                <div className="text-sm text-gray-600">
                                                    {item.season_name || 'Selected season'} • {item.booking_date} • {item.selected_slot?.time || item.start_time}
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <div className="text-xs font-medium text-gray-500">{item.rate_source_label}</div>
                                                <div className="text-lg font-semibold text-[#4D81E7]">R {Number(item.booking_total || 0).toFixed(2)}</div>
                                            </div>
                                        </div>
                                        <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-3">
                                            <div className="flex flex-wrap gap-3 text-sm text-gray-700">
                                                <span>Adults: {item.adults}</span>
                                                <span>Children: {item.children}</span>
                                                <span>Guides: {item.guides}</span>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => removeActivityItem(item.client_id)}
                                                className="btn-tool-red"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                    {/* Booking Details */}
                    <div className="mt-10 p-6 bg-white rounded shadow space-y-4">

                        <div className="flex items-center justify-start">
                            <div className="flex items-center mr-[128px]">
                                <label className="text-booking-subheading">Booking Details</label>
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
                                type="email"

                                onChange={(e) => setData('lead_email', e.target.value)}
                                errors={errors}
                            />
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <PhoneInput
                                label="Lead Mobile"
                                name="lead_mobile"
                                value={data.lead_mobile}
                                onChange={(val) => setData('lead_mobile', val)}
                                onCountryChange={(option) => setData('nationality_id', option?.countryId || '')}
                                countries={countries}
                                error={errors.lead_mobile}
                                defaultCountryCode="27"
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
                            {/* <div className="flex w-full items-center justify-start space-x-4 ">
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
                            </div> */}


                            <div className="flex flex-col items-center justify-center space-y-4 mb-7">
                                <div className="flex items-center space-x-[37px]">
                                    <span className="text-booking-lbl-main w-[160px] font-normal text-right">Total value of bookings</span>
                                    <div className="flex items-center justify-between w-[417px] h-[43px] bg-[#DBECFD4D] pl-[17px] py-[12.5px] rounded-[6px]">
                                        <span className="text-booking-lbl-main-sub text-[#4D81E7]">R {Number(bookingTotalForPayment || 0).toFixed(2)}</span>
                                    </div>
                                </div>
                                <div className="flex items-center space-x-[37px]">
                                    <span className="text-booking-lbl-main w-[160px] font-normal text-right">Deposit Due Today</span>
                                    <div className="flex items-center justify-between w-[417px] h-[43px] bg-[#DBECFD4D] pl-[17px] py-[12.5px] rounded-[6px]">
                                        <span className="text-booking-lbl-main-sub text-[#AFAFAF]">R {((Number(data?.deposit_due) || 0) * (Number(payableTotalForPayment) || 0)).toFixed(2)}</span>
                                    </div>
                                </div>
                                <div className="flex items-center space-x-[37px]">
                                    <span className="text-booking-lbl-main w-[160px] text-right">Total Due Today</span>
                                    <div className="flex items-center justify-between w-[417px] h-[43px] bg-[#DBECFD4D] pl-[17px] py-[12.5px] rounded-[6px]">
                                        <span className="text-booking-lbl-main-sub text-[#FF0000]">R {((Number(data?.due_today) || 0) * (Number(payableTotalForPayment) || 0)).toFixed(2)}</span>
                                    </div>
                                </div>
                                <div className="flex items-center space-x-[37px]">
                                    <span className="text-booking-lbl-main w-[160px] text-right text-[#4D81E7]">Total Rack Value</span>
                                    <div className="flex items-center justify-between w-[417px] h-[43px] bg-[#DBECFD4D] pl-[17px] py-[12.5px] rounded-[6px]">
                                        <span className="text-booking-lbl-main-sub text-[#4D81E7]">R {((Number(data?.rack_value) || 0) * (Number(bookingTotalForPayment) || 0)).toFixed(2)}</span>
                                    </div>
                                </div>
                                {payableTotalForPayment === 0 && bookingTotalForPayment > 0 && (
                                    <div className="w-[417px] rounded-[6px] bg-[#DBECFD4D] px-4 py-3 text-sm text-gray-600">
                                        External prepaid voucher: booking value is kept for reporting; customer balance is marked paid.
                                    </div>
                                )}
                            </div>


                            <div className="flex items-center mt-2 space-x-4">
                                <CustomCheckbox
                                    label="Pay Later"
                                    name="pay_later"
                                    checked={data.pay_later}
                                    onChange={handleChange}
                                    errors={errors}
                                />
                                {(settings?.show_send_invoice_checkbox ?? false) && (
                                    <CustomCheckbox
                                        label="Send Invoice"
                                        name="send_invoice"
                                        checked={data.send_invoice}
                                        onChange={handleChange}
                                        errors={errors}
                                    />
                                )}
                                {(settings?.show_send_quote_checkbox ?? false) && (
                                    <CustomCheckbox
                                        label="Send Quote"
                                        name="send_quote"
                                        checked={data.send_quote}
                                        onChange={handleChange}
                                        errors={errors}
                                    />
                                )}
                                <BlueButton
                                    title="Submit Booking"
                                    width={"w-[169px]"}
                                    type="button"
                                    onClick={(e) => handleSubmit(e)}
                                    disabled={processing}
                                />
                            </div>

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
                <Modal show={slotOverrideOpen} maxWidth="md" onClose={closeSlotOverrideModal}>
                    <div className="p-6">
                        <div className="flex items-start justify-between mb-4">
                            <div>
                                <h2 className="text-lg font-semibold">Add Internal Time Slot</h2>
                                <p className="text-sm text-gray-500 mt-1">
                                    This creates a date override for the selected day. Default weekly slots are included automatically.
                                </p>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <CustomInputText
                                title="Date"
                                name="override_date"
                                value={data.booking_date || ''}
                                disabled
                            />
                            <div className="mb-4 w-full">
                                <label className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]">
                                    Time Slot
                                </label>
                                <input
                                    type="time"
                                    value={slotOverrideData.time || ''}
                                    onChange={(e) => {
                                        setSlotOverrideData(prev => ({ ...prev, time: e.target.value }));
                                        if (slotOverrideErrors.time || slotOverrideErrors.time_slots || slotOverrideErrors.time_slot) {
                                            setSlotOverrideErrors(prev => ({ ...prev, time: undefined, time_slots: undefined, time_slot: undefined }));
                                        }
                                    }}
                                    className={`mt-1 block w-full h-[48px] ${slotOverrideTimeError ? 'border-red-500' : 'border-gray-300'} shadow-sm input-text rounded-[10px] hover:border-black active:border-blue-600 focus:outline-none focus:ring-0 focus:border-black`}
                                />
                                {slotOverrideTimeError && (
                                    <p className="text-red-500 text-sm mt-2">{slotOverrideTimeError}</p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <CustomInputText
                                title="Capacity Override (optional)"
                                name="capacity_override"
                                type="number"
                                value={slotOverrideData.capacity_override ?? ''}
                                onChange={(e) => setSlotOverrideData(prev => ({ ...prev, capacity_override: e.target.value }))}
                                errors={slotOverrideErrors}
                            />
                            <div className="flex items-end">
                                <CustomCheckbox
                                    label="Internal only (hide from public)"
                                    name="internal_only"
                                    checked={slotOverrideData.internal_only}
                                    onChange={(name, value) => setSlotOverrideData(prev => ({ ...prev, [name]: value }))}
                                    errors={{}}
                                />
                            </div>
                        </div>

                        <CustomTextarea
                            label="Note (optional)"
                            name="note"
                            value={slotOverrideData.note || ''}
                            onChange={(e) => setSlotOverrideData(prev => ({ ...prev, note: e.target.value }))}
                            error={slotOverrideErrors.note}
                            rows={4}
                        />

                        <div className="flex items-center justify-end gap-3 mt-4">
                            <WhiteButton
                                title="Cancel"
                                width="w-[120px]"
                                onClick={closeSlotOverrideModal}
                                disabled={slotOverrideSaving}
                                type="button"
                            />
                            <BlueButton
                                title={slotOverrideSaving ? 'Saving...' : 'Save Override'}
                                width="w-[160px]"
                                onClick={saveSlotOverride}
                                disabled={slotOverrideSaving}
                                type="button"
                            />
                        </div>
                    </div>
                </Modal>

                {selectedService?.id && data.booking_date && (
                    <PromoModal
                        isOpen={promoModalOpen}
                        serviceId={selectedService?.id}
                        bookingDate={data.booking_date}
                        onClose={handlePromoModalClose}
                        onSelect={handlePromoModalSelect}
                    />
                )}
            </div>


        </AuthenticatedLayout>
    );
}
