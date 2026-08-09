import React, { useEffect, useState } from "react";
import { router, useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";

const ManifestsModal = ({ isOpen, onClose, mode, customers, tot_adults, tot_children }) => {

    console.log("customers", customers);
    console.log("mode", mode);

    const [adults, setAdults] = useState([]);

    const { data, setData, post, put, errors, reset, processing } = useForm({
        customer_manifests: [],
    });

    const ageGroupOptions = [
        { id: 1, name: "5-17" },
        { id: 2, name: "18-30" },
        { id: 3, name: "31-50" },
        { id: 4, name: "51-103" },
    ];

    const resolveAgeGroup = (value) => {
        if (value === null || value === undefined || value === "") {
            return "-";
        }

        const match = ageGroupOptions.find((option) => String(option.id) === String(value));

        return match?.name ?? String(value);
    };

    useEffect(() => {
        if (mode === "edit" && customers?.length) {
            // Map API data into form shape
            const mapped = customers.map((m, i) => {
                console.log("m-------", m);
                return ({
                    id: i,
                    customer_id: m.customer_id, // Keep existing link to customer
                    type: m.type,
                    parent_id: m.parent_id ?? "",
                    position: m.position ?? "",
                    notes: m.notes ?? "",
                    is_indemnity: !!m.is_indemnity,
                    full_name: m?.full_name ?? "",
                    email_address: m?.email_address ?? "",
                    nationality_id: m?.nationality_id ?? "",
                    age: m.age ?? null,
                    contact_number: m.contact_number ?? "",
                    booking_item_id: m.booking_item_id ?? "",
                    discount_id: m.discount_id ?? "",
                    discount: m.discount ?? "",
                    due: m.due ?? "",
                    is_lead: m.is_lead ?? "",
                    paid: m.paid ?? "",
                    price: m.price ?? "",
                    reference: m.reference ?? "",
                    team_id: m.team_id ?? "",
                    source: m.source ?? "",
                })
            });
            console.log("mapped", mapped);
            // const adults = data.customer_manifests.filter(p => p.type === "adult");
            // setAdults(adults);
            setData("customer_manifests", mapped);
        } else if (mode === "create") {
            reset(); // Clear form
        }
    }, [mode, customers]);
    useEffect(() => {
        const updatedAdults = data.customer_manifests
            .filter(p => p.type === "adult")
            .map((p, i) => {
                console.log("p", p);
                return ({
                    id: typeof p.customer_id == 'number' ? p.customer_id : i + 1,
                    name: p.full_name || "Unnamed Adult"
                })
            });
        console.log("updatedAdults", updatedAdults);
        setAdults(updatedAdults);
    }, [data.customer_manifests]);
    // Add Adult/Child
    const handleAddPerson = (type) => {
        const adultsCount = data.customer_manifests.filter(p => p.type === "adult").length;
        console.log("adultsCount", adultsCount);
        const childrenCount = data.customer_manifests.filter(p => p.type === "child").length;
        console.log("childrenCount", childrenCount);

        //if (type === "adult" && adultsCount >= tot_adults) return;

        if (type === "child") {
            if (adultsCount === 0) {
                alert("You must add at least one adult before adding a child.");
                return;
            }
            if (childrenCount >= tot_children) return;

            // Get first adult's customer_id or temporary id
            const firstAdult = data.customer_manifests.find(p => p.type === "adult");
            const parentId = firstAdult?.customer_id || firstAdult?.id || null;

            setData("customer_manifests", [
                ...data.customer_manifests,
                {
                    type: "child",
                    parent_id: parentId,
                    position: "",
                    notes: "",
                    is_indemnity: false,
                    full_name: "",
                    email_address: "",
                    nationality_id: "",
                    age: null,
                    contact_number: "",
                    customer_id: null, // new record
                    booking_item_id: bookingItem.id,
                }
            ]);
            return;
        }

        // Default adult adding
        setData("customer_manifests", [
            ...data.customer_manifests,
            {
                type: "adult",
                parent_id: "",
                position: "",
                notes: "",
                is_indemnity: false,
                full_name: "",
                email_address: "",
                nationality_id: "",
                age: null,
                contact_number: "",
                customer_id: null,
                booking_item_id: bookingItem.id,
            }
        ]);
    };


    // const handleAddPerson = (type) => {
    //     const adultsCount = data.customer_manifests.filter((p) => p.type === "adult").length;
    //     const childrenCount = data.customer_manifests.filter((p) => p.type === "child").length;

    //     if (type === "adult" && adultsCount >= tot_adults) return;
    //     if (type === "child" && childrenCount >= tot_children) return;

    //     setData("customer_manifests", [
    //         ...data.customer_manifests,
    //         {
    //             type,
    //             position: "",
    //             notes: "",
    //             is_indemnity: false,
    //             full_name: "",
    //             email_address: "",
    //             nationality_id: "",
    //             age: null,
    //             parent_id: null,
    //         },
    //     ]);
    // };

    // Update person data
    const handlePersonChange = (index, field, value) => {
        const updated = [...data.customer_manifests];
        updated[index][field] = value;
        setData("customer_manifests", updated);
    };

    // Remove person
    const handleRemovePerson = (index) => {
        const updated = [...data.customer_manifests];
        updated.splice(index, 1);
        setData("customer_manifests", updated);
    };

    // Submit
    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("admin.customer-manifests.add"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("admin.customer-manifests.edit", customers[0].booking_item_id), {
                onSuccess: () => onClose(),
            });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Manifest"}
                    {mode === "edit" && "Edit Manifest"}
                    {mode === "view" && "View Manifest"}
                </h2>

                <form onSubmit={handleSubmit}>
                    {/* Dynamic person forms */}
                    {data?.customer_manifests?.map((person, index) => (
                        <div key={index} className="border p-3 mb-4 rounded-lg">
                            <div className="flex justify-between items-center mb-2">
                                <h3 className="font-semibold">
                                    {person.type.toUpperCase()} #{index + 1} {person.is_lead ? "(Lead)" : ""} {person.discount ? "(Discount) " + person.discount : ""}
                                </h3>
                                {/* <button
                                    type="button"
                                    onClick={() => handleRemovePerson(index)}
                                    className="text-red-500 hover:underline"
                                >
                                    Remove
                                </button> */}
                            </div>

                            <InputText
                                title="Full Name"
                                field="full_name"
                                value={person.full_name}
                                mode={mode}
                                handleChange={(f, val) => handlePersonChange(index, f, val)}
                                errors={errors}
                            />

                            <InputText
                                title="Email Address"
                                field="email_address"
                                value={person.email_address}
                                mode={mode}
                                handleChange={(f, val) => handlePersonChange(index, f, val)}
                                errors={errors}
                            />
                            {person.type === "child" && (
                                <div className="w-full mb-4">
                                    <label
                                        className="text-[#161618] font-poppins text-[12px] font-normal  leading-[130%]"
                                    >
                                        Parent
                                    </label>


                                    <select
                                        id={parent}
                                        name={parent}
                                        value={person.parent_id || ""}
                                        onChange={(e) => {
                                            const updated = data.customer_manifests.map((p, idx) =>
                                                idx === index ? { ...p, parent_id: e.target.value } : p
                                            );
                                            setData("customer_manifests", updated);
                                        }}
                                        className={`mt-1 block shadow-sm input-text rounded-[10px] border-gray-300
                                         hover:border-black  active:border-blue-600 w-full
                                            focus:outline-none focus:ring-0 focus:border-black
                                    `}
                                        disabled={mode === "view"}

                                    >
                                        {adults?.map(adult => {
                                            console.log("adult", adult);
                                            return (
                                                <option className="input-text rounded-[10px] border-gray-300" key={adult.id} value={adult.id}>
                                                    {adult.name}
                                                </option>
                                            )
                                        })}
                                    </select>
                                </div>
                            )}
                            {/* <CustomSelect
                                title="Nationality"
                                field="nationality_id"
                                value={person.nationality_id}
                                options={[]} // Pass nationality options here
                                handleChange={(f, val) => handlePersonChange(index, f, val)}
                            /> */}


                            <div className="flex flex-row w-full items-center justify-start gap-2 mt-2">
                                <CustomCheckbox
                                    label="Indemnity Signed?"
                                    name="is_indemnity"
                                    checked={person.is_indemnity}
                                    onChange={(f, val) => handlePersonChange(index, f, val)}
                                    errors={errors}
                                />
                                <span className="flex flex-row items-center justify-start gap-2">
                                    <span className="text-[#161618] font-poppins text-[12px] font-normal  leading-[130%]">Age group</span>
                                    <span className="min-w-[100px] rounded-[10px] border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                        {resolveAgeGroup(person.age)}
                                    </span>
                                </span>
                                <span className=" flex flex-row items-center justify-start gap-2">
                                    <span className="text-[#161618] font-poppins text-[12px] font-normal  leading-[130%]">Position</span>
                                    <InputText
                                        field="position"
                                        value={person.position}
                                        width="w-100px"
                                        mode={mode}
                                        handleChange={(f, val) => handlePersonChange(index, f, val)}
                                        errors={errors}
                                    />
                                </span>
                            </div>

                            <InputText
                                title="Notes"
                                field="notes"
                                value={person.notes}
                                mode={mode}
                                handleChange={(f, val) => handlePersonChange(index, f, val)}
                                errors={errors}
                            />
                        </div>
                    ))}

                    {/* Footer buttons */}
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

export default ManifestsModal;
