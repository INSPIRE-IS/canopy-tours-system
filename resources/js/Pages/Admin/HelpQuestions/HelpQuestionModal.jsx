import React, { useEffect, useState } from "react";
import { router, useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomTextarea from "@/Components/CustomTextarea";
import Tiptap from "@/Components/Tiptap";
import parse from "html-react-parser";


const HelpQuestionModal = ({ isOpen, onClose, mode, helpQuestion = null, helpQuestionFields, fieldValues, auth, team }) => {
    // console.log("mode", mode);
     console.log("helpQuestion", helpQuestion);
    // console.log("roles", roles);
const [answer, setAnswer] = useState("");
const [file, setFile] = useState(null);

    const { data, setData, post, put, errors, reset, processing } = useForm({
        question: "",
        answer: "",
        created_by_id: auth?.user?.id,
        updated_by_id: auth?.user?.id,
    });

    useEffect(() => {
        if (helpQuestion && mode !== "create") {
            setData({
                question: helpQuestion?.question || "",
                answer: helpQuestion?.answer || "",
                created_by_id: helpQuestion.created_by_id || auth?.user?.id,
                updated_by_id: helpQuestion.updated_by_id || auth?.user?.id,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode,helpQuestion,team]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("question", e.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("helpQuestions.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("helpQuestions.update", helpQuestion.id), {
                onSuccess: () => onClose(),
            });
        }
    };



    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Help Question"}
                    {mode === "edit" && "Edit Help Question"}
                    {mode === "view" && "View Help Question"}
                </h2>
                <form onSubmit={handleSubmit}>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Question"
                            field="question"
                            value={data.question}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Answer"
                            field="answer"
                            value={data.answer}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                            isRequired
                            isReadOnly={mode === "view"}
                        />  
                      {/*   <div className="w-full mb-4">
                            <label className="block text-main mb-1">answer</label>
                            <Tiptap setAnswer={setAnswer} />                           
                        </div>*/}
                    {/*  show the answer in a textarea
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <div className="ProseMirror">{parse(answer)}</div>
                    </div>      
                    */}                        

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

export default HelpQuestionModal;
