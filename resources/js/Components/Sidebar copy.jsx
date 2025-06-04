import { Link } from '@inertiajs/react';
import { useState } from 'react';
//import { FaTachometerAlt, FaUsers, FaUnlockAlt, FaBriefcase, FaUser, FaProjectDiagram, FaFolder, FaKey, FaSignOutAlt, FaCaretDown } from 'react-icons/fa';

export default function Sidebar({ isOpen, setIsOpen, permissions, translations }) {
    const [isUserManagementOpen, setIsUserManagementOpen] = useState(false);
    console.log(permissions);

    return (
                   
        // <div className="sidebar-menu transform -translate-x-full ease-in bg-white">
        <div className={`fixed z-40 inset-y-0 left-0 w-64 transform bg-white transition-transform duration-300 ease-in-out 
            ${isOpen ? 'translate-x-0' : '-translate-x-full'}`}>
            <div className="flex items-center justify-center mt-4">
                <span className="text-white text-2xl mx-2 font-semibold">{translations?.site_title}</span> 
            </div>

            <nav className="mt-4">
                <Link  className="nav-link">
                    {/* href={route('admin?.home')}<FaTachometerAlt className="mr-2" /> */}
                    <span>Dashboard</span>
                </Link>

                {permissions?.user_management_access && (
                    <div className="nav-dropdown ">
                        <button className="nav-link flex items-center justify-between w-full" onClick={() => setIsUserManagementOpen(!isUserManagementOpen)}>
                            {/* <FaUsers className="mr-2" /> */}
                            <span>{translations?.user_management}</span>
                            {/* <FaCaretDown className={`ml-auto transform ${isUserManagementOpen ? 'rotate-180' : ''}`} /> */}
                        </button>
                        {isUserManagementOpen && (
                            <div className="dropdown-items">
                                {permissions.permission_access && (
                                    <Link  className="nav-link">
                                        {/* href={route('admin?.permissions.index')}<FaUnlockAlt className="mr-2" /> */}
                                        <span>{translations?.permission}</span>
                                    </Link>
                                )}
                                {permissions.role_access && (
                                    <Link  className="nav-link">
                                        {/* href={route('admin?.roles.index')}<FaBriefcase className="mr-2" /> */}
                                        <span>{translations?.role}</span>
                                    </Link>
                                )}
                                {permissions.user_access && (
                                    <Link  className="nav-link">
                                        {/* href={route('admin?.users.index')}<FaUser className="mr-2" /> */}
                                        <span>{translations?.user}</span>
                                    </Link>
                                )}
                            </div>
                        )}
                    </div>
                )}
                {permissions?.project_access && (
                    <Link  className="nav-link">
                        {/* href={route('admin?.projects.index')}<FaProjectDiagram className="mr-2" /> */}
                        <span>{translations?.project}</span>
                    </Link>
                )}
                {permissions?.folder_access && (
                    <Link  className="nav-link">
                        {/* href={route('admin?.folders.index')}<FaFolder className="mr-2" /> */}
                        <span>{translations?.folder}</span>
                    </Link>
                )}
                <Link  className="nav-link">
                    {/* href={route('profile?.password.edit')}<FaKey className="mr-2" /> */}
                    <span>{translations?.change_password}</span>
                </Link>
                <button onClick={() => document.getElementById('logoutform').submit()} className="nav-link">
                    {/* <FaSignOutAlt className="mr-2" /> */}
                    <span>{translations?.logout}</span>
                </button>
            </nav>
        </div>
    );
}
