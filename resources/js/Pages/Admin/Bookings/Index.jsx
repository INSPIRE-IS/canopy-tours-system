import BookingToolsModal from '@/Components/BookingToolsModal';
import CustomerToolsModal from '@/Components/CustomerToolsModal';
import GuestEmailModal from '@/Components/GuestEmailModal';
import Header from '@/Components/Header';
import { icons } from '@/icons';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import React, { useState } from 'react';
// import { useRoute } from 'ziggy-js';
// import { Ziggy } from '@/ziggy'; // the generated file

export default function Dashboard({ auth, bookings }) {


    console.log("bookings", bookings)
    const [expanded, setExpanded] = useState(null); // track which booking is expanded
    const [toolsCustomer, setToolsCustomer] = useState(null); // null or { id: ..., name: ... }
    const [customerToolsTarget, setCustomerToolsTarget] = useState(null); // { customer, position }
    const [bookingToolsTarget, setBookingToolsTarget] = useState(null); // { customer, position }
    const [guestEmailTarget, setGuestEmailTarget] = useState(null); // { customer, position }
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState("create");
    const [selectedBooking, setSelectedBooking] = useState(null);

    const openCustomerToolsModal = (event, customer) => {
        event.preventDefault();
        console.log("customer----------tool-------- ", customer)

        const rect = event.target.getBoundingClientRect();
        setCustomerToolsTarget({
            customer,
            position: {
                top: rect.top + window.scrollY + 30, // offset below the button
                left: rect.left + window.scrollX,
            }
        });
    };

    const CustomerToolClick = (tool, booking) => {
        console.log("-------tool clicked-------", tool);
        if (tool === 'check_in') {
            router.get(route('admin.bookings.check_in'));
        }
        if (tool === 'send_invoice') {
            router.get(route('admin.bookings.send_invoice'));
        }
        if (tool === 'resend_invoice') {
            router.get(route('admin.bookings.resend_invoice'));
        }
        if (tool === 'manifest') {
            router.get(route('admin.bookings.manifest'));
        }
        if (tool === 'customer_invoice') {
            router.get(route('admin.bookings.customer_invoice'));
        }
        if (tool === 'print_ticket') {
            router.get(route('admin.bookings.print_ticket'));
        }
        if (tool === 'voucher') {
            router.get(route('admin.bookings.voucher'));
        }
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

    function BookingToolClick(tool, booking) {

        console.log("-------tool clicked-------", tool);
        if (tool === 'edit_timeslot') {
            router.get(route('admin.bookings.edit_timeslot'));
        }
        if (tool === 'message_guest') {
            setSelectedBooking(booking);
            setGuestEmailTarget(true);
        }
        if (tool === 'message_staff') {
            router.get(route('admin.messenger.createTopic'));
        }
        if (tool === 'check_in_progress') {
            router.get(route('admin.bookings.check_in_progress'));
        }
        if (tool === 'manifest_edit') {
            router.get(route('admin.bookings.manifest_edit'));
        }
        if (tool === 'remove_resource') {
            router.get(route('admin.bookings.remove_resource'));
        }
        if (tool === 'cancel_all_bookings') {
            router.get(route('admin.bookings.cancel_all_bookings'));
        }

    };
    const handleCloseModal = () => {
        setGuestEmailTarget(false);
        setSelectedBooking(null);
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Bookings</h2>}
        >
            <Head title="Dashboard" />
            <div className="bg-white p-6 ">
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <span>
                        <span className="font-semibold text-lg text-gray-800 block text-start">Welcome Back, {auth?.user?.name}👋</span>
                        <span className="font-semibold text-lg text-gray-800 block text-start">We have 2 Pending Tasks and 5 New Bookings.</span>

                    </span>
                    <span className="flex gap-2">
                        <button className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] border-primary-14 text-primary-14 cursor-pointer">
                            <img src="/assets/icons/bookings.svg" alt="bookings" className="w-5 h-5" />
                            <span className="text-btn-outline-gray">01 January 2025</span>
                        </button>
                        <button onClick={() => router.get(route('admin.bookings.create'))} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                            <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                            <span className="text-btn-outline-gray">New Booking</span>
                        </button>
                        <button onClick={() => router.get(route('questionnaire.index'))} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-red-gradient-overlay border-[#C65468] text-white shadow-inset-white cursor-pointer">
                            <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                            <span className="text-btn-outline-gray">Check in</span>
                        </button>
                    </span>
                </div>
                {/* bottom */}
                <div className="bg-white border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="flex justify-between items-center px-4 pt-4 pb-[9px] ">
                            <span className='flex items-center gap-2'>
                                <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                                <span className="font-semibold text-lg text-gray-800 block text-start">Bookings</span>
                            </span>
                            <span className="flex gap-2">
                                <button className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] border-primary-14 text-primary-14 cursor-pointer">
                                    <img src="/assets/icons/search-normal.svg" alt="bookings" className="w-5 h-5" />
                                    <span className="text-btn-outline-gray">Search here</span>
                                </button>
                                <button className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] border-primary-14 text-primary-14 cursor-pointer">
                                    <img src="/assets/icons/filter.svg" alt="filter" className="w-5 h-5" />
                                    <span className="text-btn-outline-gray">Filter</span>
                                </button>
                                <button className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] border-primary-14 text-primary-14 cursor-pointer">
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
                                    {bookings?.map((item, i) => {
                                        console.log('item ', item);
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
                                                    <td className="w-[132px] p-3">R{item?.price.toLocaleString()}</td>
                                                    <td className="w-[111px] p-3">R{item?.paid.toLocaleString()}</td>
                                                    <td className="w-[107px] p-3">
                                                        <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${item?.due === 0 ? 'bg-blue-100 text-blue-600'
                                                            : item?.dueStatus === 'Normal' ? 'bg-orange-100 text-orange-600'
                                                                : 'bg-red-100 text-red-600'
                                                            }`}>
                                                            • {item?.due === 0 ? 'R0.00' : item?.dueStatus === 'Normal' ? 'Normal' : `R${item?.due.toLocaleString()}`}
                                                        </span>
                                                    </td>
                                                    <td className="w-[185px] p-3 flex gap-2">
                                                        {expanded === i ? (
                                                            <button onClick={() => router.get(route('admin.booking_payments.edit', item?.id))} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
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
                                                                    <div className="text-sm text-gray-800 font-medium">{customer.name}</div>
                                                                    <div className="text-sm text-gray-600">{customer.email} | {customer.phone}</div>
                                                                    <div className="text-sm text-gray-600">Children Age: {customer?.age || 'N/A'}</div>
                                                                </td>
                                                                <td className="w-[132px] p-3">R{customer.price?.toLocaleString() || '0.00'}</td>
                                                                <td className="w-[111px] p-3">R{customer.paid?.toLocaleString() || '0.00'}</td>
                                                                <td className="w-[107px] p-3">
                                                                    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${customer.due === 0 ? 'bg-blue-100 text-blue-600'
                                                                        : customer.dueStatus === 'Normal' ? 'bg-orange-100 text-orange-600'
                                                                            : 'bg-red-100 text-red-600'
                                                                        }`}>
                                                                        • {customer.due === 0 ? 'R0.00' : customer.dueStatus === 'Normal' ? 'Normal' : `R${customer.due.toLocaleString()}`}
                                                                    </span>
                                                                </td>
                                                                <td className="flex flex-col w-[120px] p-3  gap-2">
                                                                    <button type='button' className="btn-tool">{icons.docs} Docs</button>
                                                                    <button type='button' onClick={(e) => openCustomerToolsModal(e, customer)} className="btn-tool">{icons.tools} Tools</button>
                                                                    {/* <button type='button'
                                                                    onClick={(e) => openCustomerToolsModal(e, customer)}
                                                                    className="flex items-center gap-1 text-blue-600 hover:text-blue-800"
                                                                    >
                                                                    Tools
                                                                    </button> */}
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
                {customerToolsTarget && (
                    <CustomerToolsModal
                        customer={customerToolsTarget.customer}
                        position={customerToolsTarget.position}
                        onClose={() => setCustomerToolsTarget(null)}
                        CustomerToolClick={CustomerToolClick}
                    />
                )}
                {bookingToolsTarget && (
                    <BookingToolsModal
                        booking={bookingToolsTarget.booking}
                        position={bookingToolsTarget.position}
                        onClose={() => setBookingToolsTarget(null)}
                        BookingToolClick={BookingToolClick}
                    />
                )}

                <GuestEmailModal
                    isOpen={guestEmailTarget}
                    onClose={handleCloseModal}
                    mode='create'
                    booking={selectedBooking}
                />


            </div>
        </AuthenticatedLayout>
    );
}
