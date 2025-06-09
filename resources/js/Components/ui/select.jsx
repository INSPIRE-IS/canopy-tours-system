import React from 'react';

const Select = ({ options = [], className = '', ...props }) => (
  <select
    className={`w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300 ${className}`}
    {...props}
  >
    {options.map((option) => (
      <option key={option.value} value={option.value}>
        {option.label}
      </option>
    ))}
  </select>
);

export default Select;
