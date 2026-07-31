// resources/js/Pages/Users/Index.jsx

import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import CModal from '@/Components/CModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import UserModal from './UserModal';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import Pagination from '@/Components/Pagination';
import SettingsHeader from '@/Components/HeaderSites';


export default function Index({ users, permissions, roles, teams, auth, searchTerm, sortColumn, sortDirection }) {
    //const [modalOpen, setModalOpen] = useState(false);

    const [selectedUser, setSelectedUser] = useState(null);
    const { props } = usePage();
    console.log('auth on user ', auth);
    const [search, setSearch] = useState(searchTerm);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState("create");
    const [isOpenTab, setIsOpenTab] = useState('Users');

    const handleOpenModal = (mode, user = null) => {
        console.log('mode ', mode);
        console.log('user ', user);
        setModalMode(mode);
        setSelectedUser(user);
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedUser(null);
    };

    const handleSearchChange = (e) => {
        console.log('value ', e.target.value);
        setSearch(e.target.value);
    };

    const handleSearchSubmit = (e) => {
        // e.preventDefault();
        // Redirect to the route with search query and other parameters
        console.log('search ', search);

        window.location.href = route('admin.users.index', {
            search,
            sort: sortColumn,
            direction: sortDirection
        });
    };

    const handleDelete = (user) => {
        if (confirm("Are you sure you want to delete this user?")) {
            //post(route("users.destroy", user), { _method: "DELETE",});
            router.delete(route('admin.users.destroy', user), { preserveScroll: true });
        }
    };

    return (
        // <AuthenticatedLayout user={props.auth.user}>
        <AuthenticatedLayout
            user={props.auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Users</h2>}
        >
            <Head title="Users" />
            {/* <header className="bg-white shadow-md px-6 py-4 flex justify-between items-center">
            <div className="flex items-center gap-4">

                <span className="font-semibold text-lg text-gray-800">Users, {props.auth?.user?.name}</span>
            </div>
        </header> */}
            <div className="bg-white p-6 ">
                <SettingsHeader permissions={permissions} isOpenTab={isOpenTab} setIsOpenTab={setIsOpenTab} />
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <SearchBar search={search} handleSearchChange={(e) => handleSearchChange(e)} handleSearchSubmit={handleSearchSubmit} />

                    <span className="flex gap-2">
                        {permissions.users_create && (
                            <BlueButton title="Add User" onClick={() => handleOpenModal("create")} icon={true} />
                        )}
                    </span>
                </div>
                {/* bottom */}
                <div className="bg-white border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                            <span className='flex items-center gap-2'>
                                <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                                <span className="font-semibold text-lg text-gray-800 block text-start">Users</span>
                            </span>
                        </div>
                        <div className="flex justify-between items-center px-4 pb-[9px] ">
                            {/* table */}

                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50 text-left text-gray-500">
                                    <tr className='flex justify-between items-center'>
                                        <th className="w-[95px] p-3">ID</th>
                                        <th className="w-[95px] p-3">Name</th>
                                        <th className="w-[268px] p-3">Email</th>
                                        <th className="w-[132px] p-3">Roles</th>
                                        <th className="w-[185px] p-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {users?.data.map((user, i) => (
                                        <tr key={user.id} className="flex justify-between items-center hover:bg-gray-50">
                                            <td className="w-[95px] p-3">{user.id}</td>
                                            <td className="w-[95px] p-3">{user.name}</td>
                                            <td className="w-[268px] p-3">{user.email}</td>
                                            <td className="w-[132px] p-3">{user.roles.map((r) => r.name).join(', ')}</td>
                                            <td className="w-[185px] p-3 flex  gap-2">
                                                {permissions.users_edit && (
                                                    <button onClick={() => handleOpenModal("edit", user)}
                                                        className="flex items-center gap-1 text-green-600 hover:text-green-800">
                                                        Edit
                                                    </button>)}

                                                {permissions.users_delete && (
                                                    <button onClick={() => handleDelete(user)}
                                                        className="flex items-center gap-1 text-red-600 hover:text-red-800">
                                                        Remove
                                                    </button>)}
                                                {permissions.users_show && (
                                                    <button onClick={() => handleOpenModal("view", user)}
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
                <Pagination links={users?.links} />
            </div>






            <UserModal
                isOpen={isModalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                user={selectedUser}
                roles={roles}

                teams={teams}
                team={auth?.team}
            />
            {/* {modalOpen && (
            <CModal title={selectedUser ? 'Edit User' : 'Add User'} onClose={closeModal}>
            <form className="space-y-4">
                <input type="text" defaultValue={selectedUser?.name} placeholder="Name" className="w-full border px-3 py-2" />
                <input type="email" defaultValue={selectedUser?.email} placeholder="Email" className="w-full border px-3 py-2" />
                <button type="submit" className="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
            </form>
            </CModal>
      )} */}
        </AuthenticatedLayout>
    );
}
