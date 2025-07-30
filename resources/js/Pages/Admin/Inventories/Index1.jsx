// resources/js/Pages/Users/Index.jsx

import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import InventoryModal from './InventoryModal';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import Pagination from '@/Components/Pagination';
import ServicesHeader from '@/Components/ServicesHeader';


export default function Index({ permissions,seasons,services,inventories,day_slots,auth, searchTerm, sortColumn, sortDirection }) {
  const [selectedInventory, setSelectedInventory] = useState(null);
  const { props } = usePage();
  console.log('inventory:', inventories);
console.log('timeSlots:', inventories?.data?.[0]?.time_slots);
    console.log('auth on inventory ', auth);
    const [search, setSearch] = useState(searchTerm);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState("create");
    const [isOpenTab, setIsOpenTab] = useState('Inventory');
    const handleOpenModal = (mode, inventory = null) => {
        console.log('mode ', mode);
        console.log('inventory ', inventory);
        setModalMode(mode);
        setSelectedInventory(inventory);
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedInventory(null);
    };

    const handleSearchChange = (e) => {
        console.log('value ', e.target.value);
        setSearch(e.target.value);
    };

    const handleSearchSubmit = (e) => {
       // e.preventDefault();
        // Redirect to the route with search query and other parameters
        console.log('search ', search);

        window.location.href = route('inventories.index', {
            search,
            sort: sortColumn,
            direction: sortDirection
        });
    };

    const handleDelete = (inventory) => {
        if (confirm("Are you sure you want to delete this inventory?")) {
            //post(route("inventorys.destroy", inventory), { _method: "DELETE",});
            router.delete(route('inventories.destroy', inventory), {preserveScroll: true});
        }
    };
  

  function toggleDropdown (tab, routeName) {
    setIsOpenTab(prev => (prev === tab ? '' : tab));
    router.get(route(routeName), {}, {
      preserveScroll: true,
      preserveState: true,
    });
  }

  return (
    // <AuthenticatedLayout user={props.auth.user}>
    <AuthenticatedLayout
        user={props.auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Inventories</h2>}
    >
        <Head title="Inventories" />
        <div className="overflow-x-auto px-[15px]">
<ServicesHeader isOpenTab={isOpenTab} setIsOpenTab={setIsOpenTab} />
            <div id="Inventory-content" className="tab-content">
                            <div className="bg-white p-6 ">
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <SearchBar search={search} handleSearchChange={(e) => handleSearchChange(e)} handleSearchSubmit={handleSearchSubmit} />

                    <span className="flex gap-2">
                        <BlueButton title="Add Inventory" onClick={() => handleOpenModal("create")} icon={true}/>

                    </span>
                </div>
                {/* bottom */}
                <div className="bg-white border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                        <span className='flex items-center gap-2'>
                            <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                            <span className="font-semibold text-lg text-gray-800 block text-start">Inventorys</span>
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
                                <th className="w-[185px] p-3">Start Date</th>
                                <th className="w-[185px] p-3">End Date</th>
                                <th className="w-[132px] p-3">Times</th> 
                                <th className="w-[185px] p-3">Actions</th>
                            </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                            {inventories?.data.map((inventory, i) => (
                                <tr key={inventory?.id} className="flex justify-between items-center hover:bg-gray-50">
                                <td className="w-[95px] p-3">{inventory?.id}</td>
                                <td className="w-[95px] p-3">{inventory?.service?.name}</td>
                                <td className="w-[95px] p-3">{inventory?.season?.name}</td>
                                <td className="w-[185px] p-3">{inventory?.start_date}</td>
                                <td className="w-[185px] p-3">{inventory?.end_date}</td>
                                <td className="w-[132px] p-3">{inventory?.time_slots?.map((r) => r.start_time).join(', ')}</td>
                                <td className="w-[185px] p-3 flex  gap-2">
                                    {permissions.inventory_edit && (
                                    <button onClick={() => handleOpenModal("edit",inventory)}
                                    className="flex items-center gap-1 text-green-600 hover:text-green-800">
                                    Edit
                                    </button>)}

                                    {permissions.inventory_edit && (
                                    <button onClick={() => handleDelete(inventory)}
                                    className="flex items-center gap-1 text-red-600 hover:text-red-800">
                                    Remove
                                    </button>)}
                                    {permissions.inventory_edit && (
                                    <button onClick={() => handleOpenModal("view",inventory)}
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
                <Pagination links={inventories?.links} />
            </div>  
            <InventoryModal
                isOpen={isModalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                inventory={selectedInventory}
                seasons={seasons}
                services={services}
                day_slots={day_slots}
                auth = {auth}
            />
            </div>
        </div> 

    </AuthenticatedLayout>
  );
}
