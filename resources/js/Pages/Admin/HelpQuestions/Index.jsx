// resources/js/Pages/Users/Index.jsx

import { useState } from 'react';
import { usePage, router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import HelpQuestionModal from './HelpQuestionModal';
import SearchBar from '@/Components/SearchBar';
import BlueButton from '@/Components/BlueButton';
import Pagination from '@/Components/Pagination';
import SettingsHeader from '@/Components/HeaderSites';
import CustomSelect from '@/Components/CustomSelect';


export default function Index({ permissions, helpQuestions, typeHelpQuestions, auth, searchTerm, sortColumn, sortDirection, teamFilter = "" }) {
    //const [modalOpen, setModalOpen] = useState(false);

    const [selectedHelpQuestion, setSelectedHelpQuestion] = useState(null);
    const { props } = usePage();
    console.log('auth on user ', auth);
    const [search, setSearch] = useState(searchTerm);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isOpenTab, setIsOpenTab] = useState('helpQuestions');
    const [modalMode, setModalMode] = useState("create");
    const [selectedTeamId, setSelectedTeamId] = useState(teamFilter ?? "");
    const isAdmin = auth?.user?.is_admin || auth?.user?.roles?.some((role) => role?.id === 1);
    const teamOptions = [
        { id: "", name: "All Teams" },
        ...(auth?.teams || []),
    ];

    const handleOpenModal = (mode, helpQuestion = null) => {
        console.log('mode ', mode);
        console.log('helpQuestion ', helpQuestion);
        setModalMode(mode);
        setSelectedHelpQuestion(helpQuestion);
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedHelpQuestion(null);
    };

    const handleSearchChange = (e) => {
        console.log('value ', e.target.value);
        setSearch(e.target.value);
    };

    const handleSearchSubmit = (e) => {
        // e.preventDefault();
        // Redirect to the route with search query and other parameters
        console.log('search ', search);

        router.get(route('admin.help-questions.index'), {
            search,
            sort: sortColumn,
            direction: sortDirection,
            team_id: selectedTeamId || undefined
        }, { preserveState: true });
    };
    const handleDelete = (helpQuestion) => {
        if (confirm("Are you sure you want to delete this helpQuestion?")) {
            //post(route("helpQuestions.destroy", helpQuestion), { _method: "DELETE",});
            router.delete(route('admin.help-questions.destroy', helpQuestion), { preserveScroll: true });
        }
    };

    // function toggleDropdown (tab, routeName) {
    //     setIsOpenTab(prev => (prev === tab ? '' : tab));
    //     router.get(route(routeName), {}, {
    //     preserveScroll: true,
    //     preserveState: true,
    //     });
    // }

    return (
        // <AuthenticatedLayout user={props.auth.user}>
        <AuthenticatedLayout
            user={props.auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Help Question</h2>}
        >
            <Head title="Help Question" />

            <div className="bg-white p-6 ">
                {/* top */}
                <SettingsHeader permissions={permissions} isOpenTab={isOpenTab} setIsOpenTab={setIsOpenTab} />
                <div className="flex justify-between items-center mb-6">
                    <div className="flex gap-4 items-center w-full">
                        <SearchBar search={search} handleSearchChange={(e) => handleSearchChange(e)} handleSearchSubmit={handleSearchSubmit} />
                        {isAdmin && (
                            <div className="w-[260px]">
                                <CustomSelect
                                    label="Team"
                                    name="team_id"
                                    value={selectedTeamId}
                                    showOption={false}
                                    options={teamOptions}
                                    onChange={(e) => {
                                        const next = e.target.value;
                                        setSelectedTeamId(next);
                                        router.get(route('admin.help-questions.index'), {
                                            search,
                                            sort: sortColumn,
                                            direction: sortDirection,
                                            team_id: next || undefined
                                        }, { preserveState: true });
                                    }}
                                />
                            </div>
                        )}
                    </div>

                    <span className="flex gap-2">
                        {permissions.help_questions_create && (
                            <BlueButton title="Question" onClick={() => handleOpenModal("create")} icon={true} />
                        )}
                    </span>
                </div>
                {/* bottom */}
                <div className="bg-white border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                            <span className='flex items-center gap-2'>
                                <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                                <span className="font-semibold text-lg text-gray-800 block text-start">Help Questions</span>
                            </span>
                        </div>
                        <div className="flex justify-between items-center px-4 pb-[9px] ">
                            {/* table */}

                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50 text-left text-gray-500">
                                    <tr className='flex justify-between items-center'>
                                        <th className="w-[10%] p-3">ID</th>
                                        <th className="w-[35%] p-3">Question</th>
                                        <th className="w-[35%] p-3">Category</th>
                                        <th className="w-[20%] p-3">Internal</th>
                                        <th className="w-[35%] p-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {helpQuestions?.data.map((helpQuestion, i) => (
                                        <tr key={helpQuestion.id} className="flex justify-between items-center hover:bg-gray-50">
                                            <td className="w-[10%] p-3">{helpQuestion.id}</td>
                                            <td className="w-[35%] p-3">{helpQuestion.question}</td>
                                            <td className="w-[35%] p-3">{helpQuestion.type_help_question.name}</td>
                                            <td className="w-[20%] p-3">{helpQuestion.is_internal ? 'Yes' : 'No'}</td>
                                            <td className="w-[35%] p-3 flex  gap-2">
                                                {permissions.help_questions_edit && (
                                                    <button onClick={() => handleOpenModal("edit", helpQuestion)}
                                                        className="flex items-center gap-1 text-green-600 hover:text-green-800">
                                                        Edit
                                                    </button>)}

                                                {permissions.help_questions_edit && (
                                                    <button onClick={() => handleDelete(helpQuestion)}
                                                        className="flex items-center gap-1 text-red-600 hover:text-red-800">
                                                        Remove
                                                    </button>)}
                                                        {permissions.help_questions_edit && (
                                                            <Link
                                                                href={route('admin.help-questions.show', helpQuestion.id)}
                                                                className="flex items-center gap-1 text-blue-600 hover:text-blue-800"
                                                            >
                                                                View
                                                            </Link>
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
                <Pagination links={helpQuestions?.links} />
            </div>

            <HelpQuestionModal
                isOpen={isModalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                helpQuestion={selectedHelpQuestion}
                typeHelpQuestions={typeHelpQuestions}
                auth={auth}
                teams={auth?.teams || []}
                team_id={auth?.user.team_id}
            />


        </AuthenticatedLayout>
    );
}
