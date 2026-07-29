import { useMemo, useEffect, useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import SettingsHeader from '@/Components/HeaderSites';
import BlueButton from '@/Components/BlueButton';

export default function Index({ permissions, settings = [], reportSettings = [], canManageReportSettings = false }) {
    const { props } = usePage();
    const [isOpenTab, setIsOpenTab] = useState('Settings');

    const settingsMap = useMemo(() => (
        settings.reduce((acc, item) => {
            acc[item.slug] = !!item.value;
            return acc;
        }, {})
    ), [settings]);
    const reportSettingsMap = useMemo(() => (
        reportSettings.reduce((acc, item) => {
            acc[item.slug] = item.rate ?? '';
            return acc;
        }, {})
    ), [reportSettings]);
    const formDefaults = useMemo(() => ({
        ...settingsMap,
        report_settings: reportSettingsMap,
    }), [settingsMap, reportSettingsMap]);

    const { data, setData, post, processing, isDirty } = useForm(formDefaults);
    const canOpenPulse = !!canManageReportSettings;

    useEffect(() => {
        setData(formDefaults);
    }, [formDefaults, setData]);

    const setReportSetting = (slug, value) => {
        setData('report_settings', {
            ...(data.report_settings || {}),
            [slug]: value,
        });
    };

    const handleSubmit = () => {
        post(route("admin.settings.store"), {
            preserveScroll: true,
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

                {canManageReportSettings && reportSettings.length > 0 && (
                    <div className="bg-white border border-secondary-100 mt-6">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="flex justify-start items-center px-4 pt-4 pb-[9px]">
                                <span className="flex items-center gap-2">
                                    <img src="/assets/icons/task-square.svg" alt="report settings" className="w-5 h-5" />
                                    <span className="font-semibold text-lg text-gray-800 block text-start">Admin Report Settings</span>
                                </span>
                            </div>
                            <div className="px-4 pb-4 text-sm text-gray-600">
                                Hidden franchise report percentages. Leave blank to use the system default.
                            </div>
                            <div className="px-4 pb-[9px]">
                                <table className="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead className="bg-gray-50 text-left text-gray-500">
                                        <tr className="flex justify-between items-center">
                                            <th className="w-[35%] p-3">Setting</th>
                                            <th className="w-[45%] p-3">Description</th>
                                            <th className="w-[20%] p-3">Percent</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {reportSettings.map((setting) => (
                                            <tr key={setting.slug} className="flex justify-between items-center hover:bg-gray-50">
                                                <td className="w-[35%] p-3">{setting.name}</td>
                                                <td className="w-[45%] p-3 text-gray-600">
                                                    {setting.description || `Default: ${setting.fallback}%`}
                                                </td>
                                                <td className="w-[20%] p-3">
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max="100"
                                                        step="0.01"
                                                        placeholder={`${setting.fallback}`}
                                                        value={data.report_settings?.[setting.slug] ?? ''}
                                                        onChange={(event) => setReportSetting(setting.slug, event.target.value)}
                                                        className="block w-full h-[42px] border-gray-300 shadow-sm input-text rounded-[10px] hover:border-black focus:outline-none focus:ring-0 focus:border-black"
                                                    />
                                                </td>
                                            </tr>
                                        ))}
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
