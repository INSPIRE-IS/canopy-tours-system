import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function SidebarMenuLink({ label, isOpen, url = null, icon: Icon, submenuItems = [] }) {
  const [open, setOpen] = useState(false);
  useEffect(() => {
    if (!isOpen) setOpen(false);
  }, [isOpen]);
  return (
    <div>
      <div
        onClick={(e) => {
          e.preventDefault();
          setOpen(!open);
        }}
        className={`group menu-link flex items-center justify-between ${isOpen ? 'px-3' : 'px-1.5'}  py-2 hover:bg-secondary-200 rounded-lg ${
          open ? 'bg-secondary-200' : ''
        }`}
      >
        <span className="flex items-center gap-2">
          <span
            className={`dark-icon flex items-center justify-center w-5 h-5 transition-colors duration-300 ${open ? 'text-black' : 'text-dark-gray'}`}>
            {Icon ? <Icon /> : null}
          </span>
          {url ? (
            <Link
                key={label}
                href={route(url)}
                className={`${isOpen ? '' : 'hidden'} menu-label text-menu-heading`}
                >
               {label} 
            </Link>
          ):(
          <span className={`${isOpen ? '' : 'hidden'} menu-label text-menu-heading`}>{label}</span>
          )}
        </span>
        {submenuItems.length > 0 && (
        <span className={`${isOpen ? '' : 'hidden'} arrow transition-transform duration-300 ${open ? 'rotate-90' : ''}`}>
          <img src="/assets/icons/arrow-left.svg" alt="arrow" className="w-3 h-3" />
        </span>
        )}

      </div>

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
