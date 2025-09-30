import React, { useEffect, useState } from "react";
import { router, useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";

const ManifestModalOld = ({ isOpen, onClose, mode, customer_manifests, pax, tot_adults, tot_children }) => {

    console.log("customer_manifests", customer_manifests);
    console.log("mode", mode);

    const [adults, setAdults] = useState([]);

    const { data, setData, post, put, errors, reset, processing } = useForm({
        customer_manifests: [],
    });

    useEffect(() => {
        if (mode === "edit" && customer_manifests?.length) {
            // Map API data into form shape
            const mapped = customer_manifests.map((m) => {
                console.log("m-------", m);
                return ({
                    id: m.id,
                    customer_id: m.customer_id, // Keep existing link to customer
                    type: m.customer?.parent_id ? "child" : "adult",
                    parent_id: m.customer?.parent_id ?? null,
                    position: m.position ?? "",
                    notes: m.notes ?? "",
                    is_indemnity: !!m.is_indemnity,
                    full_name: m.customer?.full_name ?? "",
                    email_address: m.customer?.email_address ?? "",
                    nationality_id: m.customer?.nationality_id ?? "",
                    age: m.customer?.age ?? null,
                    contact_number: m.customer?.contact_number ?? "",
                    booking_item_id: m.booking_item_id ?? "",
                })
            });
            console.log("mapped", mapped);
            // const adults = data.customer_manifests.filter(p => p.type === "adult");
            // setAdults(adults);
            setData("customer_manifests", mapped);
        } else if (mode === "create") {
            reset(); // Clear form
        }
    }, [mode, customer_manifests]);
    useEffect(() => {
        const updatedAdults = data.customer_manifests
            .filter(p => p.type === "adult")
            .map(p => {
                console.log("p", p);
                return ({
                    id: p.customer_id || p.id,
                    name: p.full_name || "Unnamed Adult"
                })
            });
        console.log("updatedAdults", updatedAdults);
        setAdults(updatedAdults);
    }, [data.customer_manifests]);
    // Add Adult/Child
    const handleAddPerson = (type) => {
        const adultsCount = data.customer_manifests.filter(p => p.type === "adult").length;
        const childrenCount = data.customer_manifests.filter(p => p.type === "child").length;

        if (type === "adult" && adultsCount >= tot_adults) return;

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
                parent_id: null,
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
            put(route("admin.customer-manifests.edit", customer_manifests[0].booking_item_id), {
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

                    {/* Add Adult / Add Child buttons */}
                    <div className="flex gap-4 mb-6">
                        <BlueButton
                            title="Add Adult"
                            onClick={(e) => {
                                e.preventDefault();
                                handleAddPerson("adult");
                            }}
                            disabled={
                                data.customer_manifests.filter((p) => p.type === "adult").length >= tot_adults
                            }
                            type="button"
                        /> <span className="text-btn-outline-gray">{tot_adults - data.customer_manifests.filter((p) => p.type === "adult").length} more</span>
                        <BlueButton
                            title="Add Child"
                            onClick={(e) => {
                                e.preventDefault();
                                handleAddPerson("child");
                            }}
                            disabled={
                                data.customer_manifests.filter((p) => p.type === "child").length >= tot_children
                            }
                            type="button"
                        />
                        <span className="text-btn-outline-gray">{tot_children - data.customer_manifests.filter((p) => p.type === "child").length} more</span>
                    </div>

                    {/* Dynamic person forms */}
                    {data?.customer_manifests?.map((person, index) => (
                        <div key={index} className="border p-3 mb-4 rounded-lg">
                            <div className="flex justify-between items-center mb-2">
                                <h3 className="font-semibold">
                                    {person.type.toUpperCase()} #{index + 1}
                                </h3>
                                <button
                                    type="button"
                                    onClick={() => handleRemovePerson(index)}
                                    className="text-red-500 hover:underline"
                                >
                                    Remove
                                </button>
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
                                <select
                                    value={person.parent_id || ""}
                                    onChange={(e) => {
                                        const updated = data.customer_manifests.map((p, idx) =>
                                            idx === index ? { ...p, parent_id: e.target.value } : p
                                        );
                                        setData("customer_manifests", updated);
                                    }}
                                >
                                    {adults?.map(adult => {
                                        console.log("adult", adult);
                                        return (
                                            <option key={adult.id} value={adult.id}>
                                                {adult.name}
                                            </option>
                                        )
                                    })}
                                </select>
                            )}
                            {/* <CustomSelect
                                title="Nationality"
                                field="nationality_id"
                                value={person.nationality_id}
                                options={[]} // Pass nationality options here
                                handleChange={(f, val) => handlePersonChange(index, f, val)}
                            /> */}

                            <InputText
                                title="Age"
                                field="age"
                                type="number"
                                value={person.age}
                                mode={mode}
                                handleChange={(f, val) => handlePersonChange(index, f, val)}
                                errors={errors}
                            />

                            <CustomCheckbox
                                label="Indemnity Signed?"
                                name="is_indemnity"
                                checked={person.is_indemnity}
                                onChange={(f, val) => handlePersonChange(index, f, val)}
                                errors={errors}
                            />

                            <InputText
                                title="Position"
                                field="position"
                                value={person.position}
                                mode={mode}
                                handleChange={(f, val) => handlePersonChange(index, f, val)}
                                errors={errors}
                            />

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

export default ManifestModal;
