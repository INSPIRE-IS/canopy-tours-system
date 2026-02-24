import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { icons } from '../icons';

export default function SidebarMenuLink({ label, isOpen, url = null, params = null, iconName: iconName, submenuItems = [] }) {
  const [open, setOpen] = useState(false);
  const Icon = iconName ? icons[iconName] : null;
  const hasSubmenu = submenuItems.length > 0;
  const isActive = !!url && route().current(url);

  useEffect(() => {
    if (!isOpen) setOpen(false);
  }, [isOpen]);
  return (
    <div className={`${open || isActive ? 'text-[#3863C6]' : 'text-dark-gray'} `}>
      {hasSubmenu ? (
        <div
          onClick={() => setOpen(!open)}
          className={`group menu-link flex items-center justify-between ${isOpen ? 'px-3' : 'px-1.5'} ${
            open ? 'text-[#3863C6]' : 'text-dark-gray'
          } py-2 hover:bg-secondary-200 hover:text-black rounded-lg ${open ? 'bg-secondary-200' : ''} cursor-pointer`}
        >
          <span className={`flex items-center gap-2 ${open ? 'text-[#3863C6]' : 'text-dark-gray'} `}>
            <span
              className={`dark-icon flex items-center justify-center w-5 h-5 transition-colors duration-300 ${open ? 'text-[#3863C6]' : 'text-dark-gray'} `}>
              {Icon}
            </span>
            <span className={`${isOpen ? '' : 'hidden'} menu-label text-menu-heading`}>{label}</span>
          </span>
          <span className={`${isOpen ? '' : 'hidden'} arrow transition-transform duration-300 ${open ? 'rotate-90' : ''}`}>
            <img src="/assets/icons/arrow-left.svg" alt="arrow" className="w-3 h-3" />
          </span>
        </div>
      ) : (
        <Link
          key={label}
          href={params ? route(url, params) : route(url)}
          className={`group menu-link flex items-center justify-between ${isOpen ? 'px-3' : 'px-1.5'} ${
            isActive ? 'text-[#3863C6] bg-secondary-200' : 'text-dark-gray'
          } py-2 hover:bg-secondary-200 hover:text-black rounded-lg cursor-pointer`}
        >
          <span className={`flex items-center gap-2 ${isActive ? 'text-[#3863C6]' : 'text-dark-gray'} `}>
            <span
              className={`dark-icon flex items-center justify-center w-5 h-5 transition-colors duration-300 ${isActive ? 'text-[#3863C6]' : 'text-dark-gray'} `}>
              {Icon}
            </span>
            <span className={`${isOpen ? '' : 'hidden'} menu-label text-menu-heading`}>{label}</span>
          </span>
        </Link>
      )}

      {/* Submenu (if you want it) */}
      {open && submenuItems.length > 0 && (
        <div className="mt-0 bg-secondary-200 rounded-b-lg overflow-hidden">
          {submenuItems.map(({ name, url }, idx) => (
            <Link
            key={idx}
            href={route(url)}
            className="block pl-[37px] py-2 border-t border-primary-2 text-menu-subheading hover:bg-secondary-300"
          >
            {name}
          </Link>
          ))}
        </div>
      )}
    </div>
  );
}
