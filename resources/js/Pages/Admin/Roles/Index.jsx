// resources/js/Pages/Roles/Index.jsx

import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import CModal from '@/Components/CModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import RoleModal from './RoleModal';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import Pagination from '@/Components/Pagination';
import SettingsHeader from '@/Components/HeaderSites';


export default function Index({ user, permissions, roles, adminRole, permissionList, teams, auth, searchTerm, sortColumn, sortDirection }) {
    //const [modalOpen, setModalOpen] = useState(false);

    const [selectedRole, setSelectedRole] = useState(null);
    const { props } = usePage();
    console.log('auth on role ', auth);
    console.log('user on role ', user);
    console.log('permissions ', permissions);
    const [search, setSearch] = useState(searchTerm);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState("create");
    const [isOpenTab, setIsOpenTab] = useState('Users');

    const handleOpenModal = (mode, role = null) => {
        console.log('mode ', mode);
        console.log('role ', role);
        setModalMode(mode);
        setSelectedRole(role);
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedRole(null);
    };

    const handleSearchChange = (e) => {
        console.log('value ', e.target.value);
        setSearch(e.target.value);
    };

    const handleSearchSubmit = (e) => {
        // e.preventDefault();
        // Redirect to the route with search query and other parameters
        console.log('search ', search);

        window.location.href = route('admin.roles.index', {
            search,
            sort: sortColumn,
            direction: sortDirection
        });
    };

    const handleDelete = (role) => {
        if (confirm("Are you sure you want to delete this role?")) {
            //post(route("roles.destroy", role), { _method: "DELETE",});
            router.delete(route('admin.roles.destroy', role), { preserveScroll: true });
        }
    };










    return (
        // <AuthenticatedLayout role={props.auth.role}>
        <AuthenticatedLayout
            user={props.auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Roles</h2>}
        >
            <Head title="Roles" />
            {/* <header className="bg-white shadow-md px-6 py-4 flex justify-between items-center">
            <div className="flex items-center gap-4">

                <span className="font-semibold text-lg text-gray-800">Roles, </span>
            </div>
        </header> */}
            <div className="bg-white p-6 ">
                <SettingsHeader permissions={permissions} isOpenTab={isOpenTab} setIsOpenTab={setIsOpenTab} />
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <SearchBar search={search} handleSearchChange={(e) => handleSearchChange(e)} handleSearchSubmit={handleSearchSubmit} />

                    <span className="flex gap-2">
                        {permissions?.roles_create && (
                            <BlueButton title="Add Role" onClick={() => handleOpenModal("create")} icon={true} />
                        )}
                    </span>
                </div>
                {/* bottom */}
                <div className="bg-white border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                            <span className='flex items-center gap-2'>
                                <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                                <span className="font-semibold text-lg text-gray-800 block text-start">Roles</span>
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
                                    {roles?.data.map((role, i) => (
                                        <tr key={role.id} className="flex justify-between items-center hover:bg-gray-50">
                                            <td className="w-[95px] p-3">{role.id}</td>
                                            <td className="w-[95px] p-3">{role.name}</td>

                                            <td className="w-[185px] p-3 flex  gap-2">
                                                {/* {permissions.roles_edit && ()} */}
                                                <button onClick={() => handleOpenModal("edit", role)}
                                                    className="flex items-center gap-1 text-green-600 hover:text-green-800">
                                                    Edit
                                                </button>

                                                {permissions.roles_delete && (
                                                    <button onClick={() => handleDelete(role)}
                                                        className="flex items-center gap-1 text-red-600 hover:text-red-800">
                                                        Remove
                                                    </button>)}
                                                {permissions.roles_show && (
                                                    <button onClick={() => handleOpenModal("view", role)}
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
                <Pagination links={roles?.links} />
            </div>

            <RoleModal
                isOpen={isModalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                role={selectedRole}
                roles={roles}
                user={user}
                permissions={permissionList}
                adminRole={adminRole}
                permissionList={permissionList}
                teams={teams}
                team={auth?.team}
            />
        </AuthenticatedLayout>
    );
}
