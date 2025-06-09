import React from 'react';

const Calendar = ({ selected, onSelect }) => (
  <input
    type="date"
    value={selected.toISOString().split('T')[0]}
    onChange={(e) => onSelect(new Date(e.target.value))}
    className="w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300"
  />
);

export default Calendar;
