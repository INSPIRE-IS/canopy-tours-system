import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import InputRichText from "@/Components/InputRichText";

const SeasonModal = ({ isOpen, onClose, mode, season = null, services, auth, team}) => {
    // console.log("mode", mode);
    console.log("season", season);
    console.log("auth", auth);
    console.log("services", services);

    const { data, setData, post, put, errors, reset, processing } = useForm({
        service_id: "",
        season_id: "",
        name: "",
        start_date: "",
        end_date: "",
        rake_rate_adult: "",
        rake_rate_child: "",
        rake_rate_guide: "",
        allow_internal_price_override: 0,
        allow_internal_override: 0,
        duration: "",
        capacity: "",
        status: 0,
        pillbox_conditions: "",
        created_by_id: auth?.user?.id,
        team_id: auth?.user?.team_id,
    });

    useEffect(() => {
        if (season && mode !== "create") {
            setData({
                service_id: season.service_id || "",
                name: season.name || "",
                start_date: season.start_date || "",
                end_date: season.end_date || "",
                rake_rate_adult: season.rake_rate_adult || "",
                rake_rate_child: season.rake_rate_child || "",
                rake_rate_guide: season.rake_rate_guide || "",
                allow_internal_price_override: season.allow_internal_price_override || 0,
                allow_internal_override: season.allow_internal_override || 0,
                duration: season.duration || "",
                capacity: season.capacity || "",
                status: season.status || 0,
                pillbox_conditions: season.pillbox_conditions || "",
                updated_by_id: season.updated_by_id || "",
                updated_by_id: auth?.user?.id,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode,season,team]);
const handleFieldChange = (target, value) => {
    console.log('target ', target);
    console.log('value ', value);
setData(prevData => ({
  ...prevData,
  fields: {
    ...prevData.fields,
    [`${target}`]: value,
  },
}));
  console.log('data --------------------------', data);
};
    const handleChange = (target, value) => {
        console.log('target ', target);
        console.log('value ', value);
        setData(target, value);
        //setData("name", e.target.value);
    };
// const handleSubmit = (e) => {
//     e.preventDefault();
//     console.log('data ', data);

//     const formData = new FormData();
//     formData.append('service_id', data.service_id);
//     formData.append('name', data.name);
//     formData.append('created_by_id', data.created_by_id);
//     formData.append('updated_by_id', data.updated_by_id);

//     // Dynamic fields
//     Object.entries(data.fields).forEach(([key, value]) => {
//         formData.append(`fields[${key}]`, value);
//     });

//     // File (if present)
//     if (file) {
//         formData.append('file', file);
//     }
// for (let pair of formData.entries()) {
//     console.log(pair[0] + ':', pair[1]);
// }
// console.log('mode', mode);

//     if(mode === "create") {

//         axios.post(route("admin.seasons.store"), formData, {
//             headers: {
//                 "Content-Type": "multipart/form-data",
//             },
//         }).then(() => onClose());
//     }else{
//         formData.append('_method', 'PUT');
//         axios.post(route('admin.seasons.update', season.id), formData, {
//             headers: {
//                 'Content-Type': 'multipart/form-data',
//             },
//         }).then(() => onClose());

//         // router.post(route('services.update', service.id), formData, {
//         // method: 'put',
//         // preserveScroll: true,
//         // })
//     }
// };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("admin.seasons.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("admin.seasons.update", season.id), {
                onSuccess: () => onClose(),
            });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Season"}
                    {mode === "edit" && "Edit Season"}
                    {mode === "view" && "View Season"}
                </h2>
                <form onSubmit={handleSubmit}>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <CustomSelect
                            label="Service"
                            name="service_id"
                            value={data.service_id}
                            mode={mode}
                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            error={errors.service_id}
                            options={services}
                        /> 
                        <InputText
                            title="name"
                            field="name"
                            height="h-[48px]"
                            value={data.name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
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
                            title="Rake Rate Guide"
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
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">

                        {/* <div className="mb-4 w-full">                        
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
                        </div> */}
                        <InputText
                            title="Duration"
                            field="duration"
                            type='time'
                            height="h-[48px]"
                            value={data.duration}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />

                        <InputText
                            title="Conditions"
                            field="pillbox_conditions"
                            height="h-[48px]"
                            value={data.pillbox_conditions}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
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
                                label="Allow Time Slot Override"
                                name="allow_internal_override"
                                checked={data.allow_internal_override}
                                mode={mode}
                                onChange={handleChange}
                                errors={errors}
                            />
                            <CustomCheckbox
                                label="Close Booking"
                                name="status"
                                checked={data.status}
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

export default SeasonModal;
