import React from "react";

const CustomSelect = ({ label, name, value = null, width="w-full" , height="h-[48px]", onChange, error,mode, options }) => {
  //console.log('options ', options);
  return (
    <div className="w-full mb-3">
      {label && (
            <label
                htmlFor={name}
                className="text-[#161618] font-poppins text-[12px] font-normal  leading-[130%]"
            >
                {label}
            </label>
      )}
      <select
        name={name}
        value={value != null ? String(value) : ""}
        onChange={onChange}
        className={`mt-1 block ${width} ${height} ${error ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-xl
                        hover:border-black  active:border-blue-600 
                focus:outline-none focus:ring-0 focus:border-black
        `}
        disabled={mode === "view"}

      >
        {/* Render the options */}
        <option value="" disabled className="input-text">
          -- Select an option --
        </option>
        {options?.map((option, index) => {
          //console.log('option', option);
          const value = typeof option === "object" ? option.id : option;
          const label = typeof option === "object" ? option.name : option;
        return (
          <option key={index} value={value} className="input-text">
            {label}
          </option>
        )})}
      </select>
      {error && <p className="text-red-500 text-sm mt-1">{error}</p>}
    </div>
  );
};
export default CustomSelect;
