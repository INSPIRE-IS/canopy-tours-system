import React from "react";

const CustomTextarea1 = ({ label, name, value, onChange, error, placeholder,rows = 4 }) => {
  return (
    <div className="w-full mb-4">
      {label && (
        <label className="block text-main mb-[14px]">
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
        className={`w-full pl-[19px] pt-[11px] pb-[12px] bg-[#FBF9F7] border rounded-2xl focus:outline-none focus:ring-2 resize-none ${
          error
            ? "border-red-500 focus:ring-red-300 text-red-700"
            : "border-gray-300 focus:ring-blue-300 text-main2"
        }`}
      />
      {error && <p className="text-red-500 text-sm mt-1">{error}</p>}
    </div>
  );
};

export default CustomTextarea1;
