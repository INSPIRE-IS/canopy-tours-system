import { useMemo, useEffect, useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import SettingsHeader from '@/Components/HeaderSites';
import BlueButton from '@/Components/BlueButton';

const emptyPeriod = {
    royalty_percent: '',
    marketing_percent: '',
    effective_from: '',
    effective_to: '',
    is_active: true,
    note: '',
};

export default function Index({
    permissions,
    settings = [],
    canManageReportSettings = false,
    franchiseFeePeriods = [],
    franchiseFeeDefaults = {},
}) {
    const { props } = usePage();
    const [isOpenTab, setIsOpenTab] = useState('Settings');

    const settingsMap = useMemo(() => (
        settings.reduce((acc, item) => {
            acc[item.slug] = !!item.value;
            return acc;
        }, {})
    ), [settings]);
    const formDefaults = useMemo(() => ({ ...settingsMap }), [settingsMap]);

    const { data, setData, post, processing, isDirty } = useForm(formDefaults);
    const {
        data: periodData,
        setData: setPeriodData,
        post: postPeriod,
        put: putPeriod,
        processing: periodProcessing,
        errors: periodErrors,
        reset: resetPeriod,
        clearErrors: clearPeriodErrors,
    } = useForm(emptyPeriod);
    const [editingPeriodId, setEditingPeriodId] = useState(null);
    const canOpenPulse = !!canManageReportSettings;

    useEffect(() => {
        setData(formDefaults);
    }, [formDefaults, setData]);

    const handleSubmit = () => {
        post(route("admin.settings.store"), {
            preserveScroll: true,
        });
    };

    const clearPeriodForm = () => {
        setEditingPeriodId(null);
        resetPeriod();
        clearPeriodErrors();
    };

    const editPeriod = (period) => {
        setEditingPeriodId(period.id);
        clearPeriodErrors();
        setPeriodData({
            royalty_percent: period.royalty_percent,
            marketing_percent: period.marketing_percent,
            effective_from: period.effective_from,
            effective_to: period.effective_to || '',
            is_active: period.is_active,
            note: period.note || '',
        });
    };

    const submitPeriod = (event) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: clearPeriodForm,
        };

        if (editingPeriodId) {
            put(route('admin.settings.franchise-fee-periods.update', editingPeriodId), options);
            return;
        }

        postPeriod(route('admin.settings.franchise-fee-periods.store'), options);
    };

    const removePeriod = (period) => {
        if (!window.confirm(`Remove the franchise fee period starting ${period.effective_from}?`)) {
            return;
        }

        router.delete(route('admin.settings.franchise-fee-periods.destroy', period.id), {
            preserveScroll: true,
            onSuccess: () => {
                if (editingPeriodId === period.id) {
                    clearPeriodForm();
                }
            },
        });
    };

    return (
        <AuthenticatedLayout
            user={props.auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Settings</h2>}
        >
            <Head title="Settings" />

            <div className="bg-white p-6">
                <SettingsHeader permissions={permissions} isOpenTab={isOpenTab} setIsOpenTab={setIsOpenTab} />

                <div className="flex justify-between items-center mb-6">
                    <div className="text-gray-600">Team settings for booking features.</div>
                    <span className="flex gap-2">
                        <BlueButton title="Save Changes" onClick={handleSubmit} icon={true} disabled={processing || !isDirty} />
                    </span>
                </div>

                {canOpenPulse && (
                    <div className="mb-6 rounded-[10px] border border-secondary-100 bg-[#FBF9F7] p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h3 className="font-semibold text-gray-800">Application Monitoring</h3>
                            <p className="text-sm text-gray-600">
                                View Laravel Pulse performance, exceptions, slow requests, and system health.
                            </p>
                        </div>
                        <a
                            href="/pulse"
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center justify-center h-[42px] px-5 rounded-[10px] bg-[#2F66F6] text-white font-semibold text-sm hover:bg-[#2554D8]"
                        >
                            Open Monitoring
                        </a>
                    </div>
                )}

                <div className="bg-white border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="flex justify-start items-center px-4 pt-4 pb-[9px]">
                            <span className="flex items-center gap-2">
                                <img src="/assets/icons/task-square.svg" alt="settings" className="w-5 h-5" />
                                <span className="font-semibold text-lg text-gray-800 block text-start">Settings</span>
                            </span>
                        </div>
                        <div className="flex justify-between items-center px-4 pb-[9px]">
                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50 text-left text-gray-500">
                                    <tr className="flex justify-between items-center">
                                        <th className="w-[35%] p-3">Setting</th>
                                        <th className="w-[45%] p-3">Description</th>
                                        <th className="w-[20%] p-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {settings.map((setting) => (
                                        <tr key={setting.slug} className="flex justify-between items-center hover:bg-gray-50">
                                            <td className="w-[35%] p-3">{setting.name}</td>
                                            <td className="w-[45%] p-3 text-gray-600">{setting.description || '-'}</td>
                                            <td className="w-[20%] p-3">
                                                <label className="flex items-center gap-3 text-xs text-gray-500">
                                                    <input
                                                        type="checkbox"
                                                        checked={!!data[setting.slug]}
                                                        onChange={(event) => setData(setting.slug, event.target.checked)}
                                                        className="h-5 w-5 rounded border-gray-300 text-green-600 focus:ring-green-500"
                                                    />
                                                    <span>{data[setting.slug] ? 'Enabled' : 'Disabled'}</span>
                                                </label>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {canManageReportSettings && (
                    <div className="bg-white border border-secondary-100 mt-6">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="flex justify-start items-center px-4 pt-4 pb-[9px]">
                                <span className="flex items-center gap-2">
                                    <img src="/assets/icons/task-square.svg" alt="report settings" className="w-5 h-5" />
                                    <span className="font-semibold text-lg text-gray-800 block text-start">Franchise Fee Schedule</span>
                                </span>
                            </div>
                            <div className="px-4 pb-4 text-sm text-gray-600 max-w-4xl">
                                Royalty and marketing percentages are selected by activity date. Date boundaries are inclusive,
                                and active periods may not overlap. If no period applies, the report falls back to
                                {' '}{franchiseFeeDefaults.royalty_percent ?? 10}% royalty and
                                {' '}{franchiseFeeDefaults.marketing_percent ?? 1.5}% marketing.
                            </div>

                            <form onSubmit={submitPeriod} className="mx-4 mb-5 rounded-[10px] border border-secondary-100 bg-[#FBF9F7] p-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                                    <label className="text-sm text-gray-700">
                                        <span className="mb-1 block font-medium">Effective from *</span>
                                        <input
                                            type="date"
                                            value={periodData.effective_from}
                                            onChange={(event) => setPeriodData('effective_from', event.target.value)}
                                            className="block w-full h-[42px] border-gray-300 shadow-sm input-text rounded-[10px] hover:border-black focus:outline-none focus:ring-0 focus:border-black"
                                            required
                                        />
                                        {periodErrors.effective_from && <span className="mt-1 block text-xs text-red-600">{periodErrors.effective_from}</span>}
                                    </label>
                                    <label className="text-sm text-gray-700">
                                        <span className="mb-1 block font-medium">Effective to</span>
                                        <input
                                            type="date"
                                            value={periodData.effective_to}
                                            onChange={(event) => setPeriodData('effective_to', event.target.value)}
                                            className="block w-full h-[42px] border-gray-300 shadow-sm input-text rounded-[10px] hover:border-black focus:outline-none focus:ring-0 focus:border-black"
                                        />
                                        {periodErrors.effective_to && <span className="mt-1 block text-xs text-red-600">{periodErrors.effective_to}</span>}
                                    </label>
                                    <label className="text-sm text-gray-700">
                                        <span className="mb-1 block font-medium">Royalty % *</span>
                                        <input
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            value={periodData.royalty_percent}
                                            onChange={(event) => setPeriodData('royalty_percent', event.target.value)}
                                            className="block w-full h-[42px] border-gray-300 shadow-sm input-text rounded-[10px] hover:border-black focus:outline-none focus:ring-0 focus:border-black"
                                            required
                                        />
                                        {periodErrors.royalty_percent && <span className="mt-1 block text-xs text-red-600">{periodErrors.royalty_percent}</span>}
                                    </label>
                                    <label className="text-sm text-gray-700">
                                        <span className="mb-1 block font-medium">Marketing % *</span>
                                        <input
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            value={periodData.marketing_percent}
                                            onChange={(event) => setPeriodData('marketing_percent', event.target.value)}
                                            className="block w-full h-[42px] border-gray-300 shadow-sm input-text rounded-[10px] hover:border-black focus:outline-none focus:ring-0 focus:border-black"
                                            required
                                        />
                                        {periodErrors.marketing_percent && <span className="mt-1 block text-xs text-red-600">{periodErrors.marketing_percent}</span>}
                                    </label>
                                </div>
                                <div className="mt-4 flex flex-wrap items-end gap-4">
                                    <label className="flex-1 text-sm text-gray-700">
                                        <span className="mb-1 block font-medium">Note</span>
                                        <input
                                            type="text"
                                            maxLength="1000"
                                            value={periodData.note}
                                            onChange={(event) => setPeriodData('note', event.target.value)}
                                            className="block w-full h-[42px] border-gray-300 shadow-sm input-text rounded-[10px] hover:border-black focus:outline-none focus:ring-0 focus:border-black"
                                        />
                                    </label>
                                    <label className="flex h-[42px] items-center gap-2 text-sm text-gray-700">
                                        <input
                                            type="checkbox"
                                            checked={!!periodData.is_active}
                                            onChange={(event) => setPeriodData('is_active', event.target.checked)}
                                            className="h-5 w-5 rounded border-gray-300 text-green-600 focus:ring-green-500"
                                        />
                                        Active
                                    </label>
                                    <button
                                        type="submit"
                                        disabled={periodProcessing}
                                        className="h-[42px] rounded-[10px] bg-[#2F66F6] px-5 text-sm font-semibold text-white hover:bg-[#2554D8] disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {editingPeriodId ? 'Update period' : 'Add period'}
                                    </button>
                                    {editingPeriodId && (
                                        <button
                                            type="button"
                                            onClick={clearPeriodForm}
                                            className="h-[42px] rounded-[10px] border border-gray-300 px-5 text-sm font-semibold text-gray-700 hover:border-black"
                                        >
                                            Cancel
                                        </button>
                                    )}
                                </div>
                                {periodErrors.note && <span className="mt-1 block text-xs text-red-600">{periodErrors.note}</span>}
                            </form>

                            <div className="px-4 pb-[9px]">
                                <table className="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead className="bg-gray-50 text-left text-gray-500">
                                        <tr className="flex justify-between items-center">
                                            <th className="w-[18%] p-3">Effective from</th>
                                            <th className="w-[18%] p-3">Effective to</th>
                                            <th className="w-[14%] p-3">Royalty</th>
                                            <th className="w-[14%] p-3">Marketing</th>
                                            <th className="w-[18%] p-3">Status</th>
                                            <th className="w-[18%] p-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {franchiseFeePeriods.map((period) => (
                                            <tr key={period.id} className="flex justify-between items-center hover:bg-gray-50">
                                                <td className="w-[18%] p-3">{period.effective_from}</td>
                                                <td className="w-[18%] p-3">{period.effective_to || 'No end date'}</td>
                                                <td className="w-[14%] p-3">{Number(period.royalty_percent).toFixed(2)}%</td>
                                                <td className="w-[14%] p-3">{Number(period.marketing_percent).toFixed(2)}%</td>
                                                <td className="w-[18%] p-3">
                                                    <span className={period.is_active ? 'text-green-700' : 'text-gray-500'}>
                                                        {period.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                    {period.is_current && (
                                                        <span className="ml-2 rounded-full bg-green-50 px-2 py-1 text-xs font-semibold text-green-700">Current</span>
                                                    )}
                                                </td>
                                                <td className="w-[18%] p-3 text-right">
                                                    <button type="button" onClick={() => editPeriod(period)} className="font-semibold text-[#2F66F6] hover:underline">
                                                        Edit
                                                    </button>
                                                    <button type="button" onClick={() => removePeriod(period)} className="ml-4 font-semibold text-red-600 hover:underline">
                                                        Remove
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                        {franchiseFeePeriods.length === 0 && (
                                            <tr>
                                                <td colSpan="6" className="p-6 text-center text-gray-500">
                                                    No dated periods are configured. The fallback percentages are currently used.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                )}
            </div>

        </AuthenticatedLayout>
    );
}
