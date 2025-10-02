import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomButton from "@/Components/CustomButton";

const QuestionnaireModal = ({ isOpen, onClose, mode, questionnaire = null, team_id, services, questionnaireFields }) => {
    // console.log("mode", mode);
    console.log("questionnaire", questionnaire);
    const [filteredSeasons, setFilteredSeasons] = useState([]);
    // console.log("roles", roles);
    const { data, setData, post, put, errors, reset, processing } = useForm({
        name: "",
        description: "",
        fields: [], // array of question fields
        team_id: team_id,
        service_id: null,
        season_id: null,
    });
    useEffect(() => {
        if (questionnaire && mode !== "create") {
            const normalizedFields = questionnaire.fields.map(f => {
                return {
                    ...f,
                    questionnaire_id: f.pivot?.questionnaire_id ?? null,
                    questionnaire_field_id: f.pivot?.questionnaire_field_id ?? f.id,
                    required: f.pivot?.required ?? true,
                    visable: f.pivot?.visable ?? 1,
                    sort_order: f.pivot?.sort_order ?? 0,
                };
            });

            setData({
                name: questionnaire.name || "",
                description: questionnaire.description || "",
                fields: normalizedFields,
                team_id: questionnaire.team_id || null,
                service_id: questionnaire.service_id || null,
                season_id: questionnaire.season_id || null,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
            if (!questionnaireFields) return;
            console.log('questionnaireFields ', questionnaireFields);
            const normalizedFields = questionnaireFields?.map((f, i) => {
                return {
                    ...f,
                    questionnaire_id: null,
                    questionnaire_field_id: f.id,
                    required: true,
                    visable: 1,
                    sort_order: i,
                };
            });
            console.log('normalizedFields ', normalizedFields);
            setData({
                name: "",
                description: "",
                fields: normalizedFields,
                team_id: team_id,
                service_id: null,
                season_id: null,
            });

        }
    }, [mode, questionnaire, questionnaireFields, team_id]);
    useEffect(() => {
        console.log('data.service_id', data.service_id);

        if (data.service_id) {
            const filtered = services.filter(
                (item) => item.id == data.service_id
            );
            console.log('filtered', filtered);
            setData("name", filtered?.name);
            const seasons = filtered[0]?.seasons || [];
            setFilteredSeasons(seasons);
        } else {
            setFilteredSeasons([]);
        }
    }, [data.service_id]);

    // Handle parent form fields (title, description)
    const handleChange = (target, value) => {
        setData(target, value);
    };

    // Handle updating a field
    const updateField = (index, key, value) => {
        const newFields = [...data.fields];
        newFields[index][key] = value;
        setData("fields", newFields);
    };


    // Submit form
    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("admin.questionnaire.store"), {
                onSuccess: () => onClose()
            });
        } else if (mode === "edit") {
            put(route("admin.questionnaire.update", questionnaire.id), {
                onSuccess: () => onClose()
            });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Questionnaire"}
                    {mode === "edit" && "Edit Questionnaire"}
                    {mode === "view" && "View Questionnaire"}
                </h2>
                <form onSubmit={handleSubmit}>
                    {/* Questionnaire details */}
                    {/* <InputText
                        title="Title"
                        field="name"
                        value={data.name}
                        height="h-[48px]"
                        mode={mode}
                        handleChange={handleChange}
                        errors={errors}
                    />

                    <InputText
                        title="Description"
                        field="description"
                        value={data.description}
                        height="h-[48px]"
                        mode={mode}
                        handleChange={handleChange}
                        errors={errors}
                    /> */}
                    <CustomSelect
                        label="services"
                        name="service_id"
                        value={data.service_id}
                        mode={mode}
                        onChange={(e) => handleChange(e.target.name, e.target.value)}
                        error={errors.service_id}
                        options={services}
                    />
                    <CustomSelect
                        label="Season/Name"
                        name="season_id"
                        value={data.season_id}
                        mode={mode}
                        onChange={(e) => handleChange(e.target.name, e.target.value)}
                        error={errors.season_id}
                        options={filteredSeasons}
                    />
                    {/* Questions / Fields */}
                    <div className="mt-6">
                        <h3 className="font-semibold text-lg mb-2">Questions</h3>
                        {data.fields.map((field, index) => {
                            // console.log('field ', field);
                            // console.log('index ', index);
                            return (
                                <div key={index} className="border rounded p-4 mb-3">
                                    <div className="flex gap-2 mb-2 justify-around">
                                        <InputText
                                            title="Question"
                                            field="question"
                                            value={field.question}
                                            height="h-[42px]"
                                            mode={mode}
                                            handleChange={(key, val) => updateField(index, key, val)}
                                            errors={errors[`fields.${index}.label`]}
                                        />
                                        <InputText
                                            title="Sort Order"
                                            field="sort_order"
                                            width="w-[75px]"
                                            value={field.sort_order}
                                            mode={mode}
                                            handleChange={(key, val) => updateField(index, key, val)}
                                            errors={errors[`fields.${index}.sort_order`]}
                                        />

                                        <div className="mt-7 flex flex-row gap-2 items-start justify-start ">
                                            <CustomButton
                                                title={field.required ? "required" : "not required"}
                                                bgColor={field.required ? "bg-green-500" : "bg-gray-400"}
                                                textColor="text-white"
                                                height="h-[41px]"
                                                width="w-[169px]"
                                                type="button"
                                                icon={field.required ? <img src="/assets/icons/eye.svg" alt="eye" className="w-5 h-5" /> : <img src="/assets/icons/eye-slash.svg" alt="eye-slash" className="w-5 h-5" />}
                                                hoverColor="bg-green-600"
                                                hoverTextColor="text-white"
                                                onClick={() => updateField(index, "required", !field.required)}
                                            />
                                            <CustomButton
                                                title={field.visable ? "Show" : "Hide"}
                                                bgColor={field.visable ? "bg-green-500" : "bg-gray-400"}
                                                textColor="text-white"
                                                height="h-[41px]"
                                                width="w-[169px]"
                                                type="button"
                                                icon={field.visable ? <img src="/assets/icons/eye.svg" alt="eye" className="w-5 h-5" /> : <img src="/assets/icons/eye-slash.svg" alt="eye-slash" className="w-5 h-5" />}
                                                hoverColor="bg-green-600"
                                                hoverTextColor="text-white"
                                                onClick={() => updateField(index, "visable", !field.visable)}
                                            />
                                        </div>
                                    </div>
                                </div>
                            )
                        })}
                    </div>

                    {/* Actions */}
                    <div className="flex justify-center gap-4 mt-6">
                        <OutlineButton title="Cancel" onClick={onClose} width="w-[180px]" />
                        {mode !== "view" && (
                            <BlueButton
                                title={mode === "create" ? "Create" : "Update"}
                                width="w-[180px]"
                                onClick={handleSubmit}
                                disabled={processing}
                                type="submit"
                            />
                        )}
                    </div>
                </form>
            </div>
        </div>
    );
};

export default QuestionnaireModal;
