import React, { useEffect } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import InputRichText from "@/Components/InputRichText";

const BoardingPassModal = ({
    isOpen,
    onClose,
    mode,
    boardingpass = null,
    services = [],
    seasons = [],
    sectionTitles = [],
    sectionIcons = [],
    sectionTitleIconMap = {},
    user,
}) => {
    const { data, setData, post, put, errors, reset, processing } = useForm({
        name: "",
        location: "",
        address: "",
        footer: "",
        service_id: "",
        season_id: "",
        team_id: user?.team_id ?? null,
        sections: [],
    });
    useEffect(() => {
        if (boardingpass && mode !== "create") {
            setData({
                name: boardingpass.name || "",
                location: boardingpass.location || "",
                address: boardingpass.address || "",
                footer: boardingpass.footer || "",
                service_id: boardingpass.service_id ?? "",
                season_id: boardingpass.season_id ?? "",
                team_id: user?.team_id ?? boardingpass.team_id ?? null,
                sections: (boardingpass.sections || []).map((section) => ({
                    title: section.title || "",
                    icon_path: section.icon_path || "",
                    items: Array.isArray(section.items) ? section.items : [],
                })),
            });
        } else if (mode === "create") {
            reset();
        }
    }, [mode, boardingpass, reset, setData, user]);

    const handleChange = (target, value) => {
        setData(target, value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("admin.boardingpass.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("admin.boardingpass.update", boardingpass.id), {
                onSuccess: () => onClose(),
            });
        }
    };

    if (!isOpen) return null;

    const filteredSeasons = data.service_id
        ? seasons.filter((season) => String(season.service_id) === String(data.service_id))
        : seasons;

    const iconPathById = Object.fromEntries(
        sectionIcons.map((icon) => [icon.id, icon.path])
    );
    const iconIdByPath = Object.fromEntries(
        sectionIcons.map((icon) => [icon.path, icon.id])
    );

    const updateSection = (index, nextSection) => {
        const updated = [...(data.sections || [])];
        updated[index] = nextSection;
        setData("sections", updated);
    };

    const addSection = () => {
        setData("sections", [
            ...(data.sections || []),
            { title: "", icon_path: "", items: [] },
        ]);
    };

    const removeSection = (index) => {
        const updated = (data.sections || []).filter((_, i) => i !== index);
        setData("sections", updated);
    };

    const handleSectionTitleChange = (index, titleKey) => {
        const current = data.sections?.[index] || {};
        const next = { ...current, title: sectionTitles.find((t) => t.id === titleKey)?.name || "" };
        const mappedIconId = sectionTitleIconMap?.[titleKey];
        if (mappedIconId && iconPathById[mappedIconId]) {
            next.icon_path = iconPathById[mappedIconId];
        }
        updateSection(index, next);
    };

    const handleSectionIconChange = (index, iconId) => {
        const current = data.sections?.[index] || {};
        updateSection(index, { ...current, icon_path: iconPathById[iconId] || "" });
    };

    const handleSectionItemsChange = (index, text) => {
        const items = text
            .split("\n")
            .map((line) => line.trim())
            .filter(Boolean);
        const current = data.sections?.[index] || {};
        updateSection(index, { ...current, items });
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Boarding Pass"}
                    {mode === "edit" && "Edit Boarding Pass"}
                    {mode === "view" && "View Boarding Pass"}
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
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Location"
                            field="location"
                            value={data.location}
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
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <CustomSelect
                            label="Service"
                            name="service_id"
                            value={data.service_id}
                            mode={mode}
                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            errors={errors}
                            options={services}
                        />
                        <CustomSelect
                            label="Season"
                            name="season_id"
                            value={data.season_id}
                            mode={mode}
                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            errors={errors}
                            options={filteredSeasons}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputRichText
                            title="Footer"
                            field="footer"
                            value={data.footer}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>

                    <div className="mb-6">
                        <div className="flex items-center justify-between mb-2">
                            <h3 className="text-lg font-semibold">Sections</h3>
                            {mode !== "view" && (
                                <button
                                    type="button"
                                    onClick={addSection}
                                    className="text-blue-600 hover:text-blue-800"
                                >
                                    Add Section
                                </button>
                            )}
                        </div>
                        {(data.sections || []).map((section, index) => (
                            <div key={index} className="border border-gray-200 rounded-lg p-4 mb-4">
                                <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                                    <CustomSelect
                                        label="Section Title"
                                        name={`sections.${index}.title`}
                                        value={sectionTitles.find((t) => t.name === section.title)?.id || ""}
                                        mode={mode}
                                        onChange={(e) => handleSectionTitleChange(index, e.target.value)}
                                        errors={errors}
                                        options={sectionTitles}
                                    />
                                    <CustomSelect
                                        label="Icon"
                                        name={`sections.${index}.icon`}
                                        value={iconIdByPath[section.icon_path] || ""}
                                        mode={mode}
                                        onChange={(e) => handleSectionIconChange(index, e.target.value)}
                                        errors={errors}
                                        options={sectionIcons}
                                    />
                                </div>
                                <div className="mb-4">
                                    <label className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]">
                                        Items (one per line)
                                    </label>
                                    <textarea
                                        className="mt-1 block w-full h-32 border border-gray-300 rounded-[10px] p-2"
                                        value={(section.items || []).join("\n")}
                                        onChange={(e) => handleSectionItemsChange(index, e.target.value)}
                                        disabled={mode === "view"}
                                    />
                                </div>
                                {mode !== "view" && (
                                    <div className="flex justify-end">
                                        <button
                                            type="button"
                                            onClick={() => removeSection(index)}
                                            className="text-red-600 hover:text-red-800"
                                        >
                                            Remove Section
                                        </button>
                                    </div>
                                )}
                            </div>
                        ))}
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

export default BoardingPassModal;
