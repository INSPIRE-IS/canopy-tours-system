import BookingToolsModal from '@/Components/BookingToolsModal';
import CustomerToolsModal from '@/Components/CustomerToolsModal';
import GuestEmailModal from '@/Components/GuestEmailModal';
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
import { endOfDay, endOfWeek, format, isSameDay, isValid, isWithinInterval, parseISO, startOfDay, startOfWeek } from 'date-fns';
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
    const [toolsCustomer, setToolsCustomer] = useState(null); // null or { id: ..., name: ... }
    const [customerToolsTarget, setCustomerToolsTarget] = useState(null); // { customer, position }
    const [bookingToolsTarget, setBookingToolsTarget] = useState(null); // { customer, position }
    const [guestEmailTarget, setGuestEmailTarget] = useState(null); // { customer, position }
    const [isEmailModalOpen, setIsEmailModalOpen] = useState(false);
    const [isViewBookingModalOpen, setIsViewBookingModalOpen] = useState(false);
    const [isEditTimeSlotModalOpen, setIsEditTimeSlotModalOpen] = useState(false);
    const [isManifestModalOpen, setIsManifestModalOpen] = useState(false);
    const [isManifestsModalOpen, setIsManifestsModalOpen] = useState(false);
    const [selectedBooking, setSelectedBooking] = useState(null);
    const [selectedManifests, setSelectedManifests] = useState(null);
    const [selectedManifest, setSelectedManifest] = useState(null);
    const [selectedDate, setSelectedDate] = useState(format(new Date(), 'yyyy-MM-dd'));
    const [rangeStart, setRangeStart] = useState(format(new Date(), 'yyyy-MM-dd'));
    const [rangeEnd, setRangeEnd] = useState(format(new Date(), 'yyyy-MM-dd'));
    const [viewMode, setViewMode] = useState(VIEW_MODES.DAY);
    const [selectedServiceId, setSelectedServiceId] = useState('0');
    const [showServices, setShowServices] = useState(false);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [viewDocsIndex, setViewDocsIndex] = useState(null);
    const servicesRef = useRef();
    useEffect(() => {
        if (flash?.success) {
            showToast(flash.success, "success");
        }
        if (flash?.error) {
            showToast(flash.error, "error");
        }
    }, [flash, showToast]);
    const openCustomerToolsModal = (event, index, booking, customer) => {
        event.preventDefault();
        console.log("customer----------tool-------- ", customer)

        const rect = event.target.getBoundingClientRect();
        setCustomerToolsTarget({
            index,
            booking,
            customer,
            position: {
                top: rect.top + window.scrollY + 30, // offset below the button
                left: rect.left + window.scrollX,
            }
        });
    };

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

    const handleBookingDelete = () => {
        router.delete(route('admin.bookings.destroy', selectedBooking.id), {
            onSuccess: () => { setSelectedBooking(null); router.visit(route('admin.bookings.index')); },
            onError: () => { setSelectedBooking(null); router.visit(route('admin.bookings.index')); }
        });
    };


    function BookingToolClick(tool, booking) {

        console.log("-------tool clicked-------", tool);
        if (tool === 'edit_booking') {
            router.get(route('admin.bookings.edit', booking.id));
        }
        if (tool === 'edit_timeslot') {
            //router.get(route('admin.bookings.edit', booking.id));
            setSelectedBooking(booking);
            setIsEditTimeSlotModalOpen(true);
        }
        if (tool === 'message_guest') {
            setGuestEmailTarget(booking);
            setIsEmailModalOpen(true);
        }
        // if (tool === 'message_staff') {
        //     router.get(route('admin.messenger.createTopic'));
        // }
        if (tool === 'check_in_progress') {
            // router.get(route('admin.bookings.check_in_progress'));
            router.get(route('booking-questionnaire.index'))
        }
        if (tool === 'manifest_edit') {
            //router.get(route('admin.bookings.manifest_edit'));
            setSelectedManifests(booking.customers);
            setIsManifestsModalOpen(true);
        }
        if (tool === 'remove_resource') {
            router.get(route('admin.bookings.remove_resource'));
        }
        if (tool === 'cancel_booking') {
            setConfirmOpen(true);
            setSelectedBooking(booking);
        }
    };

    const ViewBookingClick = (booking) => {
        //router.get(route('admin.bookings.edit', booking.id));
        setSelectedBooking(booking);
        setIsViewBookingModalOpen(true);
    };

    const CustomerToolClick = (tool, index, booking, customer) => {
        // console.log("-------tool customer clicked-------", tool);
        // console.log("-------tool customer clicked-------", index);
        console.log("-------tool customer clicked-------", customer);
        //const booking = bookings[index];
        //console.log("-------tool booking clicked-------", booking);
        //const bookings = bookings[0];
        //console.log("-------tool customers clicked-------", customers);
        if (tool === 'check_in') {
            router.get(route('booking-questionnaire.create', { reference: booking.reference }));
        }
        // if (tool === 'view_invoice') {
        //     router.get(route('admin.bookings.send_invoice'));
        // }
        if (tool === 'resend_invoice') {
            router.get(route('admin.invoices.resend-invoice', { booking_id: booking.id }));
        }
        if (tool === 'manifest') {
            setSelectedManifests(booking?.customers);
            setSelectedManifest(customer);
            setIsManifestModalOpen(true);
        }
        if (tool === 'customer_invoice') {
            if (customer.files.length == 0) {
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
        // if (tool === 'print_ticket') {
        //     router.get(route('admin.bookings.print_ticket'));
        // }
        // if (tool === 'voucher') {
        //     router.get(route('admin.bookings.voucher'));
        // }
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
            const weekStart = startOfWeek(new Date(), { weekStartsOn: 1 });
            const weekEnd = endOfWeek(new Date(), { weekStartsOn: 1 });
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

    useEffect(() => {
        setFilteredBookings(applyFilters());
    }, [bookings, selectedServiceId, viewMode, selectedDate, rangeStart, rangeEnd]);

    const handleCloseModal = () => {
        setGuestEmailTarget(null);
        setIsEditTimeSlotModalOpen(false);
        setIsViewBookingModalOpen(false);
        setSelectedBooking(null);
        setIsEmailModalOpen(false);
        window.location.reload();
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
                                    title="Day"
                                    width="w-[90px]"
                                    textColor={viewMode === VIEW_MODES.DAY ? 'text-white' : 'text-primary-14'}
                                    className={viewMode === VIEW_MODES.DAY ? 'bg-blue-gradient-overlay border-[#3863C6]' : ''}
                                    onClick={() => setViewMode(VIEW_MODES.DAY)}
                                />
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
                            <button onClick={() => router.get(route('booking-questionnaire.index'))} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-red-gradient-overlay border-[#C65468] text-white shadow-inset-white cursor-pointer">
                                <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                                <span className="text-btn-outline-gray">Check in</span>
                            </button>
                            <button onClick={() => router.get(route('front.feedback-questionnaire.index'))} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-red-gradient-overlay border-[#C65468] text-white shadow-inset-white cursor-pointer">
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
                                        Week of {format(startOfWeek(new Date(), { weekStartsOn: 1 }), 'yyyy-MM-dd')} to {format(endOfWeek(new Date(), { weekStartsOn: 1 }), 'yyyy-MM-dd')}
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
                                    {filteredBookings?.map((item, i) => {
                                        console.log('----------item----- ', item);


                                        return (
                                            <React.Fragment key={i}>
                                                {/* Main Booking Row */}
                                                <tr className="hover:bg-gray-50">
                                                    <td className="w-[164px] p-3">
                                                        <div className="flex justify-between items-center gap-2">
                                                            <div className="flex items-center gap-2">
                                                                <span className="w-5 h-5 block border border-gray-500 rounded-md"></span>
                                                                {item?.time}
                                                            </div>
                                                            <img
                                                                src={`/assets/icons/${expanded === i ? 'arrow-down' : 'arrow-left'}.svg`}
                                                                alt="toggle"
                                                                className="w-5 h-5 cursor-pointer"
                                                                onClick={() => setExpanded(expanded === i ? null : i)}
                                                            />
                                                        </div>
                                                    </td>
                                                    <td className="w-[95px] p-3">{item?.bookingPax} ({item?.maxPax})</td>
                                                    <td className="w-[268px] p-3">{item?.service}</td>
                                                    <td className="w-[132px] p-3">R{Number(item?.price || 0).toFixed(2)}</td>
                                                    <td className="w-[111px] p-3">R{Number(item?.paid || 0).toFixed(2)}</td>
                                                    <td className="w-[107px] p-3">
                                                        <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                                        ${item?.dueStatus ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'}`}>
                                                            - {Number(item?.due || 0) === 0 ? 'R0.00' : `R${Number(item?.due || 0).toFixed(2)}`}
                                                        </span>
                                                    </td>
                                                    <td className="w-[185px] p-3 flex gap-2">
                                                        {expanded === i ? (
                                                            // <button onClick={() => router.get(route('admin.bookings.edit', item?.id))} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer"></button>
                                                            <button onClick={() => ViewBookingClick(item)} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                                                                {icons.eye}
                                                                <span className="text-btn-outline-gray">View Booking</span>
                                                            </button>
                                                        ) : (
                                                            <>
                                                                <button type='button' onClick={() => router.get(route('admin.bookings.add', item?.id))} className="btn-tool">{icons.plus_alt} Add</button>
                                                                <button type='button' onClick={(e) => openBookingToolsModal(e, item)} className="btn-tool">{icons.tools} Tools</button>
                                                            </>
                                                        )}

                                                    </td>
                                                </tr>

                                                {/* Expanded Customer Rows */}
                                                {expanded === i && (
                                                    item.customers && item.customers.length > 0 ? (
                                                        item.customers.map((customer, index) => (
                                                            <tr key={index} className="bg-gray-50">
                                                                <td className="w-[164px] p-3">
                                                                    {item.reference}<br />
                                                                    Notes: {customer?.notes || 'None'}
                                                                </td>
                                                                <td className="w-[95px] p-3">{index + 1}</td>
                                                                <td className="w-[268px] p-3">
                                                                    <div className="text-sm text-gray-800 font-medium">{customer.full_name ?? 'Unknown Customer'}</div>
                                                                    <div className="text-sm text-gray-600">{customer.email_address ?? 'Unknown Email'} | {customer.contact_number ?? 'Unknown Phone'}</div>
                                                                    <div className="text-sm text-gray-600 capitalize">{customer.type} Age: {customer?.age || 'Unknown Age'}</div>
                                                                </td>
                                                                <td className="w-[132px] p-3">R{Number(customer?.price || 0).toFixed(2)}</td>
                                                                <td className="w-[111px] p-3">R{Number(customer?.paid || 0).toFixed(2)}</td>
                                                                <td className="w-[107px] p-3">
                                                                    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${customer.due === 0 ? 'bg-blue-100 text-blue-600'
                                                                        : customer.dueStatus === 'Normal' ? 'bg-orange-100 text-orange-600'
                                                                            : 'bg-red-100 text-red-600'
                                                                        }`}>
                                                                        - {Number(customer?.due || 0) === 0 ? 'R0.00' : customer.dueStatus === 'Normal' ? 'Normal' : `R${Number(customer?.due || 0).toFixed(2)}`}
                                                                    </span>
                                                                </td>
                                                                <td className="flex flex-col w-[120px] p-3  gap-2">
                                                                    <div
                                                                        className="relative"
                                                                        onClick={() =>
                                                                            setViewDocsIndex(viewDocsIndex === index ? null : index)
                                                                        }
                                                                    >
                                                                        <button type='button' className="btn-tool">{icons.docs} Docs</button>
                                                                        <div>
                                                                            {viewDocsIndex === index && (
                                                                                <div
                                                                                    className="absolute right-0 mt-3 w-72 bg-white border border-gray-200 rounded-2xl shadow-lg z-50 overflow-hidden"
                                                                                >
                                                                                    {/* <div className="p-3 border-b font-semibold text-gray-700">Alerts</div> */}
                                                                                    <div className="flex justify-between items-center mb-2">
                                                                                        <h3 className="text-tool-card-title">Docs {index + 1}</h3>
                                                                                        <button type='button'
                                                                                            onClick={(e) => {
                                                                                                e.stopPropagation();
                                                                                                setViewDocsIndex(null);
                                                                                            }}
                                                                                            className="text-gray-400 hover:text-gray-600 text-sm">{icons.close}</button>
                                                                                    </div>

                                                                                    <div className="max-h-64 overflow-y-auto">
                                                                                        {customer?.files?.length > 0 ? (
                                                                                            customer.files.map((file) => (
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
                                                                    <button type='button' onClick={(e) => openCustomerToolsModal(e, index, item, customer)} className="btn-tool">{icons.tools} Tools</button>
                                                                </td>
                                                            </tr>
                                                        ))
                                                    ) : (
                                                        <tr className="bg-gray-50">
                                                            <td colSpan={7} className="p-4 text-sm text-gray-500">No customers assigned.</td>
                                                        </tr>
                                                    )
                                                )}
                                            </React.Fragment>
                                        )
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
                        index={customerToolsTarget.index}
                        booking={customerToolsTarget.booking}
                        customer={customerToolsTarget.customer}
                        position={customerToolsTarget.position}
                        onClose={() => setCustomerToolsTarget(null)}
                        CustomerToolClick={CustomerToolClick}
                    />
                )}
                {confirmOpen && (
                    <ConfirmModal
                        title="Cancel this booking?"
                        message="This action cannot be undone."
                        confirmText="Yes, cancel"
                        cancelText="Keep Booking"
                        onConfirm={handleBookingDelete}
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


            </div>
        </AuthenticatedLayout>
    );
}


