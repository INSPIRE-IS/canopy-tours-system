import React, { useEffect } from "react";
import { useForm } from "@inertiajs/react";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import InputRichText from "./InputRichText";
import CustomInputText from "./CustomInputText";

const GuestEmailModal = ({ isOpen, onClose, mode, booking }) => {
    // console.log("mode", mode);
    console.log("booking", booking);
    // console.log("roles", roles);
    const { data, setData, post, put, errors, reset, processing } = useForm({
        booking_id: '',
        customer_id: '',
        recipient: '',
        subject: '',
        content: '',
    });
    useEffect(() => {
        if (booking) {
            //reset();
            const customer = booking.customers.find(c => c.is_lead);
            console.log('customer ', customer);


            setData({
                booking_id: booking.id,
                customer_id: booking.lead_id,
                recipient: customer.email || "",
                subject: 'New Booking',
                content: `<p>Hello ${booking.lead_name},</p><p>${booking.service} ${booking.time} .</p><p>Booking ${booking.reference}</p><p>Please login to your account to view the booking.</p>`,
            });
        }
    }, [mode, booking]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("admin.bookings.send-email"), {
                onSuccess: () => onClose(),
                onError: (err) => { console.log(err); },
                data: {
                    booking_id: data.booking_id,
                    customer_id: data.customer_id,
                    recipient: data.recipient,
                    subject: data.subject,
                    content: data.content,
                },
            });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Message Guest"}
                </h2>
                <form onSubmit={handleSubmit}>


                    <div className="flex flex-col w-full items-center gap-2 justify-start mb-4">

                        <CustomInputText
                            title="Recipient*"
                            name="recipient"
                            value={data.recipient}
                            type="text"
                            mode={mode}
                            onChange={(e) => setData(e.target.name, e.target.value)}
                            errors={errors}
                        />

                        <CustomInputText
                            title="Subject*"
                            name="subject"
                            value={data.subject}
                            type="text"
                            mode={mode}
                            onChange={(e) => setData(e.target.name, e.target.value)}
                            errors={errors}
                        />
                        <InputRichText
                            title="Content"
                            field="content"
                            value={data.content}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <div className="mb-4 w-full"></div>
                    </div>



                    <div className="flex justify-center gap-4">
                        <OutlineButton title="Cancel" onClick={onClose} width="w-[180px]" />
                        {mode !== "view" && (
                            <BlueButton title={mode === "create" ? "Send Email" : ""} width="w-[180px]" onClick={handleSubmit} disabled={processing} type="submit" />
                        )}
                    </div>
                </form>
            </div>
        </div>
    );
};

export default GuestEmailModal;
