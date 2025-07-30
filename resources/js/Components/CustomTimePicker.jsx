import React, { useState, useRef, useEffect } from 'react';
import WhiteButton from './WhiteButton'; // adjust path if needed
import { icons } from '@/icons';

const pad = (val) => val.toString().padStart(2, '0');

export default function CustomTimePicker({
  value,
  meridiem: externalMeridiem,
  width="w-[187px]" , height="h-[48px]", onChange, errors,
  onMeridiemChange,
  label = 'Select Time',
}) {
  const [isOpen, setIsOpen] = useState(false);
  const [hour, setHour] = useState('');
  const [minute, setMinute] = useState('');
  const [meridiem, setMeridiem] = useState('AM');
  const pickerRef = useRef();
  const [dropUp, setDropUp] = useState(false);

  useEffect(() => {
    if (isOpen && pickerRef.current) {
      const rect = pickerRef.current.getBoundingClientRect();
      const viewportHeight = window.innerHeight;
      const dropdownHeight = 250; // estimate your dropdown height

      const spaceBelow = viewportHeight - rect.bottom;
      setDropUp(spaceBelow < dropdownHeight);
    }
  }, [isOpen]);

  // Parse initial value
  useEffect(() => {
    if (value) {
      const [h, m] = value.split(':');
      let hourNum = parseInt(h, 10);
      const min = pad(m || '00');
      const mer = hourNum >= 12 ? 'PM' : 'AM';

      if (hourNum > 12) hourNum -= 12;
      if (hourNum === 0) hourNum = 12;

      setHour(pad(hourNum));
      setMinute(min);

      if (!externalMeridiem) {
        setMeridiem(mer);
      }
    }
  }, [value]);

  // Override meridiem from props if provided
  useEffect(() => {
    if (externalMeridiem && ['AM', 'PM'].includes(externalMeridiem)) {
      setMeridiem(externalMeridiem);
    }
  }, [externalMeridiem]);

  // Close picker on outside click
  useEffect(() => {
    const handleClickOutside = (e) => {
      if (pickerRef.current && !pickerRef.current.contains(e.target)) {
        setIsOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleTimeSelect = (h, m) => {
    const formattedHour =
      meridiem === 'PM'
        ? (parseInt(h) % 12) + 12
        : parseInt(h) % 12 || 12;

    const finalHour = pad(formattedHour === 24 ? 0 : formattedHour);
    const finalMinute = pad(m);
    onChange(`${finalHour}:${finalMinute}`);
    setHour(pad(h));
    setMinute(pad(m));
    setIsOpen(false);
  };

  const handleMeridiemClick = (newMeridiem) => {
    setMeridiem(newMeridiem);
    if (onMeridiemChange) {
      onMeridiemChange(newMeridiem);
    } else {
      // Re-fire current time with new meridiem
      handleTimeSelect(hour, minute);
    }
  };

  return (
    <div className="flex mb-4 items-end gap-2 relative" ref={pickerRef}>
      {/* Label + Time Button */}
      <div className="flex flex-col">
        {label && <label className="mt-1 text-[#161618] font-poppins text-[12px] font-normal  leading-[130%]">{label}</label>}
        <button
          type="button"
          className={`flex items-center justify-between px-4 py-4 mt-2 ${width} ${height} border  ${errors ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-[10px]
                hover:border-black  active:border-blue-600 
                focus:outline-none focus:ring-0 focus:border-black
                `}
          onClick={() => setIsOpen(!isOpen)}
        >
          {hour && minute ? `${hour}:${minute}` : 'Select'}
          {icons.cheveron_down}
        </button>
      </div>

      {/* AM/PM Toggle */}
    <div className="flex bg-other-gray-bg border border-secondary-100 rounded-[10px] p-1 gap-1">
        {['PM', 'AM'].map((mer) => (
        <button
            key={mer}
            onClick={() => handleMeridiemClick(mer)}
            className={`w-[42px] h-[41px] px-2 py-1 rounded-[10px] text-sm transition ${
            meridiem === mer ? 'bg-white shadow font-semibold' : 'bg-transparent'
            }`}
        >
            {mer}
        </button>
        ))}
      </div>

      {/* Dropdown */}
      {isOpen && (
        <div className={`absolute ${
              dropUp ? 'bottom-full mb-2' : 'top-full mt-2'
            } bg-white mt-2 my-4 left-0 ${width} border rounded-lg shadow p-2 z-50`}>
          <div className="flex items-center justify-between gap-2 ">
            <div className="max-h-40 overflow-y-auto w-[50%] text-center border-r pr-1 ">
              {[...Array(12)].map((_, i) => {
                const h = pad(i + 1);
                return (
                  <div
                    key={h}
                    className="cursor-pointer px-2 py-2 hover:bg-blue-100"
                    onClick={() => setHour(h)}
                  >
                    {h}
                  </div>
                );
              })}
            </div>
            <div className="max-h-40 overflow-y-auto w-14 text-center px-1">
              {[...Array(60)].map((_, i) => {
                const m = pad(i);
                return (
                  <div
                    key={m}
                    className="cursor-pointer px-2 py-2 hover:bg-blue-100"
                    onClick={() => {
                      setMinute(m);
                      handleTimeSelect(hour, m);
                    }}
                  >
                    {m}
                  </div>
                );
              })}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
