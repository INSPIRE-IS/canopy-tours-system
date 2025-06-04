import { useState } from 'react';
import { Link } from '@inertiajs/react';
//import { FaUsers, FaCogs, FaSignOutAlt, FaChevronDown, FaChevronRight } from 'react-icons/fa';

const menuItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Bookings', href: '/bookings' },
  { label: 'Inventory', href: '/inventory' },
  { label: 'Services', href: '/services' },
  { label: 'Messages', href: '/messages' },
  { label: 'Reports', href: '/reports' },
  { label: 'Help Center', href: '/help' },
  { label: 'Settings', href: '/settings' },
  {
    label: 'User Management',
    icon: 'icon',
    submenu: [
      { label: 'Users', href: '/users' },
      { label: 'Roles', href: '/roles' },
      { label: 'Permissions', href: '/permissions' },
    ],
  },
  { label: 'Logout', href: '/logout', icon: 'icon' },
];

export default function Sidebar({ user }) {
  const [isSidebarOpen, setSidebarOpen] = useState(true);
  const [openDropdowns, setOpenDropdowns] = useState({});

  const toggleSidebar = () => setSidebarOpen(!isSidebarOpen);

  const toggleDropdown = (label) => {
    setOpenDropdowns((prev) => ({
      ...prev,
      [label]: !prev[label],
    }));
  };

  return (
    <aside
      className={`h-screen fixed top-0 left-0 bg-gray-100 shadow-lg transition-all duration-300 flex flex-col ${
        isSidebarOpen ? 'w-60' : 'w-16'
      }`}
    >
      {/* Toggle Button */}
      <div className="p-2 flex justify-end">
        <button onClick={toggleSidebar} className="text-gray-700 text-lg">
          {isSidebarOpen ? '<' : '>'}
        </button>
      </div>

      {/* Navigation */}
      <nav className="flex-1 px-2 space-y-1">
        {menuItems.map((item, index) => (
          <div key={index}>
            {item.submenu ? (
              <>
                <button
                  onClick={() => toggleDropdown(item.label)}
                  className="w-full flex items-center justify-between px-2 py-2 text-left hover:bg-gray-200 rounded"
                >
                  <span className="flex items-center gap-2">
                    {item.icon}
                    {isSidebarOpen && item.label}
                  </span>
                  {isSidebarOpen && (openDropdowns[item.label] ? 'v' : '>')}
                </button>
                {openDropdowns[item.label] && (
                  <div className="ml-6">
                    {item.submenu.map((sub, i) => (
                      <Link
                        key={i}
                        href={sub.href}
                        className="block px-2 py-1 text-sm hover:bg-gray-100 rounded"
                      >
                        {sub.label}
                      </Link>
                    ))}
                  </div>
                )}
              </>
            ) : (
              <Link
                href={item.href}
                className="flex items-center gap-2 px-2 py-2 hover:bg-gray-200 rounded"
              >
                {item.icon}
                {isSidebarOpen && item.label}
              </Link>
            )}
          </div>
        ))}
      </nav>

      {/* User Info */}
      <div className="p-4 border-t border-gray-300 flex items-center gap-2">
        <div className="w-10 h-10 bg-gray-400 rounded-full"></div>
        {isSidebarOpen && (
          <div>
            <p className="text-sm font-semibold">{user?.name}</p>
            <p className="text-xs text-gray-600">{user?.email}</p>
          </div>
        )}
      </div>
    </aside>
  );
}  
