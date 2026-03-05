import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomSelect from "@/Components/CustomSelect";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomTextarea from "@/Components/CustomTextarea";

const InventorySlotRangeModal = ({ isOpen, onClose, mode, slotRange = null, services, auth }) => {
    const [seasons, setSeasons] = useState([]);
    const [inventory, setInventory] = useState(null);
    const [newTimeSlot, setNewTimeSlot] = useState("");
    const [includeDefaults, setIncludeDefaults] = useState(false);
    const [defaultSlots, setDefaultSlots] = useState([]);

    const { data, setData, post, put, errors, reset, processing } = useForm({
        service_id: "",
        season_id: "",
        start_date: "",
        end_date: "",
        time_slots: [],
        time_slot: "",
        internal_only: false,
        capacity_override: "",
        note: "",
        ids: [],
        created_by_id: auth?.user?.id,
    });
    const isGroupedRange = mode !== "create" && Array.isArray(slotRange?.time_slots);

    useEffect(() => {
        if (slotRange && mode !== "create") {
            const startTime = slotRange.time_slot?.start_time ? slotRange.time_slot.start_time.slice(0, 5) : "";
            setData({
                service_id: slotRange?.inventory?.service_id || "",
                season_id: slotRange?.inventory?.season_id || "",
                start_date: slotRange?.start_date || "",
                end_date: slotRange?.end_date || "",
                time_slots: isGroupedRange
                    ? (slotRange?.time_slots || []).map((slot) => slot.slice(0, 5))
                    : [],
                time_slot: startTime,
                internal_only: !!slotRange?.internal_only,
                capacity_override: slotRange?.capacity_override ?? "",
                note: slotRange?.note ?? "",
                ids: slotRange?.ids || [],
                updated_by_id: auth?.user?.id,
            });
            setNewTimeSlot("");
        }
        if (mode === "create") {
            reset();
            setNewTimeSlot("");
            setIncludeDefaults(false);
        }
    }, [mode, slotRange]);

    useEffect(() => {
        if (!data.service_id) {
            setSeasons([]);
            setInventory(null);
            return;
        }

        const selectedService = services.find((service) => service.id === parseInt(data.service_id));
        const nextSeasons = selectedService?.seasons || [];
        setSeasons(nextSeasons);

        if (mode === "create") {
            setData("season_id", "");
            setInventory(null);
        }
    }, [data.service_id, services, mode]);

    useEffect(() => {
        if (!data.service_id || !data.season_id) {
            setInventory(null);
            return;
        }
        const selectedService = services.find((service) => service.id === parseInt(data.service_id));
        const season = selectedService?.seasons?.find((item) => item.id === parseInt(data.season_id));
        setInventory(season?.inventory || null);
    }, [data.service_id, data.season_id, services]);

    useEffect(() => {
        if (mode === "create") {
            setData("time_slots", []);
            setNewTimeSlot("");
            setIncludeDefaults(false);
            setDefaultSlots([]);
        }
    }, [data.season_id, mode]);

    const getDefaultSlots = () => {
        if (!inventory?.inventory_time_slots) {
            return [];
        }
        return inventory.inventory_time_slots
            .filter((slot) => !slot.is_override_only)
            .map((slot) => slot.start_time.slice(0, 5));
    };

    useEffect(() => {
        setDefaultSlots(getDefaultSlots());
    }, [inventory]);

    useEffect(() => {
        if (mode !== "create") {
            return;
        }
        if (!includeDefaults) {
            return;
        }
        if (defaultSlots.length === 0) {
            return;
        }
        const hasDefault = (data.time_slots || []).some((slot) => defaultSlots.includes(slot));
        if (!hasDefault) {
            setIncludeDefaults(false);
        }
    }, [data.time_slots, defaultSlots, includeDefaults, mode]);

    const handleAddTimeSlot = () => {
        if (!newTimeSlot.trim()) {
            return;
        }
        const next = newTimeSlot.trim();
        if (data.time_slots.includes(next)) {
            setNewTimeSlot("");
            return;
        }
        setData("time_slots", [...data.time_slots, next]);
        setNewTimeSlot("");
    };

    const handleRemoveTimeSlot = (index) => {
        setData("time_slots", data.time_slots.filter((_, i) => i !== index));
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        if (mode === "create") {
            post(route("admin.inventory-slot-ranges.store"), {
                onSuccess: () => {
                    reset();
                    onClose();
                },
            });
        } else if (mode === "edit") {
            put(route("admin.inventory-slot-ranges.update", slotRange.id), {
                onSuccess: () => {
                    reset();
                    onClose();
                },
            });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Date Override"}
                    {mode === "edit" && "Edit Date Override"}
                    {mode === "view" && "View Date Override"}
                </h2>

                <p className="text-xs text-gray-500 mb-4">
                    Date overrides replace the weekly schedule for the selected dates. You can add custom times that do not
                    exist in the weekly inventory.
                </p>

                <form onSubmit={handleSubmit}>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <CustomSelect
                            label="Service"
                            name="service_id"
                            value={data.service_id}
                            mode={mode}
                            onChange={(e) => setData(e.target.name, e.target.value)}
                            errors={errors}
                            options={services}
                        />
                        <CustomSelect
                            label="Season"
                            name="season_id"
                            value={data.season_id}
                            mode={mode}
                            onChange={(e) => setData(e.target.name, e.target.value)}
                            errors={errors}
                            options={seasons}
                        />
                    </div>

                    {!inventory && data.season_id && (
                        <p className="text-xs text-red-500 mb-4">Selected season has no inventory.</p>
                    )}

                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Start Date"
                            field="start_date"
                            value={data.start_date}
                            type="date"
                            mode={mode}
                            handleChange={(field, value) => setData(field, value)}
                            errors={errors}
                        />
                        <InputText
                            title="End Date"
                            field="end_date"
                            value={data.end_date}
                            type="date"
                            mode={mode}
                            handleChange={(field, value) => setData(field, value)}
                            errors={errors}
                        />
                    </div>

                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <div className="mb-4 w-full">
                            <label className="block input-header">Capacity Override (optional)</label>
                            <input
                                type="number"
                                min="0"
                                className="mt-1 block w-full border-gray-300 shadow-sm input-text rounded-xl hover:border-black focus:outline-none focus:ring-0 focus:border-black"
                                value={data.capacity_override ?? ""}
                                onChange={(e) => setData("capacity_override", e.target.value)}
                                disabled={mode === "view"}
                            />
                            {errors.capacity_override && <span className="text-xs text-red-500">{errors.capacity_override}</span>}
                        </div>
                        <div className="w-full">
                            <CustomTextarea
                                label="Note (optional)"
                                name="note"
                                mode={mode}
                                value={data.note ?? ""}
                                onChange={(e) => setData("note", e.target.value)}
                                error={errors.note}
                                rows={2}
                            />
                        </div>
                    </div>

                    <CustomCheckbox
                        label="Internal Only (hide from guest calendar)"
                        name="internal_only"
                        checked={data.internal_only}
                        mode={mode}
                        onChange={setData}
                        errors={errors}
                    />

                    {(mode === "create" || isGroupedRange) && (
                        <>
                            <CustomCheckbox
                                label="Include default weekly slots"
                                helper="Adds all weekly time slots from the selected inventory so you can remove any you don't want."
                                name="include_defaults"
                                checked={includeDefaults}
                                mode={mode}
                                onChange={(_, checked) => {
                                    setIncludeDefaults(checked);
                                    if (!checked) {
                                        if (defaultSlots.length === 0) {
                                            return;
                                        }
                                        const remaining = (data.time_slots || []).filter(
                                            (slot) => !defaultSlots.includes(slot)
                                        );
                                        setData("time_slots", remaining);
                                        return;
                                    }
                                    if (defaultSlots.length === 0) {
                                        return;
                                    }
                                    const merged = Array.from(new Set([...(data.time_slots || []), ...defaultSlots]));
                                    setData("time_slots", merged);
                                }}
                                errors={errors}
                            />
                            <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                                <div className="mb-4 w-full">
                                    <label className="block input-header">Time Slot</label>
                                    <input
                                        type="time"
                                        className="mt-1 block w-full border-gray-300 shadow-sm input-text rounded-xl hover:border-black focus:outline-none focus:ring-0 focus:border-black"
                                        value={newTimeSlot}
                                        onChange={(e) => setNewTimeSlot(e.target.value)}
                                        disabled={mode === "view"}
                                    />
                                </div>
                                <div className="mt-6 mb-4 w-full">
                                    <BlueButton
                                        title="Add Time Slot"
                                        width="w-full"
                                        onClick={(e) => {
                                            e.preventDefault();
                                            handleAddTimeSlot();
                                        }}
                                        disabled={processing || mode === "view"}
                                        type="button"
                                    />
                                </div>
                            </div>

                            <label className="inline-flex input-header">Time Slots</label>
                            <div className="flex flex-row w-full items-center justify-start mb-4">
                                {data.time_slots.length > 0 ? (
                                    <ul className="w-full mt-3 space-y-2">
                                        {data.time_slots.map((item, index) => (
                                            <li
                                                key={`${item}-${index}`}
                                                className="flex items-center py-2 justify-between bg-gray-100 rounded"
                                            >
                                                <span className="block ml-4">{item}</span>
                                                {mode !== "view" && (
                                                    <OutlineButton title="Remove" onClick={() => handleRemoveTimeSlot(index)} width="w-1/2" />
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <span className="block text-red-400">No time slots added</span>
                                )}
                            </div>
                            {errors.time_slots && <span className="text-xs text-red-500">{errors.time_slots}</span>}
                        </>
                    )}

                    {mode !== "create" && !isGroupedRange && (
                        <div className="mb-4">
                            <label className="block input-header">Time Slot</label>
                            <input
                                type="time"
                                className="mt-1 block w-full border-gray-300 shadow-sm input-text rounded-xl hover:border-black focus:outline-none focus:ring-0 focus:border-black"
                                value={data.time_slot ?? ""}
                                onChange={(e) => setData("time_slot", e.target.value)}
                                disabled={mode === "view"}
                            />
                            {errors.time_slot && <span className="text-xs text-red-500">{errors.time_slot}</span>}
                        </div>
                    )}

                    <div className="flex justify-center gap-4">
                        <OutlineButton title="Cancel" type="button" onClick={onClose} width="w-[180px]" />
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

export default InventorySlotRangeModal;
