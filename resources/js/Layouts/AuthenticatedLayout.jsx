import { router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';
import Sidebar from '@/Components/Sidebar1';
import Header from '@/Components/Header';
//import FlashMessages from '@/Components/FlashMessages';
import FlashMessage from '@/Components/FlashMessage';
import SidebarMenuItem from '@/Components/SidebarMenuItem';
import { icons } from '@/icons';

export default function AppLayout({  user, children }) {
 

    const { auth, flash, errors, translations, permissions} = usePage().props;
    const { data, setData, post, reset, processing } = useForm({
        team_id: "",
    });
    console.log('------------translations', translations);
    console.log('test permissions', permissions);
    console.log('test auth', auth);
    const [sidebarOpen, setSidebarOpen] = useState(true);
    const [isOpen, setIsOpen] = useState(sidebarOpen);
    const [isOpenSelect, setIsOpenSelect] = useState('');
    const [isOpenSubmenu, setIsOpenSubmenu] = useState('');
const page = usePage();
const currentRoutePath = page.props.currentRoutePath;

console.log('currentRoutePath ',currentRoutePath); // outputs: "services"

function toggleSidebar() {
    setIsOpen(!isOpen);
}    

function toggleSubmenu (element) {
    setIsOpenSubmenu(prev => (prev === element ? '' : element)); 
}

function toggleDropdown (id) {
    setIsOpenSelect(prev => (prev === id ? '' : id));  
}
// function selectOption (e,dropdownId, value) {
//     console.log('apply changes to ',dropdownId);
//     console.log('apply changes to ',value);
//     setData("team_id", value);
//     e.preventDefault();    
//     post(route("teams.change"), {
//         data: { team_id: value }, 
//         onSuccess: () => {
//             console.log('success');
//         }
//     });
//     setIsOpenSelect('');
// }
function selectOption(dropdownId, value) {
    console.log("apply changes to", dropdownId);

    axios.post(route("admin.teams.change"), {
        team_id: value,
    })
    .then((response) => {
        console.log("Team changed successfully", response.data);
        // Optional: trigger a page reload or flash message
        window.location.reload(); // or Inertia.reload()
    })
    .catch((error) => {
        console.error("Error changing team", error);
    });

    setIsOpenSelect('');
}




  const handleLogout = () => {
    router.post(route('logout'));
};

    return (
        <div className="flex h-[100%] bg-secondary-100">
            {/* <Sidebar isOpen={sidebarOpen} setIsOpen={setSidebarOpen} permissions={permissions} translations={translations} /> */}
              <aside id="sidebar" className={` ${isOpen ? '-translate-x-full md:w-60' : 'translate-x-0 w-[80%] md:w-16'} fixed md:relative z-30 top-0 left-0 h-full bg-secondary-100 transition-all duration-300 overflow-auto sm:overflow-x-visible sm:overflow-y-visible pt-16 md:pt-5 transform md:translate-x-0 -translate-x-full md:block`} >

                <div className={`${isOpen ? '' : 'hidden'} pl-[9px] md:flex`}>
                    <div className={`${isOpen ? '' : 'hidden'} icon-open flex items-center justify-between gap-2 px-4 py-4 w-[215px] h-[57px] relative rounded-[10px]`}>

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
                    <div className=" items-center justify-between pl-0.5 py-4 w-[26px] h-[57px] relative rounded-[10px] ">
                        <button onClick={()=>toggleSidebar()} className="hover:bg-gray-200 p-1 rounded-sm transition cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M1.69167 7.49992L1.69167 12.4999C1.69167 16.6666 3.35834 18.3333 7.525 18.3333L12.525 18.3333C16.6917 18.3333 18.3583 16.6666 18.3583 12.4999L18.3583 7.49992C18.3583 3.33325 16.6917 1.66658 12.525 1.66658L7.52501 1.66658C3.35834 1.66658 1.69167 3.33325 1.69167 7.49992Z" stroke="#BEBEC8" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                            <path d="M13.3583 18.3333L13.3583 1.66658" stroke="#BEBEC8" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                            <path d="M7.52499 12.1333L9.65833 9.99997L7.52499 7.86663" stroke="#BEBEC8" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>           
                        </button>
                    </div>        
                </div> 

                <div className={`${isOpen ? '' : 'hidden'} icon-open px-4 my-4 `}>
                    <div className="relative w-auto">
                        <button 
                        onClick={()=> toggleDropdown('dropdown-E1')} 
                        className="flex h-10 min-w-[208px] items-center justify-between pl-[10px] pr-4 py-2  bg-primary-0 rounded-[10px] border-[3px] border-secondary-200 transition-all 
                        text-secondary-200 hover:border-secondary-300 hover:text-secondary-300 cursor-pointer">
                            <span className="flex items-center gap-2">

                                <span className="flex w-[31px] h-[27.7px] rounded-lg items-center justify-center text-white text-lg">
                                    <img src="/assets/icons/logo-icon.png" alt="My Icon" className="w-[31px] h-[27.7px]" />
                                </span>
                                <span className="menu-label text-entity-name">{auth?.user?.team?.name ?? 'All'}</span>
                            </span>
                            <span className="arrow transform transition-transform duration-300 group-[data-open=true]:!rotate-90">
                            <img src="/assets/icons/arrow-down.svg" alt="My Icon" className="w-5 h-5" />
                            </span>
                        </button> 
                        {isOpenSelect === 'dropdown-E1' && (auth.user.roles[0].id == 1 || auth.user.roles[0].id == 2) && (
                        <ul id="dropdown-E1" className="absolute w-full bg-white mt-1 z-10 text-sm">
                            <li 
                            onClick={() => selectOption('dropdown-E1', null)} 
                            className="flex items-center justify-between border-b border-gray-200 px-4 py-2 hover:bg-primary-7 cursor-pointer"
                            >
                            <span className="flex items-center gap-2">
                                <span id="label-dropdown-E1" className="text-dropdown-option">All</span>
                            </span>
                            <span className="text-[#AF9494]">
                                <svg xmlns="http://www.w3.org/2000/svg" width="4" height="7" viewBox="0 0 4 7" fill="currentColor">
                                <path d="M3.35381 3.3269L0.526913 6.15381L-2.30321e-08 5.6269L2.3 3.3269L-2.24104e-07 1.0269L0.526912 0.499983L3.35381 3.3269Z" fill="currentColor" />
                                </svg>
                            </span>
                            </li>

                            {auth.teams.map((team, i) => (
                            <li 
                                key={i} 
                                onClick={() => selectOption('dropdown-E1', team.id)} 
                                className="flex items-center justify-between border-b border-gray-200 px-4 py-2 hover:bg-primary-7 cursor-pointer"
                            >
                                <span className="flex items-center gap-2">
                                <span id="label-dropdown-E1" className="text-dropdown-option">{team.name}</span>
                                </span>
                                <span className="text-[#AF9494]">
                                <svg xmlns="http://www.w3.org/2000/svg" width="4" height="7" viewBox="0 0 4 7" fill="currentColor">
                                    <path d="M3.35381 3.3269L0.526913 6.15381L-2.30321e-08 5.6269L2.3 3.3269L-2.24104e-07 1.0269L0.526912 0.499983L3.35381 3.3269Z" fill="currentColor" />
                                </svg>
                                </span>
                            </li>
                            ))}
                        </ul>
                        )}

                    </div>
                </div>
                <div className={`${isOpen ? 'hidden' : ''} icon-closed px-2 w-full h-[57px] items-center justify-center`}>
                <button onClick={() => toggleSidebar()} 
                    className="flex items-center justify-center bg-white border border-seconsary-200 w-12 h-12 hover:bg-gray-200 rounded-[10px] transition cursor-pointer">
                    <span className="flex items-center justify-center w-[31px] h-[27.7px]  text-white text-lg rounded-md leading-none">
                        <img src="/assets/icons/logo-icon.png" alt="My Icon" className="w-[31px] h-[27.7px]" />
                    </span>
                </button>
                </div>
                <nav className="px-4 space-y-2 ">
        <label className="menu-label text-menu-heading">Main Menu</label>
 
            <SidebarMenuItem
                label="Dashboard"
                isOpen={isOpen}
                url= 'admin.dashboard'
                iconName="dashboard"
            />
            <SidebarMenuItem
                label="Bookings"
                isOpen={isOpen}
                url= 'admin.bookings.index'
                iconName="bookings"
            />
            <SidebarMenuItem
                label="Services"
                isOpen={isOpen}
                url= 'admin.services.index'
                iconName="services"
            />
            {/* <SidebarMenuItem
                label="Seasons"
                isOpen={isOpen}
                url= 'admin.seasons.index'
                iconName="services"
            /> 
            <SidebarMenuItem
                label="Inventory"
                isOpen={isOpen}
                url= 'admin.inventories.index'
                iconName="inventory"
            /> 
            <SidebarMenuItem
                label="closeouts"
                isOpen={isOpen}
                url= 'admin.closeouts.index'
                iconName="inventory"
            />*/}
            <SidebarMenuItem
                label="Messages"
                isOpen={isOpen}
                url= 'admin.messenger.index'
                iconName="messages"
            />
            <SidebarMenuItem
                label="Reports"
                isOpen={isOpen}
                url= 'admin.reports.index'
                iconName="reports"
            />
            <div>
                <span className={`${isOpen ? '' : 'hidden'} text-menu-heading`}>Other</span>
            </div>
            <SidebarMenuItem
                label="Help & Center"
                isOpen={isOpen}
                url= 'admin.help-questions.index'
                iconName="helpcenter"
            />
            {/* <SidebarMenuItem
                label="Settings"
                isOpen={isOpen}
                url= 'admin.roles.index'
                iconName="settings"
            /> */}
            {permissions?.user_management_access && (
            <SidebarMenuItem
                label="Settings"
                isOpen={isOpen}
                iconName="settings"
                path={currentRoutePath == 'admin.home' ? '/admin' : null}
                submenuItems={[
                    // { name: 'Permissions', url: 'admin.permissions.index' },
                    // { name: 'Roles', url: 'admin.roles.index' },
                    { name: 'Users', url: 'admin.users.index' },
                    { name: 'Teams', url: 'admin.teams.index' },
                    { name: 'User Alerts', url: 'admin.user-alerts.index' },
                ]}
            />)} 
            {/* {permissions?.user_management_access && (
            <SidebarMenuItem
                label="User Management"
                isOpen={isOpen}
                iconName="admin"
                path={currentRoutePath == 'admin.home' ? '/admin' : null}
                submenuItems={[
                    { name: 'Permissions', url: 'admin.permissions.index' },
                    { name: 'Roles', url: 'admin.roles.index' },
                    { name: 'Users', url: 'admin.users.index' },
                    { name: 'Teams', url: 'admin.teams.index' },
                ]}
            />)}  */}

                <div>
                    <button
                    onClick={handleLogout}
                    data-target="submenu-dashboard-21"
                    className={`${isOpen ? 'px-3' : 'px-1.5'} group menu-link flex items-center py-2 hover:bg-secondary-200 rounded-lg`}>
                    <span className="flex items-center gap-2">
                        <span className="light-icon transition-colors duration-300 text-primary-0 group-[data-open=true]:text-black">
                            <img src="/assets/icons/logout.svg" alt="Logout" className="w-5 h-5" />
                        </span> 
                        <span className={`${isOpen ? '' : 'hidden'} text-menu-heading`} >Logout</span>
                    </span>
                    </button>          
                </div>

                </nav>
                <div className="mt-auto border-b border-gray-300 pt-[13px]"></div>


                <div className="group px-4">
                <div className="pl-[10px] mt-auto border-t pt-[13px] pb-4 flex items-center gap-3">
                    <div className=" w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-sm font-semibold">
                        {icons.user}
                    </div>
                    <div className="flex flex-col text-sm">
                        <span className={`${isOpen ? '' : 'hidden'} font-medium text-gray-800`}>{user?.name}</span>
                        <span className={`${isOpen ? '' : 'hidden'} text-gray-500 text-xs`}>{user?.email}</span>
                    </div>
                    <div className={`${isOpen ? '' : 'hidden'}`}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <path d="M11.62 5.22083L7.81667 9.02416C7.3675 9.47333 6.6325 9.47333 6.18334 9.02416L2.38 5.22083" stroke="#BEBEC8" strokeWidth="1.5" strokeMiterlimit="10" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                    </div>          
                </div>        
                </div>
            </aside>
            {/* <div className="flex transition-all duration-300 w-[100vw+240px] py-[10px] pr-[10px] bg-red-400"> */}
            <div className="flex transition-all duration-300 w-[100%] py-[10px] pr-[10px] bg-secondary-100">
                {/* <div className="flex-1 flex flex-col overflow-hidden h-screen  bg-white rounded-lg">
                    <Header user={user} toggleSidebar={() => toggleSidebar()} /></div> */}
                    <div className=" bg-[#3863C6] w-[10px] h-[100%] transition-all duration-300 rounded-l-lg " id="dividerBar"></div>
                    <main className="bg-white min-h-screen w-[100%]  rounded-r-lg">
                        {/**/} <header className="h-16 bg-white  border-b border-secondary-100 rounded-r-lg p-6 py-4 flex justify-between items-center">
                            <div className="flex items-center gap-4 ">
                                <div className={`icon-open flex md:hidden  items-center justify-between px-1 py-1 bg-secondary-100  relative rounded-[10px]`}>
                                    <button onClick={()=>toggleSidebar()} className="flex w-auto h-auto hover:bg-gray-200 rounded-sm transition cursor-pointer">
                                        <img src="/assets/icons/sidebar-left.svg" alt="My Icon" className="w-5 h-5" />         
                                    </button>
                                </div>
                                {currentRoutePath === 'admin/bookings' && <span className="font-semibold text-lg text-gray-800 capitalize">Bookings</span>}
                                {/* <span className="font-semibold text-lg text-gray-800 capitalize">{currentRoutePath}, {user?.name}</span> */}
                            </div>
                        </header> 
                        {flash?.message && <FlashMessage message={flash.message} type={flash.type} />}
                        {children}
                    </main>
                

            </div>
        </div>
    );
}
