import { Link } from '@inertiajs/react';
import { useState } from 'react';

export default function SidebarMenuItem({ label, submenuItems = [] }) {
  const [open, setOpen] = useState(false);

  return (
    <div className="mb-2">
      <button
        onClick={() => setOpen(!open)}
        className={`group w-full flex items-center justify-between px-3 py-2 rounded-lg transition-colors ${
          open ? 'bg-secondary-200' : 'hover:bg-secondary-200'
        }`}
      >
        <span className="flex items-center gap-2">
          <span className={`dark-icon text-lg ${open ? 'text-black' : 'text-dark-gray'}`}>
            <img src="/assets/icons/arrow-down.svg" alt="arrow" className="w-5 h-5" />
          </span>
          <span className="menu-label text-menu-heading">{label}</span>
        </span>
        <span className={`arrow transition-transform duration-300 ${open ? 'rotate-90' : ''}`}>
          <img src="/assets/icons/arrow-left.svg" alt="arrow" className="w-3 h-3" />
        </span>
      </button>

      {open && (
        <div className="mt-0 bg-secondary-200 rounded-b-lg overflow-hidden">
          {submenuItems.map(({ name, url }, index) => (
            <Link
              key={index}
              href={route(url)}
              className="block pl-[37px] py-2 border-t border-primary-2 text-menu-subheading hover:bg-secondary-200"
            >
              {name}
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
