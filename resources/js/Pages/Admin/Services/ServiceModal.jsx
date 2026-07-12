import React, { useEffect, useState } from "react";
import { useForm, usePage, router } from "@inertiajs/react";
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
    const { props } = usePage();
    const {
        data,
        setData,
        post,
        put,
        errors: formErrors,
        reset,
        processing,
        setError,
        clearErrors,
    } = useForm({
        service_id: "",
        name: "",
        tag: "",
        duration: "",
        pillbox_conditions: "",
        capacity: "",
        allow_internal_capacity_override: 0,
        is_online_bookable: 1,
        file: null,
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
                is_online_bookable: service.is_online_bookable ?? 1,
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
        clearErrors();

        const payload = {
            ...data,
            file: data.file ?? null,
        };

        if (mode === "create") {
            router.post(route("admin.services.store"), payload, {
                forceFormData: true,
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            router.post(route("admin.services.update", service.id), { ...payload, _method: "PUT" }, {
                forceFormData: true,
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => onClose(),
            });
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
                        className={`mt-1 block w-full ${errors ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-xl
                    hover:border-black  active:border-blue-600 
                    focus:outline-none focus:ring-0 focus:border-black
                    `}
                    />
                );
        }
    };


    if (!isOpen) return null;
    const errors = Object.keys(formErrors || {}).length ? formErrors : (props?.errors || {});
    const fieldErrors = { ...(errors.fields || {}) };
    Object.entries(errors || {}).forEach(([key, value]) => {
        if (key.startsWith("fields.")) {
            const fieldId = key.split(".")[1];
            fieldErrors[fieldId] = value;
        }
    });
    const formatFieldError = (field, message) => {
        if (!message) return message;
        if (typeof message !== "string") return message;
        if (message.startsWith("The fields.")) {
            return message.replace(/^The fields\.\d+ field/, `The ${field.name} field`);
        }
        return message;
    };

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
                    <CustomCheckbox
                        label="Online Bookable"
                        helper="Show this service on the public booking website. Turn off for staff-only products such as team builds."
                        name="is_online_bookable"
                        checked={data.is_online_bookable}
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
                                {fieldErrors?.[field.id] && (
                                    <div className="text-red-500 text-sm">
                                        {formatFieldError(field, fieldErrors[field.id])}
                                    </div>
                                )}
                            </div>
                        )
                    })}
                    {mode !== "create" && service?.file && (
                        <div className="mb-4">
                            <label className="block font-semibold mb-1">Current File</label>
                            {service.file.file_type.startsWith("image/") ? (
                                <img
                                    src={`${service.file.path}`}
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
                        <input
                            type="file"
                            onChange={e => {
                                const nextFile = e.target.files[0] ?? null;
                                setFile(nextFile);
                                setData("file", nextFile);
                            }}
                        />
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
