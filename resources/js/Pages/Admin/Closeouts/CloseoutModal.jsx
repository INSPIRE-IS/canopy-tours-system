import React, { useEffect, useState } from "react";
import { router, useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomTextarea from "@/Components/CustomTextarea";
import Tiptap from "@/Components/Tiptap";
import parse from "html-react-parser";
import CustomSelect from "@/Components/CustomSelect";


const CloseoutModal = ({ isOpen, onClose, mode, closeout = null, services, fieldValues, auth, team_id }) => {
    // console.log("mode", mode);
     console.log("closeout", closeout);
    const [seasons, setSeasons] = useState([]);

    const { data, setData, post, put, errors, reset, processing } = useForm({
        name: "",
        start_date: "",
        end_date: "",
        reason: "",
        service_id: "",
        season_id: "",
        team_id: team_id,
        created_by_id: auth?.user?.id,
    });

    useEffect(() => {
        if (closeout && mode !== "create") {
            setData({
                name: closeout?.name || "",
                start_date: closeout?.start_date || "",
                end_date: closeout?.end_date || "",
                reason: closeout?.reason || "",
                service_id: closeout?.service_id || "",
                season_id: closeout?.season_id || "",
                team_id: team_id,
                updated_by_id: closeout.updated_by_id || auth?.user?.id,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode,closeout,team_id]);

    useEffect(() => {
        const selectedService = services.find(s => {
            console.log('service', s);
            console.log('service_id ', data.service_id);
            const selected = s.id === parseInt(data.service_id);
            console.log(selected);

            return selected;
        });
        console.log(selectedService);
        const seasons = selectedService ? setSeasons(selectedService.seasons) : [];
        console.log('seasons', selectedService?.seasons);

    }, [data.service_id]);


    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("admin.closeouts.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("admin.closeouts.update", closeout.id), {
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
                            title="Start Date"
                            field="start_date"
                            value={data.start_date}
                            type="date"
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />  
                        </div>
                        <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                            <InputText
                                title="End Date"
                                field="end_date"
                                value={data.end_date}
                                type="date"
                                mode={mode}
                                handleChange={handleChange}
                                errors={errors}
                            />
                            <InputText
                                title="Reason"
                                field="reason"
                                value={data.reason}
                                type="text"
                                mode={mode}
                                handleChange={handleChange}
                                errors={errors}
                            />
                        </div>
                        <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                            <CustomSelect
                                label="Service"
                                name="service_id"
                                value={data.service_id}
                                mode={mode}
                                onChange={(e) => setData(e.target.name, e.target.value)}
                                error={errors.service_id}
                                options={services}
                            />
                            <CustomSelect
                                label="Season"
                                name="season_id"
                                value={data.season_id}
                                mode={mode}
                                onChange={(e) => setData(e.target.name, e.target.value)}
                                error={errors.season_id}
                                options={seasons}
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

export default CloseoutModal;
