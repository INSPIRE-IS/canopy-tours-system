import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import CModal from '@/Components/CModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import WholesalerContactModal from './WholesalerContactModal';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import Pagination from '@/Components/Pagination';
import HeaderWholesalers from '@/Components/HeaderWholesalers';


export default function Index({ wholesalercontacts, wholesalers, auth, permissions, searchTerm, sortColumn, sortDirection }) {
    //const [modalOpen, setModalOpen] = useState(false);

    const [selectedWholesalerContact, setSelectedWholesalerContact] = useState(null);
    const { props } = usePage();
    console.log('auth on wholesalercontact ', auth);
    console.log('wholesalercontacts on wholesalercontacts ', wholesalercontacts);
    const [search, setSearch] = useState(searchTerm);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState("create");
    const [isOpenTab, setIsOpenTab] = useState('wholesaler_contacts');

    const handleOpenModal = (mode, wholesalercontact = null) => {
        console.log('mode ', mode);
        console.log('wholesalercontact ', wholesalercontact);
        setModalMode(mode);
        setSelectedWholesalerContact(wholesalercontact);
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedWholesalerContact(null);
    };

    const handleSearchChange = (e) => {
        console.log('value ', e.target.value);
        setSearch(e.target.value);
    };

    const handleSearchSubmit = (e) => {
        // e.preventDefault();
        // Redirect to the route with search query and other parameters
        console.log('search ', search);

        window.location.href = route('admin.wholesaler-contacts.index', {
            search,
            sort: sortColumn,
            direction: sortDirection
        });
    };

    const handleDelete = (wholesalercontact) => {
        if (confirm("Are you sure you want to delete this wholesalercontact?")) {
            //post(route("wholesalercontacts.destroy", wholesalercontact), { _method: "DELETE",});
            router.delete(route('admin.wholesaler-contacts.destroy', wholesalercontact), { preserveScroll: true });
        }
    };

    return (
        // <AuthenticatedLayout wholesalercontact={props.auth.wholesalercontact}>
        <AuthenticatedLayout
            user={props.auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">WholesalerContacts</h2>}
        >
            <Head title="WholesalerContacts" />
            {/* <header className="bg-white shadow-md px-6 py-4 flex justify-between items-center">
            <div className="flex items-center gap-4">

                <span className="font-semibold text-lg text-gray-800">WholesalerContacts, {props.auth?.wholesalercontact?.name}</span>
            </div>
        </header> */}
            <div className="bg-white p-6 ">
                <HeaderWholesalers permissions={permissions} isOpenTab={isOpenTab} setIsOpenTab={setIsOpenTab} />
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <SearchBar search={search} handleSearchChange={(e) => handleSearchChange(e)} handleSearchSubmit={handleSearchSubmit} />

                    <span className="flex gap-2">
                        {permissions.wholesaler_contacts_create && (
                            <BlueButton title="Add Wholesaler Contact" onClick={() => handleOpenModal("create")} icon={true} />
                        )}
                    </span>
                </div>
                {/* bottom */}
                <div className="bg-white border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                            <span className='flex items-center gap-2'>
                                <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                                <span className="font-semibold text-lg text-gray-800 block text-start">Wholesaler Contacts</span>
                            </span>
                        </div>
                        <div className="flex justify-between items-center px-4 pb-[9px] ">
                            {/* table */}

                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50 text-left text-gray-500">
                                    <tr className='flex justify-between items-center'>
                                        <th className="w-[95px] p-3">ID</th>
                                        <th className="w-[95px] p-3">Name</th>
                                        <th className="w-[268px] p-3">email</th>
                                        <th className="w-[132px] p-3">phone</th>
                                        <th className="w-[132px] p-3">type</th>
                                        <th className="w-[185px] p-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {wholesalercontacts?.data.map((wholesalercontact, i) => (
                                        <tr key={wholesalercontact.id} className="flex justify-between items-center hover:bg-gray-50">
                                            <td className="w-[95px] p-3">{wholesalercontact.id}</td>
                                            <td className="w-[95px] p-3">{wholesalercontact.name}</td>
                                            <td className="w-[268px] p-3">{wholesalercontact.email}</td>
                                            <td className="w-[132px] p-3">{wholesalercontact.phone}</td>
                                            <td className="w-[132px] p-3">{wholesalercontact.type}</td>
                                            <td className="w-[185px] p-3 flex  gap-2">
                                                {permissions.wholesaler_contacts_edit && (
                                                    <button onClick={() => handleOpenModal("edit", wholesalercontact)}
                                                        className="flex items-center gap-1 text-green-600 hover:text-green-800">
                                                        Edit
                                                    </button>)}

                                                {permissions.wholesaler_contacts_edit && (
                                                    <button onClick={() => handleDelete(wholesalercontact)}
                                                        className="flex items-center gap-1 text-red-600 hover:text-red-800">
                                                        Remove
                                                    </button>)}
                                                {permissions.wholesaler_contacts_edit && (
                                                    <button onClick={() => handleOpenModal("view", wholesalercontact)}
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
                <Pagination links={wholesalercontacts?.links} />
            </div>

            <WholesalerContactModal
                isOpen={isModalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                wholesalercontact={selectedWholesalerContact}
                wholesalers={wholesalers}
                team={auth?.team}
            />
        </AuthenticatedLayout>
    );
}
