import React, { useEffect, useState } from "react";
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
    sectionTitleIcons = [],
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
    const [selectedSectionKey, setSelectedSectionKey] = useState("");
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
                    section_tag: section.section_tag || tagByTitle[section.title] || "",
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

    const titleByTag = Object.fromEntries(
        sectionTitleIcons.map((entry) => [entry.tag, entry.name])
    );
    const tagByTitle = Object.fromEntries(
        sectionTitleIcons.map((entry) => [entry.name, entry.tag])
    );
    const iconByTag = Object.fromEntries(
        sectionTitleIcons.map((entry) => [entry.tag, entry.path])
    );
    const updateSection = (index, nextSection) => {
        const updated = [...(data.sections || [])];
        updated[index] = nextSection;
        setData("sections", updated);
    };

    const addSection = () => {
        if (!selectedSectionKey) return;
        const titleLabel = titleByTag[selectedSectionKey] || "";
        const iconPath = iconByTag[selectedSectionKey] || "";
        const exists = (data.sections || []).some((section) => section.section_tag === selectedSectionKey);
        if (exists) return;
        setData("sections", [
            ...(data.sections || []),
            { section_tag: selectedSectionKey, title: titleLabel, icon_path: iconPath, items: [] },
        ]);
        setSelectedSectionKey("");
    };

    const removeSection = (index) => {
        const updated = (data.sections || []).filter((_, i) => i !== index);
        setData("sections", updated);
    };

    const handleSectionItemChange = (index, itemIndex, value) => {
        const current = data.sections?.[index] || {};
        const items = Array.isArray(current.items) ? [...current.items] : [];
        items[itemIndex] = value;
        updateSection(index, { ...current, items });
    };

    const addSectionItem = (index) => {
        const current = data.sections?.[index] || {};
        const items = Array.isArray(current.items) ? [...current.items] : [];
        items.push("");
        updateSection(index, { ...current, items });
    };

    const removeSectionItem = (index, itemIndex) => {
        const current = data.sections?.[index] || {};
        const items = Array.isArray(current.items)
            ? current.items.filter((_, i) => i !== itemIndex)
            : [];
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
                        </div>
                        {mode !== "view" && (
                            <div className="flex items-center gap-2 mb-4">
                                <CustomSelect
                                    label="Section"
                                    name="section_picker"
                                    value={selectedSectionKey}
                                    mode={mode}
                                    onChange={(e) => setSelectedSectionKey(e.target.value)}
                                    options={sectionTitleIcons.map((entry) => ({
                                        id: entry.tag,
                                        name: entry.name,
                                    }))}
                                />
                                <button
                                    type="button"
                                    onClick={addSection}
                                    className="mt-6 text-blue-600 hover:text-blue-800"
                                >
                                    Add
                                </button>
                            </div>
                        )}
                        {(data.sections || []).map((section, index) => (
                            <div key={index} className="border border-gray-200 rounded-lg p-4 mb-4">
                                <div className="flex flex-row w-full items-center gap-4 justify-start mb-4">
                                    <div className="w-full">
                                        <label className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]">
                                            Section Title
                                        </label>
                                        <input
                                            type="text"
                                            className="mt-1 block w-full h-[42px] border border-gray-300 rounded-[10px] px-3"
                                            value={section.title || ""}
                                            onChange={(e) =>
                                                updateSection(index, {
                                                    ...section,
                                                    title: e.target.value,
                                                })
                                            }
                                            disabled={mode === "view"}
                                        />
                                    </div>
                                    {section.icon_path && (
                                        <div className="flex items-center gap-2 mt-6">
                                            <span className="text-xs text-gray-500">Icon</span>
                                            <img src={section.icon_path} alt="icon" className="w-6 h-6" />
                                        </div>
                                    )}
                                </div>
                                <div className="mb-4">
                                    <label className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]">
                                        Items
                                    </label>
                                    <div className="mt-2 flex flex-col gap-2">
                                        {(section.items || []).map((item, itemIndex) => (
                                            <div key={itemIndex} className="flex items-center gap-2">
                                                <input
                                                    type="text"
                                                    className="block w-full h-[42px] border border-gray-300 rounded-[10px] px-3"
                                                    value={item}
                                                    onChange={(e) => handleSectionItemChange(index, itemIndex, e.target.value)}
                                                    disabled={mode === "view"}
                                                />
                                                {mode !== "view" && (
                                                    <button
                                                        type="button"
                                                        onClick={() => removeSectionItem(index, itemIndex)}
                                                        className="text-red-600 hover:text-red-800"
                                                    >
                                                        Remove
                                                    </button>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                    {mode !== "view" && (
                                        <button
                                            type="button"
                                            onClick={() => addSectionItem(index)}
                                            className="mt-2 text-blue-600 hover:text-blue-800"
                                        >
                                            Add Item
                                        </button>
                                    )}
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
