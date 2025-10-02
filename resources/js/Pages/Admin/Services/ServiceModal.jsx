import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import InputRichText from "@/Components/InputRichText";

const ServiceModal = ({ isOpen, onClose, mode, service, serviceFields, auth, team }) => {
    // console.log("mode", mode);
    console.log("service", service);
    const [file, setFile] = useState(null);
    const { data, setData, post, put, errors, reset, processing } = useForm({
        service_id: "",
        name: "",
        tag: "",
        duration: "",
        pillbox_conditions: "",
        capacity: "",
        allow_internal_capacity_override: 0,
        // pillbox_text: "",
        // arrangements: "",
        // inclusions_exclusions: "",
        // notes: "",
        // lat_lon_start: "",
        // lat_long_end: "",
        fields: Object.fromEntries(
            serviceFields.map(f => {
                const fieldValue = service?.servicefield_values?.find(v => v.field_id === f.id);
                return [f.id, fieldValue?.value || ""];
            })
        ),
        created_by_id: auth?.user?.id,
        updated_by_id: auth?.user?.id,

    });

    useEffect(() => {
        if (service && mode !== "create") {
            setData({
                service_id: service.service_id || "",
                name: service.name || "",
                tag: service.tag || "",
                duration: service.duration || "",
                pillbox_conditions: service.pillbox_conditions || "",
                capacity: service.capacity || "",
                allow_internal_capacity_override: service.allow_internal_capacity_override || 0,
                // pillbox_text: service.pillbox_text || "",
                // arrangements: service.arrangements || "",
                // inclusions_exclusions: service.inclusions_exclusions || "",
                // notes: service.notes || "",
                // lat_lon_start: service.lat_lon_start || "",
                // lat_long_end: service.lat_long_end || "",
                fields: Object.fromEntries(
                    serviceFields.map(f => {
                        const fieldValue = service?.servicefield_values?.find(v => v.field_id === f.id);
                        return [`${f.id}`, fieldValue?.value || ""];
                    })
                ),
                created_by_id: auth?.user?.id,
                updated_by_id: auth?.user?.id,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode, service, team]);

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
    const handleSubmit = (e) => {
        e.preventDefault();
        console.log('data ', data);

        const formData = new FormData();
        formData.append('service_id', data.service_id);
        formData.append('name', data.name);
        formData.append('tag', data.tag);
        formData.append('duration', data.duration);
        formData.append('pillbox_conditions', data.pillbox_conditions);
        formData.append('capacity', data.capacity);
        formData.append('allow_internal_capacity_override', data.allow_internal_capacity_override);
        formData.append('created_by_id', data.created_by_id);
        formData.append('updated_by_id', data.updated_by_id);

        // Dynamic fields
        Object.entries(data.fields).forEach(([key, value]) => {
            formData.append(`fields[${key}]`, value);
        });

        // File (if present)
        if (file) {
            formData.append('file', file);
        }
        for (let pair of formData.entries()) {
            console.log(pair[0] + ':', pair[1]);
        }
        console.log('mode', mode);

        if (mode === "create") {

            axios.post(route("admin.services.store"), formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            }).then(() => onClose());
        } else {
            formData.append('_method', 'PUT');
            axios.post(route('admin.services.update', service.id), formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            }).then(() => onClose());

            // router.post(route('services.update', service.id), formData, {
            // method: 'put',
            // preserveScroll: true,
            // })
        }
    };

    const renderField = (field) => {
        const value = data.fields?.[field.id] || "";

        switch (field.type_input_id) {
            case 1: // text input
                return (
                    <input
                        type="text"
                        value={value}
                        onChange={(e) =>
                            setData("fields", { ...data.fields, [field.id]: e.target.value })
                        }
                        required={field.required}
                        className={`mt-1 block w-full ${errors.slug ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-xl
                hover:border-black  active:border-blue-600 
                focus:outline-none focus:ring-0 focus:border-black
                `}
                    />
                );
            case 8: // textarea
                return (
                    <textarea
                        value={value}
                        onChange={(e) =>
                            setData("fields", { ...data.fields, [field.id]: e.target.value })
                        }
                        required={field.required}
                        rows={3}
                        className={`w-full pl-[19px] pt-[11px] pb-[12px] bg-[#FBF9F7] border rounded-2xl focus:outline-none focus:ring-2 resize-none`}
                    />
                );
            case 3: // number
                return (
                    <input
                        type="number"
                        value={value}
                        onChange={(e) =>
                            setData("fields", { ...data.fields, [field.id]: e.target.value })
                        }
                        required={field.required}
                        className={`mt-1 block w-full ${errors.name ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-xl
                hover:border-black  active:border-blue-600 
                focus:outline-none focus:ring-0 focus:border-black
                `}
                    />
                );
            case 4: // date
                return (
                    <input
                        type="date"
                        value={value}
                        onChange={(e) =>
                            setData("fields", { ...data.fields, [field.id]: e.target.value })
                        }
                        required={field.required}
                        className={`mt-1 block w-full ${errors.name ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-xl
                hover:border-black  active:border-blue-600 
                focus:outline-none focus:ring-0 focus:border-black
                `}
                    />
                );
            case 5: // time
                return (
                    <input
                        type="time"
                        value={value}
                        onChange={(e) =>
                            setData("fields", { ...data.fields, [field.id]: e.target.value })
                        }
                        required={field.required}
                        className={`mt-1 block w-full ${errors.name ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-xl
                hover:border-black  active:border-blue-600 
                focus:outline-none focus:ring-0 focus:border-black
                `}
                    />
                );
            case 6: // checkbox
                return (
                    <input
                        type="text"
                        value={value}
                        onChange={(e) =>
                            setData("fields", { ...data.fields, [field.id]: e.target.value })
                        }
                        required={field.required}
                        className={`mt-1 block w-full ${errors.name ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-xl
                hover:border-black  active:border-blue-600 
                focus:outline-none focus:ring-0 focus:border-black
                `}
                    />
                );
            case 2: // rich text
                return (
                    <InputRichText
                        title={field.name}
                        field={field.id}
                        value={value}
                        mode={mode}
                        handleChange={handleFieldChange}
                        errors={errors}
                    />
                );
            default:
                return (
                    <input
                        type="text"
                        value={value}
                        onChange={(e) =>
                            setData("fields", { ...data.fields, [field.id]: e.target.value })
                        }
                        required={field.required}
                        className={`mt-1 block w-full ${errors.name ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-xl
                    hover:border-black  active:border-blue-600 
                    focus:outline-none focus:ring-0 focus:border-black
                    `}
                    />
                );
        }
    };
    // const handleSubmit = (e) => {
    //     e.preventDefault();
    //     if (mode === "create") {
    //         post(route("services.store"), {
    //             onSuccess: () => onClose(),
    //         });
    //     } else if (mode === "edit") {
    //         put(route("services.update", service.id), {
    //             onSuccess: () => onClose(),
    //         });
    //     }
    // };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Service"}
                    {mode === "edit" && "Edit Service"}
                    {mode === "view" && "View Service"}
                </h2>
                <form onSubmit={handleSubmit}>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="tag"
                            field="tag"
                            height="h-[48px]"
                            value={data.tag}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
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
                            title="Duration (estimated completion time)"
                            field="duration"
                            type='time'
                            height="h-[48px]"
                            value={data.duration}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Capacity (PAX)"
                            field="capacity"
                            height="h-[48px]"
                            value={data.capacity}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>



                    <InputText
                        title="Conditions"
                        field="pillbox_conditions"
                        height="h-[48px]"
                        value={data.pillbox_conditions}
                        mode={mode}
                        handleChange={handleChange}
                        errors={errors}
                    />

                    <CustomCheckbox
                        label="Allow Time Capacity(PAX) Override"
                        name="allow_internal_capacity_override"
                        checked={data.allow_internal_capacity_override}
                        mode={mode}
                        onChange={handleChange}
                        errors={errors}
                    />
                    {serviceFields.map(field => {
                        console.log('field', field);
                        return (
                            <div key={field.id} className="mb-4">
                                <label className="block font-semibold mb-1">{field.name}</label>
                                {renderField(field)}
                                {errors?.fields?.[field.id] && (
                                    <div className="text-red-500 text-sm">{errors.fields[field.id]}</div>
                                )}
                            </div>
                        )
                    })}
                    {mode !== "create" && service?.file && (
                        <div className="mb-4">
                            <label className="block font-semibold mb-1">Current File</label>
                            {service.file.file_type.startsWith("image/") ? (
                                <img
                                    src={`/storage/${service.file.path}`}
                                    alt={service.file.file_name}
                                    className="w-32 h-auto rounded shadow"
                                />
                            ) : (
                                <a
                                    href={`/storage/${service.file.path}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-blue-600 underline"
                                >
                                    Download {service.file.file_name}
                                </a>
                            )}
                        </div>
                    )}
                    <div className="mb-4">
                        <label className="block font-semibold mb-1">Upload New File (optional)</label>
                        <input type="file" onChange={e => setFile(e.target.files[0])} />
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

export default ServiceModal;
