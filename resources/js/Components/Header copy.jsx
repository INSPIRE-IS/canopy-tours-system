import { Link } from "@inertiajs/react";
//import { FaSignOutAlt, FaUserCircle } from "react-icons/fa";

export default function Header({ user, toggleSidebar }) {
    return (
        <header className="bg-gray-800 text-white p-4 flex justify-between items-center">
            <button onClick={toggleSidebar} className="text-white text-2xl">
                ☰
            </button>
            <h1 className="text-lg font-bold">
                {import.meta.env.VITE_APP_NAME || "MyApp"}
            </h1>

            <div className="flex items-center space-x-4">
                <span>{user?.name}</span>
                {/* <FaUserCircle className="text-2xl" /> */}
                <Link
                    href={route("logout")}
                    method="post"
                    as="button"
                    className="flex items-center space-x-2 bg-red-500 hover:bg-red-600 px-3 py-2 rounded text-sm"
                >
                    {/* <FaSignOutAlt /> */}
                    <span>Logout</span>
                </Link>
            </div>
        </header>
    );
}
