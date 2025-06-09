import React from 'react';

const Input = ({ className = '', ...props }) => (
  <input
    className={`w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300 ${className}`}
    {...props}
  />
);

export default Input;
