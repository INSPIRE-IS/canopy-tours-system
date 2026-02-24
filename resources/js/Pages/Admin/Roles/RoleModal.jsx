import React, { useEffect } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";

const RoleModal = ({ isOpen, onClose, mode, role = null, user, roles, permissions, teams, team }) => {

    const { data, setData, post, put, errors, reset, processing } = useForm({
        name: "",
        permissions: [],
    });
    useEffect(() => {
        if (role && mode !== "create") {
            setData({
                name: role.name || "",
                permissions: role.permissions?.map((permission) => permission.id) || [],
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode, role, team]);


    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("admin.roles.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("admin.roles.update", role.id), {
                onSuccess: () => onClose(),
            });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Role"}
                    {mode === "edit" && "Edit Role"}
                    {mode === "view" && "View Role"}
                </h2>
                <form onSubmit={handleSubmit}>


                    <div className="flex flex-col w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Role"
                            field="name"
                            value={data.name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <div className="mb-4 w-full">
                            <label className="block text-sm font-medium text-gray-700">
                                Permissions
                            </label>
                            <div
                                className="mt-1 border border-gray-300 rounded-md p-2 max-h-80 w-full overflow-y-scroll flex flex-wrap gap-2"
                            >
                                {permissions?.map((permission) => (
                                    <label
                                        key={permission.id}
                                        className="flex items-center text-sm space-x-2"
                                    >
                                        <input
                                            type="checkbox"
                                            value={permission.id}
                                            checked={Array.isArray(data.permissions) && data.permissions.includes(permission.id)}
                                            onChange={(e) => {
                                                const isChecked = e.target.checked;
                                                setData((prevState) => {
                                                    const updatedPermissions = isChecked
                                                        ? [...prevState.permissions, permission.id]
                                                        : prevState.permissions.filter((id) => id !== permission.id);

                                                    return { ...prevState, permissions: updatedPermissions };
                                                });
                                            }
                                            }
                                            className="form-checkbox h-4 w-4 text-blue-600"
                                            disabled={mode === "view"}
                                        />
                                        <span>{permission.name}</span>
                                    </label>
                                ))}
                            </div>
                        </div>
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

export default RoleModal;
