import React, { useState, useRef, useEffect } from "react";


{/* <CustomDropdown
      label="Service"
      name="service_id"
      value={data.service_id}
      onChange={handleChange}
      options={["Option 1", "Option 2", "Option 3"]}
      error={errors.service_id}
    /> */}

const CustomDropdown = ({ label, name, value, onChange, error, mode, options = [] }) => {
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef(null);

  const toggleDropdown = () => setIsOpen(prev => !prev);

  const handleSelect = (option) => {
    onChange(option);
    setIsOpen(false);
  };

  const handleClickOutside = (event) => {
    if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
      setIsOpen(false);
    }
  };

  useEffect(() => {
    window.addEventListener("click", handleClickOutside);
    return () => {
      window.removeEventListener("click", handleClickOutside);
    };
  }, []);

  return (
    <div className="relative w-full" ref={dropdownRef}>
      {label && (
        <label className="block text-select-header mb-1.5">
          {label}
        </label>
      )}
      <div className="appearance-none border border-secondary-100 rounded-[10px] text-select-option">
        <button
          type="button"
          onClick={toggleDropdown}
          className={`group inline-flex w-full h-[40px] items-center justify-between gap-2 px-4 py-2 rounded-xl border-[3px] border-secondary-200 bg-white transition-all 
            text-secondary-200 hover:border-dark-gray hover:text-black cursor-pointer`}
        >
          <span className="text-select-filter group-hover:text-black group-active:text-[#0E0E0E] transition-colors">
            {value || "Choose option"}
          </span>
          <svg
            className={`transition-transform duration-300 ${isOpen ? "rotate-90" : ""}`}
            xmlns="http://www.w3.org/2000/svg"
            width="14" height="14" viewBox="0 0 14 14" fill="none"
          >
            <path
              d="M5.22082 2.38012L9.02416 6.18345C9.47333 6.63262 9.47333 7.36762 9.02416 7.81678L5.22083 11.6201"
              stroke="currentColor"
              strokeWidth="1.5"
              strokeMiterlimit="10"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          </svg>
        </button>

        {isOpen && (
          <ul className="absolute w-full bg-white border mt-1 rounded-md shadow z-20 text-sm max-h-60 overflow-y-auto">
            {options.map((option, index) => (
              <li
                key={index}
                onClick={() => handleSelect(option)}
                className="flex items-center justify-between border-b border-gray-200 px-4 py-2 hover:bg-primary-7 cursor-pointer"
              >
                <span className="flex items-center gap-2 text-dropdown-option">{option}</span>
                {option === value && (
                  <span className="text-[#AF9494]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="4" height="7" viewBox="0 0 4 7" fill="currentColor">
                      <path d="M3.35381 3.3269L0.526913 6.15381L0 5.6269L2.3 3.3269L0 1.0269L0.526912 0.499983L3.35381 3.3269Z" fill="currentColor" />
                    </svg>
                  </span>
                )}
              </li>
            ))}
          </ul>
        )}
      </div>
      {error && <p className="text-red-500 text-sm mt-1">{error}</p>}
    </div>
  );
};

export default CustomDropdown;
