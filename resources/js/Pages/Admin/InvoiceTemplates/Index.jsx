import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import CModal from '@/Components/CModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import TemplateModal from './TemplateModal';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import Pagination from '@/Components/Pagination';
import SettingsHeader from '@/Components/HeaderSites';


export default function Index({ templates, users, auth, permissions, searchTerm, sortColumn, sortDirection }) {
    //const [modalOpen, setModalOpen] = useState(false);

    const [selectedTemplate, setSelectedTemplate] = useState(null);
    const { props } = usePage();
    console.log('auth on template ', auth);
    console.log('auth on users ', users);
    console.log('auth on templates ', templates);
    const [search, setSearch] = useState(searchTerm);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState("create");
    const [isOpenTab, setIsOpenTab] = useState('Invoice_Templates');
    const [data, setData] = useState(null);

    const handleOpenModal = (mode, template = null) => {
        console.log('mode ', mode);
        console.log('template ', template);
        setModalMode(mode);
        setSelectedTemplate(template);
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedTemplate(null);
    };

    const handleSearchChange = (e) => {
        console.log('value ', e.target.value);
        setSearch(e.target.value);
    };

    const handleSearchSubmit = (e) => {
        // e.preventDefault();
        // Redirect to the route with search query and other parameters
        console.log('search ', search);

        window.location.href = route('admin.invoice-templates.index', {
            search,
            sort: sortColumn,
            direction: sortDirection
        });
    };

    const handleDelete = (template) => {
        if (confirm("Are you sure you want to delete this template?")) {
            //post(route("templates.destroy", template), { _method: "DELETE",});
            router.delete(route('admin.invoice-templates.destroy', template), { preserveScroll: true });
        }
    };

    return (
        // <AuthenticatedLayout template={props.auth.template}>
        <AuthenticatedLayout
            user={props.auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Templates</h2>}
        >
            <Head title="Templates" />
            {/* <header className="bg-white shadow-md px-6 py-4 flex justify-between items-center">
            <div className="flex items-center gap-4">

                <span className="font-semibold text-lg text-gray-800">Templates, {props.auth?.template?.name}</span>
            </div>
        </header> */}
            <div className="bg-white p-6 ">
                <SettingsHeader permissions={permissions} isOpenTab={isOpenTab} setIsOpenTab={setIsOpenTab} />
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <SearchBar search={search} handleSearchChange={(e) => handleSearchChange(e)} handleSearchSubmit={handleSearchSubmit} />

                    <span className="flex gap-2">
                        {permissions.user_alerts_create && (
                            // <BlueButton title="Add Invoice Template" onClick={() => router.get(route('admin.invoice-templates.create'))} icon={true}/>
                            <BlueButton title="Add Invoice Template" onClick={() => handleOpenModal("create")} icon={true} />
                        )}
                    </span>
                </div>
                {/* bottom */}
                <div className="bg-white border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                            <span className='flex items-center gap-2'>
                                <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                                <span className="font-semibold text-lg text-gray-800 block text-start">Invoice Templates</span>
                            </span>
                        </div>
                        <div className="flex justify-between items-center px-4 pb-[9px] ">
                            {/* table */}

                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50 text-left text-gray-500">
                                    <tr className='flex justify-between items-center'>
                                        <th className="w-[95px] p-3">ID</th>
                                        <th className="w-[95px] p-3">Name</th>
                                        <th className="w-[268px] p-3">Type</th>
                                        <th className="w-[132px] p-3">Default</th>
                                        <th className="w-[185px] p-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {templates?.data.map((template, i) => (
                                        <tr key={template.id} className="flex justify-between items-center hover:bg-gray-50">
                                            <td className="w-[95px] p-3">{template.id}</td>
                                            <td className="w-[95px] p-3">{template.name}</td>
                                            <td className="w-[268px] p-3">{template.type}</td>
                                            <td className="w-[132px] p-3">{template.is_default ? "Yes" : "No"}</td>
                                            <td className="w-[185px] p-3 flex  gap-2">
                                                {false && (
                                                    <button onClick={() => handleOpenModal("edit", template)}
                                                        className="flex items-center gap-1 text-green-600 hover:text-green-800">
                                                        Edit
                                                    </button>)}

                                                {permissions.user_alerts_edit && (
                                                    <button onClick={() => handleDelete(template)}
                                                        className="flex items-center gap-1 text-red-600 hover:text-red-800">
                                                        Remove
                                                    </button>)}
                                                {false && (
                                                    <button
                                                        type="button"
                                                        className="btn-secondary"
                                                        onClick={() => {
                                                            router.post(route('admin.bookings.preview-invoice'), data, {
                                                                onSuccess: (res) => {
                                                                    // open modal with res.props.html
                                                                }
                                                            })
                                                        }}
                                                    >
                                                        Preview Invoice
                                                    </button>
                                                    // <button onClick={() => handleOpenModal("view",template)}
                                                    // className="flex items-center gap-1 text-blue-600 hover:text-blue-800">
                                                    // View
                                                    // </button>
                                                )}
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
                <Pagination links={templates?.links} />
            </div>

            <TemplateModal
                isOpen={isModalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                template={selectedTemplate}
                users={users}
                team={auth?.team}
            />
        </AuthenticatedLayout>
    );
}
