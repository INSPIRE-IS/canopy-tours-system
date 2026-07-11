// resources/js/Pages/Admin/InventorySlotRanges/Index.jsx

import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import InventorySlotRangeModal from './InventorySlotRangeModal';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import OutlineButton from '@/Components/OutlineButton';
import Pagination from '@/Components/Pagination';
import HeaderServices from '@/Components/HeaderServices';
import AvailabilityPreviewModal from '../Inventories/AvailabilityPreviewModal';

export default function Index({ permissions, slotRanges, services, auth, searchTerm, sortColumn, sortDirection, viewMode = 'detailed' }) {
    const { props } = usePage();
    const [selectedRange, setSelectedRange] = useState(null);
    const [search, setSearch] = useState(searchTerm);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState('create');
    const [isOpenTab, setIsOpenTab] = useState('DateOverrides');
    const [isPreviewModalOpen, setIsPreviewModalOpen] = useState(false);
    const [currentView, setCurrentView] = useState(viewMode);
    const [visibilityFilter, setVisibilityFilter] = useState('all'); // all | online | admin_only
    const inventoryOptions = (services || [])
        .flatMap((service) => (service.seasons || [])
            .filter((season) => season.inventory)
            .map((season) => ({
                ...season.inventory,
                service,
                season,
            })));

    const ranges = slotRanges?.data || [];
    const filteredRanges = ranges.filter((range) => {
        if (visibilityFilter === 'online') return !range.internal_only;
        if (visibilityFilter === 'admin_only') return !!range.internal_only;
        return true;
    });

    const renderVisibilityBadge = (range) => {
        const isAdminOnly = !!range?.internal_only;
        return (
            <span
                className={`inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium ${
                    isAdminOnly ? 'bg-gray-200 text-gray-800' : 'bg-green-100 text-green-800'
                }`}
                title={isAdminOnly ? 'Visible to admin calendars only' : 'Visible to online + admin calendars'}
            >
                {isAdminOnly ? 'Admin-only' : 'Online'}
            </span>
        );
    };

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
            view: currentView,
        });
    };

    const handleDelete = (range) => {
        if (confirm('Are you sure you want to delete this override?')) {
            const data = Array.isArray(range?.ids) ? { ids: range.ids } : {};
            router.delete(route('admin.inventory-slot-ranges.destroy', range.id), {
                preserveScroll: true,
                data,
            });
        }
    };

    const toggleView = (nextView) => {
        setCurrentView(nextView);
        router.get(route('admin.inventory-slot-ranges.index'), {
            search,
            sort: sortColumn,
            direction: sortDirection,
            view: nextView,
        }, {
            preserveScroll: true,
            preserveState: true,
        });
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
                            <span className="flex gap-2 items-center">
                                <span className="flex gap-2 items-center mr-2">
                                    <span className="text-sm text-gray-500">Show:</span>
                                    <button
                                        type="button"
                                        onClick={() => setVisibilityFilter('all')}
                                        className={`px-3 h-[38px] rounded-[10px] border text-sm ${
                                            visibilityFilter === 'all'
                                                ? 'bg-blue-600 border-blue-600 text-white'
                                                : 'bg-white border-secondary-100 text-gray-700 hover:bg-gray-50'
                                        }`}
                                    >
                                        All
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setVisibilityFilter('online')}
                                        className={`px-3 h-[38px] rounded-[10px] border text-sm ${
                                            visibilityFilter === 'online'
                                                ? 'bg-blue-600 border-blue-600 text-white'
                                                : 'bg-white border-secondary-100 text-gray-700 hover:bg-gray-50'
                                        }`}
                                    >
                                        Online
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setVisibilityFilter('admin_only')}
                                        className={`px-3 h-[38px] rounded-[10px] border text-sm ${
                                            visibilityFilter === 'admin_only'
                                                ? 'bg-blue-600 border-blue-600 text-white'
                                                : 'bg-white border-secondary-100 text-gray-700 hover:bg-gray-50'
                                        }`}
                                    >
                                        Admin-only
                                    </button>
                                </span>
                                {currentView === 'grouped' ? (
                                    <BlueButton
                                        title="Compact"
                                        width="w-[170px]"
                                        onClick={() => toggleView('grouped')}
                                    />
                                ) : (
                                    <OutlineButton
                                        title="Compact"
                                        width="w-[170px]"
                                        onClick={() => toggleView('grouped')}
                                    />
                                )}
                                {currentView === 'detailed' ? (
                                    <BlueButton
                                        title="Expanded"
                                        width="w-[160px]"
                                        onClick={() => toggleView('detailed')}
                                    />
                                ) : (
                                    <OutlineButton
                                        title="Expanded"
                                        width="w-[160px]"
                                        onClick={() => toggleView('detailed')}
                                    />
                                )}
                                <BlueButton title="Preview Availability" onClick={() => setIsPreviewModalOpen(true)} />
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
                                    {currentView === 'grouped' ? (
                                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                                            <thead className="bg-gray-50 text-left text-gray-500">
                                                <tr className="flex justify-between items-center">
                                                    <th className="w-[70px] p-3">ID</th>
                                                    <th className="w-[140px] p-3">Service</th>
                                                    <th className="w-[140px] p-3">Season</th>
                                                    <th className="w-[120px] p-3">Start</th>
                                                    <th className="w-[120px] p-3">End</th>
                                                    <th className="w-[200px] p-3">Times</th>
                                                    <th className="w-[120px] p-3">Capacity</th>
                                                    <th className="w-[180px] p-3">Note</th>
                                                    <th className="w-[110px] p-3">Internal</th>
                                                    <th className="w-[185px] p-3">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-100">
                                                {filteredRanges.map((range) => (
                                                    <tr key={range.id} className="flex justify-between items-center hover:bg-gray-50">
                                                        <td className="w-[70px] p-3">{range.id}</td>
                                                        <td className="w-[140px] p-3">{range.inventory?.service?.name}</td>
                                                        <td className="w-[140px] p-3">{range.inventory?.season?.name}</td>
                                                        <td className="w-[120px] p-3">{range.start_date}</td>
                                                        <td className="w-[120px] p-3">{range.end_date}</td>
                                                        <td className="w-[200px] p-3">
                                                            {(range.time_slots || []).map((slot) => slot.slice(0, 5)).join(', ')}
                                                        </td>
                                                        <td className="w-[120px] p-3">{range.capacity_override ?? ''}</td>
                                                        <td className="w-[180px] p-3">{range.note ?? ''}</td>
                                                        <td className="w-[110px] p-3">{renderVisibilityBadge(range)}</td>
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
                                    ) : (
                                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                                            <thead className="bg-gray-50 text-left text-gray-500">
                                                <tr className="flex justify-between items-center">
                                                    <th className="w-[70px] p-3">ID</th>
                                                    <th className="w-[140px] p-3">Service</th>
                                                    <th className="w-[140px] p-3">Season</th>
                                                    <th className="w-[120px] p-3">Start</th>
                                                    <th className="w-[120px] p-3">End</th>
                                                    <th className="w-[110px] p-3">Time</th>
                                                    <th className="w-[120px] p-3">Capacity</th>
                                                    <th className="w-[180px] p-3">Note</th>
                                                    <th className="w-[110px] p-3">Internal</th>
                                                    <th className="w-[185px] p-3">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-100">
                                                {filteredRanges.map((range) => (
                                                    <tr key={range.id} className="flex justify-between items-center hover:bg-gray-50">
                                                        <td className="w-[70px] p-3">{range.id}</td>
                                                        <td className="w-[140px] p-3">{range.inventory?.service?.name}</td>
                                                        <td className="w-[140px] p-3">{range.inventory?.season?.name}</td>
                                                        <td className="w-[120px] p-3">{range.start_date}</td>
                                                        <td className="w-[120px] p-3">{range.end_date}</td>
                                                        <td className="w-[110px] p-3">
                                                            {range.time_slot?.start_time ? range.time_slot.start_time.slice(0, 5) : ''}
                                                        </td>
                                                        <td className="w-[120px] p-3">{range.capacity_override ?? ''}</td>
                                                        <td className="w-[180px] p-3">{range.note ?? ''}</td>
                                                        <td className="w-[110px] p-3">{renderVisibilityBadge(range)}</td>
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
                                    )}
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
                    <AvailabilityPreviewModal
                        isOpen={isPreviewModalOpen}
                        onClose={() => setIsPreviewModalOpen(false)}
                        inventory={inventoryOptions?.[0] || null}
                        inventoryOptions={inventoryOptions}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
