import { Link } from '@inertiajs/react';

export default function Guest({ children }) {
    return (
        <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-10">
            <div className="w-full sm:max-w-md px-6 py-6 bg-white shadow-lg border border-slate-100 rounded-2xl">
                <div className="flex justify-center mb-4">
                    <Link href="/" className="flex items-center gap-3">
                        <img
                            src="/assets/icons/logo-icon.png"
                            alt="Canopy Tours Icon"
                            className="h-12 w-12 sm:h-14 sm:w-14 object-contain"
                        />
                        <img
                            src="/assets/icons/logo-name.png"
                            alt="Canopy Tours"
                            className="h-9 sm:h-10 object-contain"
                        />
                    </Link>
                </div>

                {children}
            </div>
        </div>
    );
}
