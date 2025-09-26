import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import CModal from '@/Components/CModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import QuestionnaireModal from './QuestionnaireModal';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import Pagination from '@/Components/Pagination';
import SettingsHeader from '@/Components/HeaderSites';


export default function Index({ questionnaires, users, auth, inputTypes, dataTypes, permissions, searchTerm, sortColumn, sortDirection }) {
    //const [modalOpen, setModalOpen] = useState(false);

    const [selectedQuestionnaire, setSelectedQuestionnaire] = useState(null);
    const { props } = usePage();
    console.log('auth on questionnaire ', auth);
    const [search, setSearch] = useState(searchTerm);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState("create");
    const [isOpenTab, setIsOpenTab] = useState('Questionnaires');

    const handleOpenModal = (mode, questionnaire = null) => {
        console.log('mode ', mode);
        console.log('questionnaire ', questionnaire);
        setModalMode(mode);
        setSelectedQuestionnaire(questionnaire);
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedQuestionnaire(null);
    };

    const handleSearchChange = (e) => {
        console.log('value ', e.target.value);
        setSearch(e.target.value);
    };

    const handleSearchSubmit = (e) => {
        // e.preventDefault();
        // Redirect to the route with search query and other parameters
        console.log('search ', search);

        window.location.href = route('admin.questionnaire.index', {
            search,
            sort: sortColumn,
            direction: sortDirection
        });
    };

    const handleDelete = (questionnaire) => {
        if (confirm("Are you sure you want to delete this questionnaire?")) {
            //post(route("questionnaires.destroy", questionnaire), { _method: "DELETE",});
            router.delete(route('admin.questionnaire.destroy', questionnaire), { preserveScroll: true });
        }
    };

    const handleShow = (questionnaire) => {
        //console.log('questionnaire ', questionnaire ); return;, {preserveScroll: true}
        router.get(route('admin.questionnaire.show', questionnaire.id));
    };

    return (
        // <AuthenticatedLayout questionnaire={props.auth.questionnaire}>
        <AuthenticatedLayout
            user={props.auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Questionnaires</h2>}
        >
            <Head title="Questionnaires" />
            {/* <header className="bg-white shadow-md px-6 py-4 flex justify-between items-center">
            <div className="flex items-center gap-4">

                <span className="font-semibold text-lg text-gray-800">Questionnaires, {props.auth?.questionnaire?.name}</span>
            </div>
        </header> */}
            <div className="bg-white p-6 ">
                <SettingsHeader isOpenTab={isOpenTab} setIsOpenTab={setIsOpenTab} />
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <SearchBar search={search} handleSearchChange={(e) => handleSearchChange(e)} handleSearchSubmit={handleSearchSubmit} />

                    <span className="flex gap-2">
                        {permissions.questionnaires_create && (
                            <BlueButton title="Add Questions" onClick={() => handleOpenModal("create")} icon={true} />
                        )}
                    </span>
                </div>
                {/* bottom */}
                <div className="bg-white border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                            <span className='flex items-center gap-2'>
                                <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                                <span className="font-semibold text-lg text-gray-800 block text-start">Questionnaires</span>
                            </span>
                        </div>
                        <div className="flex justify-between items-center px-4 pb-[9px] ">
                            {/* table */}

                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50 text-left text-gray-500">
                                    <tr className='flex justify-between items-center'>
                                        <th className="w-[95px] p-3">ID</th>
                                        <th className="w-[95px] p-3">Name</th>
                                        <th className="w-[268px] p-3">Description</th>
                                        <th className="w-[268px] p-3">Questions</th>
                                        <th className="w-[185px] p-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {questionnaires?.data.map((questionnaire, i) => (
                                        <tr key={questionnaire.id} className="flex justify-between items-center hover:bg-gray-50">
                                            <td className="w-[95px] p-3">{questionnaire.id}</td>
                                            <td className="w-[95px] p-3">{questionnaire.name}</td>
                                            <td className="w-[268px] p-3">{questionnaire.description}</td>
                                            <td className="w-[132px] p-3">{questionnaire?.fields?.length()}</td>
                                            <td className="w-[185px] p-3 flex  gap-2">
                                                {permissions.questionnaires_edit && (
                                                    <button onClick={() => handleOpenModal("edit", questionnaire)}
                                                        className="flex items-center gap-1 text-green-600 hover:text-green-800">
                                                        Edit
                                                    </button>)}

                                                {permissions.questionnaires_edit && (
                                                    <button onClick={() => handleDelete(questionnaire)}
                                                        className="flex items-center gap-1 text-red-600 hover:text-red-800">
                                                        Remove
                                                    </button>)}
                                                {permissions.questionnaires_edit && (
                                                    <button onClick={() => handleShow(questionnaire)}
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
                <Pagination links={questionnaires?.links} />
            </div>

            <QuestionnaireModal
                isOpen={isModalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                questionnaire={selectedQuestionnaire}
                inputTypes={inputTypes}
                dataTypes={dataTypes}
                users={users}
                team_id={auth.user.team_id}
            />
        </AuthenticatedLayout>
    );
}
