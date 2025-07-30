import CustomerToolsModal from '@/Components/CustomerToolsModal';
import Header from '@/Components/Header';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import React, { useState } from 'react';

export default function Dashboard({ auth  }) {





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
                    <button className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                        <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                        <span className="text-btn-outline-gray">New Booking</span>
                    </button>
                    <button className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-red-gradient-overlay border-[#C65468] text-white shadow-inset-white cursor-pointer">
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




                    </div>
                    </div>
                </div>

            </div>
        </AuthenticatedLayout>
    );
}
