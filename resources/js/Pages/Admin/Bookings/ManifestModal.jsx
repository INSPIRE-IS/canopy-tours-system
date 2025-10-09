import React, { useEffect, useState } from "react";
import { router, useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";

const ManifestModal = ({ isOpen, onClose, mode, customer, customers, tot_adults, tot_children }) => {

    console.log("customer single", customer);
    console.log("mode", mode);

    const [adults, setAdults] = useState([]);

    const { data, setData, post, put, errors, reset, processing } = useForm({
        id: '',
        customer_id: '',
        type: '',
        parent_id: '',
        position: '',
        notes: '',
        is_indemnity: '',
        full_name: '',
        email_address: '',
        nationality_id: '',
        age: '',
        contact_number: '',
        booking_item_id: '',
        discount_id: '',
        discount: '',
        due: '',
        is_lead: '',
        paid: '',
        price: '',
        reference: '',
        team_id: '',
        source: '',
        booking_discount_item_id: '',
    });

    useEffect(() => {
        if (mode === "edit") {
            // Map API data into form shape
            setData({
                id: customer?.customer_id,
                customer_id: customer?.customer_id, // Keep existing link to customer
                type: customer?.type,
                parent_id: customer?.parent_id ?? "",
                position: customer?.position ?? "",
                notes: customer?.notes ?? "",
                is_indemnity: !!customer?.is_indemnity,
                full_name: customer?.full_name ?? "",
                email_address: customer?.email_address ?? "",
                nationality_id: customer?.nationality_id ?? "",
                age: customer?.age ?? null,
                contact_number: customer?.contact_number ?? "",
                booking_item_id: customer?.booking_item_id ?? "",
                discount_id: customer?.discount_id ?? "",
                discount: customer?.discount ?? "",
                due: customer?.due ?? "",
                is_lead: customer?.is_lead ?? "",
                paid: customer?.paid ?? "",
                price: customer?.price ?? "",
                reference: customer?.reference ?? "",
                team_id: customer?.team_id ?? "",
                source: customer?.source ?? "",
            });
            console.log("customer777777", customer);
            console.log("data7777777777", data);

        } else if (mode === "create") {
            reset(); // Clear form
        }
    }, [mode, customer]);

    useEffect(() => {
        console.log("customers", customers);
        const updatedAdults = customers
            ?.filter(p => p.type === "adult")
            ?.map((p, i) => {
                console.log("p", typeof p.customer_id == 'number');
                return ({
                    id: typeof p.customer_id == 'number' ? p.customer_id : i + 1,
                    name: p.full_name || "Unnamed Adult"
                })
            });
        console.log("updatedAdults", updatedAdults);
        setAdults(updatedAdults);
    }, [customers]);

    const handleRemovePerson = (index) => {
        const updated = [...data.customer_manifests];
        updated.splice(index, 1);
        setData("customer_manifests", updated);
    };

    const handleChange = (target, value) => {
        console.log("target", target);
        console.log("value", value);
        setData(target, value);
        //setData("name", e.target.value);
    };

    // Submit
    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("admin.customer-manifests.add"), {
                onSuccess: () => onClose(),
                onError: (err) => { console.log(err); },
                data: {
                    customer_manifests: data,
                },
            });
        } else if (mode === "edit") {
            put(route("admin.customer-manifests.edit-single", { customer_manifests: data, booking_item_id: customer.booking_item_id }), {
                onSuccess: () => onClose(),
                onError: (err) => { console.log(err); },
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

                    <div className="border p-3 mb-4 rounded-lg">
                        <div className="flex justify-between items-center mb-2">
                            <h3 className="font-semibold">
                                {data?.type?.toUpperCase()} {data?.is_lead ? "(Lead)" : ""} {data?.discount ? "(Discount) " + data?.discount : ""}
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
                            value={data?.full_name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />

                        <InputText
                            title="Email Address"
                            field="email_address"
                            value={data?.email_address}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        {data?.type === "child" && (
                            <CustomSelect
                                title="Parent"
                                name="parent_id"
                                value={data?.parent_id}
                                options={adults} // Pass nationality options here
                                onChange={(e) => handleChange(e.target.name, e.target.value)}
                            />
                        )}
                        {/* <CustomSelect
                                title="Nationality"
                                field="nationality_id"
                                value={data?.nationality_id}
                                options={[]} // Pass nationality options here
                                handleChange={(e) => handleChange(e.target.name, e.target.value)}
                            /> */}


                        <div className="flex flex-row w-full items-center justify-start gap-2 mt-2">
                            <CustomCheckbox
                                label="Indemnity Signed?"
                                name="is_indemnity"
                                checked={data?.is_indemnity}
                                onChange={(e) => handleChange(e.target.name, e.target.value)}
                                errors={errors}
                            />
                            <span className="flex flex-row items-center justify-start gap-2">
                                <span className="text-[#161618] font-poppins text-[12px] font-normal  leading-[130%]">Age</span>
                                <InputText
                                    field="age"
                                    type="number"
                                    value={data?.age}
                                    width="w-100px"
                                    mode={mode}
                                    handleChange={handleChange}
                                    errors={errors}
                                />
                            </span>
                            <span className=" flex flex-row items-center justify-start gap-2">
                                <span className="text-[#161618] font-poppins text-[12px] font-normal  leading-[130%]">Position</span>
                                <InputText
                                    field="position"
                                    value={data?.position}
                                    width="w-100px"
                                    mode={mode}
                                    handleChange={handleChange}
                                    errors={errors}
                                />
                            </span>
                        </div>

                        <InputText
                            title="Notes"
                            field="notes"
                            value={data?.notes}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>


                    {/* Footer buttons */}
                    <div className="flex justify-center gap-4 mt-6">
                        <OutlineButton type="button" title="Cancel" onClick={onClose} width="w-[180px]" />
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
