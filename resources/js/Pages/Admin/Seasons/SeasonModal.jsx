import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import InputRichText from "@/Components/InputRichText";

const SeasonModal = ({ isOpen, onClose, mode, season = null, services, auth, team }) => {
    // console.log("mode", mode);

    const { data, setData, post, put, errors, reset, processing } = useForm({
        service_id: "",
        season_id: "",
        name: "",
        start_date: "",
        end_date: "",
        rake_rate_adult: "0",
        rake_rate_child: "0",
        rake_rate_guide: "0",
        allow_internal_price_override: 0,
        internal_only: 0,
        include_public_extras: false,
        extras: [],
        capacity: "",
        status: 0,
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
                rake_rate_adult: season.rake_rate_adult || "0",
                rake_rate_child: season.rake_rate_child || "0",
                rake_rate_guide: season.rake_rate_guide || "0",
                allow_internal_price_override: season.allow_internal_price_override || 0,
                internal_only: season.internal_only || 0,
                include_public_extras: Boolean(season.include_public_extras),
                extras: (season.extras || []).map((extra) => ({
                    id: extra.id,
                    name: extra.name || "",
                    description: extra.description || "",
                    unit_price: extra.unit_price ?? "",
                    is_per_guest: Boolean(extra.is_per_guest),
                    is_active: Boolean(extra.is_active),
                })),
                status: season.status || 0,
                updated_by_id: auth?.user?.id,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode, season, team]);
    const handleFieldChange = (target, value) => {
        setData(prevData => ({
            ...prevData,
            fields: {
                ...prevData.fields,
                [`${target}`]: value,
            },
        }));
    };
    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };
    const extras = Array.isArray(data.extras) ? data.extras : [];
    const addExtra = () => {
        if (extras.length >= 20) return;

        setData("extras", [
            ...extras,
            {
                id: null,
                name: "",
                description: "",
                unit_price: "",
                is_per_guest: false,
                is_active: true,
            },
        ]);
    };
    const updateExtra = (index, field, value) => {
        setData("extras", extras.map((extra, currentIndex) => (
            currentIndex === index ? { ...extra, [field]: value } : extra
        )));
    };
    const removeExtra = (index) => {
        setData("extras", extras.filter((_, currentIndex) => currentIndex !== index));
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
            <div className="bg-white rounded-lg shadow-lg p-6 w-[70%] max-w-[1100px] min-w-[760px] h-[98%] overflow-y-auto">
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
                        <InputText
                            title="Rake Rate Guide"
                            field="rake_rate_guide"
                            value={data.rake_rate_guide}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    {/* <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">

                    </div> */}
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
                            label="Staff Only / Not Online Bookable"
                            name="internal_only"
                            checked={data.internal_only}
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
                    <div className="mb-6 rounded-[10px] border border-secondary-100 bg-white p-4 shadow-sm">
                        <CustomCheckbox
                            label="Offer extras during online checkout"
                            helper="When enabled, active extras configured below are shown to guests booking this season."
                            name="include_public_extras"
                            checked={data.include_public_extras}
                            mode={mode}
                            onChange={handleChange}
                            errors={errors}
                            disabled={mode === "view"}
                        />

                        {!data.include_public_extras && extras.length > 0 && (
                            <p className="mb-3 text-xs text-gray-500">
                                These configured extras are retained but hidden from public checkout.
                            </p>
                        )}

                        {data.include_public_extras && (
                            <div className="space-y-3">
                                {extras.length === 0 && (
                                    <p className="text-sm text-gray-500">
                                        Add the optional items guests may select during checkout.
                                    </p>
                                )}

                                {extras.map((extra, index) => (
                                    <div key={extra.id || `new-extra-${index}`} className="rounded-md border border-gray-200 p-3">
                                        <div className="grid grid-cols-12 gap-3 items-end">
                                            <div className="col-span-4 min-w-0">
                                                <InputText
                                                    title="Extra name (max 30 characters)"
                                                    field={`extras.${index}.name`}
                                                    value={extra.name}
                                                    mode={mode}
                                                    handleChange={(_, value) => updateExtra(index, "name", value)}
                                                    errors={errors}
                                                    maxLength={30}
                                                />
                                            </div>
                                            <div className="col-span-5 min-w-0">
                                                <InputText
                                                    title="Short description"
                                                    field={`extras.${index}.description`}
                                                    value={extra.description}
                                                    mode={mode}
                                                    handleChange={(_, value) => updateExtra(index, "description", value)}
                                                    errors={errors}
                                                    maxLength={255}
                                                />
                                            </div>
                                            <div className="col-span-3 min-w-0">
                                                <InputText
                                                    title="Price"
                                                    field={`extras.${index}.unit_price`}
                                                    type="number"
                                                    value={extra.unit_price}
                                                    mode={mode}
                                                    handleChange={(_, value) => updateExtra(index, "unit_price", value)}
                                                    errors={errors}
                                                    min="0.01"
                                                    step="0.01"
                                                />
                                            </div>
                                        </div>
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <div className="flex flex-wrap items-center gap-5">
                                                <CustomCheckbox
                                                    label="Charge per guest"
                                                    helper="Off charges once per booking. On multiplies the price by total booked guests."
                                                    name={`extras.${index}.is_per_guest`}
                                                    checked={extra.is_per_guest}
                                                    mode={mode}
                                                    onChange={(_, value) => updateExtra(index, "is_per_guest", value)}
                                                    errors={errors}
                                                    disabled={mode === "view"}
                                                />
                                                <CustomCheckbox
                                                    label="Available online"
                                                    name={`extras.${index}.is_active`}
                                                    checked={extra.is_active}
                                                    mode={mode}
                                                    onChange={(_, value) => updateExtra(index, "is_active", value)}
                                                    errors={errors}
                                                    disabled={mode === "view"}
                                                />
                                            </div>
                                            {mode !== "view" && (
                                                <button
                                                    type="button"
                                                    className="flex h-[40px] items-center justify-center rounded-[10px] border-[3px] border-[#C65468] bg-red-gradient-overlay px-4 py-2 text-white shadow-inset-white disabled:cursor-not-allowed disabled:opacity-50"
                                                    onClick={() => removeExtra(index)}
                                                    disabled={processing}
                                                >
                                                    <span className="text-btn-outline-gray">Remove</span>
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                ))}

                                {errors.extras && <p className="text-sm text-red-500">{errors.extras}</p>}

                                {mode !== "view" && (
                                    <BlueButton
                                        title="Add extra"
                                        type="button"
                                        icon
                                        width="w-auto"
                                        onClick={addExtra}
                                        disabled={processing || extras.length >= 20}
                                    />
                                )}
                            </div>
                        )}
                    </div>
                    <div className="flex justify-center gap-4">
                        <OutlineButton title="Cancel" onClick={onClose} width="w-[180px]" />
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
