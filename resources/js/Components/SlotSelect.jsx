import React, { useState, useRef, useEffect } from "react";

export default function SlotSelect({ availableTimes, onSelect }) {
    const [isOpen, setIsOpen] = useState(false);
    const [selected, setSelected] = useState(null);
    const dropdownRef = useRef();

    // close dropdown when clicking outside
    useEffect(() => {
        function handleClickOutside(event) {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        }
        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    const handleSelect = (item) => {
        const dayKey = Object.keys(item.days)[0];
        const dayInfo = item.days[dayKey];
        setSelected({
            label: `${item.time.slice(0, 5)} — ${dayKey}`,
            time: item.time,
            slot_id: item.slot_id,
            date: dayInfo.date,
            promo: dayInfo?.promo || null,
        });
        onSelect?.(item); // send back to parent
        setIsOpen(false);
    };

    return (
        <div ref={dropdownRef} className="relative w-full">
            {/* Header / Toggle */}
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className="w-full border border-gray-300 rounded-[10px] bg-white px-4 py-2 text-left shadow-sm hover:border-black focus:border-black focus:outline-none"
            >
                {selected ? (
                    <span className="font-medium">{selected.label}</span>
                ) : (
                    <span className="text-gray-400">Select a time slot</span>
                )}
                <span className="float-right">{isOpen ? "▲" : "▼"}</span>
            </button>

            {/* Dropdown options */}
            {isOpen && (
                <ul className="absolute z-20 mt-1 w-full max-h-60 overflow-y-auto rounded-[10px] border border-gray-200 bg-white shadow-lg">
                    {availableTimes.map((item) => {
                        const dayKey = Object.keys(item.days)[0];
                        const dayInfo = item.days[dayKey];
                        const color = dayInfo?.promo ? "bg-[#55FF9E]" : (dayInfo.status === "available" ? "bg-[#D6FBE6]" : "bg-[#FCE4DF] cursor-not-allowed");
                        const cost = dayInfo?.promo
                            ? dayInfo.rake_rate_adult -
                            (dayInfo.rake_rate_adult * dayInfo.promo.value) / 100
                            : dayInfo.rake_rate_adult;

                        return (
                            <li
                                key={item.slot_id}
                                onClick={() =>
                                    dayInfo.status === "available" ? handleSelect(item) : null
                                }
                                className={`px-4 py-2 cursor-pointer ${color} ${dayInfo.status === "available"
                                    ? "hover:bg-green-100"
                                    : "opacity-60 cursor-not-allowed"
                                    } border-b-2 border-gray-200`}
                            >
                                <div className="flex flex-col">
                                    <span className="relative font-medium">
                                        {item.time.slice(0, 5)}
                                        {dayInfo?.promo && dayInfo.promo.value > 0 ? (<span className="absolute top-1 right-1 bg-red-500 text-white  text-[10px] px-2 py-[2px] rounded-full shadow z-10">({dayInfo.promo.value}% off)</span>) : null}
                                    </span>
                                    <span className="text-sm text-gray-600">
                                        {dayInfo?.promo && dayInfo.promo.value > 0 ? (
                                            <div className="flex flex-col gap-2">
                                                <span className="line-through text-gray-500 mr-1">
                                                    {dayInfo.slots} Slots • R{dayInfo.rake_rate_adult} / Adult
                                                </span>
                                                <span className="font-bold text-green-700">
                                                    {dayInfo.slots} Slots • R{cost} / Adult
                                                </span>
                                            </div>
                                        ) : (
                                            <span>{dayInfo.slots} Slots • R{dayInfo.rake_rate_adult} / Adult</span>
                                        )}
                                    </span>

                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
    );
}
