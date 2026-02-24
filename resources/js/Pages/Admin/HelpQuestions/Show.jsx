import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import parse from 'html-react-parser';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import HelpQuestionModal from './HelpQuestionModal';
import SearchBar from '@/Components/SearchBar';

const formatDate = (value) => {
    if (!value) return 'Not set';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString();
};

const buildShowUrl = (id, searchValue) => {
    const base = route('admin.help-questions.show', id);
    if (!searchValue) return base;
    const query = new URLSearchParams({ search: searchValue }).toString();
    return `${base}?${query}`;
};

export default function Show({ helpQuestions = [], activeQuestion = null, typeHelpQuestions = [], searchTerm = '' }) {
    const { props } = usePage();
    const { auth, permissions } = props;
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [search, setSearch] = useState(searchTerm);

    const list = useMemo(() => (Array.isArray(helpQuestions) ? helpQuestions : []), [helpQuestions]);
    const selected = useMemo(() => {
        if (search && list.length === 0) return null;
        if (search && list.length > 0) return list[0];
        return activeQuestion ?? list[0] ?? null;
    }, [activeQuestion, list, search]);
    const answerHtml = selected?.answer?.answer ?? '';
    const hasAnswer = answerHtml.trim().length > 0;
    const categoryLabel = selected?.type_help_question?.name || 'Uncategorized';
    const teamLabel = selected?.team?.name || 'All Teams (Generic)';
    const internalLabel = selected?.is_internal ? 'Internal only' : 'Visible to staff';
    const isAdmin = auth?.user?.is_admin || auth?.user?.roles?.some((role) => role?.id === 1);
    const canEdit = !!permissions?.help_questions_edit;
    const canDelete = !!permissions?.help_questions_delete || canEdit;

    const handleDelete = () => {
        if (!selected) return;
        if (confirm('Are you sure you want to delete this help question?')) {
            router.delete(route('admin.help-questions.destroy', selected.id), { preserveScroll: true });
        }
    };

    const handleSearchChange = (event) => {
        setSearch(event.target.value);
    };

    const handleSearchSubmit = (value) => {
        const isShowRoute = route().current('admin.help-questions.show');
        const targetId = selected?.id ?? list[0]?.id;
        const target = isShowRoute && targetId
            ? route('admin.help-questions.show', targetId)
            : route('admin.help-questions.index');
        router.get(target, { search: value }, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout user={auth?.user}>
            <Head title="Help Center" />
            <div className="overflow-x-auto px-[15px]">
                <div className="bg-white p-6">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
                        <div>
                            <h3 className="font-semibold text-lg text-gray-800">Help Center</h3>
                            <p className="text-sm text-gray-500">
                                Browse questions and answers for your team.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {isAdmin && canEdit && (
                                <Link
                                    href={route('admin.help-questions.index')}
                                    className="flex items-center justify-center h-[40px] px-4 py-2 rounded-xl border-[3px] border-[#979797] text-[#7C7C7C] hover:border-black hover:text-black"
                                >
                                    Manage Questions
                                </Link>
                            )}
                            {canEdit && selected && (
                                <button
                                    type="button"
                                    onClick={() => setIsModalOpen(true)}
                                    className="flex items-center justify-center h-[40px] px-4 py-2 rounded-xl border-[3px] border-[#3863C6] bg-[#3863C6] text-white hover:border-black hover:text-black"
                                >
                                    Edit Selected
                                </button>
                            )}
                            {canDelete && selected && (
                                <button
                                    type="button"
                                    onClick={handleDelete}
                                    className="flex items-center justify-center h-[40px] px-4 py-2 rounded-xl border-[3px] border-[#C65468] bg-[#C65468] text-white hover:border-black hover:text-black"
                                >
                                    Remove
                                </button>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div className="border border-secondary-100 rounded-lg p-5 lg:col-span-1">
                            <div className="mb-4">
                                <SearchBar
                                    search={search}
                                    handleSearchChange={handleSearchChange}
                                    handleSearchSubmit={handleSearchSubmit}
                                />
                            </div>
                            <div className="text-xs uppercase tracking-wide text-gray-500 mb-3">
                                Questions ({list.length})
                            </div>
                            <div className="space-y-2 max-h-[560px] overflow-y-auto pr-1">
                                {list.length === 0 && (
                                    <div className="text-sm text-gray-500">No questions match your search.</div>
                                )}
                                {list.map((question) => {
                                    const isActive = selected?.id === question.id;
                                    return (
                                        <Link
                                            key={question.id}
                                            href={buildShowUrl(question.id, search)}
                                            className={`block border rounded-lg p-3 transition ${
                                                isActive
                                                    ? 'border-primary-14 bg-primary-10'
                                                    : 'border-secondary-100 hover:border-primary-14 hover:bg-primary-10'
                                            }`}
                                        >
                                            <div className="text-sm font-semibold text-gray-800">
                                                {question.question}
                                            </div>
                                            <div className="mt-1 text-xs text-gray-500 flex flex-wrap gap-2">
                                                <span>{question?.type_help_question?.name || 'Uncategorized'}</span>
                                                {question.is_internal && (
                                                    <span className="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] text-amber-700">
                                                        Internal
                                                    </span>
                                                )}
                                            </div>
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>

                        <div className="border border-secondary-100 rounded-lg p-5 lg:col-span-2">
                            {selected ? (
                                <>
                                    <div className="flex flex-wrap items-center gap-3">
                                        <h4 className="font-semibold text-lg text-gray-800">{selected.question}</h4>
                                        <span
                                            className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${
                                                selected.is_internal
                                                    ? 'bg-amber-100 text-amber-800'
                                                    : 'bg-emerald-100 text-emerald-700'
                                            }`}
                                        >
                                            {selected.is_internal ? 'Internal' : 'Public'}
                                        </span>
                                    </div>
                                    <div className="mt-1 text-sm text-gray-500">
                                        {categoryLabel} · {teamLabel} · {internalLabel}
                                    </div>

                                    <div className="mt-6">
                                        <div className="text-xs uppercase tracking-wide text-gray-500">Answer</div>
                                        <div className="mt-3 text-sm text-gray-700">
                                            {hasAnswer ? (
                                                <div className="ProseMirror">{parse(answerHtml)}</div>
                                            ) : (
                                                <p className="text-gray-500">No answer has been added yet.</p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-500">
                                        <div>
                                            <span className="font-semibold text-gray-600">Created:</span>{' '}
                                            {formatDate(selected.created_at)}
                                        </div>
                                        <div>
                                            <span className="font-semibold text-gray-600">Updated:</span>{' '}
                                            {formatDate(selected.updated_at)}
                                        </div>
                                    </div>
                                </>
                            ) : (
                                <div className="text-gray-500 text-sm">Select a question to see the answer.</div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {canEdit && selected && (
                <HelpQuestionModal
                    isOpen={isModalOpen}
                    onClose={() => setIsModalOpen(false)}
                    mode="edit"
                    helpQuestion={selected}
                    typeHelpQuestions={typeHelpQuestions}
                    auth={auth}
                    teams={auth?.teams || []}
                    team_id={auth?.user?.team_id}
                />
            )}
        </AuthenticatedLayout>
    );
}
