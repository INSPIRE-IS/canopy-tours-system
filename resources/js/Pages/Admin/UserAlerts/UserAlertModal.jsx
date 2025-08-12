import React, { useEffect } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";

const UserAlertModal = ({ isOpen, onClose, mode, useralert = null, users, team }) => {
    // console.log("mode", mode);
     console.log("useralert", useralert);
    // console.log("roles", roles);
    const { data, setData, post, put, errors, reset, processing } = useForm({
        alert_text: "",
        alert_link: "",
        user_id: null,
    });
    useEffect(() => {
        if (useralert && mode !== "create") {
            setData({
                alert_text: useralert.alert_text || "",
                alert_link: useralert.alert_link || "",
                user_id: useralert.user_id || null,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode,useralert,team]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("user_alerts.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("user_alerts.update", useralert.id), {
                onSuccess: () => onClose(),
            });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create User Alert"}
                    {mode === "edit" && "Edit User Alert"}
                    {mode === "view" && "View User Alert"}
                </h2>
                <form onSubmit={handleSubmit}>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <CustomSelect
                            label="User"
                            name="user_id"
                            value={data.user_id}
                            mode={mode}
                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            error={errors.user_id}
                            options={users}
                        />                        
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Alert Text"
                            field="alert_text"
                            value={data.alert_text}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>                    
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Alert Link"
                            field="alert_link"
                            value={data.alert_link}
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

export default UserAlertModal;
