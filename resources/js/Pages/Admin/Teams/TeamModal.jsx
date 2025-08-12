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
        description: "",
        logo: "",
        image: "",
        color: "",
        // created_by_id: auth?.user?.id,
        // updated_by_id: auth?.user?.id,
        owner_id: team?.owner_id,

    });
    useEffect(() => {
        if (team && mode !== "create") {
            setData({
                name: team.name || "",
                description: team.description || "",
                logo: team.logo || "",
                image: team.image || "",
                color: team.color || "",
                // created_by_id: team.created_by_id || auth?.user?.id,
                // updated_by_id: team.updated_by_id || auth?.user?.id,
                owner_id: team.owner_id,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode,team,team]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };

const handleSubmit = (e) => {
    e.preventDefault();
    console.log('data ', data);

    const formData = new FormData();
    formData.append('service_id', data.service_id);
    formData.append('name', data.name);
    formData.append('created_by_id', data.created_by_id);
    formData.append('updated_by_id', data.updated_by_id);

    // Dynamic fields
    Object.entries(data.fields).forEach(([key, value]) => {
        formData.append(`fields[${key}]`, value);
    });

    // File (if present)
    if (file) {
        formData.append('file', file);
    }
for (let pair of formData.entries()) {
    console.log(pair[0] + ':', pair[1]);
}
console.log('mode', mode);

    if(mode === "create") {

        axios.post(route("admin.seasons.store"), formData, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        }).then(() => onClose());
    }else{
        formData.append('_method', 'PUT');
        axios.post(route('admin.seasons.update', season.id), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        }).then(() => onClose());

        // router.post(route('services.update', service.id), formData, {
        // method: 'put',
        // preserveScroll: true,
        // })
    }
};

    // const handleSubmit = (e) => {
    //     e.preventDefault();
    //     if (mode === "create") {
    //         post(route("teams.store"), {
    //             onSuccess: () => onClose(),
    //         });
    //     } else if (mode === "edit") {
    //         put(route("teams.update", team.id), {
    //             onSuccess: () => onClose(),
    //         });
    //     }
    // };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Team"}
                    {mode === "edit" && "Edit Team"}
                    {mode === "view" && "View Team"}
                </h2>
                <form onSubmit={handleSubmit}>


                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Team"
                            field="team"
                            value={data.team}
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
                    {mode !== "create" && data?.logo && (
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <label className="block font-semibold mb-1">Current File</label>
                        {data?.logo.file_type.startsWith("image/") ? (
                        <img
                            src={`/storage/${data?.logo.path}`}
                            alt={data?.logo.file_name}
                            className="w-32 h-auto rounded shadow"
                        />
                        ) : (
                        <a
                            href={`/storage/${data?.logo.path}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-blue-600 underline"
                        >
                            Download {data?.logo.file_name}
                        </a>
                        )}
                    </div>
                    )}
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                    <label className="block font-semibold mb-1">Upload New File (optional)</label>
                    <input type="file" onChange={e => setLogo(e.target.files[0])} />
                    </div>
                    {mode !== "create" && data?.image && (
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <label className="block font-semibold mb-1">Current File</label>
                        {data?.image.file_type.startsWith("image/") ? (
                        <img
                            src={`/storage/${data?.image.path}`}
                            alt={data?.image.file_name}
                            className="w-32 h-auto rounded shadow"
                        />
                        ) : (
                        <a
                            href={`/storage/${data?.image.path}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-blue-600 underline"
                        >
                            Download {data?.image.file_name}
                        </a>
                        )}
                    </div>
                    )}
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                    <label className="block font-semibold mb-1">Upload New File (optional)</label>
                    <input type="file" onChange={e => setImage(e.target.files[0])} />
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

export default TeamModal;
