import React, { useEffect } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomSelect from "@/Components/CustomSelect1";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import DatePicker from "@/Components/DatePicker";
import CustomDropdown from "@/Components/CustomDropdown";

const SeasonModal = ({ isOpen, onClose, mode, season = null, services , auth, team}) => {
    // console.log("mode", mode);
     console.log("season", season);
     console.log("services", services);
    const { data, setData, post, put, errors, reset, processing } = useForm({
        service_id: "",
        name: "",
        start_date: "",
        end_date: "",
        rake_rate_adult: null,
        rake_rate_child: null,
        rake_rate_guide: null,
        allow_internal_price_override: 0,
        created_by_id: auth?.user?.id,
        updated_by_id: auth?.user?.id,

    });
    useEffect(() => {
        if (season && mode !== "create") {
            setData({
                service_id: season.service_id || "",
                name: season.name || "",
                start_date: season.start_date || "",
                end_date: season.end_date || "",
                rake_rate_adult: season.rake_rate_adult || 0,
                rake_rate_child: season.rake_rate_child || 0,
                rake_rate_guide: season.rake_rate_guide || 0,
                allow_internal_price_override: season.allow_internal_price_override || 0,
                updated_by_id: auth?.user?.id,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode,season,team]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("seasons.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("seasons.update", season.id), {
                onSuccess: () => onClose(),
            });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Season"}
                    {mode === "edit" && "Edit Season"}
                    {mode === "view" && "View Season"}
                </h2>
                <form onSubmit={handleSubmit}>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                         <CustomSelect
                            label="Service"
                            name="service_id"
                            value={data.service_id}
                            mode={mode}
                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            error={errors.service_id}
                            options={services}
                        />  
                        <InputText
                            title="name"
                            field="name"
                            value={data.name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <DatePicker label="Start Date" name="start_date" onChange={handleChange} errors={errors} />
                        <DatePicker label="End Date" name="end_date" onChange={handleChange} errors={errors} />
                    </div>
<div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                    <InputText
                        title="Rake Rate Adult"
                        field="rake_rate_adult"
                        value={data.rake_rate_adult}
                        mode={mode}
                        handleChange={handleChange}
                        errors={errors}
                    />
                    <InputText
                        title="Rake Rate Child"
                        field="rake_rate_child"
                        value={data.rake_rate_child}
                        mode={mode}
                        handleChange={handleChange}
                        errors={errors}
                    />
                </div>
                <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                    <InputText
                        title="Rake Rate Guide"
                        field="rake_rate_guide"
                        value={data.rake_rate_guide}
                        mode={mode}
                        handleChange={handleChange}
                        errors={errors}
                    />
                    <div className="mb-4 w-full"></div>
                </div>
                <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                    <CustomCheckbox
                        label="Allow Internal Price Override"
                        name="allow_internal_price_override"
                        checked={data.allow_internal_price_override}
                        mode={mode}
                        onChange={handleChange}
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

export default SeasonModal;
