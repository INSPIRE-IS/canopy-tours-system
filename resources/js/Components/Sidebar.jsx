import { Link } from '@inertiajs/react';
import { useState } from 'react';
import SidebarMenuItem from './SidebarMenuItem';
//import { FaTachometerAlt, FaUsers, FaUnlockAlt, FaBriefcase, FaUser, FaProjectDiagram, FaFolder, FaKey, FaSignOutAlt, FaCaretDown } from 'react-icons/fa';

export default function Sidebar({ isOpen, setIsOpen, permissions, translations }) {
    const [isUserManagementOpen, setIsUserManagementOpen] = useState(false);
    //const [isOpen, setIsOpen] = useState(sidebarOpen);
    const [isOpenSelect, setIsOpenSelect] = useState('');
    const [isOpenSubmenu, setIsOpenSubmenu] = useState('');


    function toggleSidebar() {
        setIsOpen(!isOpen);
    }

    function toggleSubmenu(element) {
        setIsOpenSubmenu(prev => (prev === element ? '' : element));
    }

    function toggleDropdown(id) {
        setIsOpenSelect(prev => (prev === id ? '' : id));
    }
    function selectOption(dropdownId, value) {
        console.log('apply changes to ', dropdownId);
        setIsOpenSelect('');

    }
    return (
        <aside id="sidebar" className={` ${isOpen ? '-translate-x-full md:w-60' : 'translate-x-0 w-[80%] md:w-16'} fixed md:relative z-30 top-0 left-0 h-full bg-secondary-100 transition-all duration-300 overflow-auto sm:overflow-x-visible sm:overflow-y-visible pt-16 md:pt-5 transform md:translate-x-0 -translate-x-full md:block`} >

            <div className={`${isOpen ? '' : 'hidden'} pl-[9px] md:flex`}>
                <div className={`${isOpen ? '' : 'hidden'} icon-open flex items-center justify-between gap-2 px-4 py-4 bg-black w-[215px] h-[57px] relative rounded-[10px]`}>
                    <span className='flex flex-row items-center gap-2'>
                        <img src="/assets/icons/logo-icon.png" alt="My Icon" className="w-[31px] h-[27.7px]" />
                        <img src="/assets/icons/logo-name.png" alt="My Icon" className="w-[55px] h-[28.2px]" />
                    </span>
                    <button onClick={() => toggleSidebar()} className="hover:bg-gray-200 p-1 rounded-sm transition cursor-pointer hidden md:inline-block">
                        <img src="/assets/icons/sidebar-left.svg" alt="My Icon" className="w-5 h-5" />
                    </button>
                </div>
            </div>
            <div className={`${isOpen ? 'hidden' : ''} icon-closed pl-4`}>
                <div className=" items-center justify-between pl-0.5 py-4 w-[26px] h-[57px] relative rounded-[10px] ">
                    <button onClick={() => toggleSidebar()} className="hover:bg-gray-200 p-1 rounded-sm transition cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M1.69167 7.49992L1.69167 12.4999C1.69167 16.6666 3.35834 18.3333 7.525 18.3333L12.525 18.3333C16.6917 18.3333 18.3583 16.6666 18.3583 12.4999L18.3583 7.49992C18.3583 3.33325 16.6917 1.66658 12.525 1.66658L7.52501 1.66658C3.35834 1.66658 1.69167 3.33325 1.69167 7.49992Z" stroke="#BEBEC8" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                            <path d="M13.3583 18.3333L13.3583 1.66658" stroke="#BEBEC8" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                            <path d="M7.52499 12.1333L9.65833 9.99997L7.52499 7.86663" stroke="#BEBEC8" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                    </button>
                </div>
            </div>

            <div className={`${isOpen ? '' : 'hidden'} icon-open px-4 my-4 `}>
                <div className="relative w-auto">
                    <button
                        onClick={() => toggleDropdown('dropdown-E1')}
                        className="flex h-10 min-w-[208px] items-center justify-between pl-[10px] pr-4 py-2  bg-primary-0 rounded-[10px] border-[3px] border-secondary-200 transition-all 
                        text-secondary-200 hover:border-secondary-300 hover:text-secondary-300 cursor-pointer">
                        <span className="flex items-center gap-2">
                            <span className="w-7 h-7 bg-black rounded-lg flex items-center justify-center text-primary-0 text-sm">

                                <svg className="w-[14px] h-[14px]" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                    <text x="50%" y="50%"
                                        dy=".35em"
                                        textAnchor="middle"
                                        fontSize="60"
                                        className="text-entity-logo"
                                        fill="currentColor">
                                        E
                                    </text>
                                </svg>
                            </span>
                            <span id="label-dropdown-E1" className="text-entity-name">Entity Name</span>
                        </span>

                        <svg id="icon-dropdown-E1"
                            className="transform transition-transform duration-300 "
                            xmlns="http://www.w3.org/2000/svg"
                            width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <path d="M5.22082 2.38012L9.02416 6.18345C9.47333 6.63262 9.47333 7.36762 9.02416 7.81678L5.22083 11.6201"
                                stroke="currentColor" strokeWidth="1.5" strokeMiterlimit="10" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                    </button>
                    {isOpenSelect === 'dropdown-E1' && (
                        <ul id="dropdown-E1" className="absolute w-full bg-white mt-1 z-10 text-sm">
                            <li onClick={() => selectOption('dropdown-E1', 'Test Site')} className="flex items-center justify-between border-b border-gray-200 px-4 py-2 hover:bg-primary-7 cursor-pointer">
                                <span className="flex items-center gap-2">
                                    <span className="w-4 h-4 bg-[#FF3939] rounded-[2px] flex items-center justify-center text-primary-0 text-sm">

                                        <svg className="w-[6px] h-[15px]" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                            <text x="50%" y="50%"
                                                dy=".35em"
                                                textAnchor="middle"
                                                fontSize="60"
                                                className="text-entity-logo"
                                                fill="currentColor">
                                                T
                                            </text>
                                        </svg>
                                    </span>
                                    <span id="label-dropdown-E1" className="text-dropdown-option">Test Site</span>
                                </span>
                                <span className="text-[#AF9494]">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="4" height="7" viewBox="0 0 4 7" fill="currentColor">
                                        <path d="M3.35381 3.3269L0.526913 6.15381L-2.30321e-08 5.6269L2.3 3.3269L-2.24104e-07 1.0269L0.526912 0.499983L3.35381 3.3269Z" fill="currentColor" />
                                    </svg>
                                </span>
                            </li>
                            <li onClick={() => selectOption('dropdown-E1', 'Entity Name')} className="flex items-center justify-between border-b border-gray-200 px-4 py-2 hover:bg-primary-7 cursor-pointer">
                                <span className="flex items-center gap-2">
                                    <span className="w-4 h-4 bg-[#B8E50D] rounded-[2px] flex items-center justify-center text-primary-0 text-sm">

                                        <svg className="w-[6px] h-[15px]" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                            <text x="50%" y="50%"
                                                dy=".35em"
                                                textAnchor="middle"
                                                fontSize="60"
                                                className="text-entity-logo"
                                                fill="currentColor">
                                                E
                                            </text>
                                        </svg>
                                    </span>
                                    <span id="label-dropdown-E1" className="text-dropdown-option">Entity Name</span>
                                </span>
                                <span className="text-[#AF9494]">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="4" height="7" viewBox="0 0 4 7" fill="currentColor">
                                        <path d="M3.35381 3.3269L0.526913 6.15381L-2.30321e-08 5.6269L2.3 3.3269L-2.24104e-07 1.0269L0.526912 0.499983L3.35381 3.3269Z" fill="currentColor" />
                                    </svg>
                                </span>
                            </li>
                            <li onClick={() => selectOption('dropdown-E1', 'Site Test')} className="flex items-center justify-between border-b border-gray-200 px-4 py-2 hover:bg-primary-7 cursor-pointer">
                                <span className="flex items-center gap-2">
                                    <span className="w-4 h-4 bg-[#5795FF] rounded-[2px] flex items-center justify-center text-primary-0 text-sm">

                                        <svg className="w-[6px] h-[15px]" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                            <text x="50%" y="50%"
                                                dy=".35em"
                                                textAnchor="middle"
                                                fontSize="60"
                                                className="text-entity-logo"
                                                fill="currentColor">
                                                S
                                            </text>
                                        </svg>
                                    </span>
                                    <span id="label-dropdown-E1" className="text-dropdown-option">Site Test</span>
                                </span>
                                <span className="text-[#AF9494]">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="4" height="7" viewBox="0 0 4 7" fill="currentColor">
                                        <path d="M3.35381 3.3269L0.526913 6.15381L-2.30321e-08 5.6269L2.3 3.3269L-2.24104e-07 1.0269L0.526912 0.499983L3.35381 3.3269Z" fill="currentColor" />
                                    </svg>
                                </span>
                            </li>
                        </ul>
                    )}
                </div>
            </div>
            <div className={`${isOpen ? 'hidden' : ''} icon-closed px-2 w-full h-[57px] items-center justify-center`}>
                <button onClick={() => toggleSidebar()}
                    className="flex items-center justify-center bg-white border border-seconsary-200 w-12 h-12 hover:bg-gray-200 rounded-[10px] transition cursor-pointer">

                    <span className="flex items-center justify-center w-7 h-7 bg-black text-white text-lg rounded-md leading-none">
                        <svg className="w-[14px] h-[14px]" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                            <text
                                x="50%"
                                y="50%"
                                dy=".35em"
                                textAnchor="middle"
                                className="text-entity-logo"
                                fill="currentColor"
                            >
                                E
                            </text>
                        </svg>
                    </span>

                </button>
            </div>
            <nav className="px-4 space-y-2 ">
                <div>
                    <button
                        onClick={() => toggleSubmenu('submenu-dashboard-1')}
                        data-open="false"
                        className="group w-full flex items-center justify-between px-3 py-2 rounded-lg transition-colors
                    hover:bg-secondary-200"
                    >
                        <span className="flex items-center gap-2">
                            <span className="dark-icon transition-colors duration-300 text-dark-gray ">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="15" viewBox="0 0 14 15" fill="currentColor">
                                    <g clipPath="url(#clip0_54_1278)">
                                        <path fillRule="evenodd" clipRule="evenodd" d="M1.66827 0.922194C2.15709 0.838192 2.70066 0.75 3.26489 0.75C3.82912 0.75 4.3727 0.838192 4.86151 0.922194C5.55589 1.04152 6.09092 1.62143 6.14397 2.3291L6.15631 2.49319C6.21615 3.28739 6.27979 4.13187 6.27979 4.99881C6.27979 5.86575 6.21615 6.71021 6.15631 7.5044L6.14397 7.66852C6.09092 8.37619 5.55589 8.9561 4.86151 9.07543C4.3727 9.15943 3.82912 9.24762 3.26489 9.24762C2.70066 9.24762 2.15709 9.15943 1.66827 9.07543C0.9739 8.9561 0.438863 8.37619 0.38582 7.66852L0.373476 7.50442C0.313631 6.71021 0.25 5.86576 0.25 4.99881C0.25 4.13186 0.313631 3.2874 0.373476 2.4932L0.385819 2.3291C0.438862 1.62143 0.973899 1.04152 1.66827 0.922194ZM10.7351 5.75238C10.1709 5.75238 9.6273 5.84057 9.13849 5.92457C8.44411 6.0439 7.90908 6.62381 7.85603 7.33148L7.84369 7.49558C7.78385 8.28978 7.72021 9.13424 7.72021 10.0012C7.72021 10.8681 7.78385 11.7126 7.84369 12.5068L7.85603 12.6709C7.90908 13.3786 8.44411 13.9585 9.13849 14.0778C9.6273 14.1618 10.1709 14.25 10.7351 14.25C11.2993 14.25 11.8429 14.1618 12.3317 14.0778C13.0261 13.9585 13.5611 13.3786 13.6142 12.6709L13.6265 12.5068C13.6864 11.7127 13.75 10.8681 13.75 10.0012C13.75 9.13423 13.6864 8.28977 13.6265 7.49557L13.6142 7.33148C13.5611 6.62381 13.0261 6.0439 12.3317 5.92457C11.8429 5.84057 11.2993 5.75238 10.7351 5.75238ZM9.18267 0.809986C9.67524 0.779969 10.1978 0.75 10.7351 0.75C11.2724 0.75 11.795 0.779969 12.2875 0.809986C12.9641 0.851214 13.5946 1.34532 13.7068 2.06563C13.7317 2.22548 13.75 2.39772 13.75 2.57812C13.75 2.75853 13.7317 2.93077 13.7068 3.09062C13.5946 3.81093 12.9641 4.30504 12.2875 4.34626C11.795 4.37628 11.2724 4.40625 10.7351 4.40625C10.1978 4.40625 9.67524 4.37628 9.18267 4.34626C8.50614 4.30504 7.87562 3.81093 7.76342 3.09062C7.73852 2.93077 7.72021 2.75853 7.72021 2.57812C7.72021 2.39772 7.73852 2.22548 7.76342 2.06563C7.87562 1.34532 8.50614 0.851214 9.18267 0.809986ZM3.26489 10.5938C2.72762 10.5938 2.20503 10.6237 1.71246 10.6537C1.03593 10.695 0.405406 11.1891 0.293203 11.9094C0.268304 12.0692 0.25 12.2415 0.25 12.4219C0.25 12.6023 0.268304 12.7745 0.293203 12.9344C0.405406 13.6547 1.03593 14.1488 1.71246 14.19C2.20503 14.22 2.72762 14.25 3.26489 14.25C3.80217 14.25 4.32476 14.22 4.81733 14.19C5.49386 14.1488 6.12438 13.6547 6.23658 12.9344C6.26148 12.7745 6.27979 12.6023 6.27979 12.4219C6.27979 12.2415 6.26148 12.0692 6.23658 11.9094C6.12438 11.1891 5.49386 10.695 4.81733 10.6537C4.32476 10.6237 3.80217 10.5938 3.26489 10.5938Z" fill="currentColor" />
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_54_1278">
                                            <rect width="14" height="14" fill="white" transform="translate(0 0.5)" />
                                        </clipPath>
                                    </defs>
                                </svg>
                            </span>
                            <span className={`${isOpen ? '' : 'hidden'} text-menu-heading`} >Authorisation Dashboard</span>
                        </span>
                        <span className={`${isOpen ? '' : 'hidden'} arrow transform transition-transform duration-300`}>
                            <svg xmlns="http://www.w3.org/2000/svg" width="4" height="7" viewBox="0 0 4 7" fill="none">
                                <path d="M3.35381 3.3269L0.526913 6.15381L-2.30321e-08 5.6269L2.3 3.3269L-2.24104e-07 1.0269L0.526912 0.499983L3.35381 3.3269Z" fill="#AF9494" />
                            </svg>
                        </span>
                    </button>
                    {isOpenSubmenu === 'submenu-dashboard-1' && (
                        <div id="submenu-dashboard-1" className="mt-0 rounded-b-lg overflow-hidden bg-secondary-200">
                            <a href="#" className="self-stretch justify-center block pl-[37px] py-2 border-t border-primary-2 text-menu-subheading hover:bg-secondary-200">Bond Stores</a>
                            <a href="#" className="block pl-[37px] py-2 border-t border-primary-2 text-menu-subheading hover:bg-secondary-200">Clearing Instruction Recon</a>
                            <a href="#" className="block pl-[37px] py-2 border-t border-primary-2 text-menu-subheading hover:bg-secondary-200">Customs Account</a>
                            <a href="#" className="block pl-[37px] py-2 border-t border-primary-2 text-menu-subheading hover:bg-secondary-200">Customs Risk Management</a>
                            <a href="#" className="block pl-[37px] py-2 border-t border-primary-2 text-menu-subheading hover:bg-secondary-200 rounded-b-lg">Refund Claims</a>
                        </div>)}
                </div>
                <div>
                    <a
                        href="#"
                        onClick={() => { alert('about') }}
                        data-target="submenu-dashboard-18"
                        className=" menu-link flex items-center px-3 py-2 hover:bg-secondary-200 rounded-lg">
                        <span className="flex items-center gap-2">
                            <span className="dark-icon transition-colors duration-300 text-dark-gray ">
                                icon
                            </span> <span className={`${isOpen ? '' : 'hidden'} text-menu-heading`}>About GTS</span>
                        </span>
                    </a>
                </div>
                <div>
                    <span className={`${isOpen ? '' : 'hidden'} text-menu-heading`}>Other</span>
                </div>

                <div>
                    <a
                        href="#"
                        onClick={() => { alert('logout') }}
                        data-target="submenu-dashboard-21"
                        className="group menu-link flex items-center px-3 py-2 hover:bg-secondary-200 rounded-lg">
                        <span className="flex items-center gap-2">
                            <span className="light-icon transition-colors duration-300 text-primary-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="19" viewBox="0 0 18 19" fill="currentColor">
                                    <path d="M2.25 7.33252V11.66C2.25 13.25 2.25 13.25 3.75 14.2625L7.875 16.6475C8.4975 17.0075 9.51 17.0075 10.125 16.6475L14.25 14.2625C15.75 13.25 15.75 13.25 15.75 11.6675V7.33252C15.75 5.75002 15.75 5.75002 14.25 4.73752L10.125 2.35252C9.51 1.99252 8.4975 1.99252 7.875 2.35252L3.75 4.73752C2.25 5.75002 2.25 5.75002 2.25 7.33252Z" stroke="#8C8C95" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                                    <path d="M9 11.75C10.2426 11.75 11.25 10.7426 11.25 9.5C11.25 8.25736 10.2426 7.25 9 7.25C7.75736 7.25 6.75 8.25736 6.75 9.5C6.75 10.7426 7.75736 11.75 9 11.75Z" stroke="#8C8C95" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                                </svg>
                            </span> <span className={`${isOpen ? '' : 'hidden'} text-menu-heading`} >Logout</span>
                        </span>
                    </a>
                </div>

            </nav>
            <div className="mt-auto border-b border-gray-300 pt-[13px]"></div>


            <div className="group px-4">
                <div className="pl-[10px] mt-auto border-t pt-[13px] pb-4 flex items-center gap-3">
                    <div className=" w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-sm font-semibold">
                        image
                    </div>
                    <div className="flex flex-col text-sm">
                        <span className={`${isOpen ? '' : 'hidden'} font-medium text-gray-800`}>John Cornor</span>
                        <span className={`${isOpen ? '' : 'hidden'} text-gray-500 text-xs`}>Johncornor@mail.com</span>
                    </div>
                    <div className={`${isOpen ? '' : 'hidden'}`}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <path d="M11.62 5.22083L7.81667 9.02416C7.3675 9.47333 6.6325 9.47333 6.18334 9.02416L2.38 5.22083" stroke="#BEBEC8" strokeWidth="1.5" strokeMiterlimit="10" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                    </div>
                </div>
            </div>
        </aside>
    );
}
