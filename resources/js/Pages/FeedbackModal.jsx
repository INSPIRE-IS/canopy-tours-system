import React, { useEffect, useState } from "react";
import { router, useForm } from "@inertiajs/react";
import OutlineButton from "@/Components/OutlineButton";
import GreenButton from "@/Components/GreenButton";

const FeedbackModal = ({ isOpen, onClose, mode, booking, tot_adults, tot_children }) => {

    // console.log("customers", booking?.customers);
    // console.log("mode", mode);

    const [adults, setAdults] = useState([]);

    const { data, setData, post, put, errors, reset, processing } = useForm({
        customer_manifests: [],
    });

    useEffect(() => {
        if (mode === "edit" && booking?.customers?.length) {
            // Map API data into form shape
            const mapped = booking?.customers.map((m, i) => {
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
                    index: null,
                })
            });
            console.log("mapped", mapped);
            // const adults = data.customer_manifests.filter(p => p.type === "adult");
            // setAdults(adults);
            setData("customer_manifests", mapped);
        } else if (mode === "create") {
            reset(); // Clear form
        }
    }, [mode, booking?.customers]);

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


    // Submit
    const handleSubmit = async (e, index) => {
        e.preventDefault();
        const customer_manifest = data.customer_manifests[index];
        console.log("customer_manifest", customer_manifest);
        if (!customer_manifest.is_indemnity) {
            alert("Please complete the indemnity checkbox");
            return;
        }
        //return;
        router.get(route('front.feedback-questionnaire.signed',
            {
                booking_item_id: booking.booking_item_id,
                customer_id: customer_manifest.customer_id
            }));

        // put(route('front.feedback-questionnaire.signed', booking?.customers[0].booking_item_id), {
        //     onSuccess: () => onClose(),
        // });
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-full xl:w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "edit" && "Booking Feedback"}
                </h2>

                <form onSubmit={handleSubmit}>
                    {/* Dynamic person forms */}
                    {data?.customer_manifests?.map((person, index) => (
                        <div key={index} className="border p-3 mb-4 rounded-lg">
                            <div className="flex justify-between items-center mb-2">
                                <h3 className="font-semibold">
                                    {person.type.toUpperCase()} #{index + 1} {person.is_lead ? "(Lead)" : ""} {person.discount ? "(Discount) " + person.discount : ""}
                                </h3>
                            </div>
                            <div className="flex justify-between items-center mb-2">
                                <div className="flex items-start gap-2">
                                    <h3>Full Name</h3>
                                    <h4>{person.full_name}</h4>
                                    <h3>Indemnity Complete</h3>
                                    <h4>{person.is_indemnity ? "Yes" : "No"}</h4>
                                </div>

                                {person.is_indemnity && (
                                    <GreenButton
                                        title="complete feedback"
                                        onClick={(e) => handleSubmit(e, index)}
                                        width="w-[180px]"
                                    />
                                )}
                            </div>
                        </div>
                    ))}

                    {/* Footer buttons */}
                    <div className="flex justify-center gap-4 mt-6">
                        <OutlineButton title="Close" onClick={onClose} width="w-[180px]" />
                    </div>
                </form>
            </div>
        </div>
    );
};

export default FeedbackModal;
