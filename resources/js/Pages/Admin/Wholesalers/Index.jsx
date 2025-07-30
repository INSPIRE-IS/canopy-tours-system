// resources/js/Pages/Users/Index.jsx

import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import WholesalerModal from './WholesalerModal';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import Pagination from '@/Components/Pagination';
import ServicesHeader from '@/Components/ServicesHeader';


export default function Index({ permissions,wholesalers, auth, searchTerm, sortColumn, sortDirection }) {
  //const [modalOpen, setModalOpen] = useState(false);

  const [selectedWholesaler, setSelectedWholesaler] = useState(null);
  const { props } = usePage();
  console.log('auth on user ', auth);
  const [search, setSearch] = useState(searchTerm);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isOpenTab, setIsOpenTab] = useState('Wholesalers');
  const [modalMode, setModalMode] = useState("create");

    const handleOpenModal = (mode, wholesaler = null) => {
        console.log('mode ', mode);
        console.log('wholesaler ', wholesaler);
        setModalMode(mode);
        setSelectedWholesaler(wholesaler);
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedWholesaler(null);
    };

    const handleSearchChange = (e) => {
        console.log('value ', e.target.value);
        setSearch(e.target.value);
    };

    const handleSearchSubmit = (e) => {
       // e.preventDefault();
        // Redirect to the route with search query and other parameters
        console.log('search ', search);

        window.location.href = route('wholesalers.index', {
            search,
            sort: sortColumn,
            direction: sortDirection
        });
    };
    const handleDelete = (wholesaler) => {
        if (confirm("Are you sure you want to delete this wholesaler?")) {
            //post(route("wholesalers.destroy", wholesaler), { _method: "DELETE",});
            router.delete(route('wholesalers.destroy', wholesaler), {preserveScroll: true});
        }
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
          <ServicesHeader isOpenTab={isOpenTab} setIsOpenTab={setIsOpenTab} />
            <div id="Wholesalers-content" className="tab-content">
                <div className="bg-white p-6 ">
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <SearchBar search={search} handleSearchChange={(e) => handleSearchChange(e)} handleSearchSubmit={handleSearchSubmit} />

                    <span className="flex gap-2">
                        {permissions.wholesalers_create && (
                        <BlueButton title="Add wholesaler" onClick={() => handleOpenModal("create")} icon={true}/>
                        )}
                    </span>
                </div>
                {/* bottom */}
                <div className="bg-white border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                        <span className='flex items-center gap-2'>
                            <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                            <span className="font-semibold text-lg text-gray-800 block text-start">wholesalers</span>
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
                            {wholesalers?.data.map((wholesaler, i) => (
                                <tr key={wholesaler.id} className="flex justify-between items-center hover:bg-gray-50">
                                <td className="w-[95px] p-3">{wholesaler.id}</td>
                                <td className="w-[95px] p-3">{wholesaler.name}</td>
                                <td className="w-[185px] p-3 flex  gap-2">
                                    {permissions.wholesalers_edit && (
                                    <button onClick={() => handleOpenModal("edit",wholesaler)}
                                    className="flex items-center gap-1 text-green-600 hover:text-green-800">
                                    Edit
                                    </button>)}

                                    {permissions.wholesalers_edit && (
                                    <button onClick={() => handleDelete(wholesaler)}
                                    className="flex items-center gap-1 text-red-600 hover:text-red-800">
                                    Remove
                                    </button>)}
                                    {permissions.wholesalers_edit && (
                                    <button onClick={() => handleOpenModal("view",wholesaler)}
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
                <Pagination links={wholesalers?.links} />
            </div>  

            <WholesalerModal
                isOpen={isModalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                wholesaler={selectedWholesaler}
                auth={auth}
                team = {auth?.team}
            />
            </div>
        </div> 

    </AuthenticatedLayout>
  );
}
