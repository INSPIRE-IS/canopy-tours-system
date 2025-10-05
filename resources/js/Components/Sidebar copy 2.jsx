import { Link } from '@inertiajs/react';
import { useState } from 'react';
import SidebarMenuItem from './SidebarMenuItem';
//import { FaTachometerAlt, FaUsers, FaUnlockAlt, FaBriefcase, FaUser, FaProjectDiagram, FaFolder, FaKey, FaSignOutAlt, FaCaretDown } from 'react-icons/fa';

export default function Sidebar({ isOpen, setIsOpen, permissions, translations }) {
    const [isUserManagementOpen, setIsUserManagementOpen] = useState(false);
    console.log(permissions);

    return (

        <aside className={`bg-secondary-100 ${isOpen ? 'w-60' : 'w-16'} transition-all duration-300 flex flex-col pt-5`}>
            <div className={`${isOpen ? '' : 'hidden'} pl-[9px]`}>
                <div className="icon-open flex items-center justify-between gap-2 px-4 py-4 bg-secondary-100 w-full h-[57px] rounded-[10px]">
                    <span className='flex flex-row items-center gap-2'>
                        <img src="/assets/icons/logo-icon.png" alt="My Icon" className="w-[31px] h-[27.7px]" />
                        <img src="/assets/icons/logo-name.png" alt="My Icon" className="w-[55px] h-[28.2px]" />
                    </span>

                    <button
                        onClick={() => setIsOpen(!isOpen)}
                        className="hover:bg-gray-200 p-1 rounded-sm transition cursor-pointer"
                    >
                        <img src="/assets/icons/sidebar-left.svg" alt="My Icon" className="w-5 h-5" />
                    </button>
                </div>
            </div>
            <div className={`${isOpen ? 'hidden' : ''} icon-closed pl-4`}>
                <div className="flex items-center justify-between py-4 w-[26px] h-[57px] relative rounded-[10px] ">
                    <button onClick={() => setIsOpen(!isOpen)} className="hover:bg-gray-200 p-1 rounded transition cursor-pointer">
                        <img src="/assets/icons/sidebar-left.svg" alt="My Icon" className="w-5 h-5" />
                    </button>
                </div>
            </div>


            <div className={`${isOpen ? '' : 'hidden'} icon-open px-4 my-4`}>

                <button
                    onClick={() => { }}
                    data-target="submenu-entity-1"
                    data-open="false"
                    className=" w-full flex items-center justify-between px-3 py-2 rounded-lg transition-colors
          bg-primary-3
          hover:bg-primary-3"
                >
                    <span className="flex items-center gap-2">

                        <span className="flex w-[31px] h-[27.7px] rounded-lg items-center justify-center text-white text-lg">
                            <img src="/assets/icons/logo-icon.png" alt="My Icon" className="w-[31px] h-[27.7px]" />
                        </span>
                        <span className="menu-label text-entity-name">Tsitsikamma</span>
                    </span>
                    <span className="arrow transform transition-transform duration-300">
                        <img src="/assets/icons/arrow-down.svg" alt="My Icon" className="w-5 h-5" />
                    </span>
                </button>
            </div>

            <div className={`${isOpen ? 'hidden' : ''} icon-closed px-2 w-full h-[57px] items-center justify-center`}>
                <button onClick={() => { }}
                    className="flex items-center justify-center bg-white border border-seconsary-200 w-12 h-12 hover:bg-gray-200 rounded-[10px] transition cursor-pointer">

                    <span className="flex items-center justify-center w-[31px] h-[27.7px]  text-white text-lg rounded-md leading-none">
                        <img src="/assets/icons/logo-icon.png" alt="My Icon" className="w-[31px] h-[27.7px]" />
                    </span>

                </button>
            </div>




            {/* Example Navigation */}
            <nav className="px-4 space-y-2 mt-4">
                <label className="menu-label text-menu-heading">Main Menu</label>
                {permissions?.user_management_access && (
                    <SidebarMenuItem
                        label="User Management"
                        icon={() => (
                            <img src="/assets/icons/arrow-left.svg" alt="users" className="w-5 h-5" />
                        )}
                        submenuItems={[
                            { name: 'Permissions', url: 'permissions.index' },
                            { name: 'Roles', url: 'roles.index' },
                            { name: 'Users', url: 'users.index' },
                        ]}
                    />)}
                <SidebarMenuItem
                    label="Dashboard"
                    url='dashboard'
                    icon={() => (
                        <img src="/assets/icons/dashboard.svg" alt="dashboard" className="w-5 h-5" />
                    )}
                />
                <SidebarMenuItem
                    label="Bookings"
                    url='bookings.index'
                    icon={() => (
                        <img src="/assets/icons/bookings.svg" alt="bookings" className="w-5 h-5" />
                    )}
                />
                <SidebarMenuItem
                    label="Inventory"
                    url='roles.index'
                    icon={() => (
                        <img src="/assets/icons/inventory.svg" alt="Inventory" className="w-5 h-5" />
                    )}
                />
                <SidebarMenuItem
                    label="Services"
                    url='roles.index'
                    icon={() => (
                        <img src="/assets/icons/services.svg" alt="Services" className="w-5 h-5" />
                    )}
                />
                <SidebarMenuItem
                    label="Messages"
                    url='roles.index'
                    icon={() => (
                        <img src="/assets/icons/messages.svg" alt="Messages" className="w-5 h-5" />
                    )}
                />
                <SidebarMenuItem
                    label="Reports"
                    url='roles.index'
                    icon={() => (
                        <img src="/assets/icons/reports.svg" alt="Reports" className="w-5 h-5" />
                    )}
                />
                <label className="menu-label text-menu-heading">Other</label>
                <SidebarMenuItem
                    label="Help & Center"
                    url='roles.index'
                    icon={() => (
                        <img src="/assets/icons/helpcenter.svg" alt="Help & Center" className="w-5 h-5" />
                    )}
                />
                <SidebarMenuItem
                    label="Settings"
                    url='roles.index'
                    icon={() => (
                        <img src="/assets/icons/settings.svg" alt="Settings" className="w-5 h-5" />
                    )}
                />
                {/* Add other menu items as needed */}
            </nav>
            <div className="mt-auto border-b border-gray-300 pt-[13px]"></div>
            <div className="group px-4">
                <div className="pl-[10px] mt-auto border-t pt-[13px] pb-4 flex items-center gap-3">
                    <div className=" w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-sm font-semibold">
                        <img src="/assets/temp.jpg" alt="Settings" className="w-8 h-8 rounded-full" />
                    </div>
                    <div className="flex flex-col text-sm">
                        <span className={`${isOpen ? '' : 'hidden'} font-medium text-gray-800`} >John Cornor</span>
                        <span className={`${isOpen ? '' : 'hidden'} text-gray-500 text-xs`} >Johncornor@mail.com</span>
                    </div>
                    <div className={`${isOpen ? '' : 'hidden'}`}>
                        <img src="/assets/icons/arrow-down.svg" alt="My Icon" className="w-5 h-5" />
                    </div>
                </div>
            </div>
        </aside>
    );
}
