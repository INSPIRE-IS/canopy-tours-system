import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import Pagination from '@/Components/Pagination';
import Modal from './Modal';
import HeaderMarketing from '@/Components/HeaderMarketing';

export default function Index({ promos, searchTerm, sortColumn, sortDirection, permissions,services }) {
  const [selected, setSelected] = useState(null);
  const [search, setSearch] = useState(searchTerm || '');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState("create");
  const [isOpenTab, setIsOpenTab] = useState('promotions');

  const handleOpenModal = (mode, item = null) => {
    setModalMode(mode);
    setSelected(item);
    setIsModalOpen(true);
  };

  const handleCloseModal = () => {
    setIsModalOpen(false);
    setSelected(null);
  };

  const handleSearchSubmit = () => {
    window.location.href = route('admin.promotions.index', {
      search: search,
      sort: sortColumn,
      direction: sortDirection
    });
  };

  const handleDelete = (item) => {
    if (confirm("Are you sure?")) {
      router.delete(route('admin.promotions.destroy', item), { preserveScroll: true });
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
    <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">PROMOTIONS</h2>}>
      <Head title="PROMOTIONS" />
      <div className="bg-white p-6">
        <HeaderMarketing isOpenTab={isOpenTab} setIsOpenTab={setIsOpenTab} />
        <div className="flex justify-between items-center mb-6">
          <SearchBar search={search} handleSearchChange={(e) => setSearch(e.target.value)} handleSearchSubmit={handleSearchSubmit} />
          <span className="flex gap-2">
            {permissions?.promotions_create && (
              <BlueButton title="Add" onClick={() => handleOpenModal("create")} icon={true}/>
            )}
          </span>
        </div>

        <div className="bg-white border border-secondary-100">
          <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
              <span className='flex items-center gap-2'>
                <img src="/assets/icons/task-square.svg" alt="icon" className="w-5 h-5" />
                <span className="font-semibold text-lg text-gray-800 block text-start">PROMOTIONS</span>
              </span>
            </div>

            <div className="flex justify-between items-center px-4 pb-[9px] ">
              <table className="min-w-full divide-y divide-gray-200 text-sm">
                <thead className="bg-gray-50 text-left text-gray-500">
                  <tr className='flex justify-between items-center'>
                    <th className="w-[80px] p-3">ID</th>
                    <th className="w-[200px] p-3">Name</th>
                    <th className="w-[140px] p-3">Code</th>
                    <th className="w-[140px] p-3">Type</th>
                    <th className="w-[120px] p-3">Value</th>
                    <th className="w-[180px] p-3">Service</th>
                    <th className="w-[140px] p-3">Start</th>
                    <th className="w-[140px] p-3">End</th>
                    <th className="w-[120px] p-3">Internal</th>
                    <th className="w-[185px] p-3"></th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {promos?.data.map((item) => (
                    <tr key={item.id} className="flex justify-between items-center hover:bg-gray-50">
                      <td className="w-[80px] p-3">{item.id}</td>
                      <td className="w-[200px] p-3">{item.name}</td>
                      <td className="w-[140px] p-3">{item.promo_code}</td>
                      <td className="w-[140px] p-3">{item.type}</td>
                      <td className="w-[120px] p-3">{item.value}</td>
                      <td className="w-[180px] p-3">{item.service?.name}</td>
                      <td className="w-[140px] p-3">{item?.booking_start_date?.split('T')[0]}</td>
                      <td className="w-[140px] p-3">{item?.booking_end_date?.split('T')[0]}</td>
                      <td className="w-[120px] p-3">{item.internal_use_only ? 'Yes' : 'No'}</td>
                      <td className="w-[185px] p-3 flex gap-2">
                        {permissions?.promotions_edit && (
                          <button onClick={() => handleOpenModal("edit", item)} className="text-green-600 hover:text-green-800">Edit</button>
                        )}
                        {permissions?.promotions_delete && (
                          <button onClick={() => handleDelete(item)} className="text-red-600 hover:text-red-800">Remove</button>
                        )}
                        {permissions?.promotions_show && (
                          <button onClick={() => handleOpenModal("view", item)} className="text-blue-600 hover:text-blue-800">View</button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div className="mt-4">
          <Pagination links={promos?.links} />
        </div>
      </div>

      <Modal isOpen={isModalOpen} onClose={handleCloseModal} mode={modalMode} item={selected} permissions={permissions} services={services} />
    </AuthenticatedLayout>
  );
}
