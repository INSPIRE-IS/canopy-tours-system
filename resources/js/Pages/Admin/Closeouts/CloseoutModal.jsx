import React, { useEffect, useState } from "react";
import { router, useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomTextarea from "@/Components/CustomTextarea";
import Tiptap from "@/Components/Tiptap";
import parse from "html-react-parser";


const CloseoutModal = ({ isOpen, onClose, mode, closeout = null, closeoutFields, fieldValues, auth, team }) => {
    // console.log("mode", mode);
     console.log("closeout", closeout);
    // console.log("roles", roles);
const [description, setDescription] = useState("");
const [file, setFile] = useState(null);

    const { data, setData, post, put, errors, reset, processing } = useForm({
        name: "",
        description: "",
        created_by_id: auth?.user?.id,
        updated_by_id: auth?.user?.id,
    });

    useEffect(() => {
        if (closeout && mode !== "create") {
            setData({
                name: closeout?.name || "",
                description: closeout?.description || "",
                created_by_id: closeout.created_by_id || auth?.user?.id,
                updated_by_id: closeout.updated_by_id || auth?.user?.id,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode,closeout,team]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("closeouts.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("closeouts.update", closeout.id), {
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
                <form onSubmit={handleSubmit}>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Closeout"
                            field="name"
                            value={data.name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
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
                      {/*   <div className="w-full mb-4">
                            <label className="block text-main mb-1">Description</label>
                            <Tiptap setDescription={setDescription} />                           
                        </div>*/}
                    {/*  show the description in a textarea
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <div className="ProseMirror">{parse(description)}</div>
                    </div>      
                    */}                        

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

export default CloseoutModal;
