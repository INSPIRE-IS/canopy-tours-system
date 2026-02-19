import React from "react";

const CustomCodeSelect = ({ label, name, value = null, width = "w-full", height = "h-[48px]", onChange, showOption = true, optionText = " -- Select an option --", errors = {}, mode, options }) => {
  //console.log('options ', options);
  return (
    <div className={`mb-4 w-[30%]`}>
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
        className={`mt-1 block ${width} ${height} ${errors?.[name] ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-[10px]
                        hover:border-black  active:border-blue-600 
                focus:outline-none focus:ring-0 focus:border-black
        `}
        disabled={mode === "view"}
      >
        {/* Render the options */}
        {showOption && (
          <option value="" disabled className="input-text">
            {optionText}
          </option>)}
        {options?.map((option, index) => {
          //console.log('option', option);
          const value = typeof option === "object" ? option.id : option;
          const label = typeof option === "object" ? option.name : option;
          return (
            <option key={index} value={value} className={`input-text ${width}`}>
              {label}
            </option>
          )
        })}
      </select>
      {errors?.[name] && (
        <p className="text-red-500 text-sm mt-2">
          {errors[name]}
        </p>
      )}
    </div>
  );
};
export default CustomCodeSelect;
