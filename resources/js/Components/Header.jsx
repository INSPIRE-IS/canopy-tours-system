import { Link } from "@inertiajs/react";
//import { FaSignOutAlt, FaUserCircle } from "react-icons/fa";

export default function Header({ user, toggleSidebar }) {
    return (
        <header className="bg-white shadow-md px-6 py-4 flex justify-between items-center">
        <div className="flex items-center gap-4">
            <button onClick={toggleSidebar} className="text-gray-600 hover:text-black">
                {/* Sidebar toggle icon */}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 21" className="h-6 w-6">
                    <path d="M6.64166 2.49243V19.1591" stroke="#BEBEC8" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                    <path d="M12.475 8.69238L10.3417 10.8257L12.475 12.959" stroke="#BEBEC8" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            </button>
            <span className="font-semibold text-lg text-gray-800">Welcome, {user?.name}</span>
        </div>
        {/* Add user dropdown, settings, notifications, etc. */}
    </header>
    );
}
