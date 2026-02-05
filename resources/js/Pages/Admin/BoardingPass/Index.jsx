// resources/js/Pages/BoardingPass/Index.jsx

import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import CModal from '@/Components/CModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import BoardingPassModal from './BoardingPassModal';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import Pagination from '@/Components/Pagination';
import SettingsHeader from '@/Components/HeaderSites';


export default function Index({
    permissions,
    boardingpass,
    services = [],
    seasons = [],
    sectionTitleIcons = [],
    auth,
    searchTerm,
    sortColumn,
    sortDirection,
}) {
    //const [modalOpen, setModalOpen] = useState(false);

    const [selectedBoardingPass, setSelectedBoardingPass] = useState(null);
    const { props } = usePage();
    console.log('auth on boardingpass ', auth);
    const [search, setSearch] = useState(searchTerm);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState("create");
    const [isOpenTab, setIsOpenTab] = useState('Boarding_Pass');

    const handleOpenModal = (mode, boardingpass = null) => {
        console.log('mode ', mode);
        console.log('boardingpass ', boardingpass);
        setModalMode(mode);
        setSelectedBoardingPass(boardingpass);
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedBoardingPass(null);
    };

    const handleSearchChange = (e) => {
        console.log('value ', e.target.value);
        setSearch(e.target.value);
    };

    const handleSearchSubmit = (e) => {
        // e.preventDefault();
        // Redirect to the route with search query and other parameters
        console.log('search ', search);

        window.location.href = route('admin.boardingpass.index', {
            search,
            sort: sortColumn,
            direction: sortDirection
        });
    };

    const handleDelete = (boardingpass) => {
        if (confirm("Are you sure you want to delete this boardingpass?")) {
            //post(route("boardingpass.destroy", boardingpass), { _method: "DELETE",});
            router.delete(route('admin.boardingpass.destroy', boardingpass), { preserveScroll: true });
        }
    };

    return (
        // <AuthenticatedLayout boardingpass={props.auth.boardingpass}>
        <AuthenticatedLayout
            user={props.auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">BoardingPass</h2>}
        >
            <Head title="BoardingPass" />
            {/* <header className="bg-white shadow-md px-6 py-4 flex justify-between items-center">
            <div className="flex items-center gap-4">

                <span className="font-semibold text-lg text-gray-800">BoardingPass, </span>
            </div>
        </header> */}
            <div className="bg-white p-6 ">
                <SettingsHeader permissions={permissions} isOpenTab={isOpenTab} setIsOpenTab={setIsOpenTab} />
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <SearchBar search={search} handleSearchChange={(e) => handleSearchChange(e)} handleSearchSubmit={handleSearchSubmit} />

                    <span className="flex gap-2">
                        {permissions?.boarding_pass_templates_create && (
                            <BlueButton title="Add Boarding Pass" onClick={() => handleOpenModal("create")} icon={true} />
                        )}
                    </span>
                </div>
                {/* bottom */}
                <div className="bg-white border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                            <span className='flex items-center gap-2'>
                                <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                                <span className="font-semibold text-lg text-gray-800 block text-start">Boarding Pass</span>
                            </span>
                        </div>
                        <div className="flex justify-between items-center px-4 pb-[9px] ">
                            {/* table */}

                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50 text-left text-gray-500">
                                    <tr className='flex justify-between items-center'>
                                        <th className="w-[95px] p-3">ID</th>
                                        <th className="w-[95px] p-3">Name</th>
                                        <th className="w-[185px] p-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {boardingpass?.data.map((boardingpass, i) => (
                                        <tr key={boardingpass.id} className="flex justify-between items-center hover:bg-gray-50">
                                            <td className="w-[95px] p-3">{boardingpass.id}</td>
                                            <td className="w-[95px] p-3">{boardingpass.name}</td>

                                            <td className="w-[185px] p-3 flex  gap-2">
                                                {permissions.boarding_pass_templates_edit && (
                                                    <button onClick={() => handleOpenModal("edit", boardingpass)}
                                                        className="flex items-center gap-1 text-green-600 hover:text-green-800">
                                                        Edit
                                                    </button>)}

                                                {permissions.boarding_pass_templates_delete && (
                                                    <button onClick={() => handleDelete(boardingpass)}
                                                        className="flex items-center gap-1 text-red-600 hover:text-red-800">
                                                        Remove
                                                    </button>)}
                                                {permissions.boarding_pass_templates_show && (
                                                    <button onClick={() => handleOpenModal("view", boardingpass)}
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
                <Pagination links={boardingpass?.links} />
            </div>

            <BoardingPassModal
                isOpen={isModalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                boardingpass={selectedBoardingPass}
                services={services}
                seasons={seasons}
                sectionTitleIcons={sectionTitleIcons}
                user={auth.user}
            />

        </AuthenticatedLayout>
    );
}
