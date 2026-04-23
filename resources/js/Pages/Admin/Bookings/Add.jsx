import CustomerToolsModal from '@/Components/CustomerToolsModal';
import GuestEmailModal from '@/Components/GuestEmailModal';
import Header from '@/Components/Header';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import React, { useEffect, useRef, useState } from 'react';
import { icons } from '../../../icons';
import CustomInputText from '@/Components/CustomInputText';
import BlueButton from '@/Components/BlueButton';
import WhiteButton from '@/Components/WhiteButton';
import CustomSelect from '@/Components/CustomSelect';
import ManifestCount from '@/Components/ManifestCount';
import CustomTextarea from '@/Components/CustomTextarea';
import CustomTimePicker from '@/Components/CustomTimePicker';
import ManifestModal from './ManifestsModal';
import { useToast } from '@/Context/ToastContext';
import ManifestsModal from './ManifestsModal';
import CheckInOptionsModal from '@/Components/CheckInOptionsModal';
import CheckInAnswersModal from '@/Components/CheckInAnswersModal';
// import { useRoute } from 'ziggy-js';
// import { Ziggy } from '@/ziggy'; // the generated file

export default function Dashboard({ auth, booking, manifest, bookingItem, staff, lookups, bookingNotes = [], bookingEmails = [], bookingAuditLogs = [], settings = {} }) {
    // console.log("auth", auth);
    // console.log("booking", booking);
    // console.log("bookingItem", bookingItem);
    // console.log("staff", staff);
    const { props } = usePage();
    const { flash } = props;
    const { showToast } = useToast();
    const [toggleCustomer, setToggleCustomer] = useState(true);
    const [toggleManifest, setToggleManifest] = useState(true);
    const [manifestFilter, setManifestFilter] = useState('checked_in'); // all | checked_in

    const [isManifestsModalOpen, setIsManifestsModalOpen] = useState(false);
    const [selectedManifests, setSelectedManifests] = useState(null);
    const [toggleInformation, setToggleInformation] = useState(true);
    const [toggleStaff, setToggleStaff] = useState(true);
    const [toggleComments, setToggleComments] = useState(true);
    const [toggleEmail, setToggleEmail] = useState(true);
    const pickupEnabled = settings?.enable_pickup_dropoff ?? true;
    const assignStaffEnabled = settings?.enable_assign_staff ?? true;
    const bookingInformationEnabled = settings?.show_booking_information ?? true;
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState("create");
    const [total, setTotal] = useState(null);
    const [checkInTarget, setCheckInTarget] = useState(null);
    const [checkInAnswersTarget, setCheckInAnswersTarget] = useState(null);
    const [customerToolsTarget, setCustomerToolsTarget] = useState(null);
    const [guestEmailTarget, setGuestEmailTarget] = useState(null);
    const [isEmailModalOpen, setIsEmailModalOpen] = useState(false);
    const [noteInput, setNoteInput] = useState('');
    const [noteAuthorFilter, setNoteAuthorFilter] = useState('all');
    const [noteLimit, setNoteLimit] = useState(5);
    const [auditLogLimit, setAuditLogLimit] = useState(5);
    const [emailTypeFilter, setEmailTypeFilter] = useState('all');
    const [attendanceOverrides, setAttendanceOverrides] = useState({});
    const commentsRef = useRef(null);

    const manifestRows = Array.isArray(manifest) ? manifest : [];
    const filteredManifestRows = manifestFilter === 'checked_in'
        ? manifestRows.filter((row) => Boolean(row?.is_indemnity))
        : manifestRows;

    const noteAuthorOptions = [
        { id: 'all', name: 'All Authors' },
        ...Array.from(
            new Map(
                (bookingNotes || [])
                    .filter(n => n?.created_by)
                    .map(n => [n.created_by, { id: n.created_by, name: n.created_by }])
            ).values()
        ),
    ];

    const filteredBookingNotes = (bookingNotes || []).filter((note) => {
        return noteAuthorFilter === 'all' || note?.created_by === noteAuthorFilter;
    });

    const limitedBookingNotes = noteLimit === 'all'
        ? filteredBookingNotes
        : filteredBookingNotes.slice(0, Number(noteLimit));

    const limitedAuditLogs = auditLogLimit === 'all'
        ? (bookingAuditLogs || [])
        : (bookingAuditLogs || []).slice(0, Number(auditLogLimit));

    const emailTypeOptions = [
        { id: 'all', name: 'All Emails' },
        { id: 'App\\Models\\Booking', name: 'Booking Emails' },
        { id: 'App\\Models\\Invoice', name: 'Invoice Emails' },
    ];

    const filteredBookingEmails = (bookingEmails || []).filter((email) => {
        if (emailTypeFilter === 'all') return true;
        const resolvedType = (email?.auditable_type || 'App\\Models\\Booking').trim();
        return resolvedType === emailTypeFilter;
    });
    useEffect(() => {
        if (flash?.success) {
            showToast(flash.success, "success");
        }
        if (flash?.error) {
            showToast(flash.error, "error");
        }
    }, [flash, showToast]);

    const { data, setData, put, isDirty, reset, processing, errors } = useForm(() => {
        console.log("booking", booking);
        console.log("customers", bookingItem.customers);
        const lead = bookingItem?.customers.find(c => c?.pivot?.is_lead);
        const inventory = bookingItem?.booking_inventory;
        const adultsCount = inventory?.adults ?? bookingItem?.pax_adults ?? 0;
        const childrenCount = inventory?.children ?? bookingItem?.pax_children ?? 0;
        const guidesCount = inventory?.guides ?? bookingItem?.pax_guides ?? 0;

        console.log("lead", lead);
        return {

            service_id: '',
            season_id: '',
            lead_person: bookingItem?.customers.find(c => c?.pivot?.is_lead)?.full_name ?? '',
            lead_email: bookingItem?.customers.find(c => c?.pivot?.is_lead)?.email_address ?? '',
            lead_mobile: bookingItem?.customers.find(c => c?.pivot?.is_lead)?.contact_number ?? '',
            lead_pax: 'A:' + adultsCount + ' C:' + childrenCount + ' G:' + guidesCount,
            lead_nationality: bookingItem?.customers.find(c => c?.pivot?.is_lead)?.nationality_id ?? '',
            pickup_location: bookingItem?.booking_infos.find(c => c?.type_location_id == 1)?.location ?? '',
            pickup_time: bookingItem?.booking_infos.find(c => c?.type_location_id == 1)?.time ?? '',
            pickup_meridiem: bookingItem?.booking_infos.find(c => c?.type_location_id == 1)?.meridiem ?? 'AM',
            start_location: bookingItem?.booking_infos.find(c => c?.type_location_id == 2)?.location ?? '',
            start_time: bookingItem?.booking_infos.find(c => c?.type_location_id == 2)?.time ?? '',
            start_meridiem: bookingItem?.booking_infos.find(c => c?.type_location_id == 2)?.meridiem ?? 'AM',
            end_location: bookingItem?.booking_infos.find(c => c?.type_location_id == 3)?.location ?? '',
            end_time: bookingItem?.booking_infos.find(c => c?.type_location_id == 3)?.time ?? '',
            end_meridiem: bookingItem?.booking_infos.find(c => c?.type_location_id == 3)?.meridiem ?? 'AM',
            dropoff_location: bookingItem?.booking_infos.find(c => c?.type_location_id == 4)?.location ?? '',
            dropoff_time: bookingItem?.booking_infos.find(c => c?.type_location_id == 4)?.time ?? '',
            dropoff_meridiem: bookingItem?.booking_infos.find(c => c?.type_location_id == 4)?.meridiem ?? 'AM',

        }
    }
    );

    const totalDue = Math.max(0, Number(booking?.grand_tot_due || 0) - Number(booking?.tot_paid || 0));
    const totalPaid = Number(booking?.tot_paid || 0);

    console.log('data', data);
    const handleOpenModal = (mode, user = null) => {
        console.log('mode ', mode);
        console.log('user ', user);
        const pick = bookingItem?.customer_manifests.length > 0 ? "edit" : "create";
        setModalMode(pick);
        //setSelectedUser(user);
        setIsModalOpen(true);
    };
    const handleOpenManifestsModal = () => {
        console.log('manifest', manifest);

        setSelectedManifests(manifest);
        setIsManifestsModalOpen(true);
    };

    const logCheckInAction = (action, manifestRow = null) => {
        const bookingId = booking?.id;
        const bookingItemId = bookingItem?.id;
        if (!bookingId || !bookingItemId || !window?.axios) return;
        const customerId = manifestRow?.customer?.id ?? manifestRow?.customer_id ?? null;
        window.axios.post(route('admin.bookings.log-checkin'), {
            booking_id: bookingId,
            booking_item_id: bookingItemId,
            customer_id: customerId,
            action,
        }).catch(() => {});
    };

    const handleManifestCheckIn = (manifestRow) => {
        const answers = Array.isArray(manifestRow?.question_answers) ? manifestRow.question_answers : [];
        logCheckInAction('open_checkin_detail', manifestRow);
        setCheckInAnswersTarget({
            manifest: manifestRow,
            answers,
        });
    };

    const buildToolsBooking = () => {
        const leadCustomer = bookingItem?.customers?.find(c => c?.pivot?.is_lead) || bookingItem?.customers?.[0] || null;
        const slotTime = bookingItem?.booking_inventory?.inventory_time_slot?.start_time;

        return {
            ...(booking || {}),
            customers: bookingItem?.customers || [],
            customer_id: leadCustomer?.id ?? booking?.customer_id ?? null,
            lead_id: leadCustomer?.id ?? booking?.customer_id ?? null,
            lead_name: booking?.lead_name ?? data?.lead_person ?? leadCustomer?.full_name ?? '',
            service: bookingItem?.service?.name ?? bookingItem?.service?.title ?? '',
            time: slotTime ?? '',
            reference: booking?.reference,
            files: booking?.files || [],
        };
    };

    const openPaymentToolsModal = (event) => {
        event.preventDefault();

        const leadCustomer = bookingItem?.customers?.find(c => c?.pivot?.is_lead) || bookingItem?.customers?.[0] || null;
        if (!leadCustomer) {
            showToast('No customer found for tools.', 'error');
            return;
        }

        const rect = event.currentTarget.getBoundingClientRect();
        setCustomerToolsTarget({
            booking: buildToolsBooking(),
            customer: leadCustomer,
            indexKey: 'payment-tools',
            position: {
                top: rect.top,
                bottom: rect.bottom,
                left: rect.left,
                right: rect.right,
            },
        });
    };

    const CustomerToolClick = (tool, toolsBooking, customer) => {
        if (tool === 'customer_invoice') {
            const file = (toolsBooking?.files || []).find(f => f?.table_name === 'invoices');
            if (!file?.path) {
                showToast('No invoice found.', 'error');
                return;
            }
            window.open(file.path, '_blank', 'noopener,noreferrer');
            return;
        }

        if (tool === 'resend_invoice') {
            if (!toolsBooking?.id) return;
            router.get(route('admin.invoices.resend-invoice', { booking_id: toolsBooking.id }));
            return;
        }

        if (tool === 'message_guest') {
            setGuestEmailTarget({
                ...toolsBooking,
                customers: toolsBooking?.customers || bookingItem?.customers || [],
                customer_id: toolsBooking?.customer_id ?? customer?.id ?? null,
                lead_id: toolsBooking?.lead_id ?? customer?.id ?? null,
                lead_name: toolsBooking?.lead_name ?? customer?.full_name ?? data?.lead_person ?? '',
            });
            setIsEmailModalOpen(true);
            return;
        }

        if (tool === 'booking_notes') {
            setToggleComments(false);
            setTimeout(() => {
                commentsRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 0);
        }
    };

    const resolveAttendanceKey = (bookingItemId, customerId) => `${bookingItemId || 'na'}-${customerId || 'na'}`;

    const attendanceNextStatus = (currentStatus) => {
        if (currentStatus === 'arrived') return 'cancelled';
        if (currentStatus === 'cancelled') return 'no_show';
        return 'arrived';
    };

    const attendanceLabel = (status) => {
        if (status === 'arrived') return 'Yes';
        if (status === 'cancelled') return 'Cancelled';
        return 'No';
    };

    const attendanceButtonClass = (status, disabled) => {
        const base = ' px-[9px] rounded-[6px] border transition';
        if (disabled) {
            return `${base} border-gray-200 text-gray-400 bg-gray-100 cursor-not-allowed`;
        }
        if (status === 'arrived') {
            return `${base} text-booking-card-manifest-yes border-gray-300 bg-[#00FF044D] hover:bg-green-50`;
        }
        if (status === 'cancelled') {
            return `${base} text-booking-card-manifest-no border-gray-300 bg-[#FCE4DF4D] hover:bg-gray-50`;
        }
        return `${base} text-booking-card-manifest-no border-gray-300  bg-[#FCE4DF4D] hover:bg-gray-50`;
    };

    const handleAttendanceStatus = (manifestRow, status) => {
        const customerId = manifestRow?.customer?.id ?? manifestRow?.customer_id ?? null;
        const bookingItemId = manifestRow?.booking_item_id ?? bookingItem?.id ?? null;

        if (!customerId || !bookingItemId) {
            showToast('Attendance status requires a customer record.', 'error');
            return;
        }

        const key = resolveAttendanceKey(bookingItemId, customerId);

        router.put(
            route('admin.customer-manifests.status'),
            {
                booking_item_id: bookingItemId,
                customer_id: customerId,
                attendance_status: status,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setAttendanceOverrides((prev) => ({ ...prev, [key]: status }));
                    showToast('Attendance status updated.', 'success');
                },
                onError: () => {
                    showToast('Unable to update attendance status.', 'error');
                },
            }
        );
    };

    const handleIndividualCheckin = async (e, index) => {
        e.preventDefault();
        data.customer_manifests[index].index = 1;
        //console.log('data', data); 
        put(route('front.customer-manifests.edit', booking?.customers[0].booking_item_id), {
            onSuccess: () => onClose(),
        });
    };


    const handleCloseModal = () => {
        window.location.reload();
        setIsModalOpen(false);
        //setSelectedUser(null);
    };


    const handleSubmit = (e) => {
        e.preventDefault();
        console.log('booking', booking);
        console.log('data', data);
        put(route('admin.bookings.update', { booking }));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Bookings</h2>}
        >
            <Head title="Dashboard" />
            {/* <header className="h-16 bg-white  border-b border-secondary-100 rounded-r-lg p-6 py-4 flex justify-between items-center">
                                <div className={`icon-open flex md:hidden  items-center justify-between px-1 py-1 bg-secondary-100  relative rounded-[10px]`}>
                                    <button onClick={()=>toggleSidebar()} className="flex w-auto h-auto hover:bg-gray-200 rounded-sm transition cursor-pointer">
                                        <img src="/assets/icons/sidebar-left.svg" alt="My Icon" className="w-5 h-5" />         
                                    </button>
                                </div>
                                <span className="font-semibold text-lg text-gray-800 capitalize">{currentRoutePath}, {auth?.user?.name}</span>                                
                                {route().current() === 'admin.bookings.edit' && <span className="font-semibold text-lg text-gray-800 capitalize">Bookings</span>}

                                <div className="flex items-center gap-2">
                                {icons.messages} {icons.notifications}                                    
                                </div>
                        </header> */}
            <div className="bg-white p-6 ">
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <span>
                        <span className="font-semibold text-lg text-gray-800 block text-start">{bookingItem?.service?.name} - {bookingItem?.season?.name}</span>
                        <span className="font-semibold text-lg text-gray-800 block text-start">
                            Booking Date: {bookingItem?.booking_inventory?.date || '—'}
                        </span>
                        {isDirty && (<p className="text-yellow-600 text-sm mt-1">You have unsaved changes</p>)}
                    </span>
                    <span className="flex gap-2">
                        {/*  <button onClick={() => router.get(route('admin.checkins.index', { 'booking_id': booking?.id, 'customer_id': booking?.customer_id }))} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-red-gradient-overlay border-[#C65468] text-white shadow-inset-white cursor-pointer">
                            <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                            <span className="text-btn-outline-gray">Check in</span>
                        </button>*/}
                        <button
                            type="button"
                            onClick={() => {
                                logCheckInAction('open_checkin_options');
                                setCheckInTarget({
                                    booking_item_id: bookingItem?.id,
                                    reference: booking?.reference,
                                    lead_name: data.lead_person,
                                    lead_phone: data.lead_mobile,
                                    lead_email: data.lead_email,
                                });
                            }}
                            className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-red-gradient-overlay border-[#C65468] text-white shadow-inset-white cursor-pointer"
                        >
                            <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                            <span className="text-btn-outline-gray">Check in</span>
                        </button>


                    </span>
                </div>
                {/* bottom */}
                <div className="bg-white border-secondary-100">

                    <div className="flex justify-start items-center px-4 pt-4 pb-[9px] gap-4">
                        <WhiteButton
                            title="Check-In"
                            icon={true}
                            iconName={icons.check_in}
                            textColor='text-[#4D81E7]'
                            width="w-[158.8px]"
                            height="h-[31px]"
                            className="mt-4"
                            onClick={() => {
                                logCheckInAction('open_checkin_options');
                                setCheckInTarget({
                                    booking_item_id: bookingItem?.id,
                                    reference: booking?.reference,
                                    lead_name: data.lead_person,
                                    lead_phone: data.lead_mobile,
                                    lead_email: data.lead_email,
                                });
                            }}
                        />
                        {/* <WhiteButton
                            title="Send Invoice"
                            icon={true}
                            iconName={icons.send_invoice}
                            textColor='text-[#4D81E7]'
                            width="w-[158.8px]"
                            height="h-[31px]"
                            className="mt-4"
                            onClick={() => handleSubmit(e)}
                        /> */}
                        <WhiteButton
                            title="Resend Invoice"
                            icon={true}
                            iconName={icons.resend_invoice}
                            textColor='text-[#4D81E7]'
                            width="w-[158.8px]"
                            height="h-[31px]"
                            className="mt-4"
                            onClick={() => router.get(route('admin.invoices.resend-invoice', { booking_id: booking?.id }))}
                        />
                        <WhiteButton
                            title="Manifest"
                            icon={true}
                            iconName={icons.manifest}
                            textColor='text-[#4D81E7]'
                            width="w-[158.8px]"
                            height="h-[31px]"
                            className="mt-4"
                            onClick={handleOpenManifestsModal}
                        />
                        <WhiteButton
                            title="Customer Invoice"
                            icon={true}
                            iconName={icons.customer_invoice}
                            textColor='text-[#4D81E7]'
                            width="w-[158.8px]"
                            height="h-[31px]"
                            className="mt-4"
                            onClick={() => {
                                // router.get(route('admin.invoices.download', { 'booking': booking?.id }))
                                if (booking?.files?.length == 0) {
                                    prompt('No invoice found');
                                    return;
                                }

                                const file = booking?.files?.find(f => f.table_name == 'invoices');
                                if (!file) {
                                    prompt('No invoice found');
                                    return;
                                }
                                const fileUrl = `${file.path}`;
                                window.open(fileUrl, '_blank', 'noopener,noreferrer');
                            }}
                        />
                        {/* <a
                            href={route('admin.invoices.download', { booking: booking.id })}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Proforma Invoice
                        </a> */}

                        <WhiteButton
                            title="Booking Pass"
                            icon={true}
                            iconName={icons.manifest}
                            textColor="text-[#4D81E7]"
                            width="w-[158.8px]"
                            height="h-[31px]"
                            className="mt-4"
                            onClick={() => {
                                if (!booking?.id) return;
                                const passFile = (booking?.files || [])
                                    .filter(file => Number(file?.type_file_id) === 6)
                                    .sort((a, b) => (a?.id ?? 0) - (b?.id ?? 0))
                                    .pop();
                                if (!passFile?.path) {
                                    window.open(route('admin.details.download', { booking: booking.id }), '_blank', 'noopener,noreferrer');
                                    return;
                                }
                                window.open(passFile.path, '_blank', 'noopener,noreferrer');
                            }}
                        />

                        {/* 
                        <WhiteButton
                                title="Print Ticket" 
                                icon={true}
                                iconName={icons.print_ticket}
                                textColor='text-[#4D81E7]'
                                width="w-[158.8px]"
                                height="h-[31px]"
                                className="mt-4"
                                onClick={() => handleSubmit(e)}
                            />
                            <WhiteButton
                                title="Voucher" 
                                icon={true}
                                iconName={icons.voucher}
                                textColor='text-[#4D81E7]'
                                width="w-[158.8px]"
                                height="h-[31px]"
                                className="mt-4"
                                onClick={() => handleSubmit(e)}
                            /> 
                        */}
                    </div>
                    <div className="flex w-full items-start px-4 pb-[9px] gap-6 ">
                        {/* left */}
                        <div className='flex basis-[70%] items-center'>
                            <div className="flex flex-col w-full gap-6">
                                {/* Customer */}
                                <div className=" overflow-hidden shadow-medium sm:rounded-lg">
                                    <div className="flex flex-col justify-between items-center ">
                                        <span className='flex items-center justify-between w-full px-6 py-4 border-b border-gray-300'>
                                            <span className="text-booking-card-heading block text-start">Customer Information </span>
                                            <span onClick={() => setToggleCustomer(!toggleCustomer)}>
                                                {toggleCustomer ? icons.cheveron_down : icons.cheveron_up}
                                            </span>
                                        </span>
                                        {toggleCustomer && (
                                            <span className="flex flex-col w-full px-6 py-6 gap-2">
                                                <CustomInputText
                                                    title="Lead Person"
                                                    name="lead_person"
                                                    value={data.lead_person}
                                                    height="h-[52px]"
                                                    type="text"
                                                    mode="view"
                                                    onChange={(e) => setData('lead_person', e.target.value)}
                                                    errors={errors}
                                                />
                                                <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">

                                                    <CustomInputText
                                                        title="Lead Email"
                                                        name="lead_email"
                                                        value={data.lead_email}
                                                        height="h-[52px]"
                                                        mode="view"
                                                        type="email"
                                                        onChange={(e) => setData('lead_email', e.target.value)}
                                                        errors={errors}
                                                    />
                                                    <CustomInputText
                                                        title="Lead Mobile"
                                                        name="lead_mobile"
                                                        value={data.lead_mobile}
                                                        height="h-[52px]"
                                                        type="text"
                                                        mode="view"
                                                        onChange={(e) => setData('lead_mobile', e.target.value)}
                                                        errors={errors}
                                                    />
                                                </div>
                                                <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">

                                                    <CustomInputText
                                                        title="PAX"
                                                        name="lead_pax"
                                                        value={data.lead_pax}
                                                        height="h-[52px]"
                                                        type="text"
                                                        mode="view"
                                                        onChange={(e) => setData('lead_pax', e.target.value)}
                                                        errors={errors}
                                                    />
                                                    <CustomInputText
                                                        title="Nationality"
                                                        name="lead_nationality"
                                                        value={data.lead_nationality}
                                                        height="h-[52px]"
                                                        mode="view"
                                                        onChange={(e) => setData('lead_nationality', e.target.value)}
                                                        errors={errors}
                                                    />
                                                </div>
                                            </span>
                                        )}
                                    </div>
                                </div>
                                {/* Customer Manifest */}
                                <div className=" overflow-hidden shadow-medium sm:rounded-lg">
                                    <div className="flex flex-col justify-between items-center ">
                                        <span className='flex items-center justify-between w-full px-6 py-4 border-b border-gray-300'>
                                            <span className="text-booking-card-heading block text-start">Customer Manifest</span>
                                            <span className="flex items-center gap-3">
                                                <div className="flex items-center rounded-lg border border-gray-200 bg-white p-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => setManifestFilter('all')}
                                                        className={`px-3 py-1 text-xs rounded-md transition ${manifestFilter === 'all'
                                                            ? 'bg-gray-900 text-white'
                                                            : 'text-gray-600 hover:bg-gray-50'
                                                            }`}
                                                    >
                                                        All
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setManifestFilter('checked_in')}
                                                        className={`px-3 py-1 text-xs rounded-md transition ${manifestFilter === 'checked_in'
                                                            ? 'bg-gray-900 text-white'
                                                            : 'text-gray-600 hover:bg-gray-50'
                                                            }`}
                                                    >
                                                        Checked-in
                                                    </button>
                                                </div>
                                                <span onClick={() => setToggleManifest(!toggleManifest)}>
                                                    {toggleManifest ? icons.cheveron_down : icons.cheveron_up}
                                                </span>
                                            </span>
                                        </span>
                                        {toggleManifest && (
                                            <div className="flex flex-col w-full  gap-2 ">
                                                {filteredManifestRows.length === 0 ? (
                                                    <div className="px-6 py-6 text-sm text-gray-500">
                                                        No guests have checked in yet.
                                                    </div>
                                                ) : filteredManifestRows.map((manifest, i) => {
                                                    const manifestCustomer = manifest?.customer || {};
                                                    const hasIndemnity = Boolean(manifest?.is_indemnity);
                                                    const hasCheckIn = hasIndemnity || (manifest?.question_answers?.length > 0);
                                                    const manifest_id = (i + 1) + '(' + (i + 1) + ')';
                                                    const customerId = manifestCustomer?.id ?? manifest?.customer_id ?? null;
                                                    const bookingItemId = manifest?.booking_item_id ?? bookingItem?.id ?? null;
                                                    const attendanceKey = resolveAttendanceKey(bookingItemId, customerId);
                                                    const attendanceStatus =
                                                        attendanceOverrides[attendanceKey] ??
                                                        manifest?.attendance_status ??
                                                        manifestCustomer?.attendance_status ??
                                                        'no_show';
                                                    const canUpdateAttendance = Boolean(customerId && bookingItemId);
                                                    console.log('manifest', manifest);
                                                    return (
                                                        <div key={i} className="flex flex-col px-6 py-6 gap-2 border-b border-gray-100">
                                                            <div className="flex w-full items-center justify-between gap-5">
                                                                <div className='basis-[5%]'>
                                                                    <ManifestCount
                                                                        title="#"
                                                                        value={manifest_id}
                                                                        height="h-[48px]"
                                                                    />
                                                                </div>
                                                                <div className='basis-[75%] items-center'>
                                                                    <CustomInputText
                                                                        title="Name"
                                                                        name="customer_name"
                                                                        value={manifestCustomer?.full_name || manifest?.full_name || manifestCustomer?.email_address || manifest?.email_address || ''}
                                                                        height="h-[48px]"
                                                                        type="text"
                                                                        mode="view"
                                                                        onChange={(e) => setData('customer_email', e.target.value)}
                                                                        errors={errors}
                                                                    />
                                                                </div>
                                                                <div className='basis-[20%] items-center pt-2 ' >
                                                                    <WhiteButton
                                                                        title={hasCheckIn ? "Checked-In" : "Check-In"}
                                                                        icon={true}
                                                                        iconName={icons.check_in}
                                                                        textColor='text-[#4D81E7]'
                                                                        width="w-[132.8px]"
                                                                        height="h-[31px]"
                                                                        className="mt-4"
                                                                        onClick={() => handleManifestCheckIn(manifest)}
                                                                    />
                                                                </div>
                                                            </div>
                                                            <div className="flex w-full items-center gap-5">
                                                                <div className='basis-[5%] '>
                                                                    <CustomInputText
                                                                        title="Position"
                                                                        name="customer_position"
                                                                        value={manifest?.position || ''}
                                                                        height="h-[48px]"
                                                                        width="w-[61px]"
                                                                        type="text"
                                                                        mode="view"
                                                                        onChange={(e) => setData('customer_position', e.target.value)}
                                                                        errors={errors}
                                                                    />
                                                                </div>
                                                                <div className='basis-[75%]'>
                                                                    <CustomInputText
                                                                        title="Notes"
                                                                        name="customer_notes"
                                                                        value={manifest?.notes || ''}
                                                                        height="h-[48px]"
                                                                        type="text"
                                                                        mode="view"
                                                                        onChange={(e) => setData('customer_notes', e.target.value)}
                                                                        errors={errors}
                                                                    />
                                                                </div>
                                                                <div className='basis-[20%] pt-1 space-y-[15px]' >
                                                                    <div className="flex gap-4">
                                                                        <span className="text-booking-card-manifest-lbl w-[69px]">Indemnity</span>
                                                                        {hasIndemnity ? (
                                                                            <span className="text-booking-card-manifest-yes px-[9px] bg-[#00FF044D] rounded-[6px] border border-gray-300">Yes</span>
                                                                        ) : (
                                                                            <span className="text-booking-card-manifest-no px-[9px] bg-[#FCE4DF4D] rounded-[6px] border border-gray-300">No</span>
                                                                        )}
                                                                    </div>
                                                                    <div className="flex gap-4 items-start">
                                                                        <span
                                                                            className="text-booking-card-manifest-lbl w-[69px]"
                                                                            title="Click to cycle: No → Yes → Cancelled → No"
                                                                        >
                                                                            Arrived
                                                                        </span>
                                                                        <button
                                                                            type="button"
                                                                            disabled={!canUpdateAttendance}
                                                                            className={attendanceButtonClass(attendanceStatus, !canUpdateAttendance)}
                                                                            onClick={() => {
                                                                                const nextStatus = attendanceNextStatus(attendanceStatus);
                                                                                handleAttendanceStatus(manifest, nextStatus);
                                                                            }}
                                                                            title="Click to cycle: No → Yes → Cancelled → No"
                                                                        >
                                                                            {attendanceLabel(attendanceStatus)}
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    )
                                                })}

                                            </div>
                                        )}
                                    </div>
                                </div>
                                {bookingInformationEnabled && (
                                    <div className=" overflow-hidden shadow-medium sm:rounded-lg">
                                        <div className="flex flex-col justify-between items-center ">
                                            <span className='flex items-center justify-between w-full px-6 py-4 border-b border-gray-300'>
                                                <span className="text-booking-card-heading block text-start">Booking Information</span>
                                                <div className="flex gap-4">
                                                    {isDirty ? (
                                                        <button onClick={(e) => handleSubmit(e)} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                                                            <span className="text-btn-outline-gray">Save</span>
                                                        </button>
                                                    )
                                                        : ''}
                                                    <span onClick={() => setToggleInformation(!toggleInformation)}>
                                                        {toggleInformation ? icons.cheveron_down : icons.cheveron_up}
                                                    </span>
                                                </div>
                                            </span>
                                            {toggleInformation && (
                                                <div className="flex flex-col w-full  gap-2 ">
                                                    <div className="flex flex-col px-6 py-6 gap-2 border-b border-gray-100">
                                                        {pickupEnabled && (
                                                            <div className="flex w-full items-center justify-between gap-5">
                                                                <CustomInputText
                                                                    title="Pickup Location"
                                                                    name="pickup_location"
                                                                    value={data.pickup_location}
                                                                    height="h-[48px]"
                                                                    type="text"
                                                                    onChange={(e) => setData('pickup_location', e.target.value)}
                                                                    errors={errors}
                                                                />
                                                                <CustomTimePicker
                                                                    label="Pickup Time"
                                                                    value={data.pickup_time}
                                                                    meridiem={data.pickup_time}
                                                                    onChange={(time) => setData('pickup_time', time)}
                                                                    onMeridiemChange={(mer) => setData('pickup_meridiem', mer)}
                                                                />
                                                            </div>
                                                        )}

                                                        <div className="flex w-full items-center justify-between gap-5">
                                                            <CustomInputText
                                                                title="Start Location"
                                                                name="start_location"
                                                                value={data.start_location}
                                                                height="h-[48px]"
                                                                type="text"
                                                                onChange={(e) => setData('start_location', e.target.value)}
                                                                errors={errors}
                                                            />
                                                            <CustomTimePicker
                                                                label="Start Time"
                                                                value={data.start_time}
                                                                meridiem={data.start_time}
                                                                onChange={(time) => setData('start_time', time)}
                                                                onMeridiemChange={(mer) => setData('start_meridiem', mer)}
                                                            />
                                                        </div>
                                                        <div className="flex w-full gap-5">
                                                            {/* <div className="basis-[65%]"></div> */}
                                                            <CustomInputText
                                                                title="End Location"
                                                                name="end_location"
                                                                value={data.end_location}
                                                                height="h-[48px]"
                                                                type="email"
                                                                onChange={(e) => setData('end_location', e.target.value)}
                                                                errors={errors}
                                                            />

                                                            <CustomTimePicker
                                                                label="End Time"
                                                                value={data.end_time}
                                                                meridiem={data.end_time}
                                                                onChange={(time) => setData('end_time', time)}
                                                                onMeridiemChange={(mer) => setData('end_meridiem', mer)}
                                                            />

                                                        </div>
                                                        {pickupEnabled && (
                                                            <div className="flex w-full items-center justify-between gap-5">
                                                                <CustomInputText
                                                                    title="Dropoff Location"
                                                                    name="dropoff_location"
                                                                    value={data.dropoff_location}
                                                                    height="h-[48px]"
                                                                    type="text"
                                                                    onChange={(e) => setData('dropoff_location', e.target.value)}
                                                                    errors={errors}
                                                                />
                                                                <CustomTimePicker
                                                                    label="Dropoff Time"
                                                                    value={data.dropoff_time}
                                                                    meridiem={data.dropoff_time}
                                                                    onChange={(time) => setData('dropoff_time', time)}
                                                                    onMeridiemChange={(mer) => setData('dropoff_meridiem', mer)}
                                                                />
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}

                            </div>
                        </div>
                        {/* right */}
                        <div className='flex basis-[30%] items-center'>
                            <div className="flex flex-col gap-6">
                                {/* Staff */}
                                {assignStaffEnabled && (
                                    <div className=" overflow-hidden shadow-medium sm:rounded-lg">
                                        <div className="flex flex-col justify-between items-center ">
                                            <span className='flex items-center justify-between w-full px-6 py-4 border-b border-gray-300'>
                                                <span className="text-booking-card-heading block text-start">Assigned Staff</span>
                                                <span onClick={() => setToggleStaff(!toggleStaff)}>
                                                    <button className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] border-primary-14 text-primary-14 cursor-pointer">
                                                        <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5 text-black" />
                                                        <span className="text-btn-outline-gray">Add Staff</span>
                                                    </button>
                                                </span>
                                            </span>
                                            {toggleStaff ? (
                                                <div className="flex flex-col w-full px-6 py-6 gap-2">
                                                    <div className="flex flex-col px-6 py-6 gap-2 border-b border-gray-100">
                                                        <div className="flex w-full items-center justify-between gap-5">
                                                            <div className='basis-[80%] px-[13px] py-1 gap-[6px] flex items-center justify-start w-[298px] h-[46px] rounded-[100px] border border-gray-300'>
                                                                <span className="flex items-center justify-center w-[15.5px] h-[12.4px]">{icons.user}</span>
                                                                <span className="text-booking-card-title">Name</span>
                                                            </div>
                                                            <div className='basis-[20%] flex items-center gap-[7px]'>
                                                                <span className={`flex items-center justify-center w-[36px] h-[36px] gap-2 px-4 py-2 rounded-[10px] border border-primary-14
                                                                        bg-white-gradient-overlay text-black shadow-inset-white 
                                                                        hover:border-black hover:text-blackcursor-pointer`}
                                                                    onClick={() => { }}
                                                                >{icons.envelope}</span>
                                                                <span className={`flex items-center justify-center w-[36px] h-[36px] gap-2 px-4 py-2 rounded-[10px] border border-primary-14
                                                                        bg-white-gradient-overlay text-black shadow-inset-white 
                                                                        hover:border-black hover:text-blackcursor-pointer`}
                                                                    onClick={() => { }}
                                                                >{icons.cross}</span>

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            ) : (
                                                <span className="flex flex-col w-full px-6 py-6 gap-2">
                                                    <CustomSelect
                                                        name="wholesaler_id"
                                                        value={data.wholesaler_id}

                                                        onChange={(e) => setData(e.target.name, e.target.value)}
                                                        error={errors.wholesaler_id}
                                                        options={staff}
                                                    />
                                                    <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">
                                                        <button onClick={() => { }} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                                                            <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                                                            <span className="text-btn-outline-gray">Add</span>
                                                        </button>
                                                        <button className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-red-gradient-overlay border-[#C65468] text-white shadow-inset-white cursor-pointer">
                                                            <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                                                            <span className="text-btn-outline-gray">Cancel</span>
                                                        </button>
                                                    </div>
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                )}
                                {/* Comments */}
                                <div ref={commentsRef} className=" overflow-hidden shadow-medium sm:rounded-lg">
                                    <div className="flex flex-col justify-between items-center ">
                                        {/* <span className='flex items-center justify-between w-full px-6 py-4 border-b border-gray-300'>
                                                <span className="text-booking-card-heading block text-start">Comments</span>
                                                <span onClick={() => setToggleComments(!toggleComments)}>
                                                    {toggleComments ? icons.cheveron_down : icons.cheveron_up}
                                                </span>
                                            </span> */}

                                        <div className="flex items-center justify-between w-full px-6 py-4 border-b border-gray-300">
                                            <span className="text-booking-card-heading ">Comments</span>
                                            <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-xs text-gray-500">Show</span>
                                                    <button
                                                        type="button"
                                                        onClick={() => setNoteLimit(5)}
                                                        className={`px-2 py-1 text-xs rounded ${noteLimit === 5 ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'}`}
                                                    >
                                                        5
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setNoteLimit(10)}
                                                        className={`px-2 py-1 text-xs rounded ${noteLimit === 10 ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'}`}
                                                    >
                                                        10
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setNoteLimit('all')}
                                                        className={`px-2 py-1 text-xs rounded ${noteLimit === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'}`}
                                                    >
                                                        All
                                                    </button>
                                                </div>
                                                <CustomSelect
                                                    name="note_author"
                                                    value={noteAuthorFilter}
                                                    onChange={(e) => setNoteAuthorFilter(e.target.value)}
                                                    error={errors.note_author}
                                                    options={noteAuthorOptions}
                                                />

                                            </div>
                                        </div>
                                        {toggleComments ? (
                                            <div className="flex flex-col w-full px-6 py-6 gap-2">
                                                {filteredBookingNotes.length === 0 ? (
                                                    <div className="bg-gray-50 border border-gray-200 rounded-md p-4 text-gray-800 whitespace-pre-wrap">
                                                        No booking notes found.
                                                    </div>
                                                ) : (
                                                    <div className="space-y-3">
                                                        {limitedBookingNotes.map((note) => (
                                                            <div key={note.id} className="bg-gray-50 border border-gray-200 rounded-md p-4 text-gray-800 whitespace-pre-wrap">
                                                                <div className="text-xs text-gray-500 mb-2">
                                                                    {note.created_at || 'Unknown date'}
                                                                    {note.created_by ? ` • ${note.created_by}` : ''}
                                                                </div>
                                                                <div>{note.note}</div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}

                                                <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">
                                                    {/* <button onClick={() => setToggleComments(!toggleComments)} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] border-primary-14 text-primary-14 cursor-pointer">
                                                            <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5 text-black" />
                                                            <span className="text-btn-outline-gray">Add Comment</span>
                                                        </button> */}
                                                    <WhiteButton
                                                        title="Add Comment"
                                                        textColor="text-[#161618]"
                                                        width="w-[141px]"
                                                        height="h-[40px]"
                                                        className="mt-4"
                                                        onClick={() => setToggleComments(!toggleComments)}
                                                    />

                                                </div>
                                            </div>
                                        ) : (
                                            <span className="flex flex-col w-full px-6 py-6 gap-2">
                                                <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">
                                                    <CustomTextarea
                                                        name="booking_note"
                                                        value={noteInput}
                                                        onChange={(e) => setNoteInput(e.target.value)}
                                                        rows={4}
                                                    />
                                                </div>
                                                <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            if (!noteInput.trim()) {
                                                                showToast('Please enter a note.', 'error');
                                                                return;
                                                            }
                                                            router.post(route('admin.bookings.notes.store'), {
                                                                booking_id: booking?.id,
                                                                booking_item_id: bookingItem?.id,
                                                                note: noteInput,
                                                            }, {
                                                                onSuccess: () => {
                                                                    setNoteInput('');
                                                                    showToast('Booking note added.', 'success');
                                                                    router.reload({ only: ['bookingNotes'] });
                                                                    setToggleComments(true);
                                                                },
                                                            });
                                                        }}
                                                        className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer"
                                                    >
                                                        <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                                                        <span className="text-btn-outline-gray">Add</span>
                                                    </button>
                                                    <button onClick={() => setToggleComments(!toggleComments)} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-red-gradient-overlay border-[#C65468] text-white shadow-inset-white cursor-pointer">
                                                        <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                                                        <span className="text-btn-outline-gray">Cancel</span>
                                                    </button>
                                                </div>
                                            </span>
                                        )}
                                    </div>
                                </div>
                                {/* Payment Information */}
                                <div className=" overflow-hidden shadow-medium sm:rounded-lg">
                                    <div className="flex flex-col justify-between items-center ">
                                        <span className='flex items-center justify-between w-full px-6 py-4 border-b border-gray-300'>
                                            <span className="text-booking-card-heading block text-start">Payment Information</span>
                                        </span>

                                        <div className="flex flex-col w-full px-6 py-6 gap-2">

                                            <div className="flex items-center just space-x-4">
                                                <label className="text-booking-lbl-main basis-[20%]">Overdue</label>
                                                <div className="flex items-center justify-between w-[417px] h-[43px] bg-[#FCE4DF4D] pl-[7px] pr-[7px] py-[7px] rounded-[6px]">
                                                    <span className="text-booking-lbl-main-sub-error inline-flex items-center gap-2">{icons.red_dot} R {totalDue.toFixed(2)}</span>
                                                    <div className='flex items-center gap-2'>

                                                        <WhiteButton
                                                            title="Pay"
                                                            icon={true}
                                                            iconName={icons.pay}
                                                            textColor='text-[#4D81E7]'
                                                            width="w-[91px]"
                                                            height="h-[31px]"
                                                            className="mt-4"
                                                            onClick={() => router.get(route('admin.booking-payments.create', booking?.id))}
                                                        />
                                                        <WhiteButton
                                                            title="Tools"
                                                            icon={true}
                                                            iconName={icons.tools}
                                                            textColor='text-[#4D81E7]'
                                                            width="w-[91px]"
                                                            height="h-[31px]"
                                                            className="mt-4"
                                                            onClick={openPaymentToolsModal}
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="flex items-center space-x-4">
                                                <label className="text-booking-lbl-main basis-[20%]">Paid</label>
                                                <div className="flex items-center justify-between w-[417px] h-[43px] bg-[#DBECFD4D] pl-[7px] pr-[7px] py-[7px] rounded-[6px]">
                                                    <span className="text-booking-lbl-main-sub inline-flex items-center gap-2">{icons.blue_dot} R {totalPaid.toFixed(2)}</span>
                                                    <WhiteButton
                                                        title="Tools"
                                                        icon={true}
                                                        iconName={icons.tools}
                                                        textColor='text-[#4D81E7]'
                                                        width="w-[91px]"
                                                        height="h-[31px]"
                                                        className="mt-4"
                                                        onClick={openPaymentToolsModal}
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {/* Email Log */}
                                <div className="overflow-hidden shadow-medium sm:rounded-lg">
                                    <div className="flex flex-col w-full">  {/* Remove items-center + justify-between here */}
                                        <div className="flex items-center justify-between w-full px-6 py-4 border-b border-gray-300">
                                            <span className="text-booking-card-heading ">Email Log</span>
                                            <div className="flex items-center gap-2">
                                                <CustomSelect
                                                    name="email_type_filter"
                                                    width="w-[200px]"
                                                    value={emailTypeFilter}
                                                    showOption={false}
                                                    onChange={(e) => { setEmailTypeFilter(e.target.value); }}
                                                    error={errors.email_type_filter}
                                                    options={emailTypeOptions}
                                                />
                                            </div>
                                        </div>
                                        <div className="flex flex-col w-full px-6 py-6 gap-2">
                                            {filteredBookingEmails.length === 0 ? (
                                                <div className="bg-gray-50 border border-gray-200 rounded-md p-4 text-gray-800 whitespace-pre-wrap">
                                                    No emails found.
                                                </div>
                                            ) : (
                                                <div className="space-y-3">
                                                    {filteredBookingEmails.map((email) => (
                                                        <div key={email.id} className="bg-gray-50 border border-gray-200 rounded-md p-4 text-gray-800">
                                                            <div className="text-xs text-gray-500 mb-2">
                                                                {email.sent_at || 'Unknown date'}
                                                            </div>
                                                            <div className="text-sm">
                                                                <div><span className="font-semibold">To:</span> {email.receiver || '-'}</div>
                                                                <div><span className="font-semibold">Subject:</span> {email.subject || '-'}</div>
                                                            </div>
                                                            {email.attachment_path && (
                                                                <div className="mt-2">
                                                                    <a
                                                                        href={email.attachment_path}
                                                                        target="_blank"
                                                                        rel="noopener noreferrer"
                                                                        className="text-blue-600 hover:underline text-sm"
                                                                    >
                                                                        View attachment
                                                                    </a>
                                                                </div>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                {/* Audit Logs */}
                                <div className="overflow-hidden shadow-medium sm:rounded-lg">
                                    <div className="flex flex-col w-full">
                                        <div className="flex items-center justify-between w-full px-6 py-4 border-b border-gray-300">
                                            <span className="text-booking-card-heading ">Audit Logs</span>
                                            <div className="flex items-center gap-2">
                                                <span className="text-xs text-gray-500">Show</span>
                                                <button
                                                    type="button"
                                                    onClick={() => setAuditLogLimit(5)}
                                                    className={`px-2 py-1 text-xs rounded ${auditLogLimit === 5 ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'}`}
                                                >
                                                    5
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => setAuditLogLimit(10)}
                                                    className={`px-2 py-1 text-xs rounded ${auditLogLimit === 10 ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'}`}
                                                >
                                                    10
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => setAuditLogLimit('all')}
                                                    className={`px-2 py-1 text-xs rounded ${auditLogLimit === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'}`}
                                                >
                                                    All
                                                </button>
                                            </div>
                                        </div>
                                        <div className="flex flex-col w-full px-6 py-6 gap-2">
                                            {bookingAuditLogs.length === 0 ? (
                                                <div className="bg-gray-50 border border-gray-200 rounded-md p-4 text-gray-800 whitespace-pre-wrap">
                                                    No audit logs found.
                                                </div>
                                            ) : (
                                                <div className="space-y-3">
                                                    {limitedAuditLogs.map((log) => (
                                                        <div key={log.id} className="bg-gray-50 border border-gray-200 rounded-md p-4 text-gray-800">
                                                            <div className="text-xs text-gray-500 mb-2">
                                                                {log.created_at || 'Unknown date'}
                                                                {log.user ? ` • ${log.user}` : ''}
                                                            </div>
                                                            <div className="text-sm">
                                                                <div>{log.description}</div>
                                                                {log.host && (
                                                                    <div className="text-xs text-gray-500 mt-1">Host: {log.host}</div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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

                <CheckInAnswersModal
                    isOpen={!!checkInAnswersTarget}
                    onClose={() => setCheckInAnswersTarget(null)}
                    manifest={checkInAnswersTarget?.manifest}
                    answers={checkInAnswersTarget?.answers}
                />

                {customerToolsTarget && (
                    <CustomerToolsModal
                        index={customerToolsTarget.indexKey}
                        booking={customerToolsTarget.booking}
                        customer={customerToolsTarget.customer}
                        position={customerToolsTarget.position}
                        onClose={() => setCustomerToolsTarget(null)}
                        allowedTools={['customer_invoice', 'resend_invoice', 'message_guest', 'booking_notes']}
                        CustomerToolClick={(tool, _index, bookingPayload, customerPayload) => {
                            setCustomerToolsTarget(null);
                            CustomerToolClick(tool, bookingPayload, customerPayload);
                        }}
                    />
                )}

                <GuestEmailModal
                    isOpen={isEmailModalOpen}
                    onClose={() => {
                        setIsEmailModalOpen(false);
                        setGuestEmailTarget(null);
                    }}
                    mode="create"
                    booking={guestEmailTarget}
                />

            </div>
        </AuthenticatedLayout>
    );
}
