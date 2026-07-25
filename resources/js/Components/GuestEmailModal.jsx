import React, { useEffect } from "react";
import { useForm } from "@inertiajs/react";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import InputRichText from "./InputRichText";
import CustomInputText from "./CustomInputText";
import CustomCheckbox from "@/Components/CustomCheckbox";

const GuestEmailModal = ({ isOpen, onClose, mode, booking }) => {

    const { data, setData, post, put, errors, reset, processing } = useForm({
        booking_id: '',
        booking_item_id: '',
        customer_id: '',
        recipient: '',
        subject: '',
        content: '',
        include_invoice_link: false,
        include_checkin_link: false,
        include_payment_link: false,
        include_invoice_pdf: false,
        include_boarding_pass_pdf: false,
    });
    useEffect(() => {
        if (booking) {
            //reset();
            const customers = booking.customers || [];
            const customer = customers.find(c => (c.id || c.customer_id) == booking.customer_id || (c.id || c.customer_id) == booking.lead_id) || customers[0];
            setData({
                booking_id: booking.id,
                booking_item_id: booking.booking_item_id || booking.bookingItemId || '',
                customer_id: booking.lead_id || booking.customer_id || customer?.id || customer?.customer_id || '',
                recipient: customer?.email_address || customer?.email || "",
                subject: 'New Booking',
                content: `<p>Hello ${booking.lead_name},</p><p>Your booking reference is ${booking.reference}.</p><p>${booking.service || ''} ${booking.time || ''}</p><p>Please contact us if you need any assistance with your booking.</p>`,
                include_invoice_link: !!booking.include_invoice_link,
                include_checkin_link: !!booking.include_checkin_link,
                include_payment_link: !!booking.include_payment_link,
                include_invoice_pdf: !!booking.include_invoice_pdf,
                include_boarding_pass_pdf: !!booking.include_boarding_pass_pdf,
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
                    booking_item_id: data.booking_item_id,
                    customer_id: data.customer_id,
                    recipient: data.recipient,
                    subject: data.subject,
                    content: data.content,
                    include_invoice_link: data.include_invoice_link,
                    include_checkin_link: data.include_checkin_link,
                    include_payment_link: data.include_payment_link,
                    include_invoice_pdf: data.include_invoice_pdf,
                    include_boarding_pass_pdf: data.include_boarding_pass_pdf,
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
                        <div className="w-full grid grid-cols-1 md:grid-cols-2 gap-3">
                            <CustomCheckbox
                                label="Include Invoice Link"
                                name="include_invoice_link"
                                checked={!!data.include_invoice_link}
                                mode={mode}
                                onChange={handleChange}
                                errors={errors}
                                helper="Adds the secure signed invoice link for this booking."
                            />
                            <CustomCheckbox
                                label="Include Check-in Link"
                                name="include_checkin_link"
                                checked={!!data.include_checkin_link}
                                mode={mode}
                                onChange={handleChange}
                                errors={errors}
                                helper="Adds the secure signed check-in link for this booking."
                            />
                            <CustomCheckbox
                                label="Include Payment Link"
                                name="include_payment_link"
                                checked={!!data.include_payment_link}
                                mode={mode}
                                onChange={handleChange}
                                errors={errors}
                                helper="Adds the secure signed payment link for this booking."
                            />
                            <CustomCheckbox
                                label="Attach Invoice PDF"
                                name="include_invoice_pdf"
                                checked={!!data.include_invoice_pdf}
                                mode={mode}
                                onChange={handleChange}
                                errors={errors}
                                helper="Attaches the latest generated invoice PDF."
                            />
                            <CustomCheckbox
                                label="Attach Boarding Pass PDF"
                                name="include_boarding_pass_pdf"
                                checked={!!data.include_boarding_pass_pdf}
                                mode={mode}
                                onChange={handleChange}
                                errors={errors}
                                helper="Attaches the latest boarding pass PDF for the selected activity."
                            />
                        </div>
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
