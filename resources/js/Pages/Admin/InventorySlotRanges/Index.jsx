// resources/js/Pages/Admin/InventorySlotRanges/Index.jsx

import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import InventorySlotRangeModal from './InventorySlotRangeModal';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import Pagination from '@/Components/Pagination';
import HeaderServices from '@/Components/HeaderServices';

export default function Index({ permissions, slotRanges, services, auth, searchTerm, sortColumn, sortDirection }) {
    const { props } = usePage();
    const [selectedRange, setSelectedRange] = useState(null);
    const [search, setSearch] = useState(searchTerm);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState('create');
    const [isOpenTab, setIsOpenTab] = useState('DateOverrides');

    const handleOpenModal = (mode, range = null) => {
        setModalMode(mode);
        setSelectedRange(range);
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedRange(null);
    };

    const handleSearchChange = (e) => {
        setSearch(e.target.value);
    };

    const handleSearchSubmit = () => {
        window.location.href = route('admin.inventory-slot-ranges.index', {
            search,
            sort: sortColumn,
            direction: sortDirection,
        });
    };

    const handleDelete = (range) => {
        if (confirm('Are you sure you want to delete this override?')) {
            router.delete(route('admin.inventory-slot-ranges.destroy', range), { preserveScroll: true });
        }
    };

    return (
        <AuthenticatedLayout
            user={props.auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Date Overrides</h2>}
        >
            <Head title="Date Overrides" />
            <div className="overflow-x-auto px-[15px]">
                <HeaderServices permissions={permissions} isOpenTab={isOpenTab} setIsOpenTab={setIsOpenTab} />
                <div id="InventoryOverrides-content" className="tab-content">
                    <div className="bg-white p-6 ">
                        <div className="flex justify-between items-center mb-6">
                            <SearchBar search={search} handleSearchChange={handleSearchChange} handleSearchSubmit={handleSearchSubmit} />
                            <span className="flex gap-2">
                                {permissions.inventories_create && (
                                    <BlueButton title="Add Date Override" onClick={() => handleOpenModal('create')} icon={true} />
                                )}
                            </span>
                        </div>

                        <div className="bg-white border border-secondary-100">
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                                    <span className="flex items-center gap-2">
                                        <img src="/assets/icons/task-square.svg" alt="inventory overrides" className="w-5 h-5" />
                                        <span className="font-semibold text-lg text-gray-800 block text-start">Date Overrides</span>
                                    </span>
                                </div>
                                <div className="flex justify-between items-center px-4 pb-[9px] ">
                                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead className="bg-gray-50 text-left text-gray-500">
                                            <tr className="flex justify-between items-center">
                                                <th className="w-[70px] p-3">ID</th>
                                                <th className="w-[140px] p-3">Service</th>
                                                <th className="w-[140px] p-3">Season</th>
                                                <th className="w-[120px] p-3">Start</th>
                                                <th className="w-[120px] p-3">End</th>
                                                <th className="w-[110px] p-3">Time</th>
                                                <th className="w-[185px] p-3">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100">
                                            {slotRanges?.data.map((range) => (
                                                <tr key={range.id} className="flex justify-between items-center hover:bg-gray-50">
                                                    <td className="w-[70px] p-3">{range.id}</td>
                                                    <td className="w-[140px] p-3">{range.inventory?.service?.name}</td>
                                                    <td className="w-[140px] p-3">{range.inventory?.season?.name}</td>
                                                    <td className="w-[120px] p-3">{range.start_date}</td>
                                                    <td className="w-[120px] p-3">{range.end_date}</td>
                                                    <td className="w-[110px] p-3">
                                                        {range.time_slot?.start_time ? range.time_slot.start_time.slice(0, 5) : ''}
                                                    </td>
                                                    <td className="w-[185px] p-3 flex gap-2">
                                                        {permissions.inventories_edit && (
                                                            <button
                                                                onClick={() => handleOpenModal('edit', range)}
                                                                className="flex items-center gap-1 text-green-600 hover:text-green-800"
                                                            >
                                                                Edit
                                                            </button>
                                                        )}
                                                        {permissions.inventories_edit && (
                                                            <button
                                                                onClick={() => handleDelete(range)}
                                                                className="flex items-center gap-1 text-red-600 hover:text-red-800"
                                                            >
                                                                Remove
                                                            </button>
                                                        )}
                                                        {permissions.inventories_edit && (
                                                            <button
                                                                onClick={() => handleOpenModal('view', range)}
                                                                className="flex items-center gap-1 text-blue-600 hover:text-blue-800"
                                                            >
                                                                View
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
                        <Pagination links={slotRanges?.links} />
                    </div>

                    <InventorySlotRangeModal
                        isOpen={isModalOpen}
                        onClose={handleCloseModal}
                        mode={modalMode}
                        slotRange={selectedRange}
                        auth={auth}
                        services={services}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
