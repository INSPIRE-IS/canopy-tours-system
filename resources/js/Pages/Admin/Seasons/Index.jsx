// resources/js/Pages/Users/Index.jsx

import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import CModal from '@/Components/CModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import SeasonModal from './SeasonModal';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import Pagination from '@/Components/Pagination';


export default function Index({ permissions, seasons, services, seasonFields, auth, searchTerm, sortColumn, sortDirection }) {
    //const [modalOpen, setModalOpen] = useState(false);

    const [selectedSeason, setSelectedSeason] = useState(null);
    const { props } = usePage();
    console.log('seasons on season ', seasons);
    const [search, setSearch] = useState(searchTerm);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState("create");

    const handleOpenModal = (mode, season = null) => {
        console.log('mode ', mode);
        console.log('season ', season);
        setModalMode(mode);
        setSelectedSeason(season);
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedSeason(null);
    };

    const handleSearchChange = (e) => {
        console.log('value ', e.target.value);
        setSearch(e.target.value);
    };

    const handleSearchSubmit = (e) => {
        // e.preventDefault();
        // Redirect to the route with search query and other parameters
        console.log('search ', search);

        window.location.href = route('seasons.index', {
            search,
            sort: sortColumn,
            direction: sortDirection
        });
    };

    const handleDelete = (season) => {
        if (confirm("Are you sure you want to delete this season?")) {
            //post(route("seasons.destroy", season), { _method: "DELETE",});
            router.delete(route('admin.seasons.destroy', season), { preserveScroll: true });
        }
    };

    return (
        // <AuthenticatedLayout user={props.auth.user}>
        <AuthenticatedLayout
            user={props.auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Seasons</h2>}
        >
            <Head title="Seasons" />
            {/* <header className="bg-white shadow-md px-6 py-4 flex justify-between items-center">
            <div className="flex items-center gap-4">

                <span className="font-semibold text-lg text-gray-800">Seasons, {props.auth?.user?.name}</span>
            </div>
        </header> */}
            <div className="bg-white p-6 ">
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <SearchBar search={search} handleSearchChange={(e) => handleSearchChange(e)} handleSearchSubmit={handleSearchSubmit} />

                    <span className="flex gap-2">
                        {permissions.season_create && (
                            <BlueButton title="Add Season" onClick={() => handleOpenModal("create")} icon={true} />
                        )}
                    </span>
                </div>
                {/* bottom */}
                <div className="bg-white border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                            <span className='flex items-center gap-2'>
                                <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                                <span className="font-semibold text-lg text-gray-800 block text-start">Seasons</span>
                            </span>
                        </div>
                        <div className="flex justify-between items-center px-4 pb-[9px] ">
                            {/* table */}

                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50 text-left text-gray-500">
                                    <tr className='flex justify-between items-center'>
                                        <th className="w-[95px] p-3">ID</th>
                                        <th className="w-[95px] p-3">Name</th>
                                        <th className="w-[132px] p-3">Service id</th>
                                        <th className="w-[132px] p-3">Start date</th>
                                        <th className="w-[132px] p-3">End date</th>
                                        <th className="w-[132px] p-3">Visibility</th>
                                        <th className="w-[185px] p-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {seasons?.data.map((season, i) => (
                                        <tr key={season.id} className="flex justify-between items-center hover:bg-gray-50">
                                            <td className="w-[95px] p-3">{season.id}</td>
                                            <td className="w-[95px] p-3">{season.name}</td>
                                            <td className="w-[132px] p-3">{season.service.name}</td>
                                            <td className="w-[132px] p-3">{season.start_date}</td>
                                            <td className="w-[132px] p-3">{season.end_date}</td>
                                            <td className="w-[132px] p-3">
                                                <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${season.internal_only ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800'}`}>
                                                    {season.internal_only ? 'Staff only' : 'Bookable online'}
                                                </span>
                                            </td>

                                            <td className="w-[185px] p-3 flex  gap-2">
                                                {permissions.season_edit && (
                                                    <button onClick={() => handleOpenModal("edit", season)}
                                                        className="flex items-center gap-1 text-green-600 hover:text-green-800">
                                                        Edit
                                                    </button>)}

                                                {permissions.season_edit && (
                                                    <button onClick={() => handleDelete(season)}
                                                        className="flex items-center gap-1 text-red-600 hover:text-red-800">
                                                        Remove
                                                    </button>)}
                                                {permissions.season_edit && (
                                                    <button onClick={() => handleOpenModal("view", season)}
                                                        className="flex items-center gap-1 text-blue-600 hover:text-blue-800">
                                                        View
                                                    </button>)}
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
                <Pagination links={seasons?.links} />
            </div>

            <SeasonModal
                isOpen={isModalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                season={selectedSeason}
                services={services}
                seasonFields={seasonFields}
                team={auth?.team}
                auth={auth}
            />
        </AuthenticatedLayout>
    );
}
