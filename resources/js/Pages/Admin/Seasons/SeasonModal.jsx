import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import InputRichText from "@/Components/InputRichText";

const SeasonModal = ({ isOpen, onClose, mode, season = null, services, seasonFields, auth, team}) => {
    // console.log("mode", mode);
     console.log("season", season);
     console.log("services", services);
     const [file, setFile] = useState(null);
    const { data, setData, post, put, errors, reset, processing } = useForm({
        service_id: "",
        name: "",
        fields: Object.fromEntries(
            seasonFields.map(f => {
                const fieldValue = season?.seasonfield_values?.find(v => v.field_id === f.id);
                return [f.id, fieldValue?.value || ""];
            })
        ),
        created_by_id: auth?.user?.id,
        updated_by_id: auth?.user?.id,

    });

    useEffect(() => {
        if (season && mode !== "create") {
            setData({
                service_id: season.service_id || "",
                name: season.name || "",
                fields: Object.fromEntries(
                    seasonFields.map(f => {
                        const fieldValue = season?.seasonfield_values?.find(v => v.field_id === f.id);
                        return [`${f.id}`, fieldValue?.value || ""];
                    })
                ),
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
const handleSubmit = (e) => {
    e.preventDefault();
    console.log('data ', data);

    const formData = new FormData();
    formData.append('service_id', data.service_id);
    formData.append('name', data.name);
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

    if(mode === "create") {

        axios.post(route("admin.seasons.store"), formData, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        }).then(() => onClose());
    }else{
        formData.append('_method', 'PUT');
        axios.post(route('admin.seasons.update', season.id), formData, {
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
    //         post(route("seasons.store"), {
    //             onSuccess: () => onClose(),
    //         });
    //     } else if (mode === "edit") {
    //         put(route("seasons.update", season.id), {
    //             onSuccess: () => onClose(),
    //         });
    //     }
    // };

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
                    {seasonFields.map(field => {
                        console.log('field', field);
                        return(
                        <div key={field.id} className="mb-4">
                            <label className="block font-semibold mb-1">{field.name}</label>
                            {renderField(field)}
                            {errors?.fields?.[field.id] && (
                                <div className="text-red-500 text-sm">{errors.fields[field.id]}</div>
                            )}
                        </div>
                    )})}
                    {mode !== "create" && season?.file && (
                    <div className="mb-4">
                        <label className="block font-semibold mb-1">Current File</label>
                        {season.file.file_type.startsWith("image/") ? (
                        <img
                            src={`/storage/${season.file.path}`}
                            alt={season.file.file_name}
                            className="w-32 h-auto rounded shadow"
                        />
                        ) : (
                        <a
                            href={`/storage/${season.file.path}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-blue-600 underline"
                        >
                            Download {season.file.file_name}
                        </a>
                        )}
                    </div>
                    )}
<div className="mb-4">
  <label className="block font-semibold mb-1">Upload New File (optional)</label>
  <input type="file" onChange={e => setFile(e.target.files[0])} />
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
