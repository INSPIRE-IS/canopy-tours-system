import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import SettingsHeader from '@/Components/HeaderSites';
import BlueButton from '@/Components/BlueButton';
import SettingsModal from './SettingsModal';

export default function Index({ permissions, settings = [] }) {
    const { props } = usePage();
    const [isOpenTab, setIsOpenTab] = useState('Settings');
    const [isModalOpen, setIsModalOpen] = useState(false);

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
                        <BlueButton title="Edit Settings" onClick={() => setIsModalOpen(true)} icon={true} />
                    </span>
                </div>

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
                                            <td className="w-[45%] p-3 text-gray-600">{setting.description}</td>
                                            <td className="w-[20%] p-3">
                                                {setting.value ? 'Enabled' : 'Disabled'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <SettingsModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                settings={settings}
            />
        </AuthenticatedLayout>
    );
}
