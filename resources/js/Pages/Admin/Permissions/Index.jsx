// resources/js/Pages/Permissions/Index.jsx

import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import CModal from '@/Components/CModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import PermissionModal from './PermissionModal';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import Pagination from '@/Components/Pagination';


export default function Index({ users,permissions,permission_list,teams,auth, searchTerm, sortColumn, sortDirection }) {
  //const [modalOpen, setModalOpen] = useState(false);

  const [selectedPermission, setSelectedPermission] = useState(null);
  const { props } = usePage();

    console.log('auth on permission ', auth);
    const [search, setSearch] = useState(searchTerm);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState("create");

    const handleOpenModal = (mode, permission = null) => {
        console.log('mode ', mode);
        console.log('permission ', permission);
        setModalMode(mode);
        setSelectedPermission(permission);
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedPermission(null);
    };

    const handleSearchChange = (e) => {
        console.log('value ', e.target.value);
        setSearch(e.target.value);
    };

    const handleSearchSubmit = (e) => {
       // e.preventDefault();
        // Redirect to the route with search query and other parameters
        console.log('search ', search);

        window.location.href = route('permissions.index', {
            search,
            sort: sortColumn,
            direction: sortDirection
        });
    };

    const handleDelete = (permission) => {
        if (confirm("Are you sure you want to delete this permission?")) {
            //post(route("permissions.destroy", permission), { _method: "DELETE",});
            router.delete(route('permissions.destroy', permission), {preserveScroll: true});
        }
    };










  return (
    // <AuthenticatedLayout user={props.auth.user}>
    <AuthenticatedLayout
        user={props.auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Permissions</h2>}
    >
        <Head title="Permissions" />
        {/* <header className="bg-white shadow-md px-6 py-4 flex justify-between items-center">
            <div className="flex items-center gap-4">

                <span className="font-semibold text-lg text-gray-800">Permissions, {props.auth?.user?.name}</span>
            </div>
        </header> */}
            <div className="bg-white p-6 ">
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <SearchBar search={search} handleSearchChange={(e) => handleSearchChange(e)} handleSearchSubmit={handleSearchSubmit} />

                    <span className="flex gap-2">
                        {permissions.permission_edit && (
                        <BlueButton title="Add Permission" onClick={() => handleOpenModal("create")} icon={true}/>
                            )}
                    </span>
                </div>
                {/* bottom */}
                <div className="bg-white border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                        <span className='flex items-center gap-2'>
                            <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                            <span className="font-semibold text-lg text-gray-800 block text-start">Permissions</span>
                        </span>
                    </div>
                    <div className="flex justify-between items-center px-4 pb-[9px] ">
                        {/* table */}

                        <table className="min-w-full divide-y divide-gray-200 text-sm overflow-x-scroll">
                            <thead className="bg-gray-50 text-left text-gray-500">
                            <tr className='flex justify-between items-center'>
                                <th className="w-[95px] p-3">ID</th>
                                <th className="w-[95px] p-3">Name</th>
                                <th className="w-[185px] p-3">Actions</th>
                            </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                            {permission_list?.data.map((permission, i) => (
                                <tr key={permission.id} className="flex justify-between items-center hover:bg-gray-50">
                                <td className="w-[95px] p-3">{permission.id}</td>
                                <td className="w-[95px] p-3">{permission.name}</td>
                                <td className="w-[185px] p-3 flex  gap-2">
                                    {permissions.permission_edit && (
                                    <button onClick={() => handleOpenModal("edit",permission)}
                                    className="flex items-center gap-1 text-green-600 hover:text-green-800">
                                    Edit
                                    </button>)}

                                    {permissions.permission_edit && (
                                    <button onClick={() => handleDelete(permission)}
                                    className="flex items-center gap-1 text-red-600 hover:text-red-800">
                                    Remove
                                    </button>)}
                                    {permissions.permission_edit && (
                                    <button onClick={() => handleOpenModal("view",permission)}
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
                <Pagination links={permission_list?.links} />
            </div>  
            <PermissionModal
                isOpen={isModalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                permission={selectedPermission}
                teams={teams}
                team = {auth?.team}
            />
        {/* {modalOpen && (
            <CModal title={selectedPermission ? 'Edit Permission' : 'Add Permission'} onClose={closeModal}>
            <form className="space-y-4">
                <input type="text" defaultValue={selectedPermission?.name} placeholder="Name" className="w-full border px-3 py-2" />
                <input type="email" defaultValue={selectedPermission?.email} placeholder="Email" className="w-full border px-3 py-2" />
                <button type="submit" className="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
            </form>
            </CModal>
      )} */}
    </AuthenticatedLayout>
  );
}
