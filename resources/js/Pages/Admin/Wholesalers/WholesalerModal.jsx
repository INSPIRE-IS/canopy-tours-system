import React, { useEffect, useState } from "react";
import { router, useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomTextarea from "@/Components/CustomTextarea";
import parse from "html-react-parser";


const WholesalerModal = ({ isOpen, onClose, mode, wholesaler = null, wholesalerFields, fieldValues, auth, team }) => {
    // console.log("mode", mode);
    console.log("wholesaler", wholesaler);
    // console.log("roles", roles);
    const [description, setDescription] = useState("");
    const [file, setFile] = useState(null);

    const { data, setData, post, put, errors, reset, processing } = useForm({
        name: "",
        reg_no: "",
        vat_no: "",
        address: "",
        description: "",
        created_by_id: auth?.user?.id,
        updated_by_id: auth?.user?.id,
    });

    useEffect(() => {
        if (wholesaler && mode !== "create") {
            setData({
                name: wholesaler?.name || "",
                reg_no: wholesaler?.reg_no || "",
                vat_no: wholesaler?.vat_no || "",
                address: wholesaler?.address || "",
                description: wholesaler?.description || "",
                created_by_id: wholesaler.created_by_id || auth?.user?.id,
                updated_by_id: wholesaler.updated_by_id || auth?.user?.id,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode, wholesaler, team]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("admin.wholesalers.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("admin.wholesalers.update", wholesaler.id), {
                onSuccess: () => onClose(),
            });
        }
    };



    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Wholesaler"}
                    {mode === "edit" && "Edit Wholesaler"}
                    {mode === "view" && "View Wholesaler"}
                </h2>
                <form onSubmit={handleSubmit}>
                    <div className="flex flex-col w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Wholesaler"
                            field="name"
                            value={data.name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Reg. No."
                            field="reg_no"
                            value={data.reg_no}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="VAT No."
                            field="vat_no"
                            value={data.vat_no}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-col w-full items-center gap-2 justify-between mb-4">
                        <CustomTextarea
                            label="Address"
                            value={data.address}
                            mode={mode}
                            name="address"
                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            error={errors.address}
                        />
                        {/* <CustomTextarea
                            label="Description"
                            field="description"
                            value={data.description}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        /> */}
                    </div>


                    {/*   <div className="w-full mb-4">
                            <label className="block text-main mb-1">Description</label>
                            <Tiptap setDescription={setDescription} />                           
                        </div>*/}
                    {/*  show the description in a textarea
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <div className="ProseMirror">{parse(description)}</div>
                    </div>      
                    */}



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

export default WholesalerModal;
