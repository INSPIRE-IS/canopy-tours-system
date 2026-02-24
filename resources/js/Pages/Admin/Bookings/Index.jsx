import BookingToolsModal from '@/Components/BookingToolsModal';
import CustomerToolsModal from '@/Components/CustomerToolsModal';
import GuestEmailModal from '@/Components/GuestEmailModal';
import BookingNotesModal from '@/Components/BookingNotesModal';
import CheckInOptionsModal from '@/Components/CheckInOptionsModal';
import FeedbackOptionsModal from '@/Components/FeedbackOptionsModal';
import Header from '@/Components/Header';
import { icons } from '@/icons';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import React, { useEffect, useRef, useState } from 'react';
import ManifestsModal from './ManifestsModal';
import ManifestModal from './ManifestModal';
import EditTimeSlotModal from './EditTimeSlotModal';
import ViewBookingModal from './ViewBookingModal';
import NavSelect from '@/Components/NavSelect';
import DatePicker from '@/Components/DatePicker';
import WhiteButton from '@/Components/WhiteButton';
import { addDays, addSeconds, endOfDay, endOfWeek, format, isSameDay, isValid, isWithinInterval, parse, parseISO, startOfDay, startOfWeek } from 'date-fns';
import ConfirmModal from '@/Components/ConfirmModal';
import { useToast } from '@/Context/ToastContext';
import CustomDatePicker from '@/Components/CustomDatePicker';
// import { useRoute } from 'ziggy-js';
// import { Ziggy } from '@/ziggy'; // the generated file

export default function Dashboard({ auth, bookings, activities, lookups, services }) {
    const VIEW_MODES = {
        DAY: 'day',
        RANGE: 'range',
        WEEK: 'week',
    };
    const { props } = usePage();
    const { flash } = props;
    const { showToast } = useToast();
    console.log("bookings", bookings)
    const [filteredBookings, setFilteredBookings] = useState(bookings);
    const [expanded, setExpanded] = useState(null); // track which booking is expanded
    const [customerToolsTarget, setCustomerToolsTarget] = useState(null); // { customer, booking, position }
    const [bookingToolsTarget, setBookingToolsTarget] = useState(null); // { customer, position }
    const [guestEmailTarget, setGuestEmailTarget] = useState(null); // { customer, position }
    const [bookingNotesTarget, setBookingNotesTarget] = useState(null);
    const [isEmailModalOpen, setIsEmailModalOpen] = useState(false);
    const [isViewBookingModalOpen, setIsViewBookingModalOpen] = useState(false);
    const [isEditTimeSlotModalOpen, setIsEditTimeSlotModalOpen] = useState(false);
    const [isManifestModalOpen, setIsManifestModalOpen] = useState(false);
    const [isManifestsModalOpen, setIsManifestsModalOpen] = useState(false);
    const [selectedBooking, setSelectedBooking] = useState(null);
    const [selectedManifests, setSelectedManifests] = useState(null);
    const [selectedManifest, setSelectedManifest] = useState(null);
    const today = format(new Date(), 'yyyy-MM-dd');
    const [selectedDate, setSelectedDate] = useState(() => {
        return sessionStorage.getItem('bookings.selectedDate') || format(new Date(), 'yyyy-MM-dd');
    });
    const [rangeStart, setRangeStart] = useState(() => {
        return sessionStorage.getItem('bookings.rangeStart') || format(new Date(), 'yyyy-MM-dd');
    });
    const [rangeEnd, setRangeEnd] = useState(() => {
        return sessionStorage.getItem('bookings.rangeEnd') || format(new Date(), 'yyyy-MM-dd');
    });
    const [viewMode, setViewMode] = useState(() => {
        return sessionStorage.getItem('bookings.viewMode') || VIEW_MODES.DAY;
    });
    const [selectedServiceId, setSelectedServiceId] = useState(() => {
        return sessionStorage.getItem('bookings.selectedServiceId') || '0';
    });
    const [showServices, setShowServices] = useState(false);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [confirmAction, setConfirmAction] = useState(null); // { type: 'booking' | 'slot', booking }
    const [slotRows, setSlotRows] = useState([]);
    const [viewDocsIndex, setViewDocsIndex] = useState(null);
    const [checkInTarget, setCheckInTarget] = useState(null);
    const [feedbackTarget, setFeedbackTarget] = useState(null);
    const servicesRef = useRef();
    useEffect(() => {
        if (flash?.success) {
            showToast(flash.success, "success");
        }
        if (flash?.error) {
            showToast(flash.error, "error");
        }
    }, [flash, showToast]);
    const openBookingToolsModal = (event, booking) => {
        event.preventDefault();
        console.log("booking----------tool-------- ", booking)

        const rect = event.target.getBoundingClientRect();
        setBookingToolsTarget({
            booking,
            position: {
                top: rect.top + window.scrollY + 30, // offset below the button
                left: rect.left + window.scrollX,
            }
        });
    };
    const openCustomerToolsModal = (event, booking, customer, indexKey) => {
        event.preventDefault();

        const rect = event.target.getBoundingClientRect();
        setCustomerToolsTarget({
            booking,
            customer,
            indexKey,
            position: {
                top: rect.top + window.scrollY + 30,
                left: rect.left + window.scrollX,
            }
        });
    };

    const handleConfirmCancel = () => {
        if (!confirmAction?.booking) {
            setConfirmOpen(false);
            return;
        }

        if (confirmAction.type === 'slot') {
            const payload = {
                booking_date: confirmAction.booking.booking_date,
                inventory_time_slot_id: confirmAction.booking.inventory_time_slot_id,
                service_id: confirmAction.booking.service_id,
            };
            router.post(route('admin.bookings.cancel-slot'), payload, {
                onSuccess: () => {
                    setConfirmAction(null);
                    setConfirmOpen(false);
                    setSelectedBooking(null);
                    router.visit(route('admin.bookings.index'));
                },
                onError: () => {
                    setConfirmAction(null);
                    setConfirmOpen(false);
                    setSelectedBooking(null);
                }
            });
            return;
        }

        router.delete(route('admin.bookings.destroy', confirmAction.booking.id), {
            onSuccess: () => {
                setConfirmAction(null);
                setConfirmOpen(false);
                setSelectedBooking(null);
                router.visit(route('admin.bookings.index'));
            },
            onError: () => {
                setConfirmAction(null);
                setConfirmOpen(false);
                setSelectedBooking(null);
            }
        });
    };

    const getSelectedBookingDate = () => {
        if (viewMode === VIEW_MODES.RANGE) {
            return rangeStart || today;
        }
        return selectedDate || today;
    };

    const openCheckInOptions = (booking) => {
        if (!booking?.booking_item_id || !booking?.reference) {
            showToast('Booking item not found for check-in.', 'error');
            return;
        }
        setCheckInTarget({
            booking_item_id: booking.booking_item_id,
            reference: booking.reference,
            lead_name: booking.lead_name,
            lead_phone: booking.lead_phone,
            lead_email: booking.lead_email,
        });
    };

    const openFeedbackOptions = (booking) => {
        if (!booking?.booking_item_id || !booking?.reference) {
            showToast('Booking item not found for feedback.', 'error');
            return;
        }

        const indemnityCustomer = booking?.customers?.find((cust) => cust?.is_indemnity);
        if (!indemnityCustomer) {
            showToast('Feedback links are available after check-in is completed.', 'error');
            return;
        }

        setFeedbackTarget({
            booking_item_id: booking.booking_item_id,
            reference: booking.reference,
            customer_id: indemnityCustomer.customer_id,
            lead_name: booking.lead_name,
            lead_phone: booking.lead_phone,
            lead_email: booking.lead_email,
        });
    };


    function BookingToolClick(tool, booking) {

        console.log("-------tool clicked-------", tool);

        if (tool === 'remove_resource') {
            router.get(route('admin.bookings.remove_resource'));
        }


        if (tool === 'cancel_bookings') {
            setConfirmOpen(true);
            setSelectedBooking(booking);
            setConfirmAction({ type: 'slot', booking });
        }
    };

    const ViewBookingClick = (booking) => {
        //router.get(route('admin.bookings.edit', booking.id));
        setSelectedBooking(booking);
        setIsViewBookingModalOpen(true);
    };

    const CustomerToolClick = (tool, booking, customer) => {
        if (tool === 'check_in') {
            openCheckInOptions(booking);
        }
        if (tool === 'feedback') {
            openFeedbackOptions(booking);
        }
        if (tool === 'resend_invoice') {
            router.get(route('admin.invoices.resend-invoice', { booking_id: booking.id }));
        }
        if (tool === 'manifest') {
            setSelectedManifests(booking?.customers);
            setSelectedManifest(customer);
            setIsManifestModalOpen(true);
        }
        if (tool === 'customer_invoice') {
            if (!customer?.files || customer.files.length === 0) {
                prompt('No invoice found');
                return;
            }

            const file = customer.files.find(f => f.table_name == 'invoices');
            if (!file) {
                prompt('No invoice found');
                return;
            }
            const fileUrl = `${file.path}`;
            window.open(fileUrl, '_blank', 'noopener,noreferrer');
        }
        if (tool === 'edit_booking') {
            router.get(route('admin.bookings.edit', booking.id));
        }
        if (tool === 'edit_timeslot') {
            //router.get(route('admin.bookings.edit', booking.id));
            // setSelectedBooking({
            //     ...booking,
            //     booking_id: booking?.booking_id ?? booking?.id,
            // });
            setSelectedBooking(booking);
            setIsEditTimeSlotModalOpen(true);
        }

        if (tool === 'message_guest') {
            setGuestEmailTarget(booking);
            setIsEmailModalOpen(true);
        }
        if (tool === 'booking_notes') {
            setBookingNotesTarget(booking);
        }
        // if (tool === 'message_staff') {
        //     router.get(route('admin.messenger.createTopic'));
        // }
        if (tool === 'check_in_progress') {
            openCheckInOptions(booking);
        }
        if (tool === 'manifest_edit') {
            //router.get(route('admin.bookings.manifest_edit'));
            setSelectedManifests(booking.customers);
            setIsManifestsModalOpen(true);
        }
        if (tool === 'cancel_booking') {
            setConfirmOpen(true);
            setSelectedBooking(booking);
            setConfirmAction({ type: 'booking', booking });
        }
    };

    const getTimeRange = (startTime, duration) => {
        if (!startTime || !duration) {
            return { start: null, end: null };
        }

        const start = parse(startTime, 'HH:mm:ss', new Date());
        const [hours, minutes, seconds] = duration.split(':').map((v) => Number(v || 0));
        const totalSeconds = (hours * 3600) + (minutes * 60) + seconds;
        const end = addSeconds(start, totalSeconds);
        return {
            start: format(start, 'HH:mm'),
            end: format(end, 'HH:mm'),
        };
    };

    const formatTimeValue = (timeStr) => {
        if (!timeStr) return null;
        return timeStr.slice(0, 5);
    };

    const timeToMinutes = (timeStr) => {
        if (!timeStr) return Number.MAX_SAFE_INTEGER;
        const [hours, minutes] = timeStr.split(':').map((v) => Number(v || 0));
        return (hours * 60) + minutes;
    };

    const isDateWithinSeason = (dateStr, season) => {
        if (!dateStr || !season?.start_date || !season?.end_date) return false;
        const date = parseISO(dateStr);
        const start = parseISO(season.start_date);
        const end = parseISO(season.end_date);
        if (!isValid(date) || !isValid(start) || !isValid(end)) return false;
        return isWithinInterval(date, { start: startOfDay(start), end: endOfDay(end) });
    };

    const handleExportCSV = (bookings) => {
        const headers = [
            "Booking ID",
            "Reference",
            "Lead Name",
            "Service",
            "Time",
            "Booking Date",
            "Booking Pax",
            "Total Price",
            "Total Paid",
            "Total Due",
            "Customer Name",
            "Customer Email",
            "Customer Phone",
            "Customer Age",
            "Customer Type",
            "Customer Price",
            "Customer Paid",
            "Customer Due",
            "Notes",
        ];

        const rows = [];

        bookings.forEach((booking) => {
            // Booking-level row
            rows.push([
                booking.id,
                booking.reference,
                booking.lead_name,
                booking.service,
                booking.time,
                booking.booking_date,
                booking.bookingPax,
                booking.price,
                booking.paid,
                booking.due,
                "", "", "", "", "", "", "", "", "" // empty customer columns
            ]);

            // Customer rows indented
            booking.customers.forEach((cust, idx) => {
                rows.push([
                    "", // blank booking ID for nesting effect
                    booking.reference, // keep reference if you want traceability
                    idx === 0 ? "Customers:" : "", // only label once
                    "", "", "", "", "", "", "",
                    cust.full_name || "Unknown Customer",
                    cust.email_address || "Unknown Email",
                    cust.contact_number || "Unknown Phone",
                    cust.age || "Unknown Age",
                    cust.type || "Unknown Type",
                    cust.price,
                    cust.paid,
                    cust.due,
                    cust.notes || "None",
                ]);
            });
        });

        const csvContent =
            "data:text/csv;charset=utf-8," +
            [headers, ...rows]
                .map((e) =>
                    e.map((v) => `"${v ?? ""}"`).join(",")
                )
                .join("\n");

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "bookings_export.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (servicesRef.current && !servicesRef.current.contains(event.target)) {
                setShowServices(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const handleFilterBookings = (activity_id) => {
        setSelectedServiceId(activity_id);
    };

    const normalizeBookingDate = (bookingDate) => {
        if (!bookingDate) return null;
        const parsed = parseISO(bookingDate);
        return isValid(parsed) ? parsed : null;
    };

    const applyFilters = () => {
        let next = Array.isArray(bookings) ? bookings : [];
        if (selectedServiceId !== '0') {
            next = next.filter(booking => Number(booking.service_id) === Number(selectedServiceId));
        }

        if (viewMode === VIEW_MODES.DAY) {
            const selected = selectedDate ? parseISO(selectedDate) : null;
            const day = selected ? startOfDay(selected) : null;
            next = next.filter(booking => {
                const bookingDate = normalizeBookingDate(booking.booking_date);
                return bookingDate && day && isSameDay(bookingDate, day);
            });
        } else if (viewMode === VIEW_MODES.RANGE) {
            const startDate = rangeStart ? parseISO(rangeStart) : null;
            const endDate = rangeEnd ? parseISO(rangeEnd) : null;
            const start = startDate && endDate && startDate > endDate ? endDate : startDate;
            const end = startDate && endDate && startDate > endDate ? startDate : endDate;
            next = next.filter(booking => {
                const bookingDate = normalizeBookingDate(booking.booking_date);
                if (!bookingDate || !start || !end) return false;
                return isWithinInterval(bookingDate, {
                    start: startOfDay(start),
                    end: endOfDay(end),
                });
            });
        } else if (viewMode === VIEW_MODES.WEEK) {
            const selected = selectedDate ? parseISO(selectedDate) : new Date();
            const weekStart = startOfWeek(selected, { weekStartsOn: 1 });
            const weekEnd = endOfWeek(selected, { weekStartsOn: 1 });
            next = next.filter(booking => {
                const bookingDate = normalizeBookingDate(booking.booking_date);
                return bookingDate
                    ? isWithinInterval(bookingDate, {
                        start: startOfDay(weekStart),
                        end: endOfDay(weekEnd),
                    })
                    : false;
            });
        }

        return next;
    };

    const buildSlotRows = () => {
        const items = Array.isArray(filteredBookings) ? filteredBookings : [];
        const grouped = new Map();

        items.forEach((item) => {
            const key = `${item.booking_date}|${item.inventory_time_slot_id}|${item.service_id}`;
            const existing = grouped.get(key) || {
                key,
                booking_date: item.booking_date,
                inventory_time_slot_id: item.inventory_time_slot_id,
                service_id: item.service_id,
                service: item.service,
                time_start: item.time_start,
                time_end: item.time_end,
                totalPax: 0,
                maxPax: item.maxPax ?? 0,
                totalPrice: 0,
                totalPaid: 0,
                totalDue: 0,
                bookings: [],
            };

            existing.totalPax += Number(item.bookingPax || 0);
            existing.totalPrice += Number(item.price || 0);
            existing.totalPaid += Number(item.paid || 0);
            existing.totalDue += Number(item.due || 0);
            existing.bookings.push(item);
            grouped.set(key, existing);
        });

        if (viewMode === VIEW_MODES.DAY) {
            const day = selectedDate || today;
            services?.forEach((service) => {
                const activeSeason = service?.seasons?.find((season) => isDateWithinSeason(day, season));
                if (!activeSeason) return;
                const slots = activeSeason?.inventory?.inventory_time_slots || [];
                slots.forEach((slot) => {
                    const key = `${day}|${slot.id}|${service.id}`;
                    if (grouped.has(key)) return;
                    const { start, end } = getTimeRange(slot.start_time, service.duration);
                    grouped.set(key, {
                        key,
                        booking_date: day,
                        inventory_time_slot_id: slot.id,
                        service_id: service.id,
                        service: service.name,
                        time_start: start,
                        time_end: end,
                        totalPax: 0,
                        maxPax: service.capacity ?? 0,
                        totalPrice: 0,
                        totalPaid: 0,
                        totalDue: 0,
                        bookings: [],
                    });
                });
            });
        }

        const rows = Array.from(grouped.values());
        rows.sort((a, b) => {
            const aTime = timeToMinutes(a.time_start);
            const bTime = timeToMinutes(b.time_start);
            if (aTime !== bTime) return aTime - bTime;
            return (a.service || '').localeCompare(b.service || '');
        });
        return rows;
    };

    useEffect(() => {
        setFilteredBookings(applyFilters());
    }, [bookings, selectedServiceId, viewMode, selectedDate, rangeStart, rangeEnd]);

    useEffect(() => {
        setSlotRows(buildSlotRows());
    }, [filteredBookings, services, viewMode, selectedDate]);

    useEffect(() => {
        sessionStorage.setItem('bookings.selectedDate', selectedDate || '');
    }, [selectedDate]);

    useEffect(() => {
        sessionStorage.setItem('bookings.rangeStart', rangeStart || '');
    }, [rangeStart]);

    useEffect(() => {
        sessionStorage.setItem('bookings.rangeEnd', rangeEnd || '');
    }, [rangeEnd]);

    useEffect(() => {
        sessionStorage.setItem('bookings.viewMode', viewMode || '');
    }, [viewMode]);

    useEffect(() => {
        sessionStorage.setItem('bookings.selectedServiceId', selectedServiceId || '0');
    }, [selectedServiceId]);

    const handleTodayClick = () => {

        if (viewMode === VIEW_MODES.RANGE) {
            setRangeStart(today);
            setRangeEnd(today);
            return;
        }
        setSelectedDate(today);
    };

    const handleCloseModal = () => {
        setGuestEmailTarget(null);
        setBookingNotesTarget(null);
        setIsEditTimeSlotModalOpen(false);
        setIsViewBookingModalOpen(false);
        setSelectedBooking(null);
        setIsEmailModalOpen(false);
        window.location.reload();
    };
    const handleCheveronDateChange = (direction) => {
        const baseDate = selectedDate ? parseISO(selectedDate) : new Date();
        const nextDate = addDays(baseDate, direction);
        setSelectedDate(format(nextDate, 'yyyy-MM-dd'));
    };
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Bookings</h2>}
        >
            <Head title="Dashboard" />
            <div className="bg-white min-h-[80%] p-6 ">
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <span>
                        <span className="font-semibold text-lg text-gray-800 block text-start">Welcome Back, {auth?.user?.name}</span>
                        {/* <span className="font-semibold text-lg text-gray-800 block text-start">We have 2 Pending Tasks and 5 New Bookings.</span> */}

                    </span>
                    <div className="flex flex-wrap items-center gap-4">
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="flex flex-wrap items-center gap-2">
                                <WhiteButton
                                    title="Today"
                                    width="w-[90px]"
                                    textColor={selectedDate === today ? 'text-white' : 'text-primary-14'}
                                    className={selectedDate === today ? 'bg-blue-gradient-overlay border-[#3863C6]' : ''}
                                    onClick={handleTodayClick}
                                />
                                <span className='flex flex-row gap-1'>
                                    <button className='px-3 py-2 border border-gray-400 rounded-lg ' onClick={() => handleCheveronDateChange(-1)}>{icons.cheveron_left}</button>
                                    <WhiteButton
                                        title="Day"
                                        width="w-[90px]"
                                        textColor={viewMode === VIEW_MODES.DAY ? 'text-white' : 'text-primary-14'}
                                        className={viewMode === VIEW_MODES.DAY ? 'bg-blue-gradient-overlay border-[#3863C6]' : ''}
                                        onClick={() => setViewMode(VIEW_MODES.DAY)}
                                    />
                                    <button className='px-3 py-2 border border-gray-400 rounded-lg ' onClick={() => handleCheveronDateChange(1)}>{icons.cheveron_right}</button>
                                </span>
                                <WhiteButton
                                    title="Date Range"
                                    width="w-[120px]"
                                    textColor={viewMode === VIEW_MODES.RANGE ? 'text-white' : 'text-primary-14'}
                                    className={viewMode === VIEW_MODES.RANGE ? 'bg-blue-gradient-overlay border-[#3863C6]' : ''}
                                    onClick={() => setViewMode(VIEW_MODES.RANGE)}
                                />
                                <WhiteButton
                                    title="Week"
                                    width="w-[90px]"
                                    textColor={viewMode === VIEW_MODES.WEEK ? 'text-white' : 'text-primary-14'}
                                    className={viewMode === VIEW_MODES.WEEK ? 'bg-blue-gradient-overlay border-[#3863C6]' : ''}
                                    onClick={() => setViewMode(VIEW_MODES.WEEK)}
                                />
                            </div>

                        </div>
                        <div className="flex gap-2">
                            <button onClick={() => router.get(route('admin.bookings.create'))} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                                <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                                <span className="text-btn-outline-gray">New Booking</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => router.get(route('booking-questionnaire.index', { booking_date: getSelectedBookingDate() }))}
                                className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-red-gradient-overlay border-[#C65468] text-white shadow-inset-white cursor-pointer"
                            >
                                <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                                <span className="text-btn-outline-gray">Check in</span>
                            </button>
                            <button onClick={() => router.get(route('front.feedback-questionnaire.index', { booking_date: getSelectedBookingDate() }))} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-red-gradient-overlay border-[#C65468] text-white shadow-inset-white cursor-pointer">
                                <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                                <span className="text-btn-outline-gray">Feedback</span>
                            </button>
                        </div>
                    </div>
                </div>
                {/* bottom */}
                <div className="bg-white min-h-[500px] ">
                    <div className="bg-white overflow-visible min-h-[500px] shadow-sm sm:rounded-lg">
                        <div className="flex justify-between items-center px-4 pt-4 pb-[9px] ">
                            <span className='flex items-center gap-2'>
                                <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                                <span className="font-semibold text-lg text-gray-800 block text-start">Bookings</span>
                            </span>
                            <span className="flex gap-2">

                                {viewMode === VIEW_MODES.DAY && (
                                    <div className="w-[200px]">
                                        <CustomDatePicker
                                            label="Date"
                                            name='selected_date'
                                            value={selectedDate}
                                            onChange={(e) => { setSelectedDate(e.target.value); }}

                                        />
                                    </div>
                                )}
                                {viewMode === VIEW_MODES.RANGE && (
                                    <div className="flex flex-wrap gap-3">
                                        <div className="w-[200px]">
                                            <CustomDatePicker
                                                label="Start Date"
                                                name='range_start'
                                                value={rangeStart}
                                                onChange={(e) => { setRangeStart(e.target.value); }}

                                            />
                                        </div>
                                        <div className="w-[200px]">
                                            <CustomDatePicker
                                                label="End Date"
                                                name='range_end'
                                                value={rangeEnd}
                                                onChange={(e) => { setRangeEnd(e.target.value); }}

                                            />
                                        </div>
                                    </div>
                                )}
                                {viewMode === VIEW_MODES.WEEK && (
                                    <div className="text-[#161618] font-poppins text-[14px] font-semibold leading-[130%] h-[40px] px-4 py-2 rounded-xl border-[3px] border-primary-14 cursor-pointer">
                                        Week of {format(startOfWeek(selectedDate ? parseISO(selectedDate) : new Date(), { weekStartsOn: 1 }), 'yyyy-MM-dd')} to {format(endOfWeek(selectedDate ? parseISO(selectedDate) : new Date(), { weekStartsOn: 1 }), 'yyyy-MM-dd')}
                                    </div>
                                )}

                                <div className="relative" ref={servicesRef}>
                                    <button onClick={() => setShowServices(!showServices)}
                                        className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] border-primary-14 text-primary-14 cursor-pointer">
                                        <img src="/assets/icons/filter.svg" alt="filter" className="w-5 h-5" />
                                        <span className="text-btn-outline-gray">Filter</span>
                                    </button>
                                    {showServices && (
                                        <div className="absolute top-full mt-2 w-64 bg-white border rounded shadow-lg p-4 z-50">
                                            <h3 className="text-sm font-semibold text-gray-700 mb-2">Choose a Service</h3>
                                            <NavSelect key="0" name={'All'} onChange={(e) => handleFilterBookings('0')} />
                                            {activities?.map((activity, i) => {
                                                return (
                                                    <NavSelect key={i} name={activity.name} onChange={() => handleFilterBookings(activity.id)} />
                                                )
                                            })}
                                        </div>
                                    )}
                                </div>

                                <button className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] border-primary-14 text-primary-14 cursor-pointer">
                                    <img src="/assets/icons/search-normal.svg" alt="bookings" className="w-5 h-5" />
                                    <span className="text-btn-outline-gray">Search here</span>
                                </button>

                                <button onClick={() => handleExportCSV(filteredBookings)} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] border-primary-14 text-primary-14 cursor-pointer">
                                    <img src="/assets/icons/export.svg" alt="export" className="w-5 h-5" />
                                    <span className="text-btn-outline-gray">Export</span>
                                </button>
                            </span>
                        </div>
                        <div className="flex justify-between items-center px-4 pb-[9px] ">
                            {/* table */}

                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50 text-left text-gray-500">
                                    <tr >
                                        <th className="w-[164px] p-3 flex justify-between items-center gap-2">
                                            <span className='flex items-center gap-2'>
                                                <span className="w-5 h-5 block border border-gray-500 rounded-md"></span>
                                                Time
                                            </span>
                                        </th>
                                        <th className="w-[95px] p-3">PAX</th>
                                        <th className="w-[268px] p-3">Service</th>
                                        <th className="w-[132px] p-3">Price</th>
                                        <th className="w-[111px] p-3">Paid</th>
                                        <th className="w-[107px] p-3">Due</th>
                                        <th className="w-[185px] p-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {slotRows?.map((row, i) => {
                                        const startLabel = formatTimeValue(row.time_start);
                                        const endLabel = formatTimeValue(row.time_end);
                                        const timeLabel = startLabel && endLabel
                                            ? `${startLabel} - ${endLabel}`
                                            : 'Time N/A';
                                        const nextRow = slotRows?.[i + 1];
                                        const isLastInTimeGroup = !nextRow || formatTimeValue(nextRow.time_start) !== startLabel;
                                        return (
                                            <React.Fragment key={row.key}>
                                                <tr className="hover:bg-gray-50">
                                                    <td className="w-[164px] p-3">
                                                        <div className="flex justify-between items-center gap-2">
                                                            <div className="flex items-center gap-2">
                                                                <span className="w-5 h-5 block border border-gray-500 rounded-md"></span>
                                                                {timeLabel}
                                                            </div>
                                                            <img
                                                                src={`/assets/icons/${expanded === row.key ? 'arrow-down' : 'arrow-left'}.svg`}
                                                                alt="toggle"
                                                                className="w-5 h-5 cursor-pointer"
                                                                onClick={() => setExpanded(expanded === row.key ? null : row.key)}
                                                            />
                                                        </div>
                                                    </td>
                                                    <td className="w-[95px] p-3">{row.totalPax} ({row.maxPax})</td>
                                                    <td className="w-[268px] p-3">{row.service}</td>
                                                    <td className="w-[132px] p-3">R{Number(row.totalPrice || 0).toFixed(2)}</td>
                                                    <td className="w-[111px] p-3">R{Number(row.totalPaid || 0).toFixed(2)}</td>
                                                    <td className="w-[107px] p-3">
                                                        <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                                        ${row.totalDue > 0 ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'}`}>
                                                            - {Number(row.totalDue || 0) === 0 ? 'R0.00' : `R${Number(row.totalDue || 0).toFixed(2)}`}
                                                        </span>
                                                    </td>
                                                    <td className="w-[185px] p-3 flex gap-2">
                                                        {Number(row.totalPax || 0) < Number(row.maxPax || 0) && (
                                                            <button
                                                                type='button'
                                                                onClick={() => router.get(route('admin.bookings.create', {
                                                                    service_id: row.service_id,
                                                                    booking_date: row.booking_date,
                                                                    start_time: row.inventory_time_slot_id,
                                                                    view_mode: 'day',
                                                                    start_date: row.booking_date,
                                                                }))}
                                                                className="btn-tool"
                                                            >
                                                                {icons.plus_alt} Add
                                                            </button>
                                                        )}
                                                        {row.bookings.length > 0 && (
                                                            <button type='button' onClick={(e) => openBookingToolsModal(e, row.bookings[0])} className="btn-tool">{icons.tools} Tools</button>
                                                        )}
                                                    </td>
                                                </tr>

                                                {expanded === row.key && (
                                                    row.bookings.length > 0 ? (
                                                        <>
                                                            {row.bookings.map((booking, index) => (
                                                                <tr key={`${row.key}-${index}`} className="bg-gray-50">
                                                                    <td className="w-[164px] p-3">
                                                                        <Link
                                                                            href={route('admin.bookings.add', booking.id)}
                                                                            className="text-blue-600 hover:underline"
                                                                        >
                                                                            {booking.reference}
                                                                        </Link>
                                                                    </td>
                                                                    <td className="w-[95px] p-3">{booking.bookingPax}</td>
                                                                    <td className="w-[268px] p-3">
                                                                        <div className="text-sm text-gray-800 font-medium">{booking.lead_name ?? 'Unknown Lead'}</div>
                                                                        <div className="text-sm text-gray-600">{booking.lead_phone ?? 'Unknown Phone'}</div>
                                                                    </td>
                                                                    <td className="w-[132px] p-3">R{Number(booking.price || 0).toFixed(2)}</td>
                                                                    <td className="w-[111px] p-3">R{Number(booking.paid || 0).toFixed(2)}</td>
                                                                    <td className="w-[107px] p-3">
                                                                        <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${booking.due > 0 ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'}`}>
                                                                            - {Number(booking.due || 0) === 0 ? 'R0.00' : `R${Number(booking.due || 0).toFixed(2)}`}
                                                                        </span>
                                                                    </td>
                                                                    <td className="w-[185px] p-3">
                                                                        <div className="flex flex-col lg:flex-row lg:items-center gap-2">
                                                                            <button
                                                                                type="button"
                                                                                onClick={() => router.get(route('admin.bookings.add', booking.id))}
                                                                                className="btn-tool"
                                                                            >
                                                                                {icons.eye} View
                                                                            </button>
                                                                            <div
                                                                                className="relative"
                                                                                onClick={() =>
                                                                                    setViewDocsIndex(viewDocsIndex === `${row.key}-${index}` ? null : `${row.key}-${index}`)
                                                                                }
                                                                            >
                                                                                <button type='button' className="btn-tool">{icons.docs} Docs</button>
                                                                                <div>
                                                                                    {viewDocsIndex === `${row.key}-${index}` && (
                                                                                        <div
                                                                                            className="absolute right-0 mt-3 w-72 bg-white border border-gray-200 rounded-2xl shadow-lg z-50 overflow-hidden"
                                                                                        >
                                                                                            <div className="flex justify-between items-center mb-2">
                                                                                                <h3 className="text-tool-card-title">Docs</h3>
                                                                                                <button type='button'
                                                                                                    onClick={(e) => {
                                                                                                        e.stopPropagation();
                                                                                                        setViewDocsIndex(null);
                                                                                                    }}
                                                                                                    className="text-gray-400 hover:text-gray-600 text-sm">{icons.close}</button>
                                                                                            </div>

                                                                                            <div className="max-h-64 overflow-y-auto">
                                                                                                {booking?.customers?.[0]?.files?.length > 0 ? (
                                                                                                    booking.customers[0].files.map((file) => (
                                                                                                        <a
                                                                                                            key={file.id}
                                                                                                            href={file.path}
                                                                                                            target="_blank"
                                                                                                            rel="noopener noreferrer"
                                                                                                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                                                                        >
                                                                                                            {file.file_name}
                                                                                                        </a>
                                                                                                    ))
                                                                                                ) : (
                                                                                                    <p className="p-4 text-gray-500 text-sm">No documents uploaded.</p>
                                                                                                )}
                                                                                            </div>
                                                                                        </div>
                                                                                    )}
                                                                                </div>
                                                                            </div>
                                                                            <button
                                                                                type="button"
                                                                                onClick={(e) => openCustomerToolsModal(e, booking, booking?.customers?.[0], `${row.key}-${index}`)}
                                                                                className="btn-tool"
                                                                            >
                                                                                {icons.tools} Tools
                                                                            </button>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            ))}
                                                            <tr className="bg-gray-50">
                                                                <td className="w-[164px] p-3 font-semibold">Totals:</td>
                                                                <td className="w-[95px] p-3 font-semibold">{row.totalPax}</td>
                                                                <td className="w-[268px] p-3"></td>
                                                                <td className="w-[132px] p-3 font-semibold">R{Number(row.totalPrice || 0).toFixed(2)}</td>
                                                                <td className="w-[111px] p-3 font-semibold">R{Number(row.totalPaid || 0).toFixed(2)}</td>
                                                                <td className="w-[107px] p-3 font-semibold">R{Number(row.totalDue || 0).toFixed(2)}</td>
                                                                <td className="w-[185px] p-3"></td>
                                                            </tr>
                                                        </>
                                                    ) : (
                                                        <tr className="bg-gray-50">
                                                            <td colSpan={7} className="p-4 text-sm text-gray-500">No bookings in this slot.</td>
                                                        </tr>
                                                    )
                                                )}
                                                {isLastInTimeGroup && (
                                                    <tr className="bg-white">
                                                        <td colSpan={7} className="p-0">
                                                            <div className="border-b-2 border-gray-200 mt-2"></div>
                                                        </td>
                                                    </tr>
                                                )}
                                            </React.Fragment>
                                        );
                                    })}
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>

                {bookingToolsTarget && (
                    <BookingToolsModal
                        booking={bookingToolsTarget.booking}
                        position={bookingToolsTarget.position}
                        onClose={() => setBookingToolsTarget(null)}
                        BookingToolClick={BookingToolClick}
                    />
                )}

                {customerToolsTarget && (
                    <CustomerToolsModal
                        index={customerToolsTarget.indexKey}
                        booking={customerToolsTarget.booking}
                        customer={customerToolsTarget.customer}
                        position={customerToolsTarget.position}
                        onClose={() => setCustomerToolsTarget(null)}
                        CustomerToolClick={(tool, _index, booking, customer) => CustomerToolClick(tool, booking, customer)}
                    />
                )}

                {confirmOpen && (
                    <ConfirmModal
                        title={confirmAction?.type === 'slot' ? 'Cancel all bookings in this slot?' : 'Cancel this booking?'}
                        message={confirmAction?.type === 'slot'
                            ? 'This will cancel all bookings for this date, time slot, and service.'
                            : 'This action cannot be undone.'}
                        confirmText="Yes, cancel"
                        cancelText="Keep Booking"
                        onConfirm={handleConfirmCancel}
                        onClose={() => {
                            setConfirmOpen(false);
                            setConfirmAction(null);
                        }}
                    />
                )}
                <ViewBookingModal
                    isOpen={isViewBookingModalOpen}
                    onClose={handleCloseModal}
                    mode='edit'
                    booking={selectedBooking}
                    services={services}
                />
                <EditTimeSlotModal
                    isOpen={isEditTimeSlotModalOpen}
                    onClose={handleCloseModal}
                    mode='edit'
                    booking={selectedBooking}
                    services={services}
                />

                <GuestEmailModal
                    isOpen={isEmailModalOpen}
                    onClose={handleCloseModal}
                    mode='create'
                    booking={guestEmailTarget}
                />
                <BookingNotesModal
                    isOpen={!!bookingNotesTarget}
                    onClose={() => setBookingNotesTarget(null)}
                    booking={bookingNotesTarget}
                />

                <ManifestModal
                    isOpen={isManifestModalOpen}
                    onClose={handleCloseModal}
                    mode="edit"
                    customers={selectedManifests}
                    customer={selectedManifest}
                    tot_adults='0'
                    tot_children='0'
                />

                <ManifestsModal
                    isOpen={isManifestsModalOpen}
                    onClose={handleCloseModal}
                    mode="edit"
                    customers={selectedManifests}
                    tot_adults='0'
                    tot_children='0'
                />

                <CheckInOptionsModal
                    isOpen={!!checkInTarget}
                    onClose={() => setCheckInTarget(null)}
                    booking={checkInTarget}
                    contextLabel="booking"
                />

                <FeedbackOptionsModal
                    isOpen={!!feedbackTarget}
                    onClose={() => setFeedbackTarget(null)}
                    booking={feedbackTarget}
                    contextLabel="booking"
                />


            </div>
        </AuthenticatedLayout>
    );
}







