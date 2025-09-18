import React, { useState } from "react";
import DynamicForm from "@/Components/DynamicForm";

export default function Show({ questionnaire, questions }) {
console.log('questionnaire ', questionnaire);
console.log('questions ', questions);



    return (
        <div className="flex items-center justify-center w-[70%] h-[70%] bg-green-300 p-6 shadow-md">
            <DynamicForm questionnaire={questionnaire} questions={questions} />
        </div>
    );
}
