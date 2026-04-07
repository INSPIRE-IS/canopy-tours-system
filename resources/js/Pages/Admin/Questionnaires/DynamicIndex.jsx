import { useEffect, useMemo, useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SettingsHeader from '@/Components/HeaderSites';
import OutlineButton from '@/Components/OutlineButton';
import BlueButton from '@/Components/BlueButton';
import CustomSelect from '@/Components/CustomSelect';

const toSlug = (value) => {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-_]/g, '')
        .replace(/\s+/g, '_')
        .replace(/_+/g, '_');
};

const parseCsvOptions = (value) => {
    if (!value) return [];
    return value
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean);
};

const buildQuestionRows = (questions, questionSites, seasonId) => {
    if (!seasonId) {
        return [];
    }

    const seasonSites = new Map(
        (questionSites || [])
            .filter((site) => Number(site.season_id) === Number(seasonId))
            .map((site) => [site.question_id, site])
    );

    return questions
        .filter((question) => question.is_visible)
        .map((question, index) => {
            const seasonSite = seasonSites.get(question.id);
            const enabledDefault = question.is_disabled ? true : (question.is_enabled ?? false);
            const enabled = question.is_disabled ? true : (seasonSite?.enabled ?? enabledDefault);
            const customOptions = seasonSite?.custom_options ?? [];
            const customOptionsInput = question.is_custom
                ? [...customOptions]
                    .filter((option) => option?.show !== 0)
                    .sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0))
                    .map((option) => option?.name)
                    .filter(Boolean)
                    .join(', ')
                : '';

            return {
                question_id: question.id,
                name: question.name,
                slug: question.slug,
                type: question.type,
                is_disabled: question.is_disabled,
                is_custom: question.is_custom,
                enabled,
                sort_order: seasonSite?.sort_order ?? index + 1,
                custom_options: customOptions,
                custom_options_input: customOptionsInput,
            };
        });
};

const QuestionnairesModal = ({ isOpen, onClose, service, questions }) => {
    const [rows, setRows] = useState([]);
    const [initialRows, setInitialRows] = useState([]);
    const [selectedSeasonId, setSelectedSeasonId] = useState(null);
    const [customErrors, setCustomErrors] = useState({});

    useEffect(() => {
        if (!service) return;
        const nextRows = buildQuestionRows(questions, service.question_sites || [], selectedSeasonId);
        setRows(nextRows);
        setInitialRows(nextRows);
        setCustomErrors({});
    }, [service, questions, selectedSeasonId]);

    useEffect(() => {
        if (service) {
            if (service.seasons?.length === 1) {
                setSelectedSeasonId(service.seasons[0].id);
            } else {
                setSelectedSeasonId(null);
            }
        }
    }, [service]);

    const enabledCount = useMemo(() => rows.filter((row) => row.enabled).length, [rows]);
    const isDirty = useMemo(() => {
        if (rows.length !== initialRows.length) return true;
        return rows.some((row, index) => {
            const base = initialRows[index];
            if (!base) return true;
            return (
                row.question_id !== base.question_id ||
                row.enabled !== base.enabled ||
                Number(row.sort_order) !== Number(base.sort_order) ||
                row.custom_options_input !== base.custom_options_input
            );
        });
    }, [rows, initialRows]);

    const handleToggle = (questionId, enabled) => {
        setRows((prev) =>
            prev.map((row) =>
                row.question_id === questionId ? { ...row, enabled } : row
            )
        );
    };

    const handleSortChange = (questionId, value) => {
        const numeric = value === '' ? '' : Number(value);
        setRows((prev) =>
            prev.map((row) =>
                row.question_id === questionId ? { ...row, sort_order: numeric } : row
            )
        );
    };

    const handleCustomOptionsChange = (questionId, value) => {
        setRows((prev) =>
            prev.map((row) =>
                row.question_id === questionId ? { ...row, custom_options_input: value } : row
            )
        );
    };

    const moveRow = (index, direction) => {
        setRows((prev) => {
            const next = [...prev];
            const targetIndex = index + direction;
            if (targetIndex < 0 || targetIndex >= next.length) return prev;
            const [moved] = next.splice(index, 1);
            next.splice(targetIndex, 0, moved);
            return next.map((row, idx) => ({
                ...row,
                sort_order: idx + 1,
            }));
        });
    };

    const handleSave = () => {
        if (!service || !selectedSeasonId) return;
        const errors = {};
        const payloadQuestions = rows.map((row) => {
            if (!row.is_custom) {
                return {
                    question_id: row.question_id,
                    enabled: row.enabled,
                    sort_order: row.sort_order === '' ? 0 : row.sort_order,
                    custom_options: row.custom_options ?? null,
                };
            }

            const inputItems = parseCsvOptions(row.custom_options_input);
            if (row.enabled && inputItems.length === 0) {
                errors[row.question_id] = 'Options are required when this question is enabled.';
            }

            const existing = Array.isArray(row.custom_options) ? row.custom_options : [];
            const existingById = new Map(existing.map((option) => [String(option.id), option]));
            const usedIds = new Set();
            const nextOptions = inputItems.map((name, index) => {
                const id = toSlug(name);
                usedIds.add(id);
                return {
                    id,
                    name,
                    show: 1,
                    sort: index + 1,
                };
            });

            existing.forEach((option) => {
                const optionId = String(option.id ?? '');
                if (!optionId || usedIds.has(optionId)) return;
                nextOptions.push({
                    id: optionId,
                    name: option.name ?? optionId,
                    show: 0,
                    sort: option.sort ?? nextOptions.length + 1,
                });
            });

            return {
                question_id: row.question_id,
                enabled: row.enabled,
                sort_order: row.sort_order === '' ? 0 : row.sort_order,
                custom_options: nextOptions,
            };
        });

        if (Object.keys(errors).length > 0) {
            setCustomErrors(errors);
            return;
        }

        setCustomErrors({});
        router.put(
            route('admin.dynamic-questionnaires.update', service.id),
            {
                season_id: selectedSeasonId,
                questions: payloadQuestions,
            },
            {
                preserveScroll: true,
                onSuccess: onClose,
            }
        );
    };

    if (!isOpen || !service) return null;

    const seasonOptions = (service.seasons || []).map((season) => ({
        id: season.id,
        name: season.name,
    }));

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[75%] h-[90%] overflow-y-auto">
                <div className="flex items-start justify-between mb-4">
                    <div>
                        <h2 className="text-xl font-bold">Questionnaire Settings</h2>
                        <p className="text-sm text-gray-600">
                            {service.name} - Enabled {enabledCount}/{rows.length}
                        </p>
                        <p className="text-xs text-gray-500 mt-1">
                            Seasons: {service.seasons?.length ? service.seasons.map((season) => season.name).join(', ') : '--'}
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700 text-2xl leading-none"
                        aria-label="Close"
                    >
                        &times;
                    </button>
                </div>

                <div className="grid grid-cols-2 gap-4 mb-4">
                    <CustomSelect
                        label="Season scope"
                        name="season_scope"
                        value={selectedSeasonId ?? ''}
                        onChange={(e) => {
                            const nextValue = e.target.value === '' ? null : Number(e.target.value);
                            setSelectedSeasonId(nextValue);
                        }}
                        options={seasonOptions}
                        showOption={true}
                        optionText="-- Select a season --"
                    />
                    <div className="flex items-end">
                        <div>
                            <p className="text-xs text-gray-500">
                                {selectedSeasonId ? 'Changes apply to the selected season.' : 'Select a season to edit questions.'}
                            </p>
                            {isDirty && (
                                <p className="text-xs text-amber-600 mt-1">Unsaved changes</p>
                            )}
                        </div>
                    </div>
                </div>

                <div className="border border-secondary-100 rounded-lg overflow-hidden">
                    <div className="grid grid-cols-12 gap-2 bg-gray-50 text-xs font-semibold text-gray-500 px-4 py-3">
                        <div className="col-span-1">On</div>
                        <div className="col-span-6">Question</div>
                        <div className="col-span-2">Type</div>
                        <div className="col-span-3">Sort Order</div>
                    </div>
                    <div className="divide-y divide-gray-100">
                        {rows.length === 0 ? (
                            <div className="px-4 py-6 text-sm text-gray-500">
                                Select a season to load questions.
                            </div>
                        ) : (
                            rows.map((row, index) => (
                                <div key={row.question_id} className="grid grid-cols-12 gap-2 px-4 py-3 items-center">
                                    <div className="col-span-1">
                                        <input
                                            type="checkbox"
                                            checked={row.enabled}
                                            onChange={(e) => handleToggle(row.question_id, e.target.checked)}
                                            className="h-4 w-4 disabled:opacity-50 disabled:cursor-not-allowed"
                                            disabled={row.is_disabled}
                                            title={row.is_disabled ? 'This question is compulsory and may not be removed.' : ''}
                                        />
                                    </div>
                                <div className="col-span-6">
                                    <div className="text-sm font-medium text-gray-800">{row.name}</div>
                                    <div className="text-xs text-gray-500">{row.slug}</div>
                                    {row.is_custom && (
                                        <div className="mt-2">
                                            <label className="block text-xs text-gray-500">
                                                Options (comma-separated)
                                            </label>
                                            <input
                                                type="text"
                                                value={row.custom_options_input}
                                                onChange={(e) => handleCustomOptionsChange(row.question_id, e.target.value)}
                                                className="mt-1 w-full rounded-md border border-gray-300 px-2 py-1 text-xs"
                                                disabled={row.is_disabled}
                                            />
                                            {customErrors[row.question_id] && (
                                                <div className="text-xs text-red-500 mt-1">
                                                    {customErrors[row.question_id]}
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                                    <div className="col-span-2 text-xs uppercase text-gray-500">{row.type}</div>
                                    <div className="col-span-3">
                                        <div className="flex items-center gap-2">
                                            <input
                                                type="number"
                                                min="0"
                                                value={row.sort_order}
                                                onChange={(e) => handleSortChange(row.question_id, e.target.value)}
                                                className="w-full border border-gray-300 rounded-md px-2 py-1 text-sm"
                                                disabled={!row.enabled || row.is_disabled}
                                            />
                                            <div className="flex flex-col gap-1">
                                                <button
                                                    type="button"
                                                    className="px-2 py-1 text-xs border border-gray-300 rounded disabled:opacity-50"
                                                    onClick={() => moveRow(index, -1)}
                                                    disabled={index === 0 || row.is_disabled}
                                                >
                                                    Up
                                                </button>
                                                <button
                                                    type="button"
                                                    className="px-2 py-1 text-xs border border-gray-300 rounded disabled:opacity-50"
                                                    onClick={() => moveRow(index, 1)}
                                                    disabled={index === rows.length - 1 || row.is_disabled}
                                                >
                                                    Down
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>

                <div className="flex justify-end gap-4 mt-6">
                    <OutlineButton title="Cancel" onClick={onClose} width="w-[140px]" />
                    <BlueButton
                        title="Save"
                        onClick={handleSave}
                        width="w-[140px]"
                        disabled={!selectedSeasonId || !isDirty}
                    />
                </div>
            </div>
        </div>
    );
};

export default function DynamicIndex({ services, questions, permissions, isAdmin }) {
    const { props } = usePage();
    const [isOpenTab, setIsOpenTab] = useState('Questionnaires');
    const [selectedService, setSelectedService] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isQuestionModalOpen, setIsQuestionModalOpen] = useState(false);
    const [isLibraryModalOpen, setIsLibraryModalOpen] = useState(false);

    const visibleQuestions = questions.filter((question) => question.is_visible);
    const totalQuestions = visibleQuestions.length;
    const visibleQuestionIds = new Set(visibleQuestions.map((question) => question.id));
    const questionById = new Map(questions.map((question) => [question.id, question]));
    const defaultEnabledById = new Map(
        questions.map((question) => [
            question.id,
            question.is_disabled ? true : (question.is_visible ? (question.is_enabled ?? false) : false),
        ])
    );

    const openModal = (service) => {
        setSelectedService(service);
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setSelectedService(null);
    };

    return (
        <AuthenticatedLayout
            user={props.auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Questionnaires</h2>}
        >
            <div className="bg-white p-6">
                <SettingsHeader permissions={permissions} isOpenTab={isOpenTab} setIsOpenTab={setIsOpenTab} />

                <div className="bg-white border border-secondary-100 rounded-lg overflow-hidden mt-4">
                    <div className="flex justify-start items-center px-4 pt-4 pb-[9px]">
                        <span className="flex items-center gap-2">
                            <img src="/assets/icons/task-square.svg" alt="questionnaires" className="w-5 h-5" />
                            <span className="font-semibold text-lg text-gray-800 block text-start">Questionnaires</span>
                        </span>
                    </div>
                    {permissions?.questionnaires_create && (
                        <div className="flex justify-end px-4 pb-2">
                            <BlueButton title="Add Question" onClick={() => setIsQuestionModalOpen(true)} width="w-[160px]" />
                        </div>
                    )}
                    {isAdmin && (
                        <div className="flex justify-end px-4 pb-2">
                            <OutlineButton title="Question Library" onClick={() => setIsLibraryModalOpen(true)} width="w-[170px]" />
                        </div>
                    )}
                    <div className="px-4 pb-4">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-left text-gray-500">
                                <tr className="flex justify-between items-center">
                                    <th className="w-[220px] p-3">Service</th>
                                    <th className="w-[320px] p-3">Seasons</th>
                                    <th className="w-[140px] p-3">Questions</th>
                                    <th className="w-[140px] p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {services.map((service) => {
                                    const seasons = service.seasons || [];
                                    const questionSites = service.question_sites || [];

                                    const seasonSites = (seasonId) =>
                                        questionSites.filter(
                                            (site) =>
                                                Number(site.season_id) === Number(seasonId) &&
                                                visibleQuestionIds.has(site.question_id)
                                        );

                                    const seasonSummaries = seasons.map((season) => {
                                        const sites = seasonSites(season.id);
                                        const enabledCount = sites.filter((site) => {
                                            const question = questionById.get(site.question_id);
                                            return question?.is_disabled ? true : !!site.enabled;
                                        }).length;
                                        const overrideCount = sites.filter((site) => {
                                            const question = questionById.get(site.question_id);
                                            if (!question) return false;
                                            const defaultEnabled = defaultEnabledById.get(site.question_id);
                                            const effectiveEnabled = question.is_disabled ? true : !!site.enabled;
                                            return defaultEnabled !== undefined && effectiveEnabled !== defaultEnabled;
                                        }).length;

                                        return {
                                            id: season.id,
                                            name: season.name,
                                            enabledCount,
                                            overrideCount,
                                            hasSites: sites.length > 0,
                                        };
                                    });

                                    const seasonsLabel = service.seasons?.length
                                        ? service.seasons.map((season) => season.name).join(', ')
                                        : '--';

                                    return (
                                        <tr key={service.id} className="flex justify-between items-center hover:bg-gray-50">
                                            <td className="w-[220px] p-3">{service.name}</td>
                                            <td className="w-[320px] p-3">{seasonsLabel}</td>
                                            <td className="w-[140px] p-3">
                                                {seasonSummaries.length === 0 && (
                                                    <span className="text-gray-400">--</span>
                                                )}
                                                {seasonSummaries.map((summary) => (
                                                    <div key={summary.id} className="text-sm text-gray-700">
                                                        {summary.name}: {summary.enabledCount}/{totalQuestions}
                                                        {summary.overrideCount > 0 && (
                                                            <span className="block text-xs text-gray-400">
                                                                +{summary.overrideCount} overrides
                                                            </span>
                                                        )}
                                                    </div>
                                                ))}
                                            </td>
                                            <td className="w-[140px] p-3">
                                                {permissions?.questionnaires_edit ? (
                                                    <button
                                                        onClick={() => openModal(service)}
                                                        className="text-blue-600 hover:text-blue-800"
                                                    >
                                                        Manage
                                                    </button>
                                                ) : (
                                                    <span className="text-gray-400">View only</span>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <QuestionnairesModal
                isOpen={isModalOpen}
                onClose={closeModal}
                service={selectedService}
                questions={questions}
            />

            <QuestionCreateModal
                isOpen={isQuestionModalOpen}
                onClose={() => setIsQuestionModalOpen(false)}
            />

            <QuestionLibraryModal
                isOpen={isLibraryModalOpen}
                onClose={() => setIsLibraryModalOpen(false)}
                questions={questions}
            />
        </AuthenticatedLayout>
    );
}

const QuestionCreateModal = ({ isOpen, onClose }) => {
    const { data, setData, post, errors, processing, reset } = useForm({
        name: '',
        slug: '',
        type: 'indemnity',
        description: '',
        options: '',
        conditional_logic: '',
        is_visible: false,
        is_disabled: false,
        is_enabled: false,
    });

    useEffect(() => {
        if (isOpen) {
            reset();
        }
    }, [isOpen, reset]);

    const handleSubmit = (event) => {
        event.preventDefault();
        post(route('admin.dynamic-questionnaires.questions.store'), {
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[55%] max-w-2xl">
                <div className="flex items-start justify-between mb-4">
                    <div>
                        <h2 className="text-xl font-bold">Add Question</h2>
                        <p className="text-xs text-gray-500 mt-1">
                            New questions are hidden by default. Enable visibility in the library and add UI support for new slugs.
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700 text-2xl leading-none"
                        aria-label="Close"
                    >
                        &times;
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="text-xs text-gray-700">Question name</label>
                        <input
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className={`mt-1 block w-full h-[42px] rounded-md border ${errors.name ? 'border-red-500' : 'border-gray-300'} px-3`}
                            required
                        />
                        {errors.name && <p className="text-red-500 text-sm mt-1">{errors.name}</p>}
                    </div>

                    <div>
                        <label className="text-xs text-gray-700">Slug (snake_case)</label>
                        <input
                            type="text"
                            value={data.slug}
                            onChange={(e) => setData('slug', e.target.value)}
                            className={`mt-1 block w-full h-[42px] rounded-md border ${errors.slug ? 'border-red-500' : 'border-gray-300'} px-3`}
                            required
                        />
                        {errors.slug && <p className="text-red-500 text-sm mt-1">{errors.slug}</p>}
                    </div>

                    <CustomSelect
                        label="Question type"
                        name="type"
                        value={data.type}
                        onChange={(e) => setData('type', e.target.value)}
                        options={[
                            { id: 'indemnity', name: 'Indemnity (check-in)' },
                            { id: 'feedback', name: 'Feedback' },
                        ]}
                        showOption={false}
                        errors={errors}
                    />

                    <div>
                        <label className="text-xs text-gray-700">Description (optional)</label>
                        <input
                            type="text"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            className="mt-1 block w-full h-[42px] rounded-md border border-gray-300 px-3"
                        />
                    </div>

                    <div>
                        <label className="text-xs text-gray-700">Options (comma-separated or JSON array)</label>
                        <input
                            type="text"
                            value={data.options}
                            onChange={(e) => setData('options', e.target.value)}
                            className="mt-1 block w-full h-[42px] rounded-md border border-gray-300 px-3"
                        />
                    </div>

                    <div>
                        <label className="text-xs text-gray-700">Conditional logic (JSON, optional)</label>
                        <input
                            type="text"
                            value={data.conditional_logic}
                            onChange={(e) => setData('conditional_logic', e.target.value)}
                            className="mt-1 block w-full h-[42px] rounded-md border border-gray-300 px-3"
                        />
                    </div>

                    <div className="flex justify-end gap-3">
                        <OutlineButton title="Cancel" onClick={onClose} width="w-[140px]" />
                        <BlueButton title="Create" width="w-[140px]" type="submit" disabled={processing} />
                    </div>
                </form>
            </div>
        </div>
    );
};

const QuestionLibraryModal = ({ isOpen, onClose, questions }) => {
    const { data, setData, put, processing, reset } = useForm({
        questions: [],
    });

    useEffect(() => {
        if (!isOpen) return;
        const rows = questions.map((question) => ({
            id: question.id,
            name: question.name,
            slug: question.slug,
            type: question.type,
            is_visible: question.is_visible,
            is_disabled: question.is_disabled,
            is_enabled: question.is_enabled,
            is_custom: question.is_custom,
        }));
        setData('questions', rows);
    }, [isOpen, questions, setData]);

    const handleToggle = (id, key, value) => {
        setData('questions', data.questions.map((row) => {
            if (row.id !== id) return row;
            const next = { ...row, [key]: value };
            if (key === 'is_disabled' && value) {
                next.is_visible = true;
                next.is_enabled = true;
            }
            if (key === 'is_visible' && !value) {
                next.is_enabled = false;
            }
            return next;
        }));
    };

    const handleSave = () => {
        put(route('admin.dynamic-questionnaires.questions.update'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
            onError: () => {
                setErrors(errors);
            },
        });
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[70%] h-[85%] overflow-y-auto">
                <div className="flex items-start justify-between mb-4">
                    <div>
                        <h2 className="text-xl font-bold">Question Library</h2>
                        <p className="text-xs text-gray-500 mt-1">
                            Visible controls whether a question appears in service toggles. Default On sets the starting state for new seasons. Custom enables per-season CSV options. Compulsory locks a question on.
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700 text-2xl leading-none"
                        aria-label="Close"
                    >
                        &times;
                    </button>
                </div>

                <div className="border border-secondary-100 rounded-lg overflow-hidden">
                    <div className="grid grid-cols-12 gap-2 bg-gray-50 text-xs font-semibold text-gray-500 px-4 py-3">
                        <div className="col-span-4">Question</div>
                        <div className="col-span-2">Type</div>
                        <div className="col-span-2">Visible</div>
                        <div className="col-span-1">Default On</div>
                        <div className="col-span-1">Custom</div>
                        <div className="col-span-2">Compulsory</div>
                    </div>
                    <div className="divide-y divide-gray-100">
                        {data.questions.map((row) => (
                            <div key={row.id} className="grid grid-cols-12 gap-2 px-4 py-3 items-center">
                                <div className="col-span-4">
                                    <div className="text-sm font-medium text-gray-800">{row.name}</div>
                                    <div className="text-xs text-gray-500">{row.slug}</div>
                                </div>
                                <div className="col-span-2 text-xs uppercase text-gray-500">{row.type}</div>
                                <div className="col-span-2">
                                    <input
                                        type="checkbox"
                                        checked={row.is_visible}
                                        onChange={(e) => handleToggle(row.id, 'is_visible', e.target.checked)}
                                        className="h-4 w-4"
                                    />
                                </div>
                                <div className="col-span-1">
                                    <input
                                        type="checkbox"
                                        checked={row.is_enabled}
                                        onChange={(e) => handleToggle(row.id, 'is_enabled', e.target.checked)}
                                        className="h-4 w-4"
                                        disabled={row.is_disabled}
                                        title={row.is_disabled ? 'Compulsory questions are always enabled.' : ''}
                                    />
                                </div>
                                <div className="col-span-1">
                                    <input
                                        type="checkbox"
                                        checked={row.is_custom}
                                        onChange={(e) => handleToggle(row.id, 'is_custom', e.target.checked)}
                                        className="h-4 w-4"
                                    />
                                </div>
                                <div className="col-span-2">
                                    <input
                                        type="checkbox"
                                        checked={row.is_disabled}
                                        onChange={(e) => handleToggle(row.id, 'is_disabled', e.target.checked)}
                                        className="h-4 w-4"
                                    />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="flex justify-end gap-3 mt-6">
                    <OutlineButton title="Cancel" onClick={onClose} width="w-[140px]" />
                    <BlueButton title="Save" width="w-[140px]" onClick={handleSave} disabled={processing} />
                </div>
            </div>
        </div>
    );
};
