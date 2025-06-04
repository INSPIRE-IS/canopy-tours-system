import Header from '@/Components/Header';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ auth, bookings  }) {
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
                        {/* table */}

                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-left text-gray-500">
                            <tr className='flex justify-between items-center'>
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
                            {bookings.map((item, i) => (
                                <tr key={i} className="flex justify-between items-center hover:bg-gray-50">
                                <td className="w-[164px] p-3 flex justify-between items-center gap-2">
                                    
                                    <span className='flex items-center gap-2'>
                                    <span className="w-5 h-5 block border border-gray-500 rounded-md"></span>
                                    {item.time}                                        
                                    </span>
                                    <img src="/assets/icons/arrow-left.svg" alt="users" className="w-5 h-5" />
                                </td>
                                <td className="w-[95px] p-3">{item.pax} ({item.maxPax})</td>
                                <td className="w-[268px] p-3">{item.service}</td>
                                <td className="w-[132px] p-3">R{item.price.toLocaleString()}</td>
                                <td className="w-[111px] p-3">R{item.paid.toLocaleString()}</td>
                                <td className="w-[107px] p-3">
                                    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                                    item.due === 0 ? 'bg-blue-100 text-blue-600'
                                    : item.dueStatus === 'Normal' ? 'bg-orange-100 text-orange-600'
                                    : 'bg-red-100 text-red-600'
                                    }`}>
                                    • {item.due === 0 ? 'R0.00' : item.dueStatus === 'Normal' ? 'Normal' : `R${item.due.toLocaleString()}`}
                                    </span>
                                </td>
                                <td className="w-[185px] p-3 flex  gap-2">
                                    <button className="flex items-center gap-1 text-green-600 hover:text-green-800">
                                    Add
                                    </button>
                                    <Link href="#" className="flex items-center gap-1 text-blue-600 hover:text-blue-800">
                                    Tools
                                    </Link>
                                </td>
                                </tr>
                            ))}
                            </tbody>
                        </table>

                    </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
