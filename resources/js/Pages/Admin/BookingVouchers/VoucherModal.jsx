import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomInputText from "@/Components/CustomInputText";

const VoucherModal = ({ isOpen, onClose, mode, voucher = null, services, user, teams }) => {
    // console.log("mode", mode);
    console.log("voucher", voucher);
    const [seasons, setSeasons] = useState([]);
    const { data, setData, post, put, errors, reset, processing } = useForm({
        service_id: '',
        season_id: '',
        name: '',
        items: [
            {
                name: 'Welcome Message',
                helper: 'This is the welcome message for the voucher',
                description: '',
                disabled: false,
                type: 'textarea',
                format: 'paragraph',
            },
            {
                name: 'Reference',
                helper: 'This is the unique reference for the voucher',
                description: '{{reference}}',
                disabled: true,
                type: 'text',
                format: 'table',
            },
            {
                name: 'Provider',
                helper: 'This is the name of the provider',
                description: '{{provider}}',
                disabled: true,
                type: 'text',
                format: 'table',
            },
            {
                name: 'Service',
                helper: 'This is the name of the service',
                description: '{{service}}',
                disabled: true,
                type: 'text',
                format: 'table',
            },
            {
                name: 'Location',
                helper: 'This is the location of the service',
                description: '',
                disabled: false,
                type: 'textarea',
                format: 'table',
            },
            {
                name: 'What to Wear',
                helper: 'This is the what to wear for the service',
                description: '',
                disabled: false,
                type: 'textarea',
                format: 'table',
            },
            {
                name: 'Starting Time and Date',
                helper: 'This is the starting time and date for the service',
                description: '{{start_time_and_date}}',
                disabled: true,
                type: 'text',
                format: 'table',
            },
            {
                name: 'Starting Location',
                helper: 'This is the starting location for the service',
                description: '',
                disabled: false,
                type: 'textarea',
                format: 'table',
            },
            {
                name: 'Ending Location',
                helper: 'This is the ending location for the service',
                description: '',
                disabled: false,
                type: 'textarea',
                format: 'table',
            },
            {
                name: 'Lead Passenger Name',
                helper: 'This is the lead name for the service',
                description: '{{lead_name}}',
                disabled: true,
                type: 'text',
                format: 'table',
            },
            {
                name: 'Lead Passenger Email',
                helper: 'This is the lead email for the service',
                description: '{{lead_email}}',
                disabled: true,
                type: 'email',
                format: 'table',
            },
            {
                name: 'Number in Party',
                helper: 'This is the number in party for the service',
                description: '{{number_in_party}}',
                disabled: true,
                type: 'text',
                format: 'table',
            },
            {
                name: 'Supplier Email',
                helper: 'This is the supplier email for the service',
                description: '',
                disabled: false,
                type: 'email',
                format: 'table',
            },
            {
                name: 'Supplier Landline',
                helper: 'This is the supplier landline for the service',
                description: '',
                disabled: false,
                type: 'text',
                format: 'table',
            },
            {
                name: 'Supplier Mobile',
                helper: 'This is the supplier mobile for the service',
                description: '',
                disabled: false,
                type: 'text',
                format: 'table',
            },
        ],
        team_id: user?.team_id || null,
    });
    useEffect(() => {
        if (voucher && mode !== "create") {
            setData({
                service_id: voucher.service_id || null,
                season_id: voucher.season_id || null,
                name: voucher.name || "",
                items: voucher.items || [],
                team_id: voucher.team_id || null,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode, voucher, user]);

    useEffect(() => {

        if (data.service_id) {
            setSeasons([]);
            const filtered = services.filter(
                (item) => item.id == data.service_id
            );
            const seasons = filtered.map((item) => item.seasons).flat();
            console.log('seasons', seasons);
            setSeasons(seasons || []);
        } else {
            setSeasons([]);
        }
    }, [data.service_id]);

    const handleChange = (target, value) => {
        setData(target, value);
    };

    const addItem = () => {
        setData('items', [...data.items, { name: '', helper: 'Additional Item', description: '', disabled: false, type: 'textarea', format: 'table' }]);
    };
    const removeItem = (index) => {
        const updatedItems = data.items.filter((_, i) => i !== index);
        setData("items", updatedItems);
    };
    // always keep at least one item
    // const removeItem = (index) => {
    //     if (data.items.length <= 1) return; // prevent empty array
    //     setData("items", data.items.filter((_, i) => i !== index));
    // };
    // protect default placeholders
    // const removeItem = (index) => {
    //     const item = data.items[index];
    //     const protected = ['Reference', 'Provider', 'Service', 'Location', 'What to Wear'];
    //     if (protected.includes(item.name)) return; // skip removing these
    //     setData("items", data.items.filter((_, i) => i !== index));
    // };

    const handleItemChange = (index, field, value) => {
        const updatedItems = [...data.items];
        updatedItems[index][field] = value;
        setData('items', updatedItems);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("admin.booking_vouchers.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("admin.booking_vouchers.update", voucher.id), {
                onSuccess: () => onClose(),
            });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Booking Voucher"}
                    {mode === "edit" && "Edit Booking Voucher"}
                    {mode === "view" && "View Booking Voucher"}
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
                        <CustomSelect
                            label="Season"
                            name="season_id"
                            value={data.season_id}
                            mode={mode}
                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            error={errors.season_id}
                            options={seasons}
                        />
                        <CustomInputText
                            title="Quick Reference Name"
                            name="name"
                            value={data.name}
                            mode={mode}
                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            errors={errors}
                        />
                    </div>
                    <div className="mb-4">
                        <div className="flex justify-between items-center mb-2">
                            <h3 className="font-semibold">Detail</h3>
                            <button
                                type="button"
                                onClick={addItem}
                                className="text-blue-600 font-medium hover:underline"
                            >
                                + Add Item
                            </button>
                        </div>
                        {data.items.map((item, index) => (
                            <div key={index} className="border p-3 mb-2 rounded">
                                <div className="flex justify-start items-center gap-2">
                                    {/* <input
                                        type="text"
                                        placeholder="Name"
                                        disabled={item.disabled}
                                        value={item.name}
                                        onChange={(e) => handleItemChange(index, 'name', e.target.value)}
                                        className="border-b mb-2 p-1 w-[80%]"
                                    /> */}
                                    <CustomInputText
                                        name="name"
                                        width="w-[80%]"
                                        height="h-[42px]"
                                        disabled={item.disabled}
                                        value={item.name}
                                        mode={mode}
                                        onChange={(e) => handleItemChange(index, 'name', e.target.value)}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => removeItem(index)}
                                        className="inline-flex items-center px-4 py-2 mb-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Remove
                                    </button>
                                </div>
                                {item.helper && (
                                    <p className="text-sm text-gray-400">{item.helper}</p>
                                )}
                                {item.type === 'textarea' && (
                                    <textarea
                                        placeholder="Description"
                                        value={item.description}
                                        onChange={(e) => handleItemChange(index, 'description', e.target.value)}
                                        className="border w-full p-1 rounded"
                                    />
                                )}
                                {(item.type === 'text' || item.type === 'email') && (
                                    // <input
                                    //     type="text"
                                    //     placeholder="Description"
                                    //     disabled={item.disabled}
                                    //     value={item.description}
                                    //     onChange={(e) => handleItemChange(index, 'description', e.target.value)}
                                    //     className="border w-full p-1 rounded"
                                    // />
                                    <CustomInputText
                                        name="description"
                                        type={item.type}
                                        width="w-full"
                                        height="h-[42px]"
                                        disabled={item.disabled}
                                        value={item.description}
                                        mode={mode}
                                        onChange={(e) => handleItemChange(index, 'description', e.target.value)}
                                    />

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

export default VoucherModal;
