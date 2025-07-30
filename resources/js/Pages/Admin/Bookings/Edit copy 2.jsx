import CustomerToolsModal from '@/Components/CustomerToolsModal';
import Header from '@/Components/Header';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import React, { useState } from 'react';
import { icons } from '../../../icons';
import CustomInputText from '@/Components/CustomInputText';
import BlueButton from '@/Components/BlueButton';
import WhiteButton from '@/Components/WhiteButton';
import CustomSelect from '@/Components/CustomSelect';
import ManifestCount from '@/Components/ManifestCount';
import CustomTimePicker from '@/Components/CustomTimePicker';


export default function Dashboard({ auth, booking, staff  }) {
    console.log("auth", auth);
    console.log("booking", booking);
    console.log("staff", staff);
    const [toggleCustomer, setToggleCustomer] = useState(true);
    const [toggleManifest, setToggleManifest] = useState(true);
    const [toggleInformation, setToggleInformation] = useState(true);
    const [toggleStaff, setToggleStaff] = useState(true);
    const [toggleComments, setToggleComments] = useState(true);
    const [togglePayment, setTogglePayment] = useState(true);
    const [toggleEmail, setToggleEmail] = useState(true);
    const to12Hour = (type, time24 = '00:00') => {
    const [h, m] = time24.split(':');
    const hour = parseInt(h);
    return {
        [`${type}_time`]: `${(hour % 12 || 12).toString().padStart(2, '0')}:${m}`,
        [`${type}_meridiem`]: hour >= 12 ? 'PM' : 'AM'
    };
    };
    const { data, setData, put, isDirty, reset, processing, errors } = useForm(()=>
        {
            console.log("booking", booking);
            console.log("customers", booking.customers);
            const lead = booking?.customers.find(c => c?.pivot?.is_lead);
            const inventory = booking?.booking_inventory;

            console.log("lead", lead);
            return {

            service_id: '',
            season_id: '',
            lead_person: booking?.customers.find(c => c?.pivot?.is_lead)?.name ?? '',
            lead_email: booking?.customers.find(c => c?.pivot?.is_lead)?.email ?? '',
            lead_mobile: booking?.customers.find(c => c?.pivot?.is_lead)?.contact_number ?? '',
            lead_pax: 'A:' + inventory?.tot_adults + ' C:' + inventory?.tot_children + ' G:' + inventory?.tot_guides,
            lead_nationality: booking?.customers.find(c => c?.pivot?.is_lead)?.nationality ?? '',
            pickup_location: booking?.booking_infos.find(c => c?.type_location_id == 1)?.location ?? '',
            ...to12Hour('pickup', booking.booking_infos.find(c => c.type_location_id === 1)?.time),
            start_location: booking?.booking_infos.find(c => c?.type_location_id == 2)?.location ?? '',
            ...to12Hour('start', booking.booking_infos.find(c => c.type_location_id === 2)?.time),           
            end_location: booking?.booking_infos.find(c => c?.type_location_id == 3)?.location ?? '',
            ...to12Hour('end', booking.booking_infos.find(c => c.type_location_id === 3)?.time),
            dropoff_location: booking?.booking_infos.find(c => c?.type_location_id == 4)?.location ?? '',
            ...to12Hour('dropoff', booking.booking_infos.find(c => c.type_location_id === 4)?.time),

            }   
        }     
    );
    console.log("data------ ", data);
    const handleSubmit = (e) => {
        e.preventDefault();
        console.log('booking', booking);
        console.log('data', data);
        put(route('admin.bookings.update',{booking}));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Bookings</h2>}
        >
            <Head title="Dashboard" />
            {/* <header className="h-16 bg-white  border-b border-secondary-100 rounded-r-lg p-6 py-4 flex justify-between items-center">
                            <div className="flex items-center gap-4 ">
                                <div className={`icon-open flex md:hidden  items-center justify-between px-1 py-1 bg-secondary-100  relative rounded-[10px]`}>
                                    <button onClick={()=>{}} className="flex w-auto h-auto hover:bg-gray-200 rounded-sm transition cursor-pointer">
                                        <img src="/assets/icons/sidebar-left.svg" alt="My Icon" className="w-5 h-5" />         
                                    </button>
                                </div>
                                <span className="font-semibold text-lg text-gray-800 capitalize">test</span>
                            </div>
                        </header> */}
            <div className="bg-white p-6 ">
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <span>
                        <span className="font-semibold text-lg text-gray-800 block text-start">{booking?.season?.name}</span>
                        <span className="font-semibold text-lg text-gray-800 block text-start">{booking?.inventory?.start_date}</span>
                        {isDirty && (<p className="text-yellow-600 text-sm mt-1">You have unsaved changes</p>)}
                    </span>
                    <span className="flex gap-2">
                    <button className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-red-gradient-overlay border-[#C65468] text-white shadow-inset-white cursor-pointer">
                        <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                        <span className="text-btn-outline-gray">Check in</span>
                    </button>
                    </span>
                </div>
                {/* bottom */}
                <div className="bg-white border-secondary-100">

                        <div className="flex justify-between items-center px-4 pt-4 pb-[9px] ">
                            <WhiteButton
                                title="Check-In" 
                                icon={true}
                                iconName={icons.check_in}
                                textColor='text-[#4D81E7]'
                                width="w-[158.8px]"
                                height="h-[31px]"
                                className="mt-4"
                                onClick={() => handleSubmit(e)}
                            /> 
                            <WhiteButton
                                title="Send Invoice" 
                                icon={true}
                                iconName={icons.send_invoice}
                                textColor='text-[#4D81E7]'
                                width="w-[158.8px]"
                                height="h-[31px]"
                                className="mt-4"
                                onClick={() => handleSubmit(e)}
                            />
                            <WhiteButton
                                title="Resend Invoice" 
                                icon={true}
                                iconName={icons.resend_invoice}
                                textColor='text-[#4D81E7]'
                                width="w-[158.8px]"
                                height="h-[31px]"
                                className="mt-4"
                                onClick={() => handleSubmit(e)}
                            />
                            <WhiteButton
                                title="Manifest" 
                                icon={true}
                                iconName={icons.manifest}
                                textColor='text-[#4D81E7]'
                                width="w-[158.8px]"
                                height="h-[31px]"
                                className="mt-4"
                                onClick={() => handleSubmit(e)}
                            />
                            <WhiteButton
                                title="Customer Invoice" 
                                icon={true}
                                iconName={icons.customer_invoice}
                                textColor='text-[#4D81E7]'
                                width="w-[158.8px]"
                                height="h-[31px]"
                                className="mt-4"
                                onClick={() => handleSubmit(e)}
                            />
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
                        </div>                    
                        <div className="flex  items-start px-4 pb-[9px] gap-6 ">
                            {/* left */}
                            <div className='flex-1 items-center'>
                                <div className="flex flex-col gap-6">
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
                                                    title="Name"
                                                    name="lead_person"
                                                    value={data.lead_person}
                                                    height="h-[52px]"
                                                    type="text"
                                                    mode = "view"   
                                                    onChange={(e) => setData('lead_person', e.target.value)}
                                                    errors={errors}
                                                />                                            
                                                <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">

                                                    <CustomInputText
                                                        title="Email"
                                                        name="lead_email"
                                                        value={data.lead_email}
                                                        height="h-[52px]"
                                                        mode = "view"   
                                                        type="email"                            
                                                        onChange={(e) => setData('lead_email', e.target.value)}
                                                        errors={errors}
                                                    />
                                                    <CustomInputText
                                                        title="Contact Number"
                                                        name="lead_mobile"
                                                        value={data.lead_mobile}
                                                        height="h-[52px]"
                                                        type="text"
                                                        mode = "view"   
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
                                                        mode = "view"                    
                                                        onChange={(e) => setData('lead_pax', e.target.value)}
                                                        errors={errors}
                                                    />
                                                    <CustomInputText
                                                        title="Nationality"
                                                        name="lead_nationality"
                                                        value={data.lead_nationality}
                                                        height="h-[52px]"
                                                        mode = "view"
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
                                                <span onClick={() => setToggleManifest(!toggleCustomer)}>
                                                    {toggleCustomer ? icons.cheveron_down : icons.cheveron_up}
                                                </span>
                                            </span>
                                            {toggleManifest && (
                                                <div className="flex flex-col w-full  gap-2 ">
                                                    {booking?.customer_manifests?.map((customer, i) => {
                                                        const isIndemnity = customer?.is_indemnity;
                                                        const isCheckedIn = customer?.is_checked_in;
                                                        const manifest_id = (i + 1) + '(' + (i + 1) + ')';
                                                        console.log('customer', customer);

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
                                                                    title="Email"
                                                                    name="customer_email"
                                                                    value={customer.email}
                                                                    height="h-[48px]"
                                                                    type="email"
                                                                    mode = "view"                            
                                                                    onChange={(e) => setData('customer_email', e.target.value)}
                                                                    errors={errors}
                                                                />  
                                                            </div>
                                                            <div className='basis-[20%] items-center pt-2 ' >
                                                                <WhiteButton
                                                                    title="Check-In" 
                                                                    icon={true}
                                                                    iconName={icons.check_in}
                                                                    textColor='text-[#4D81E7]'
                                                                    width="w-[132.8px]"
                                                                    height="h-[31px]"
                                                                    className="mt-4"
                                                                    onClick={() => router.get(route('admin.checkins'))}
                                                                />
                                                            </div>
                                                        </div>
                                                        <div className="flex w-full items-center gap-5">
                                                            <div className='basis-[5%] '>
                                                                <CustomInputText
                                                                    title="Position"
                                                                    name="customer_position"
                                                                    value={data.customer_position}
                                                                    height="h-[48px]"
                                                                    width="w-[61px]"
                                                                    type="text"
                                                                    mode = "view"                            
                                                                    onChange={(e) => setData('customer_position', e.target.value)}
                                                                    errors={errors}
                                                                />
                                                            </div>
                                                            <div className='basis-[75%]'>
                                                                <CustomInputText
                                                                    title="Notes"
                                                                    name="customer_notes"
                                                                    value={data.customer_notes}
                                                                    height="h-[48px]"
                                                                    type="email"
                                                                    mode = "view"                            
                                                                    onChange={(e) => setData('customer_notes', e.target.value)}
                                                                    errors={errors}
                                                                />  
                                                            </div>
                                                            <div className='basis-[20%] pt-1 space-y-[15px]' >
                                                                <div className="flex gap-4">
                                                                    <span className="text-booking-card-manifest-lbl w-[69px]">Indemnity</span>
                                                                    {customer?.booking_questionnaire != null ? (
                                                                    <span className="text-booking-card-manifest-yes px-[9px] bg-[#00FF044D] rounded-[6px] border border-gray-300">Yes</span>
                                                                    ) : (
                                                                    <span className="text-booking-card-manifest-no px-[9px] bg-[#FCE4DF4D] rounded-[6px] border border-gray-300">No</span>
                                                                    )}
                                                                </div>
                                                                <div className="flex gap-4">
                                                                    <span className="text-booking-card-manifest-lbl w-[69px]">Checked-in</span>
                                                                    {customer?.manifest?.length > 0 ? (
                                                                        <span className="text-booking-card-manifest-yes px-[9px] bg-[#00FF044D] rounded-[6px] border border-gray-300">Yes</span>
                                                                    ) : (
                                                                        <span className="text-booking-card-manifest-no px-[9px] bg-[#FCE4DF4D] rounded-[6px] border border-gray-300">No</span>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </div>                                                         
                                                    </div>
                                                    )})}
                                              
                                                </div>                                            
                                            )}
                                        </div>
                                    </div>
                                    {/* Booking Information */}
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
                                                    <span onClick={() => setToggleInformation(!toggleCustomer)}>
                                                        {toggleCustomer ? icons.cheveron_down : icons.cheveron_up}
                                                    </span>
                                                 </div>
                                            </span>
                                            {toggleInformation && (
                                                <div className="flex flex-col w-full  gap-2 ">
                                                    <div className="flex flex-col px-6 py-6 gap-2 border-b border-gray-100">
                                                        {/* Pickup Location */}
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
                                                    </div>                                               
                                                </div>                                            
                                            )}
                                        </div>
                                    </div>

                                </div>
                            </div>
                            {/* right */}
                            <div className='flex-2 items-center'>
                                <div className="flex flex-col gap-6">
                                    {/* Staff */}
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
                                                                    onClick={() => {}}
                                                                >{icons.envelope}</span>
                                                                <span className={`flex items-center justify-center w-[36px] h-[36px] gap-2 px-4 py-2 rounded-[10px] border border-primary-14
                                                                        bg-white-gradient-overlay text-black shadow-inset-white 
                                                                        hover:border-black hover:text-blackcursor-pointer`}
                                                                    onClick={() => {}}
                                                                >{icons.cross}</span>

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>                                                
                                            ):(
                                                <span className="flex flex-col w-full px-6 py-6 gap-2">
                                                        <CustomSelect
                                                            name="wholesaler_id"
                                                            value={data.wholesaler_id}
                                                            
                                                            onChange={(e) => setData(e.target.name, e.target.value)}
                                                            error={errors.wholesaler_id}
                                                            options={staff}
                                                        />                                            
                                                    <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">
                                                        <button onClick={() => {}} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
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
                                    {/* Comments */}
                                    <div className=" overflow-hidden shadow-medium sm:rounded-lg">
                                        <div className="flex flex-col justify-between items-center ">
                                            <span className='flex items-center justify-between w-full px-6 py-4 border-b border-gray-300'>
                                                <span className="text-booking-card-heading block text-start">Comments</span>
                                                <span onClick={() => setToggleComments(!toggleComments)}>
                                                    {toggleComments ? icons.cheveron_down : icons.cheveron_up}
                                                </span>
                                            </span>
                                            {toggleComments && (
                                            <span className="flex flex-col w-full px-6 py-6 gap-2">
                                                <CustomInputText
                                                    title="Name"
                                                    name="consultant_name"
                                                    value={data.consultant_name}
                                                    height="h-[52px]"
                                                    onChange={(e) => setData('consultant_name', e.target.value)}
                                                    errors={errors}
                                                />                                            
                                                <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">

                                                    <CustomInputText
                                                        title="Email"
                                                        name="consultant_email"
                                                        value={data.consultant_email}
                                                        height="h-[52px]"
                                                        type="email"                            
                                                        onChange={(e) => setData('consultant_email', e.target.value)}
                                                        errors={errors}
                                                    />
                                                    <CustomInputText
                                                        title="Contact Number"
                                                        name="consultant_mobile"
                                                        value={data.consultant_mobile}
                                                        height="h-[52px]"
                                                        onChange={(e) => setData('consultant_mobile', e.target.value)}
                                                        errors={errors}
                                                    />
                                                </div>
                                                <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">

                                                    <CustomInputText
                                                        title="PAX"
                                                        name="consultant_email"
                                                        value={data.consultant_email}
                                                        height="h-[52px]"
                                                        type="email"                            
                                                        onChange={(e) => setData('consultant_email', e.target.value)}
                                                        errors={errors}
                                                    />
                                                    <CustomInputText
                                                        title="Nationality"
                                                        name="consultant_mobile"
                                                        value={data.consultant_mobile}
                                                        height="h-[52px]"
                                                        onChange={(e) => setData('consultant_mobile', e.target.value)}
                                                        errors={errors}
                                                    />
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
                                                <span onClick={() => setTogglePayment(!togglePayment)}>
                                                    {togglePayment ? icons.cheveron_down : icons.cheveron_up}
                                                </span>
                                            </span>
                                            {togglePayment && (
                                            <span className="flex flex-col w-full px-6 py-6 gap-2">
                                                <CustomInputText
                                                    title="Name"
                                                    name="consultant_name"
                                                    value={data.consultant_name}
                                                    height="h-[52px]"
                                                    onChange={(e) => setData('consultant_name', e.target.value)}
                                                    errors={errors}
                                                />                                            
                                                <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">

                                                    <CustomInputText
                                                        title="Email"
                                                        name="consultant_email"
                                                        value={data.consultant_email}
                                                        height="h-[52px]"
                                                        type="email"                            
                                                        onChange={(e) => setData('consultant_email', e.target.value)}
                                                        errors={errors}
                                                    />
                                                    <CustomInputText
                                                        title="Contact Number"
                                                        name="consultant_mobile"
                                                        value={data.consultant_mobile}
                                                        height="h-[52px]"
                                                        onChange={(e) => setData('consultant_mobile', e.target.value)}
                                                        errors={errors}
                                                    />
                                                </div>
                                                <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">

                                                    <CustomInputText
                                                        title="PAX"
                                                        name="consultant_email"
                                                        value={data.consultant_email}
                                                        height="h-[52px]"
                                                        type="email"                            
                                                        onChange={(e) => setData('consultant_email', e.target.value)}
                                                        errors={errors}
                                                    />
                                                    <CustomInputText
                                                        title="Nationality"
                                                        name="consultant_mobile"
                                                        value={data.consultant_mobile}
                                                        height="h-[52px]"
                                                        onChange={(e) => setData('consultant_mobile', e.target.value)}
                                                        errors={errors}
                                                    />
                                                </div>
                                            </span>
                                            )}
                                        </div>
                                    </div>
                                    {/* Email Log */}
                                    <div className=" overflow-hidden shadow-medium sm:rounded-lg">
                                        <div className="flex flex-col justify-between items-center ">
                                            <span className='flex items-center justify-between w-full px-6 py-4 border-b border-gray-300'>
                                                <span className="text-booking-card-heading block text-start">Email Log</span>
                                                <span onClick={() => setToggleEmail(!toggleEmail)}>
                                                    {toggleEmail ? icons.cheveron_down : icons.cheveron_up}
                                                </span>
                                            </span>
                                            {toggleEmail && (
                                            <span className="flex flex-col w-full px-6 py-6 gap-2">
                                                <CustomInputText
                                                    title="Name"
                                                    name="consultant_name"
                                                    value={data.consultant_name}
                                                    height="h-[52px]"
                                                    onChange={(e) => setData('consultant_name', e.target.value)}
                                                    errors={errors}
                                                />                                            
                                                <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">

                                                    <CustomInputText
                                                        title="Email"
                                                        name="consultant_email"
                                                        value={data.consultant_email}
                                                        height="h-[52px]"
                                                        type="email"                            
                                                        onChange={(e) => setData('consultant_email', e.target.value)}
                                                        errors={errors}
                                                    />
                                                    <CustomInputText
                                                        title="Contact Number"
                                                        name="consultant_mobile"
                                                        value={data.consultant_mobile}
                                                        height="h-[52px]"
                                                        onChange={(e) => setData('consultant_mobile', e.target.value)}
                                                        errors={errors}
                                                    />
                                                </div>
                                                <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">

                                                    <CustomInputText
                                                        title="PAX"
                                                        name="consultant_email"
                                                        value={data.consultant_email}
                                                        height="h-[52px]"
                                                        type="email"                            
                                                        onChange={(e) => setData('consultant_email', e.target.value)}
                                                        errors={errors}
                                                    />
                                                    <CustomInputText
                                                        title="Nationality"
                                                        name="consultant_mobile"
                                                        value={data.consultant_mobile}
                                                        height="h-[52px]"
                                                        onChange={(e) => setData('consultant_mobile', e.target.value)}
                                                        errors={errors}
                                                    />
                                                </div>
                                            </span>
                                            )}
                                        </div>
                                    </div>
                                </div> 
                            </div>


                        </div>

                </div>

            </div>
        </AuthenticatedLayout>
    );
}
