import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomTextarea from "@/Components/CustomTextarea";
import Tiptap from "@/Components/Tiptap";
import parse from "html-react-parser";


const ServiceModal = ({ isOpen, onClose, mode, service = null, auth, team }) => {
    // console.log("mode", mode);
     console.log("service", service);
    // console.log("roles", roles);
const [description, setDescription] = useState("");

    const { data, setData, post, put, errors, reset, processing } = useForm({
        name: "",
        description: "",
        pillbox_text: "",
        activity_description: "",
        arrangements: "",
        requirements: "",
        inclusions_exclusions: "",
        notes: "",
        lat_lon_start: "",
        lat_long_end: "",
        created_by_id: auth?.user?.id,
        updated_by_id: auth?.user?.id,
    });
    useEffect(() => {
        if (service && mode !== "create") {
            setData({
                name: service.name || "",
                description: service.description || "",
                pillbox_text: service.pillbox_text || "",
                activity_description: service.activity_description || "",
                arrangements: service.arrangements || "",
                requirements: service.requirements || "",
                inclusions_exclusions: service.inclusions_exclusions || "",
                notes: service.notes || "",
                lat_lon_start: service.lat_lon_start || "",
                lat_long_end: service.lat_long_end || "",
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
        if (mode === "create") {
            post(route("services.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("services.update", service.id), {
                onSuccess: () => onClose(),
            });
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
                        <InputText
                            title="Pillbox Text"
                            field="pillbox_text"
                            value={data.pillbox_text}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        /> 
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Description"
                            field="description"
                            value={data.description}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                            isRequired
                            isReadOnly={mode === "view"}
                        />
                        {/* <CustomTextarea
                            label="Content"
                            name="content"
                            value={data.content}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        /> */}
                        <div className="w-full mb-4">
                            <label className="block text-main mb-1">Content</label>
<Tiptap setDescription={setDescription} />                           
                        </div>

                        


                    </div>
                    {/*  show the description in a textarea
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <div className="ProseMirror">{parse(description)}</div>
                    </div>      
                    */}

                                            <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Arrangements"
                            field="arrangements"
                            value={data.arrangements}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Requirements"
                            field="requirements"
                            value={data.requirements}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />


                    </div>

                                            <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Inclusions/Exclusions"
                            field="inclusions_exclusions"
                            value={data.inclusions_exclusions}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Notes"
                            field="notes"
                            value={data.notes}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />


                    </div>

                        <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Lat Lon Start"
                            field="lat_lon_start"
                            value={data.lat_lon_start}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Lat Long End"
                            field="lat_long_end"
                            value={data.lat_long_end}
                            mode={mode}
                            handleChange={handleChange}
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

export default ServiceModal;
