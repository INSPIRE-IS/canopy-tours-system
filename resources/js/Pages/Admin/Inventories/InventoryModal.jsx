import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomSelect from "@/Components/CustomSelect1";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";


const InventoryModal = ({ isOpen, onClose, mode, inventory = null,services, seasons, day__slots, auth, team }) => {
    // console.log("mode", mode);
     console.log("inventory", inventory);
     console.log('inventory.time_slots', inventory?.time_slots);
     const [newTimeSlot, setNewTimeSlot] = useState("");
     const [filteredSeasons, setFilteredSeasons] = useState([]);
    // console.log("roles", roles);
    const { data, setData, post, put, errors, reset, processing } = useForm({
        service_id: "",
        season_id: "",
        start_date: "",
        end_date: "",
        rake_rate_adult: "",
        rake_rate_child: "",
        rake_rate_guide: "",
        allow_internal_price_override: 0,
        day_slots: [1,2,3,4,5,6,7],
        time_slots: [],
        capacity: "",
        allow_internal_override: "",
        status: 0,
        created_by_id: auth?.user?.id,
        updated_by_id: auth?.user?.id,
    });
    useEffect(() => {
        if (inventory && mode !== "create") {
            setData({
                service_id: inventory.service_id || "",
                season_id: inventory.season_id || "",
                start_date: inventory.start_date || "",
                end_date: inventory.end_date || "",
                rake_rate_adult: inventory.rake_rate_adult || "",
                rake_rate_child: inventory.rake_rate_child || "",
                rake_rate_guide: inventory.rake_rate_guide || "",
                allow_internal_price_override: inventory.allow_internal_price_override || 0,
                day_slots: inventory.day_slots || [1,2,3,4,5,6,7],
                time_slots: inventory?.time_slots?.map(slot => slot.start_time) || [],
                capacity: inventory.capacity || "",
                allow_internal_override: inventory.allow_internal_override || "",
                status: inventory.status || 0,
                updated_by_id: inventory.updated_by_id || "",
                //roles: inventory.roles.map((role) => role.id),

            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode,inventory,team]);


    useEffect(() => {
        console.log('data.service_id', data.service_id);
        console.log('seasons', seasons);
        if (data.service_id) {
            const filtered = seasons.filter(
                (item) => item.service_id == data.service_id
            );
            console.log('filtered', filtered);
            setFilteredSeasons(filtered);
        } else {
            setFilteredSeasons([]);
        }
    }, [data.service_id, seasons]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };

        const handleAddIdentification = () => {
        if (newTimeSlot.trim() !== "") {
            setData("time_slots", [...data.time_slots, newTimeSlot.trim()]);
            setNewTimeSlot(""); // Reset input field
        }
    };

    const handleRemoveIdentification = (index) => {
        setData(
            "time_slots",
            data.time_slots.filter((_, i) => i !== index)
        );
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        console.log('data', data);
        if (mode === "create") {
            post(route("inventories.store"), {
                onSuccess: () => {
                    reset();
                    onClose();
                },
            });
        } else if (mode === "edit") {
            put(route("inventories.update", inventory.id), {
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
                    {mode === "create" && "Create Inventory"}
                    {mode === "edit" && "Edit Inventory"}
                    {mode === "view" && "View Inventory"}
                </h2>
                <form onSubmit={handleSubmit}>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                    <CustomSelect
                        label="services"
                        name="service_id"
                        value={data.service_id}
                        mode={mode}
                        onChange={(e) => handleChange(e.target.name, e.target.value)}
                        error={errors.service_id}
                        options={services}
                    /> 
                    <CustomSelect
                        label="Season/Name"
                        name="season_id"
                        value={data.season_id}
                        mode={mode}
                        onChange={(e) => handleChange(e.target.name, e.target.value)}
                        error={errors.season_id}
                        options={filteredSeasons}
                    />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Start Date"
                            field="start_date"
                            type='date'
                            value={data.start_date}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        
                        <InputText
                            title="End Date"
                            field="end_date"
                            type='date'
                            value={data.end_date}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />    
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Rake Rate Adult"
                            field="rake_rate_adult"
                            value={data.rake_rate_adult}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Rake Rate Child"
                            field="rake_rate_child"
                            value={data.rake_rate_child}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Rak Rate Guide"
                            field="rake_rate_guide"
                            value={data.rake_rate_guide}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Capacity (PAX)"
                            field="capacity"
                            value={data.capacity}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    {/* Identification Section */}
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <div className="mb-4 w-full">                        
                            <label className="block input-header">
                                Time Slot
                            </label>
                            <input
                                type="time"
                                className={`mt-1 block w-full ${errors.name ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-xl
                hover:border-black  active:border-blue-600 
                focus:outline-none focus:ring-0 focus:border-black
                `}
                                value={newTimeSlot ?? ""}
                                onChange={(e) => {e.preventDefault(); setNewTimeSlot(e.target.value)}}
                            />
                        </div>
                        <div className="mt-6 mb-4 w-full">
                            <BlueButton 
                            title="Add Time Slot" 
                            width="w-full" 
                            onClick={(e) => {e.preventDefault();handleAddIdentification()}} 
                            disabled={processing} 
                            type="button" 
                            />                            
                        </div>


                        </div>
                            <label className="inline-flex input-header">
                                Time Slots
                            </label>                        
<div className="flex flex-row w-full items-center justify-start mb-4">
                        {/* List of added time_slots */}

                        {data.time_slots.length > 0 ? (

                            <ul className="w-full mt-3 space-y-2">
                                {data.time_slots.map((item, index) => (
                                    <li
                                        key={index}
                                        className="flex items-center py-2 justify-between bg-gray-100 rounded"
                                    >
                                        <span className="block ml-4">{item}</span>
                                    <OutlineButton title="Remove" onClick={() => handleRemoveIdentification(index)} width="w-1/2"/>
                                    </li>
                                ))}
                            </ul>
                        ):(<span className="block text-red-400">No time slots added</span>)}
                    </div>
                    add status open for booking and close for booking
                    add condition for booking pill box text
                
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <CustomCheckbox
                            label="Allow Internal Price Override"
                            name="allow_internal_price_override"
                            checked={data.allow_internal_price_override}
                            mode={mode}
                            onChange={handleChange}
                            errors={errors}
                        />
                        <CustomCheckbox
                            label="Allow Internal Time Slot Override"
                            name="allow_internal_override"
                            checked={data.allow_internal_override}
                            mode={mode}
                            onChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex justify-center gap-4">
                        <OutlineButton title="Cancel" onClick={onClose} width="w-[180px]"/>
                        {mode !== "view" && (
                            <BlueButton title={mode === "create" ? "Create" : "Update"} width="w-[180px]" onClick={handleSubmit} disabled={processing} type="submit" />
                        )}
                    </div>
                </form>
            </div>
        </div>
    );
};

export default InventoryModal;
