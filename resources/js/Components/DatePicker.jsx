import { useEffect, useRef, useState } from "react";

const DatePicker = ({ label = "Select Date", name, onChange, errors }) => {
  const calendarRef = useRef(null);
  const buttonRef = useRef(null);
  const [showCalendar, setShowCalendar] = useState(false);
  const [currentDate, setCurrentDate] = useState(new Date());
  const [selectedDate, setSelectedDate] = useState(null);

  // Close on outside click
  useEffect(() => {
    const handleClickOutside = (e) => {
      if (
        calendarRef.current &&
        !calendarRef.current.contains(e.target) &&
        !buttonRef.current.contains(e.target)
      ) {
        setShowCalendar(false);
      }
    };
    if (showCalendar) {
      document.addEventListener("mousedown", handleClickOutside);
    }
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [showCalendar]);

  const toggleCalendar = () => {
    setShowCalendar(!showCalendar);
  };

  const handleDateSelect = (date) => {
    setSelectedDate(date);
    setShowCalendar(false);
    if (onChange) onChange(date);
  };

  const renderCalendar = () => {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const firstDay = new Date(year, month, 1).getDay();

    const daysOfWeek = ["S", "M", "T", "W", "T", "F", "S"];
    const dates = [];

    for (let i = 0; i < firstDay; i++) {
      dates.push(null);
    }

    for (let day = 1; day <= daysInMonth; day++) {
      dates.push(new Date(year, month, day));
    }

    return (
      <div
        ref={calendarRef}
        className={`absolute top-full left-0 z-50 mt-2 w-full bg-white border-[1px] rounded-xl shadow-lg p-4 ${
          showCalendar ? "" : "hidden"
        }`}
      >
        {/* Header */}
        <div className="flex justify-between items-center mb-3 space-x-2">
          <select
            value={month}
            onChange={(e) =>
              setCurrentDate(
                new Date(year, parseInt(e.target.value), currentDate.getDate())
              )
            }
            className="w-full p-1 border rounded"
          >
            {Array.from({ length: 12 }).map((_, idx) => (
              <option key={idx} value={idx}>
                {new Date(0, idx).toLocaleString("default", { month: "short" })}
              </option>
            ))}
          </select>

          <select
            value={year}
            onChange={(e) =>
              setCurrentDate(
                new Date(parseInt(e.target.value), month, currentDate.getDate())
              )
            }
            className="w-full p-1 border rounded"
          >
            {Array.from({ length: 111 }, (_, i) => year - 100 + i).map((y) => (
              <option key={y} value={y}>
                {y}
              </option>
            ))}
          </select>
        </div>

        {/* Days of Week */}
        <div className="grid grid-cols-7 gap-1 text-calandar-black text-center">
          {daysOfWeek.map((day) => (
            <div key={day} className="text-calandar-black">
              {day}
            </div>
          ))}

          {/* Dates */}
          {dates.map((date, idx) => {
            if (!date) return <div key={idx}></div>;

            const isToday =
              date.toDateString() === new Date().toDateString();
            const isSelected =
              selectedDate && date.toDateString() === selectedDate.toDateString();

            return (
              <button
                key={idx}
                onClick={() => handleDateSelect(date)}
                className={`w-10 h-10 rounded-full transition ${
                  isSelected
                    ? "bg-primary-4 text-calandar-black"
                    : isToday
                    ? "border-2 text-calandar-brown border-primary-4"
                    : "hover:bg-gray-200"
                }`}
              >
                {date.getDate()}
              </button>
            );
          })}
        </div>
      </div>
    );
  };

  return (
    <div className="relative z-10 w-full">
      {label && <label className="block text-select-header mb-1.5">{label}</label>}
      <button
        type="button"
        ref={buttonRef}
        onClick={toggleCalendar}
        className="flex justify-between items-center w-full h-[40px] px-4 py-3 bg-white border border-gray-300 rounded-xl shadow-sm text-left
                        hover:border-black  active:border-blue-600 
                focus:outline-none focus:ring-0 focus:border-black
        "
      >
        <span className="text-date-picker">
          {selectedDate ? selectedDate.toLocaleDateString() : `Select an ${label.toLowerCase()}`}
        </span>
        <span className="ml-auto pl-2">📅</span>
      </button>

      {renderCalendar()}
      {errors && <p className="text-red-500 text-sm mt-1">{errors[name]}</p>}
    </div>
  );
};

export default DatePicker;
