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


export default function Index({ users,permissions,roles,teams,auth, searchTerm, sortColumn, sortDirection }) {
  //const [modalOpen, setModalOpen] = useState(false);

  const [selectedUser, setSelectedUser] = useState(null);
  const { props } = usePage();

//   const openModal = (user = null) => {
//     setSelectedUser(user);
//     setModalOpen(true);
//   };

//   const closeModal = () => {
//     setSelectedUser(null);
//     setModalOpen(false);
//   };




//const { auth } = usePage().props;
    //const { destroy } = useForm();
    console.log('auth on user ', auth);
    const [search, setSearch] = useState(searchTerm);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isOpenTab, setIsOpenTab] = useState('');


function toggleDropdown (tab) {
    setIsOpenTab(prev => (prev === tab ? '' : tab));  
}






  return (
    // <AuthenticatedLayout user={props.auth.user}>
    <AuthenticatedLayout
        user={props.auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Users</h2>}
    >
        <Head title="Users" />
    <div className="overflow-x-auto px-[15px]">
      <div className="flex pt-2 space-x-6 border-b border-gray-200">
        <button id="Services-tab" className={` ${isOpenTab === 'Services' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-active pb-2 cursor-pointer`} onClick={()=> toggleDropdown('Services')} >Services</button>
        <button id="Sessions-tab" className={` ${isOpenTab === 'Sessions' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer`} onClick={()=> toggleDropdown('Sessions')} >Sessions</button>

        <button id="Inventory-tab" className={` ${isOpenTab === 'Inventory' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={()=> toggleDropdown('Inventory')} >Inventory</button>

      </div>
      <div className="">
        {isOpenTab === 'Services' && (
        <div id="Services-content" className="tab-content">
         <div className="bg-primary-7 p-4 rounded-md">Customs Section</div>
        </div>
        )}
                {isOpenTab === 'Sessions' && (
        <div id="Sessions-content" className="tab-content">
          <div className="bg-primary-7 p-4 rounded-md">Customs Sessions</div>
        </div>
        )}
                {isOpenTab === 'Inventory' && (
        <div id="Inventory-content" className="tab-content">
          <div className="bg-primary-7 p-4 rounded-md">Containers Inventory</div>
        </div>
        )}

      </div>
    </div> 

    </AuthenticatedLayout>
  );
}
