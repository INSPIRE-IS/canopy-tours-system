// resources/js/Pages/Users/Index.jsx

import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import ServiceModal from './ServiceModal';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import Pagination from '@/Components/Pagination';
import ServicesHeader from '@/Components/ServicesHeader';


export default function Index({ permissions,services,serviceFields, auth, searchTerm, sortColumn, sortDirection }) {
  //const [modalOpen, setModalOpen] = useState(false);

  const [selectedService, setSelectedService] = useState(null);
  const { props } = usePage();
  console.log('auth on user ', auth);
  const [search, setSearch] = useState(searchTerm);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isOpenTab, setIsOpenTab] = useState('Services');
  const [modalMode, setModalMode] = useState("create");

    const handleOpenModal = (mode, service = null) => {
        console.log('mode ', mode);
        console.log('service ', service);
        setModalMode(mode);
        setSelectedService(service);
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedService(null);
    };

    const handleSearchChange = (e) => {
        console.log('value ', e.target.value);
        setSearch(e.target.value);
    };

    const handleSearchSubmit = (e) => {
       // e.preventDefault();
        // Redirect to the route with search query and other parameters
        console.log('search ', search);

        window.location.href = route('services.index', {
            search,
            sort: sortColumn,
            direction: sortDirection
        });
    };
    const handleDelete = (service) => {
        if (confirm("Are you sure you want to delete this service?")) {
            //post(route("services.destroy", service), { _method: "DELETE",});
            router.delete(route('services.destroy', service), {preserveScroll: true});
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
            <div id="Services-content" className="tab-content">
                <div className="bg-white p-6 ">
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <SearchBar search={search} handleSearchChange={(e) => handleSearchChange(e)} handleSearchSubmit={handleSearchSubmit} />

                    <span className="flex gap-2">
                        {permissions.services_create && (
                        <BlueButton title="Add service" onClick={() => handleOpenModal("create")} icon={true}/>
                        )}
                    </span>
                </div>
                {/* bottom */}
                <div className="bg-white border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                        <span className='flex items-center gap-2'>
                            <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                            <span className="font-semibold text-lg text-gray-800 block text-start">services</span>
                        </span>
                    </div>
                    <div className="flex justify-between items-center px-4 pb-[9px] ">
                        {/* table */}

                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-left text-gray-500">
                            <tr className='flex justify-between items-center'>
                                <th className="w-[10%] p-3">ID</th>
                                <th className="w-[70%] p-3">Name</th>
                                <th className="w-[20%] p-3">Actions</th>
                            </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                            {services?.data.map((service, i) => (
                                <tr key={service.id} className="flex justify-between items-center hover:bg-gray-50">
                                <td className="w-[10%] p-3">{service.id}</td>
                                <td className="w-[70%] p-3">{service.name}</td>
                                <td className="w-[20%] p-3 flex  gap-2">
                                    {permissions.services_edit && (
                                    <button onClick={() => handleOpenModal("edit",service)}
                                    className="flex items-center gap-1 text-green-600 hover:text-green-800">
                                    Edit
                                    </button>)}

                                    {permissions.services_edit && (
                                    <button onClick={() => handleDelete(service)}
                                    className="flex items-center gap-1 text-red-600 hover:text-red-800">
                                    Remove
                                    </button>)}
                                    {permissions.services_edit && (
                                    <button onClick={() => handleOpenModal("view",service)}
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
                <Pagination links={services?.links} />
            </div>  

            <ServiceModal
                isOpen={isModalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                service={selectedService}
                serviceFields={serviceFields}
                auth={auth}
                team = {auth?.team}
            />
            </div>
        </div> 

    </AuthenticatedLayout>
  );
}
