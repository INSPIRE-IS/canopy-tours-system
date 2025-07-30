import React, { useEffect } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";

const PermissionModal = ({ isOpen, onClose, mode, permission = null, roles, teams, team }) => {
    // console.log("mode", mode);
     console.log("permission", permission);
    // console.log("roles", roles);
    const { data, setData, post, put, errors, reset, processing } = useForm({
        name: "",
    });
    useEffect(() => {
        if (permission && mode !== "create") {
            setData({
                name: permission.name || "",
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode,permission,team]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("permissions.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("permissions.update", permission.id), {
                onSuccess: () => onClose(),
            });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Permission"}
                    {mode === "edit" && "Edit Permission"}
                    {mode === "view" && "View Permission"}
                </h2>
                <form onSubmit={handleSubmit}>


                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Name"
                            field="name"
                            value={data.name || ""}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <div className="mb-4 w-full"></div>
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

export default PermissionModal;
