import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import InputRichText from "@/Components/InputRichText";

const TeamModal = ({ isOpen, onClose, mode, team = null, teams }) => {
    // console.log("mode", mode);
    // console.log("teams", teams);
    const [logo, setLogo] = useState(null);
    const [image, setImage] = useState(null);
    const { data, setData, post, put, errors, reset, processing } = useForm({
        name: "",
        full_name: "",
        description: "",
        // file_logo: "",
        // file_image: "",
        color: "",
        reg_no: "",
        address: "",
        address_code: "",
        account_owner: "",
        account_number: "",
        account_type: "",
        bank_name: "",
        branch_code: "",
        team_id: team?.team_id,
        owner_id: "",

    });
    useEffect(() => {
        if (team && mode !== "create") {
            setData({
                name: team.name || "",
                full_name: team.full_name || "",
                description: team.description || "",
                // file_logo: team.file_logo || "",
                // file_image: team.file_image || "",
                color: team.color || "",
                reg_no: team.reg_no || "",
                address: team.address || "",
                address_code: team.address_code || "",
                account_owner: team.account_owner || "",
                account_number: team.account_number || "",
                account_type: team.account_type || "",
                bank_name: team.bank_name || "",
                branch_code: team.branch_code || "",
                team_id: team.team_id,
                owner_id: team.owner_id,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode, team, team]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        console.log('data ', data);

        const formData = new FormData();
        formData.append('name', data.name);
        formData.append('full_name', data.full_name);
        formData.append('description', data.description);
        // formData.append('logo', data.logo);
        // formData.append('image', data.image);
        formData.append('color', data.color);
        formData.append('reg_no', data.reg_no);
        formData.append('address', data.address);
        formData.append('address_code', data.address_code);
        formData.append('account_owner', data.account_owner);
        formData.append('account_number', data.account_number);
        formData.append('account_type', data.account_type);
        formData.append('bank_name', data.bank_name);
        formData.append('branch_code', data.branch_code);
        formData.append('team_id', data.team_id);
        formData.append('owner_id', data.owner_id);


        // File (if present)
        if (logo) {
            formData.append('logo', logo);
        }
        if (image) {
            formData.append('image', image);
        }

        for (let pair of formData.entries()) {
            console.log(pair[0] + ':', pair[1]);
        }
        console.log('mode', mode);

        if (mode === "create") {

            axios.post(route("admin.teams.store"), formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            }).then(() => onClose());
        } else {
            formData.append('_method', 'PUT');
            axios.post(route('admin.teams.update', team.id), formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            }).then(() => onClose());
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Site"}
                    {mode === "edit" && "Edit Site"}
                    {mode === "view" && "View Site"}
                </h2>
                <form onSubmit={handleSubmit}>


                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Name"
                            field="name"
                            value={data.name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Full Name"
                            field="full_name"
                            value={data.full_name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputRichText
                            title="Description"
                            field="description"
                            value={data.description}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Color"
                            field="color"
                            value={data.color}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Reg No"
                            field="reg_no"
                            value={data.reg_no}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Address"
                            field="address"
                            value={data.address}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />

                        <InputText
                            title="Address Code"
                            field="address_code"
                            value={data.address_code}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Account Owner"
                            field="account_owner"
                            value={data.account_owner}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Account Number"
                            field="account_number"
                            value={data.account_number}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />

                        <InputText
                            title="Account Type"
                            field="account_type"
                            value={data.account_type}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Bank Name"
                            field="bank_name"
                            value={data.bank_name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Branch Code"
                            field="branch_code"
                            value={data.branch_code}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>


                    {mode !== "create" && team?.file_logo?.path && (
                        <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                            <label className="block font-semibold mb-1">Current Logo</label>
                            {team?.file_logo?.path != null ? (
                                <img
                                    src={`/storage/${team?.file_logo.path}`}
                                    alt={team?.file_logo.file_name}
                                    className="w-32 h-auto rounded shadow"
                                />
                            ) : (
                                <a
                                    href={`/storage/${team?.file_logo.path}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-blue-600 underline"
                                >
                                    Download {team?.file_logo.file_name}
                                </a>
                            )}


                        </div>
                    )}
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <label className="block font-semibold mb-1">Upload New Logo (optional)</label>
                        <input type="file" onChange={e => setLogo(e.target.files[0])} />
                    </div>
                    {mode !== "create" && team?.file_image && (
                        <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                            <label className="block font-semibold mb-1">Current Image</label>
                            {team?.file_image?.path != null ? (
                                <img
                                    src={`/storage/${team?.file_image.path}`}
                                    alt={team?.file_image.file_name}
                                    className="w-32 h-auto rounded shadow"
                                />
                            ) : (
                                <a
                                    href={`/storage/${team?.file_image.path}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-blue-600 underline"
                                >
                                    Download {team?.file_image.file_name}
                                </a>
                            )}
                        </div>
                    )}
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <label className="block font-semibold mb-1">Upload New Image (optional)</label>
                        <input type="file" onChange={e => setImage(e.target.files[0])} />
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

export default TeamModal;
