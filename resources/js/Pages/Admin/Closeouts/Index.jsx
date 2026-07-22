// resources/js/Pages/Users/Index.jsx

import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import CloseoutModal from './CloseoutModal';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import Pagination from '@/Components/Pagination';
import HeaderServices from '@/Components/HeaderServices';
import AvailabilityPreviewModal from '../Inventories/AvailabilityPreviewModal';


export default function Index({ permissions, closeouts, seasons, services, auth, searchTerm, sortColumn, sortDirection, overlapGroups = [], checkOverlaps = false }) {
    //const [modalOpen, setModalOpen] = useState(false);
    console.log('auth on user ', auth);
    console.log('closeouts ', closeouts);

    const [selectedCloseout, setSelectedCloseout] = useState(null);
    const { props } = usePage();

    const [search, setSearch] = useState(searchTerm);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isPreviewModalOpen, setIsPreviewModalOpen] = useState(false);
    const [isOpenTab, setIsOpenTab] = useState('Closeouts');
    const [modalMode, setModalMode] = useState("create");
    const [showOverlapCheck, setShowOverlapCheck] = useState(checkOverlaps);
    const overlapCloseoutIds = new Set((overlapGroups || []).flatMap((group) => group.closeout_ids || []));
    const inventoryOptions = (services || [])
        .flatMap((service) => (service.seasons || [])
            .filter((season) => season.inventory)
            .map((season) => ({
                ...season.inventory,
                service,
                season,
            })));

    const handleOpenModal = (mode, closeout = null) => {
        console.log('mode ', mode);
        console.log('closeout ', closeout);
        setModalMode(mode);
        setSelectedCloseout(closeout);
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedCloseout(null);
    };

    const handleSearchChange = (e) => {
        console.log('value ', e.target.value);
        setSearch(e.target.value);
    };

    const handleSearchSubmit = (e) => {
        // e.preventDefault();
        // Redirect to the route with search query and other parameters
        console.log('search ', search);

        window.location.href = route('admin.closeouts.index', {
            search,
            sort: sortColumn,
            direction: sortDirection
        });
    };
    const handleDelete = (closeout) => {
        if (confirm("Are you sure you want to delete this closeout?")) {
            //post(route("closeouts.destroy", closeout), { _method: "DELETE",});
            router.delete(route('admin.closeouts.destroy', closeout), { preserveScroll: true });
        }
    };

    const handleCheckOverlaps = () => {
        setShowOverlapCheck(true);
        router.get(route('admin.closeouts.index'), {
            search,
            sort: 'service_id',
            direction: 'asc',
            check_overlaps: 1,
        }, {
            preserveScroll: true,
            preserveState: false,
        });
    };

    const overlapSummaryFor = (closeout) => {
        const groups = closeout.overlap_groups || [];
        if (!groups.length) return null;

        return groups.map((group) => {
            const names = (group.names || []).filter(Boolean).join(' / ');
            const cells = (group.cells || []).slice(0, 3).join(', ');
            const extraCells = (group.cells || []).length > 3 ? ` +${group.cells.length - 3} more` : '';
            return `${names} (${group.date_window?.start} to ${group.date_window?.end}; ${cells}${extraCells})`;
        }).join(' | ');
    };

    // function toggleDropdown (tab, routeName) {
    //     setIsOpenTab(prev => (prev === tab ? '' : tab));
    //     router.get(route(routeName), {}, {
    //     preserveScroll: true,
    //     preserveState: true,
    //     });
    // }

    return (
        // <AuthenticatedLayout user={props.auth.user}>
        <AuthenticatedLayout
            user={props.auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Users</h2>}
        >
            <Head title="Users" />
            <div className="overflow-x-auto px-[15px]">
                <HeaderServices permissions={permissions} isOpenTab={isOpenTab} setIsOpenTab={setIsOpenTab} />
                <div id="Closeouts-content" className="tab-content">
                    <div className="bg-white p-6 ">
                        {/* top */}
                        <div className="flex justify-between items-center mb-6">
                            <SearchBar search={search} handleSearchChange={(e) => handleSearchChange(e)} handleSearchSubmit={handleSearchSubmit} />

                            <span className="flex gap-2">
                                <BlueButton title="Preview Availability" onClick={() => setIsPreviewModalOpen(true)} />
                                <BlueButton title="Check Overlaps" onClick={handleCheckOverlaps} />
                                {permissions.closeouts_create && (
                                    <BlueButton title="Add Closeout" onClick={() => handleOpenModal("create")} icon={true} />
                                )}
                            </span>
                        </div>
                        {showOverlapCheck && (
                            <div className={`mb-4 rounded-lg border px-4 py-3 text-sm ${overlapGroups.length ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800'}`}>
                                {overlapGroups.length
                                    ? `${overlapGroups.length} closeout overlap ${overlapGroups.length === 1 ? 'group' : 'groups'} found. Rows on this page with conflicts are highlighted.`
                                    : 'No closeout overlaps found for the current team.'}
                            </div>
                        )}
                        {/* bottom */}
                        <div className="bg-white border border-secondary-100">
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                                    <span className='flex items-center gap-2'>
                                        <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                                        <span className="font-semibold text-lg text-gray-800 block text-start">closeouts</span>
                                    </span>
                                </div>
                                <div className="flex justify-between items-center px-4 pb-[9px] ">
                                    {/* table */}

                                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead className="bg-gray-50 text-left text-gray-500">
                                            <tr className='flex justify-between items-center'>
                                                <th className="w-[95px] p-3">ID</th>
                                                <th className="w-[95px] p-3">Name</th>
                                                <th className="w-[120px] p-3">Service</th>
                                                <th className="w-[105px] p-3">Start Date</th>
                                                <th className="w-[105px] p-3">End Date</th>
                                                <th className="w-[95px] p-3">Season</th>
                                                <th className="w-[185px] p-3">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100">
                                            {closeouts?.data.map((closeout, i) => {
                                                const hasOverlap = showOverlapCheck && (closeout.has_overlap || overlapCloseoutIds.has(closeout.id));
                                                const overlapSummary = overlapSummaryFor(closeout);

                                                return (
                                                <tr
                                                    key={closeout.id}
                                                    title={hasOverlap ? overlapSummary : undefined}
                                                    className={`flex justify-between items-center hover:bg-gray-50 ${hasOverlap ? 'bg-red-50 text-red-900 ring-1 ring-inset ring-red-200' : ''}`}
                                                >
                                                    <td className="w-[95px] p-3">{closeout.id}</td>
                                                    <td className="w-[95px] p-3">
                                                        <div>{closeout.name}</div>
                                                        {hasOverlap && (
                                                            <div className="mt-1 text-xs font-semibold text-red-700">Overlap</div>
                                                        )}
                                                    </td>
                                                    <td className="w-[120px] p-3">{closeout.service?.name}</td>
                                                    <td className="w-[105px] p-3">{closeout.start_date}</td>
                                                    <td className="w-[105px] p-3">{closeout.end_date}</td>
                                                    <td className="w-[95px] p-3">{closeout.season?.name}</td>
                                                    <td className="w-[185px] p-3 flex  gap-2">
                                                        {permissions.closeouts_edit && (
                                                            <button onClick={() => handleOpenModal("edit", closeout)}
                                                                className="flex items-center gap-1 text-green-600 hover:text-green-800">
                                                                Edit
                                                            </button>)}

                                                        {(permissions.closeouts_delete || permissions.closeouts_edit) && (
                                                            <button onClick={() => handleDelete(closeout)}
                                                                className="flex items-center gap-1 text-red-600 hover:text-red-800">
                                                                Remove
                                                            </button>)}
                                                        {permissions.closeouts_edit && (
                                                            <button onClick={() => handleOpenModal("view", closeout)}
                                                                className="flex items-center gap-1 text-blue-600 hover:text-blue-800">
                                                                View
                                                        </button>)}
                                                    </td>
                                                </tr>
                                            )})}
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>
                    </div>


                    <div className="mt-4">
                        <Pagination links={closeouts?.links} />
                    </div>

                    <CloseoutModal
                        isOpen={isModalOpen}
                        onClose={handleCloseModal}
                        mode={modalMode}
                        closeout={selectedCloseout}
                        auth={auth}
                        seasons={seasons}
                        services={services}
                        team_id={auth?.user.team_id}
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
