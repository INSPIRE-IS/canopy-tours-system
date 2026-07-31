import FlashMessage from '@/Components/FlashMessage';
import { router, usePage } from '@inertiajs/react';
import React from 'react';

export default function KioskLayout({ children }) {
    const { auth, flash, errors } = usePage().props;
    const user = auth?.user;
    const teamName = user?.team?.name ?? 'Canopy Tours';

    const handleLogout = () => {
        router.post(route('logout'));
    };

    return (
        <div className="min-h-screen bg-[#F7F8FB]">
            <header className="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-3">
                        <img src="/assets/icons/logo-icon.png" alt="Canopy Tours" className="h-9 w-9" />
                        <div>
                            <div className="text-sm font-semibold text-gray-900">{teamName}</div>
                            <div className="text-xs text-gray-500">Check-in kiosk</div>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <div className="hidden text-right sm:block">
                            <div className="text-sm font-medium text-gray-900">{user?.name}</div>
                            <div className="text-xs text-gray-500">Staff access</div>
                        </div>
                        <button
                            type="button"
                            onClick={handleLogout}
                            className="h-10 rounded-[10px] border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 shadow-sm hover:border-gray-500"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </header>

            <FlashMessage flash={flash} errors={errors} />

            <main>{children}</main>
        </div>
    );
}
