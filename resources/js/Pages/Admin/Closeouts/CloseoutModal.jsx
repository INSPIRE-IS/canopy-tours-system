import React, { useEffect, useState } from "react";
import { router, useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomTextarea from "@/Components/CustomTextarea";
import CustomSelect from "@/Components/CustomSelect";

const CloseoutModal = ({ isOpen, onClose, mode, closeout = null, services, auth, team_id }) => {
    const [seasons, setSeasons] = useState([]);
    const [inventory, setInventory] = useState(null); // NEW
    const [selectedSlots, setSelectedSlots] = useState([]); // NEW

    const { data, setData, post, put, errors, reset, processing } = useForm({
        name: "",
        start_date: "",
        end_date: "",
        reason: "",
        service_id: "",
        season_id: "",
        day_id: [],
        time_slot_id: [],
        team_id: team_id,
        created_by_id: auth?.user?.id,
    });

    useEffect(() => {
        if (closeout && mode !== "create") {
            setData({
                name: closeout?.name || "",
                start_date: closeout?.start_date || "",
                end_date: closeout?.end_date || "",
                reason: closeout?.reason || "",
                service_id: closeout?.service_id || "",
                season_id: closeout?.season_id || "",
                team_id: team_id,
                updated_by_id: closeout.updated_by_id || auth?.user?.id,
            });
        } else if (mode === "create") {
            reset();
            setSelectedSlots([]); // clear slots on create
        }
    }, [mode, closeout, team_id]);

    // When service changes, load seasons
    // useEffect(() => {
    //     setSeasons([]);
    //     const selectedService = services.find(s => s.id === parseInt(data.service_id));
    //     setSeasons(selectedService ? selectedService.seasons : []);
    // }, [data.service_id]);
    // inside useEffect when service changes
    useEffect(() => {
        const selectedService = services.find(s => s.id === parseInt(data.service_id));
        setSeasons(selectedService ? selectedService.seasons : []);
        // Reset season & inventory when service changes
        setData("season_id", "");
        setInventory(null);
        setSelectedSlots([]);
    }, [data.service_id]);

    // Toggle entire day column
    const toggleDayColumn = (dayId) => {
        console.log('-------------------dayId---------------- ', dayId);
        const isAllSelected = inventory.inventory_time_slots.every(slot =>
            selectedSlots.some(s => s.day_id === dayId && s.time_slot_id === slot.id)
        );
        console.log('isAllSelected ', isAllSelected);
        if (isAllSelected) {
            // Remove all slots for this day
            setSelectedSlots(prev => prev.filter(s => s.day_id !== dayId));
        } else {
            // Add all missing slots for this day
            setSelectedSlots(prev => {
                const newSelections = [...prev];
                inventory.inventory_time_slots.forEach(slot => {
                    if (!newSelections.some(s => s.day_id === dayId && s.time_slot_id === slot.id)) {
                        newSelections.push({ day_id: dayId, time_slot_id: slot.id });
                    }
                });
                return newSelections;
            });
        }
        console.log('selectedSlots ', selectedSlots);
    };



    // When both service & season are selected, get inventory
    useEffect(() => {
        if (data.service_id && data.season_id) {
            const selectedService = services.find(s => s.id === parseInt(data.service_id));
            const season = selectedService?.seasons.find(sea => sea.id === parseInt(data.season_id));
            if (season?.inventory) {
                setInventory(season.inventory);
                console.log('inventory ', season.inventory);

                // Default: all slots selected
                const allPairs = [];
                season.inventory.inventory_type_days.forEach(day => {
                    console.log('day ', day);
                    const day_id = day.id;
                    const day_name =  day.type_day.name;

                    season.inventory.inventory_time_slots.forEach(slot => {
                        console.log('slot ', slot);
                        allPairs.push({ day_id: day_id,day_name: day_name, time_slot_id: slot.id });
                    });
                });
                console.log('allPairs ', allPairs);
                setSelectedSlots(allPairs);
            } else {
                setInventory(null);
                setSelectedSlots([]);
            }
        }
    }, [data.service_id, data.season_id, services]);

    const toggleSlot = (dayId, timeSlotId) => {
        const exists = selectedSlots.some(s => s.day_id === dayId && s.time_slot_id === timeSlotId);
        if (exists) {
            setSelectedSlots(prev =>
                prev.filter(s => !(s.day_id === dayId && s.time_slot_id === timeSlotId))
            );
        } else {
            setSelectedSlots(prev => [...prev, { day_id: dayId, time_slot_id: timeSlotId }]);
        }
    };

    const handleChange = (target, value) => {
        setData(target, value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        // Pass selectedSlots to backend
        setData("day_id", selectedSlots.map(s => s.day_id));
        setData("time_slot_id", selectedSlots.map(s => s.time_slot_id));

        data.day_id = selectedSlots.map(s => s.day_id);
        data.time_slot_id = selectedSlots.map(s => s.time_slot_id);

        if (mode === "create") {
            post(route("admin.closeouts.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("admin.closeouts.update", closeout.id), {
                onSuccess: () => onClose(),
            });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Closeout"}
                    {mode === "edit" && "Edit Closeout"}
                    {mode === "view" && "View Closeout"}
                </h2>
                <form onSubmit={handleSubmit}>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <CustomSelect
                            label="Service"
                            name="service_id"
                            value={data.service_id}
                            mode={mode}
                            onChange={(e) => setData(e.target.name, e.target.value)}
                            error={errors.service_id}
                            options={services}
                        />
                        <CustomSelect
                            label="Season"
                            name="season_id"
                            value={data.season_id}
                            mode={mode}
                            onChange={(e) => setData(e.target.name, e.target.value)}
                            error={errors.season_id}
                            options={seasons}
                        />
                    </div>

                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Name"
                            field="name"
                            value={data.name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Reason"
                            field="reason"
                            value={data.reason}
                            type="text"
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />                        
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Start Date"
                            field="start_date"
                            value={data.start_date}
                            type="date"
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="End Date"
                            field="end_date"
                            value={data.end_date}
                            type="date"
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />

                    </div>

                    {/* NEW: Day × Time selection */}
                    {inventory && (
                        <div className="mb-4 overflow-x-auto">
                            <table className="w-full border border-gray-300 text-sm">
                                <thead>
                                    <tr>
                                        <th className="border p-2 bg-gray-100">Time</th>
                                        {inventory.inventory_type_days.map(day => {
                                            console.log('---------this day ------', day);
                                            const allSelected = inventory.inventory_time_slots.every(slot =>
                                                selectedSlots.some(s => s.day_id === day.id && s.time_slot_id === slot.id)
                                            );
                                            return (
                                                <th key={day.id} className="border p-2 bg-gray-100 text-center">
                                                    <div className="flex flex-col items-center">
                                                        <span className="text-md font-inter uppercase">{day.type_day.name || `Day ${day.id}`}</span>
                                                        <input
                                                            type="checkbox"
                                                            checked={allSelected}
                                                            onChange={() => toggleDayColumn(day.id)}
                                                        />
                                                    </div>
                                                </th>
                                            );
                                        })}
                                    </tr>
                                </thead>
                                <tbody>
                                    {inventory.inventory_time_slots.map(slot => (
                                        <tr key={slot.id}>
                                            <td className="border p-2">{slot.start_time.slice(0, 5)}</td>
                                            {inventory.inventory_type_days.map(day => {
                                                const checked = selectedSlots.some(
                                                    s => s.day_id === day.id && s.time_slot_id === slot.id
                                                );
                                                return (
                                                    <td key={`${day.id}-${slot.id}`} className="border p-2 text-center">
                                                        <input
                                                            type="checkbox"
                                                            checked={checked}
                                                            onChange={() => toggleSlot(day.id, slot.id)}
                                                        />
                                                    </td>
                                                );
                                            })}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <div className="flex justify-center gap-4">
                        <OutlineButton title="Cancel" onClick={onClose} width="w-[180px]" />
                        <OutlineButton
                            title="Clear Form"
                            onClick={() => {
                                reset();
                                setSelectedSlots([]);
                                setInventory(null);
                                setSeasons([]);
                            }}
                            width="w-[180px]"
                        />
                        {mode !== "view" && (
                            <BlueButton
                                title={mode === "create" ? "Create" : "Update"}
                                width="w-[180px]"
                                onClick={handleSubmit}
                                disabled={processing}
                                type="submit"
                            />
                        )}
                    </div>
                </form>
            </div>
        </div>
    );
};

export default CloseoutModal;
