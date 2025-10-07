import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import CModal from '@/Components/CModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import VoucherModal from './VoucherModal';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import Pagination from '@/Components/Pagination';
import SettingsHeader from '@/Components/HeaderSites';


export default function Index({ vouchers, services, users, auth, permissions, searchTerm, sortColumn, sortDirection }) {
    //const [modalOpen, setModalOpen] = useState(false);

    const [selectedVoucher, setSelectedVoucher] = useState(null);

    const { props } = usePage();
    console.log('auth on voucher ', auth);
    const [search, setSearch] = useState(searchTerm);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState("create");
    const [isOpenTab, setIsOpenTab] = useState('Booking_Vouchers');

    const handleOpenModal = (mode, voucher = null) => {
        console.log('mode ', mode);
        console.log('voucher ', voucher);
        setModalMode(mode);
        setSelectedVoucher(voucher);
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedVoucher(null);
    };

    const handleSearchChange = (e) => {
        console.log('value ', e.target.value);
        setSearch(e.target.value);
    };

    const handleSearchSubmit = (e) => {
        // e.preventDefault();
        // Redirect to the route with search query and other parameters
        console.log('search ', search);

        window.location.href = route('booking_vouchers.index', {
            search,
            sort: sortColumn,
            direction: sortDirection
        });
    };

    const handleDelete = (voucher) => {
        if (confirm("Are you sure you want to delete this voucher?")) {
            //post(route("vouchers.destroy", voucher), { _method: "DELETE",});
            router.delete(route('booking_vouchers.destroy', voucher), { preserveScroll: true });
        }
    };

    return (
        // <AuthenticatedLayout voucher={props.auth.voucher}>
        <AuthenticatedLayout
            user={props.auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Booking Details Pdf</h2>}
        >
            <Head title="vouchers" />
            {/* <header className="bg-white shadow-md px-6 py-4 flex justify-between items-center">
            <div className="flex items-center gap-4">

                <span className="font-semibold text-lg text-gray-800">vouchers, {props.auth?.voucher?.name}</span>
            </div>
        </header> */}
            <div className="bg-white p-6 ">
                <SettingsHeader permissions={permissions} isOpenTab={isOpenTab} setIsOpenTab={setIsOpenTab} />
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <SearchBar search={search} handleSearchChange={(e) => handleSearchChange(e)} handleSearchSubmit={handleSearchSubmit} />

                    <span className="flex gap-2">
                        {permissions.booking_vouchers_create && (
                            <BlueButton title="Add Booking Detail" onClick={() => handleOpenModal("create")} icon={true} />
                        )}
                    </span>
                </div>
                {/* bottom */}
                <div className="bg-white border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                            <span className='flex items-center gap-2'>
                                <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                                <span className="font-semibold text-lg text-gray-800 block text-start">Booking Details Pdf</span>
                            </span>
                        </div>
                        <div className="flex justify-between items-center px-4 pb-[9px] ">
                            {/* table */}

                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50 text-left text-gray-500">
                                    <tr className='flex justify-between items-center'>
                                        <th className="w-[95px] p-3">ID</th>
                                        <th className="w-[95px] p-3">Service</th>
                                        <th className="w-[95px] p-3">Season</th>
                                        <th className="w-[95px] p-3">Name</th>
                                        <th className="w-[255px] p-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {vouchers?.data.map((voucher, i) => (
                                        <tr key={voucher.id} className="flex justify-between items-center hover:bg-gray-50">
                                            <td className="w-[95px] p-3">{voucher.id}</td>
                                            <td className="w-[95px] p-3">{voucher.service.name}</td>
                                            <td className="w-[95px] p-3">{voucher.season.name}</td>
                                            <td className="w-[95px] p-3">{voucher.name}</td>
                                            <td className="w-[255px] p-3 flex  gap-2">
                                                {permissions.booking_vouchers_edit && (
                                                    <button onClick={() => handleOpenModal("edit", voucher)}
                                                        className="flex items-center gap-1 text-green-600 hover:text-green-800">
                                                        Edit
                                                    </button>)}

                                                {permissions.booking_vouchers_edit && (
                                                    <button onClick={() => handleDelete(voucher)}
                                                        className="flex items-center gap-1 text-red-600 hover:text-red-800">
                                                        Remove
                                                    </button>)}
                                                {permissions.booking_vouchers_show && (
                                                    // <button onClick={() => handleOpenModal("view", voucher)}
                                                    //     className="flex items-center gap-1 text-blue-600 hover:text-blue-800">
                                                    //     View
                                                    // </button>
                                                    <button onClick={() => window.open(route('admin.booking_vouchers.download', voucher.id), '_blank')}
                                                        className="flex items-center gap-1 text-blue-600 hover:text-blue-800">
                                                        Download
                                                    </button>
                                                )}

                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>


            <div className="mt-4">
                <Pagination links={vouchers?.links} />
            </div>

            <VoucherModal
                isOpen={isModalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                voucher={selectedVoucher}
                services={services}
                user={auth?.user}
                teams={auth?.teams}
            />
        </AuthenticatedLayout>
    );
}
