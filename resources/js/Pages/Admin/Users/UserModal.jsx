import React, { useEffect, useMemo } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";

const UserModal = ({ isOpen, onClose, mode, user = null, roles = [], teams, team }) => {
    // console.log("mode", mode);
    console.log("user", user);
    // console.log("roles", roles);
    const { data, setData, post, put, errors, reset, processing } = useForm({
        name: "",
        surname: "",
        email: "",
        password: "",
        mobile_number: "",
        roles: [],

        team_id: null, //team?.id
        is_admin: false,
        is_email_recipient: false,
    });
    useEffect(() => {
        if (user && mode !== "create") {
            setData({
                name: user.name || "",
                surname: user.surname || "",
                email: user.email || "",
                password: "",
                mobile_number: user.mobile_number || "",
                roles: user.roles.map((role) => role.id),
                team_id: user.team_id || null,
                is_admin: user.is_admin,
                is_email_recipient: user.is_email_recipient,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode, user, team]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };


    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("admin.users.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("admin.users.update", user.id), {
                onSuccess: () => onClose(),
            });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create User"}
                    {mode === "edit" && "Edit User"}
                    {mode === "view" && "View User"}
                </h2>
                <form onSubmit={handleSubmit}>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Name"
                            field="name"
                            value={data.name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Surname"
                            field="surname"
                            value={data.surname}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Email"
                            field="email"
                            value={data.email}
                            type="email"
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Password"
                            field="password"
                            type="password"
                            value={data.password}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Mobile Number"
                            field="mobile_number"
                            type="tel"
                            value={data.mobile_number}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <div className="mb-4 w-full"></div>
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <CustomSelect
                            label="teams"
                            name="team_id"
                            value={data.team_id ?? ""}
                            mode={mode}
                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            error={errors.teams}
                            options={teams}
                        />
                    </div>

                    <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700">
                            Roles
                        </label>
                        <div className="mt-1 border border-gray-300 rounded-md p-2 max-h-56 w-full overflow-y-scroll flex flex-wrap gap-2">
                            {roles?.map((role) => (
                                <label
                                    key={role.id}
                                    className="flex items-center text-sm space-x-2"
                                >
                                    <input
                                        type="checkbox"
                                        value={role.id}
                                        checked={Array.isArray(data.roles) && data.roles.includes(role.id)}
                                        onChange={(e) => {
                                            const isChecked = e.target.checked;
                                            setData((prevState) => {
                                                const updatedRoles = isChecked
                                                    ? [...prevState.roles, role.id]
                                                    : prevState.roles.filter((id) => id !== role.id);

                                                return { ...prevState, roles: updatedRoles };
                                            });
                                        }}
                                        className="form-checkbox h-4 w-4 text-blue-600"
                                        disabled={mode === "view"}
                                    />
                                    <span>{role.name}</span>
                                </label>
                            ))}
                        </div>
                    </div>


                    {/* 
                        <div
                            className="mt-1 border border-gray-300 rounded-md p-2 max-h-80 w-full overflow-y-scroll flex flex-wrap gap-2"
                        >
                            {teams?.map((team) => (
                                <label
                                    key={team.id}
                                    className="flex items-center text-sm space-x-2"
                                >
                                    <input
                                        type="checkbox"
                                        value={team.id}
                                        checked={Array.isArray(data.teams) && data.teams.includes(team.id)}
                                        onChange={(e) => {
                                            const isChecked = e.target.checked;
                                            console.log('isChecked', isChecked);
                                            console.log('data.teams', data.teams);
                                            console.log('team.id', team.id);
                                           // console.log('prevState.teams', prevState?.teams);
                                            //console.log('updatedTeams', updatedTeams);
                                            console.log('data', data);

                                            setData((prevState) => {
                                                const updatedTeams = isChecked
                                                    ? [...prevState?.teams, team.id]
                                                    : prevState?.teams.filter((id) => id !== team.id);
                                            
                                                return { ...prevState, teams: updatedTeams };
                                            });
                                            }
                                        }
                                        className="form-checkbox h-4 w-4 text-blue-600"
                                        disabled={mode === "view"}
                                    />
                                    <span>{team.name}</span>
                                </label>
                            ))}
                        </div>
                    </div> */}
                    {/* <CustomCheckbox
                        label="Is Admin"
                        name="is_admin"
                        checked={data.is_admin}
                        mode={mode}
                        onChange={handleChange}
                        errors={errors}
                    /> */}
                    {/* <CustomCheckbox
                        label="Is Email Recipient"
                        name="is_email_recipient"
                        checked={data.is_email_recipient}
                        mode={mode}
                        onChange={handleChange}
                        errors={errors}
                    /> */}
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

export default UserModal;
