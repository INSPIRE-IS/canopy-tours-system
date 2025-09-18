import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";

const QuestionnaireModal = ({ isOpen, onClose, mode, questionnaire = null,inputTypes,dataTypes,  users, team_id }) => {
    // console.log("mode", mode);
     console.log("questionnaire", questionnaire);
    // console.log("roles", roles);
    const { data, setData, post, put, errors, reset, processing } = useForm({
        name: "",
        description: "",
        fields: [], // array of question fields
        team_id: team_id,
    });
    useEffect(() => {
        if (questionnaire && mode !== "create") {
            setData({
                name: questionnaire.name || "",
                description: questionnaire.description || "",
                fields: questionnaire.fields || [],
                team_id: questionnaire.team_id || null,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode,questionnaire,team_id]);

    // Handle parent form fields (title, description)
    const handleChange = (target, value) => {
        setData(target, value);
    };

    // Handle adding a new field/question
    const addField = () => {
        setData("fields", [
            ...data.fields,
            {
                label: "",
                input_type: "1",
                data_type: "1",
                required: false,
                options: "" // comma separated if dropdown/radio/checkbox
            }
        ]);
    };

    // Handle updating a field
    const updateField = (index, key, value) => {
        const newFields = [...data.fields];
        newFields[index][key] = value;
        setData("fields", newFields);
    };

    // Handle removing a field
    const removeField = (index) => {
        const newFields = data.fields.filter((_, i) => i !== index);
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
                    <InputText
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
                    />

                    {/* Questions / Fields */}
                    <div className="mt-6">
                        <h3 className="font-semibold text-lg mb-2">Questions</h3>
                        {data.fields.map((field, index) => {
                            console.log('field ', field);
                            console.log('index ', index);
                        return (
                            <div key={index} className="border rounded p-4 mb-3">
                                <div className="flex gap-2 mb-2">
                                    <InputText
                                        title="Label"
                                        field="label"
                                        value={field.label}
                                        height="h-[48px]"
                                        mode={mode}
                                        handleChange={(key, val) => updateField(index, key, val)}
                                        errors={errors[`fields.${index}.label`]}
                                    />
                                    <CustomSelect
                                        label="Input Type"
                                        name="input_type"
                                        value={field.input_type}
                                        mode={mode}
                                        onChange={(e) => updateField(index, "input_type", e.target.value)}
                                        options={inputTypes}
                                        //options={inputTypes.map((t) => ({ id: t, name: t }))}
                                    />
                                    <CustomSelect
                                        label="Data Type"
                                        name="data_type"
                                        value={field.data_type}
                                        mode={mode}
                                        onChange={(e) => updateField(index, "data_type", e.target.value)}
                                        options={dataTypes}
                                        //options={dataTypes.map((t) => ({ id: t, name: t }))}
                                    />
                                </div>

                                {/* Options if type requires them select radio checkbox*/}
                                {(field.input_type === "6" || field.input_type === "7" || field.input_type === "8") && (
                                    <InputText
                                        title="Options (comma-separated)"
                                        field="options"
                                        value={field.options}
                                        mode={mode}
                                        handleChange={(key, val) => updateField(index, key, val)}
                                        errors={errors[`fields.${index}.options`]}
                                    />
                                )}

                                <div className="flex justify-end">
                                    <OutlineButton
                                        title="Remove"
                                        onClick={() => removeField(index)}
                                    />
                                </div>
                            </div>
                        )})}

                        <div className="mt-2">
                            <BlueButton title="+ Add Question" onClick={addField} />
                        </div>
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
