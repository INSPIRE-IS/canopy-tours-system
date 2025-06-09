import React from "react";

const CustomSelect = ({ label, name, value, onChange, error,mode, options }) => {
  return (
    <div className="w-full mb-3">
      {label && (
            <label
                htmlFor={name}
                className="block input-header "
            >
                {label}
            </label>
      )}
      <select
        name={name}
        value={value != null ? String(value) : ""}
        onChange={onChange}
        className={`mt-1 block w-full ${error ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-xl
                        hover:border-black  active:border-blue-600 
                focus:outline-none focus:ring-0 focus:border-black
        `}
        disabled={mode === "view"}

      >
        {/* Render the options */}
        <option value="" disabled className="input-text">
          -- Select an option --
        </option>
        {options.map((option, index) => (
          <option key={index} value={option.id} className="input-text">
            {option.name}
          </option>
        ))}
      </select>
      {error && <p className="text-red-500 text-sm mt-1">{error}</p>}
    </div>
  );
};
export default CustomSelect;
