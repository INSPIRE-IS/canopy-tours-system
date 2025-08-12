import React from "react";

const NavSelect = ({ name, onChange }) => {
  return (
      <ul className="space-y-1">
        <li>
          <button
            onClick={onChange}
            className="w-full text-left px-3 py-2 hover:bg-gray-100 rounded text-sm"
          >
            {name}
          </button>
        </li>
      </ul>
  );
};
export default NavSelect;
