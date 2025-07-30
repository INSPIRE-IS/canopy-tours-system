import React from "react";

const CustomTextarea = ({ label, name, value, onChange, error, placeholder,rows = 4 }) => {
  return (
    <div className="w-full mb-4">
      {label && (
        <label className="text-[#161618] font-poppins text-[12px] font-normal  leading-[130%]">
          {label}
        </label>
      )}
      <textarea
        id={name}
        name={name}
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        rows={rows}
        className={`w-full bg-[#FFF] border rounded-[10px] focus:outline-none focus:ring-2 resize-none ${
          error
            ? "border-red-500 focus:ring-red-300 text-red-700"
            : "border-gray-300 focus:ring-blue-300 text-main2"
        }`}
      />
      {error && <p className="text-red-500 text-sm mt-1">{error}</p>}
    </div>
  );
};

export default CustomTextarea;
