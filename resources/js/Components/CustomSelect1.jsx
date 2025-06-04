import React from "react";

const CustomSelect = ({ label, name, value, onChange, error,mode, options }) => {
  return (
    <div className="w-full mb-4">
      {label && (
        <label className="block input-header mb-[14px]">
          {label}
        </label>
      )}
      <select
        name={name}
        value={value != null ? String(value) : ""}
        onChange={onChange}
        className={`mt-1 block w-full ${error ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-xl`}
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
