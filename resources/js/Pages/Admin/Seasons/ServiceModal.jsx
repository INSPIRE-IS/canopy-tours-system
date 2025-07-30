import React, { useEffect, useState } from "react";
import { router, useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomTextarea from "@/Components/CustomTextarea";
import Tiptap from "@/Components/Tiptap";
import parse from "html-react-parser";


const ServiceModal = ({ isOpen, onClose, mode, service = null, serviceFields, fieldValues, auth, team }) => {
    // console.log("mode", mode);
     console.log("service", service);
    // console.log("roles", roles);
const [description, setDescription] = useState("");
const [file, setFile] = useState(null);

    const { data, setData, post, put, errors, reset, processing } = useForm({
        name: "",
        description: "",
        fields: Object.fromEntries(
                serviceFields.map(f => {
                    const fieldValue = service?.field_values?.find(v => v.field_id === f.id);
                    return [f.id, fieldValue?.value || ""];
                })
            ),
        created_by_id: auth?.user?.id,
        updated_by_id: auth?.user?.id,
    });

    useEffect(() => {
        if (service && mode !== "create") {
            setData({
                name: service?.name || "",
                description: service?.description || "",
            fields: Object.fromEntries(
                    serviceFields.map(f => {
                        const fieldValue = service?.field_values?.find(v => v.field_id === f.id);
                        return [f.id, fieldValue?.value || ""];
                    })
                ),
                created_by_id: service.created_by_id || auth?.user?.id,
                updated_by_id: service.updated_by_id || auth?.user?.id,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode,service,team]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };
const handleSubmit = (e) => {
    e.preventDefault();

    const formData = new FormData();
    formData.append('name', data.name);
    formData.append('description', data.description);
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

    if(mode === "create") {

        axios.post(route("services.store"), formData, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        }).then(() => onClose());
    }else{
        axios.put(route('services.update', service.id), formData, {
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
                    className="w-full p-2 border rounded"
                />
            );
        case 2: // textarea
            return (
                <textarea
                    value={value}
                    onChange={(e) =>
                        setData("fields", { ...data.fields, [field.id]: e.target.value })
                    }
                    required={field.required}
                    rows={3}
                    className="w-full p-2 border rounded"
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
                    className="w-full p-2 border rounded"
                />
            );
    }
};

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
                            title="Service"
                            field="name"
                            value={data.name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        {/* <InputText
                            title="Description"
                            field="description"
                            value={data.description}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                            isRequired
                            isReadOnly={mode === "view"}
                        />  */}
                        <div className="w-full mb-4">
                            <label className="block text-main mb-1">Content</label>
<Tiptap setDescription={setDescription} />                           
                        </div>
                    {/*  show the description in a textarea
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <div className="ProseMirror">{parse(description)}</div>
                    </div>      
                    */}                        

                    </div>
                    {serviceFields.map(field => (
                        <div key={field.id} className="mb-4">
                            <label className="block font-semibold mb-1">{field.name}</label>
                            {renderField(field)}
                            {errors?.fields?.[field.id] && (
                                <div className="text-red-500 text-sm">{errors.fields[field.id]}</div>
                            )}
                        </div>
                    ))}
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

export default ServiceModal;
