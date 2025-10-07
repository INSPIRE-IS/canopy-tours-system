import React, { useEffect, useState } from "react";
import InvoiceTemplateEditor from '@/Components/InvoiceTemplateEditor';
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";





const TemplateModal = ({ isOpen, onClose, mode, template = null, typeInvoices, users, team }) => {
    // console.log("mode", mode);
    //console.log("template", template);
    console.log("type invoices", typeInvoices);
    const { data, setData, post, put, errors, reset, processing } = useForm({
        name: '',
        type: 'invoice',
        content: '',
        is_default: false,
    });

    useEffect(() => {
        if (template && mode !== "create") {

            const content = template.content || "";
            console.log('content ', content);

            setData({
                name: template.name || "",
                type: template.type || "",
                content: content,
                is_default: template.is_default || false,

            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode, template, team]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("admin.invoice-templates.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("admin.invoice-templates.update", template.id), {
                onSuccess: () => onClose(),
            });
        }
    };

    const [loadingTemplate, setLoadingTemplate] = useState(false);

    const loadStarterTemplate = async () => {
        try {
            setLoadingTemplate(true);
            const res = await fetch(`/templates/${data.type}.html`);
            console.log('res', res);
            if (!res.ok) throw new Error('Template not found');
            const html = await res.text();
            console.log('html', html);
            setData('content', html);
        } catch (err) {
            console.error('Failed to load template', err);
        } finally {
            setLoadingTemplate(false);
        }
    };
    function showOutput() {
        const html = tinymce.get("editor").getContent();
        document.getElementById("htmlOutput").textContent = html;
        document.getElementById("renderedOutput").innerHTML = html;
    }


    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[60%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create User Alert"}
                    {mode === "edit" && "Edit User Alert"}
                    {mode === "view" && "View User Alert"}
                </h2>
                <form onSubmit={handleSubmit}>
                    <div className="mb-4">
                        <label className="block">Template Name</label>
                        <input
                            type="text"
                            className="input"
                            value={data.name}
                            onChange={e => setData('name', e.target.value)}
                        />
                        {errors.name && <p className="text-red-500 text-sm">{errors.name}</p>}
                    </div>

                    <div className="mb-4 flex items-center gap-2">
                        <div className="flex-1 ">
                            <label className="block">Select Invoice Type</label>
                            <select
                                className={`mt-1 block  w-[80%] border-gray-300 shadow-sm input-text rounded-[10px]
                                                hover:border-black  active:border-blue-600 
                                        focus:outline-none focus:ring-0 focus:border-black
                                `}
                                name="type"
                                value={data.type}
                                onChange={e => setData('type', e.target.value)}
                            >
                                {typeInvoices?.map((option, index) => {
                                    //console.log('option', option);
                                    const value = typeof option === "object" ? option.type : option;
                                    const label = typeof option === "object" ? option.name : option;
                                    return (
                                        <option key={index} value={value} className="input-text">
                                            {label}
                                        </option>
                                    )
                                })}

                            </select>
                        </div>
                        <button
                            type="button"
                            className="btn-secondary mt-6 border border-gray-300 rounded-lg px-4 py-2 text-sm"
                            onClick={loadStarterTemplate}
                            disabled={loadingTemplate}
                        >
                            {loadingTemplate ? 'Loading…' : 'Load Starter'}
                        </button>
                    </div>

                    <InvoiceTemplateEditor
                        value={data.content}
                        handleChange={(field, val) => setData(field, val)}
                        errors={errors}
                    />

                    <label className="flex items-center gap-2 mt-4">
                        <input
                            type="checkbox"
                            checked={data.is_default}
                            onChange={e => setData('is_default', e.target.checked)}
                        />
                        Default
                    </label>
                    {/* <button type='button' onClick={showOutput} className="btn-primary mt-4">
                        Show Output
                    </button> */}
                    {/* <button type="submit" className="btn-primary mt-4">
                    Save
                </button> */}
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

export default TemplateModal;
