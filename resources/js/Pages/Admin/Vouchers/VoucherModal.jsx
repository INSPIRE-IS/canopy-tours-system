import React, { useEffect, useState } from "react";
import { router, useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomTextarea from "@/Components/CustomTextarea";
import Tiptap from "@/Components/Tiptap";
import parse from "html-react-parser";


const VoucherModal = ({ isOpen, onClose, mode, voucher = null, voucherFields, fieldValues, auth, team }) => {
    // console.log("mode", mode);
     console.log("voucher", voucher);
    // console.log("roles", roles);
const [note, setNote] = useState("");
const [file, setFile] = useState(null);

    const { data, setData, post, put, errors, reset, processing } = useForm({
        voucher_code: "",
        note: "",
        created_by_id: auth?.user?.id,
        updated_by_id: auth?.user?.id,
    });

    useEffect(() => {
        if (voucher && mode !== "create") {
            setData({
                voucher_code: voucher?.voucher_code || "",
                note: voucher?.note || "",
                created_by_id: voucher.created_by_id || auth?.user?.id,
                updated_by_id: voucher.updated_by_id || auth?.user?.id,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode,voucher,team]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("voucher_code", e.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("vouchers.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("vouchers.update", voucher.id), {
                onSuccess: () => onClose(),
            });
        }
    };



    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Voucher"}
                    {mode === "edit" && "Edit Voucher"}
                    {mode === "view" && "View Voucher"}
                </h2>
                <form onSubmit={handleSubmit}>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Voucher"
                            field="voucher_code"
                            value={data.voucher_code}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Note"
                            field="note"
                            value={data.note}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                            isRequired
                            isReadOnly={mode === "view"}
                        />  
                      {/*   <div className="w-full mb-4">
                            <label className="block text-main mb-1">Note</label>
                            <Tiptap setNote={setNote} />                           
                        </div>*/}
                    {/*  show the note in a textarea
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <div className="ProseMirror">{parse(note)}</div>
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

export default VoucherModal;
