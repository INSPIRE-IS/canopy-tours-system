import React, { useEffect, useMemo, useState } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";

const RoleModal = ({ isOpen, onClose, mode, role = null, user, roles, permissions, teams, team }) => {

    const { data, setData, post, put, errors, reset, processing } = useForm({
        name: "",
        permissions: [],
    });
    const [permissionFilter, setPermissionFilter] = useState("");
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

    const normalizedFilter = permissionFilter.trim().toLowerCase();
    const filteredPermissions = useMemo(() => {
        if (!Array.isArray(permissions)) return [];
        if (!normalizedFilter) return permissions;
        return permissions.filter((permission) =>
            String(permission.name || "")
                .toLowerCase()
                .includes(normalizedFilter)
        );
    }, [permissions, normalizedFilter]);

    const groupedPermissions = useMemo(() => {
        const actionOrder = {
            access: 0,
            view: 1,
            show: 2,
            create: 3,
            edit: 4,
            update: 5,
            delete: 6,
            export: 7,
            import: 8,
        };
        const groups = new Map();
        filteredPermissions.forEach((permission) => {
            const name = String(permission.name || "");
            const [prefix, suffix] = name.split("_");
            const key = prefix || "other";
            if (!groups.has(key)) {
                groups.set(key, []);
            }
            groups.get(key).push({
                ...permission,
                _action: suffix || "",
            });
        });
        const toLabel = (value) =>
            value
                .replace(/_/g, " ")
                .replace(/(^\w|\s\w)/g, (m) => m.toUpperCase());

        return Array.from(groups.entries())
            .map(([key, items]) => ({
                key,
                label: toLabel(key),
                items: items
                    .sort((a, b) => {
                        const orderA = actionOrder[a._action] ?? 99;
                        const orderB = actionOrder[b._action] ?? 99;
                        if (orderA !== orderB) return orderA - orderB;
                        return String(a.name).localeCompare(String(b.name));
                    }),
            }))
            .sort((a, b) => a.label.localeCompare(b.label));
    }, [filteredPermissions]);

    const togglePermission = (id, isChecked) => {
        setData((prevState) => {
            const exists = prevState.permissions.includes(id);
            if (isChecked && !exists) {
                return { ...prevState, permissions: [...prevState.permissions, id] };
            }
            if (!isChecked && exists) {
                return { ...prevState, permissions: prevState.permissions.filter((pid) => pid !== id) };
            }
            return prevState;
        });
    };

    const toggleGroup = (ids, isChecked) => {
        setData((prevState) => {
            if (isChecked) {
                const merged = new Set([...prevState.permissions, ...ids]);
                return { ...prevState, permissions: Array.from(merged) };
            }
            return { ...prevState, permissions: prevState.permissions.filter((pid) => !ids.includes(pid)) };
        });
    };


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
                            <div className="mt-2 flex flex-wrap items-center justify-between gap-3">
                                <input
                                    type="text"
                                    value={permissionFilter}
                                    onChange={(e) => setPermissionFilter(e.target.value)}
                                    placeholder="Search permissions..."
                                    className="w-full md:w-[60%] h-10 rounded-md border border-gray-300 px-3 text-sm focus:border-blue-500 focus:outline-none"
                                />
                                <span className="text-xs text-gray-500">
                                    Showing {filteredPermissions.length} of {permissions?.length ?? 0}
                                </span>
                            </div>
                            <div
                                className="mt-3 border border-gray-300 rounded-md p-3 max-h-[420px] w-full overflow-y-auto space-y-4"
                            >
                                {groupedPermissions.length === 0 && (
                                    <div className="text-sm text-gray-500">No permissions found.</div>
                                )}
                                {groupedPermissions.map((group) => {
                                    const ids = group.items.map((permission) => permission.id);
                                    return (
                                        <div key={group.key} className="border border-gray-200 rounded-lg p-3">
                                            <div className="flex items-start justify-between gap-4">
                                                <div>
                                                    <div className="text-sm font-semibold text-gray-800">{group.label}</div>
                                                    <div className="text-xs text-gray-500">{group.items.length} permissions</div>
                                                </div>
                                                {mode !== "view" && (
                                                    <div className="flex items-center gap-2 text-xs">
                                                        <button
                                                            type="button"
                                                            onClick={() => toggleGroup(ids, true)}
                                                            className="text-blue-600 hover:text-blue-800"
                                                        >
                                                            Select all
                                                        </button>
                                                        <span className="text-gray-300">|</span>
                                                        <button
                                                            type="button"
                                                            onClick={() => toggleGroup(ids, false)}
                                                            className="text-gray-500 hover:text-gray-700"
                                                        >
                                                            Clear
                                                        </button>
                                                    </div>
                                                )}
                                            </div>
                                            <div className="mt-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                                {group.items.map((permission) => (
                                                    <label
                                                        key={permission.id}
                                                        className="flex items-center text-sm space-x-2 rounded-md border border-transparent px-2 py-1 hover:bg-gray-50"
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            value={permission.id}
                                                            checked={Array.isArray(data.permissions) && data.permissions.includes(permission.id)}
                                                            onChange={(e) => togglePermission(permission.id, e.target.checked)}
                                                            className="form-checkbox h-4 w-4 text-blue-600"
                                                            disabled={mode === "view"}
                                                        />
                                                        <span className="text-gray-700">{permission.name}</span>
                                                    </label>
                                                ))}
                                            </div>
                                        </div>
                                    );
                                })}
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
