import React, { useEffect, useMemo, useState } from "react";
import { router, useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomTextarea from "@/Components/CustomTextarea";
import CustomSelect from "@/Components/CustomSelect";

const normalizeTime = (time) => String(time || "").slice(0, 5);
const sortByStartTime = (slots = []) => [...slots].sort((a, b) => String(a.start_time).localeCompare(String(b.start_time)));

const CloseoutModal = ({ isOpen, onClose, mode, closeout = null, services, auth, team_id }) => {
    console.log('closeout ', closeout);
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

    const inventoryTimeSlots = useMemo(() => sortByStartTime(inventory?.inventory_time_slots || []), [inventory]);

    useEffect(() => {
        if (closeout && mode !== "create") {
            setData({
                service_id: closeout?.service_id || "",
                season_id: closeout?.season_id || "",
                name: closeout?.name || "",
                start_date: closeout?.start_date || "",
                end_date: closeout?.end_date || "",
                reason: closeout?.reason || "",

                team_id: team_id,
                updated_by_id: closeout.updated_by_id || auth?.user?.id,
            });
            // Pre-check previously saved day/slot pairs
            // if (closeout.closeout_day_time_slots) {
            //     const slots = closeout.closeout_day_time_slots.map(slot => ({
            //         day_id: slot.day_id,
            //         time_slot_id: slot.time_slot_id,
            //     }))
            //     console.log('slots ', slots);

            //     setSelectedSlots(slots);
            // }

        }
        if (mode === "create") {
            reset();
            setSelectedSlots([]); // clear slots on create

        }
    }, [mode, closeout, team_id]);

    useEffect(() => {
        if ((mode === "edit" || mode === "view") && closeout && inventory) {
            const currentSlotIds = new Set(inventoryTimeSlots.map(slot => Number(slot.id)));
            const currentSlotByTime = new Map(inventoryTimeSlots.map(slot => [normalizeTime(slot.start_time), slot]));
            const slots = closeout.closeout_day_time_slots.map(slot => {
                const currentSlot = currentSlotIds.has(Number(slot.time_slot_id))
                    ? { id: Number(slot.time_slot_id) }
                    : currentSlotByTime.get(normalizeTime(slot.time_slot?.start_time));

                if (!currentSlot) return null;

                return {
                    day_id: Number(slot.day_id),
                    time_slot_id: Number(currentSlot.id),
                };
            }).filter(Boolean);
            console.log('slots ', slots);

            setSelectedSlots(slots);
        }
    }, [mode, closeout, inventory, inventoryTimeSlots]);
    // When service changes, load seasons
    // useEffect(() => {
    //     setSeasons([]);
    //     const selectedService = services.find(s => s.id === parseInt(data.service_id));
    //     setSeasons(selectedService ? selectedService.seasons : []);
    // }, [data.service_id]);
    // inside useEffect when service changes
    useEffect(() => {
        if (!data.service_id) return;

        const selectedService = services.find(s => s.id === parseInt(data.service_id));
        setSeasons(selectedService ? selectedService.seasons : []);

        // Only reset on service change if we're in create mode
        if (mode === "create") {
            setData("season_id", "");
            setInventory(null);
            setSelectedSlots([]);
        }
    }, [data.service_id, mode, services]);

    // Toggle entire day column
    const toggleDayColumn = (dayId) => {
        const isAllClosed = inventoryTimeSlots.every(slot =>
            selectedSlots.some(s => s.day_id === dayId && s.time_slot_id === slot.id)
        );

        if (isAllClosed) {
            // Uncheck = remove all slots for this day
            setSelectedSlots(prev => prev.filter(s => s.day_id !== dayId));
        } else {
            // Check all slots for this day
            setSelectedSlots(prev => {
                const newSelections = [...prev];
                inventoryTimeSlots.forEach(slot => {
                    if (!newSelections.some(s => s.day_id === dayId && s.time_slot_id === slot.id)) {
                        newSelections.push({ day_id: dayId, time_slot_id: slot.id });
                    }
                });
                return newSelections;
            });
        }
    };

    const getWeekday = (dateStr) => {
        if (!dateStr) return "";
        const d = new Date(dateStr);
        return d.toLocaleDateString("en-US", { weekday: "long" }); // "Monday", "Tuesday", etc.
    };

    // When both service & season are selected, get inventory
    useEffect(() => {
        if (data.service_id && data.season_id) {
            const selectedService = services.find(s => s.id === parseInt(data.service_id));
            const season = selectedService?.seasons.find(sea => sea.id === parseInt(data.season_id));
            if (season?.inventory) {
                setInventory(season.inventory);
                console.log('inventory ', season.inventory);
                if (mode === "create") {
                    setSelectedSlots([]); // start EMPTY
                }
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
        // console.log('data ', data);
        // console.log('selectedSlots ', selectedSlots);
        // return;

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
                <div className="mb-4">
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
                        <div className="flex flex-col w-full min-h-[80px] items-center space-y-2">
                            <InputText
                                title="Start Date"
                                field="start_date"
                                value={data.start_date}
                                type="date"
                                mode={mode}
                                handleChange={handleChange}
                                errors={errors}
                            />
                            {data.start_date && (
                                <span className="text-sm text-gray-600">
                                    {getWeekday(data.start_date)}
                                </span>
                            )}
                        </div>
                        <div className="flex flex-col  w-full min-h-[80px] items-center space-y-2">
                            <InputText
                                title="End Date"
                                field="end_date"
                                value={data.end_date}
                                type="date"
                                mode={mode}
                                handleChange={handleChange}
                                errors={errors}
                            />
                            {data.end_date && (
                                <span className="text-sm text-gray-600">
                                    {getWeekday(data.end_date)}
                                </span>
                            )}
                        </div>


                    </div>

                    <p className="mb-2 text-sm text-gray-700 font-medium">
                        Select Service and Season then Tick the days/times you want to <span className="text-red-600 font-bold">CLOSE</span> during this period
                    </p>
                    <p className="mb-4 text-xs text-gray-600">
                        Note: Closeouts block availability for both admin and online calendars. If you want a slot that is visible to staff only, use a Date Override with "Internal Only".
                    </p>

                    {inventory && (
                        <div className="mb-4 overflow-x-auto">
                            <div className="flex justify-end gap-2 mb-2">
                                <OutlineButton
                                    title="Close All"
                                    type="button"
                                    onClick={() => {
                                        const allPairs = [];
                                        inventory.inventory_type_days.forEach(day => {
                                            inventoryTimeSlots.forEach(slot => {
                                                allPairs.push({ day_id: day.type_day_id, time_slot_id: slot.id });
                                            });
                                        });
                                        setSelectedSlots(allPairs);
                                    }}
                                    width="w-[120px]"
                                />
                                <OutlineButton
                                    title="Open All"
                                    type="button"
                                    onClick={() => setSelectedSlots([])}
                                    width="w-[120px]"
                                />
                            </div>

                            <table className="w-full border border-gray-300 text-sm">
                                <thead>
                                    <tr>
                                        <th className="border p-2 bg-gray-100">Time</th>
                                        {inventory.inventory_type_days.map(day => {
                                            const allClosed = inventoryTimeSlots.every(slot =>
                                                selectedSlots.some(s => s.day_id === day.type_day_id && s.time_slot_id === slot.id)
                                            );
                                            return (
                                                <th key={day.id} className="border p-2 bg-gray-100 text-center">
                                                    <div className="flex flex-col items-center">
                                                        <span className="text-sm text-black uppercase">{day.type_day.name || `Day ${day.type_day_id}`}</span>
                                                        <input
                                                            type="checkbox"
                                                            checked={allClosed}
                                                            onChange={() => toggleDayColumn(day.type_day_id)}
                                                        />
                                                    </div>
                                                </th>
                                            );
                                        })}
                                    </tr>
                                </thead>
                                <tbody>
                                    {inventoryTimeSlots.map(slot => (
                                        <tr key={slot.id}>
                                            <td className="border p-2">{slot.start_time.slice(0, 5)}</td>
                                            {inventory.inventory_type_days.map(day => {
                                                console.log('day ', day);
                                                console.log('selectedSlots ', selectedSlots);
                                                const checked = selectedSlots.some(
                                                    s => {
                                                        console.log('s ', s);
                                                        console.log('day.type_day.id ', day.type_day_id);
                                                        console.log('slot.id ', slot.id);
                                                        return s.day_id === day.type_day_id && s.time_slot_id === slot.id;
                                                    }
                                                );
                                                console.log('checked ', checked);
                                                return (
                                                    <td
                                                        key={`${day.type_day_id}-${slot.id}`}
                                                        className={`border p-2 text-center transition-colors ${checked ? "bg-red-100" : ""
                                                            }`}
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            checked={checked}
                                                            disabled={mode === "view"}
                                                            onChange={() => toggleSlot(day.type_day_id, slot.id)}
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
                        <OutlineButton title="Cancel" type="button" onClick={onClose} width="w-[180px]" />
                        <OutlineButton
                            title="Clear Form"
                            type="button"
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
                </div>
            </div>
        </div>
    );
};

export default CloseoutModal;
