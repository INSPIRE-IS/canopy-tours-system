import React, { useState } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomSelect from "@/Components/CustomSelect";
import CustomCheckbox2 from "@/Components/CustomCheckbox2";
import BlueButton from "@/Components/BlueButton";
import OutlineButton from "@/Components/OutlineButton";

export default function DynamicForm({ questionnaire, questions }) {
console.log('questionnaire ', questionnaire);

    const { data, setData, post, processing, errors } = useForm({
        questionnaire_id: questionnaire.id,
        answers: {},
    });

    const handleChange = (questionId, value) => {
        setData("answers", {
            ...data.answers,
            [questionId]: value,
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault(); 
        post(route("admin.questionnaires.answers.store"), {
            preserveScroll: true,
        });
    };

    const renderField = (question) => {
        const value = data.answers[question.id] || "";

        switch (question.typeinput.name) {
            case "text":
                return (
                    <InputText
                        title={question.question}
                        field={question.id}
                        value={value}
                        handleChange={(field, val) =>
                            handleChange(question.id, val)
                        }
                        errors={errors[`answers.${question.id}`]}
                    />
                );

            case "textarea":
                return (
                    <textarea
                        className="w-full border rounded p-2"
                        value={value}
                        onChange={(e) =>
                            handleChange(question.id, e.target.value)
                        }
                    />
                );

            case "number":
                return (
                    <input
                        type="number"
                        className="w-full border rounded p-2"
                        value={value}
                        onChange={(e) =>
                            handleChange(question.id, e.target.value)
                        }
                    />
                );

            case "date":
                return (
                    <input
                        type="date"
                        className="w-full border rounded p-2"
                        value={value}
                        onChange={(e) =>
                            handleChange(question.id, e.target.value)
                        }
                    />
                );

            case "time":
                return (
                    <input
                        type="time"
                        className="w-full border rounded p-2"
                        value={value}
                        onChange={(e) =>
                            handleChange(question.id, e.target.value)
                        }
                    />
                );

            case "checkbox":
                return (
                    <CustomCheckbox2
                        label={question.question}
                        checked={!!value}
                        onChange={(e) =>{
                            console.log('e.target.checked ', e.target.checked);
                            console.log('question.id ', question.id);

                            handleChange(question.id, e.target.checked);
                            

                        }
                            
                        }
                    />
                );

            case "radio":
                return (
                    <div>
                        <label className="block font-medium mb-2">
                            {question.question}
                        </label>
                        {(question.options || "").split(",").map((opt, idx) => (
                            <label key={idx} className="mr-4">
                                <input
                                    type="radio"
                                    name={`question_${question.id}`}
                                    value={opt.trim()}
                                    checked={value === opt.trim()}
                                    onChange={() =>
                                        handleChange(question.id, opt.trim())
                                    }
                                />
                                <span className="ml-1">{opt.trim()}</span>
                            </label>
                        ))}
                    </div>
                );

            case "select":
                return (
                    <CustomSelect
                        label={question.question}
                        name={`question_${question.id}`}
                        value={value}
                        options={(question.options || "")
                            .split(",")
                            .map((opt) => ({ id: opt.trim(), name: opt.trim() }))}
                        onChange={(e) =>
                            handleChange(question.id, e.target.value)
                        }
                    />
                );

            default:
                return (
                    <InputText
                        title={question.question}
                        field={question.id}
                        value={value}
                        handleChange={(field, val) =>
                            handleChange(question.id, val)
                        }
                        errors={errors[`answers.${question.id}`]}
                    />
                );
        }
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <h2 className="text-xl font-bold mb-4">
                {questionnaire.name}
            </h2>

            {questions.map((q) => (
                <div key={q.id} className="mb-4">
                    {renderField(q)}
                </div>
            ))}

            <div className="flex justify-center gap-4">
                <OutlineButton title="Cancel" onClick={() => window.history.back()} />
                <BlueButton
                    title="Submit"
                    type="submit"
                    disabled={processing}
                />
            </div>
        </form>
    );
}
