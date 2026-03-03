import { useEffect, useMemo } from "react";
import { useForm } from "@inertiajs/react";
import CustomCheckbox from "@/Components/CustomCheckbox";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";

const SettingsModal = ({ isOpen, onClose, settings = [] }) => {
    const settingsMap = useMemo(() => {
        return settings.reduce((acc, item) => {
            acc[item.slug] = item.value;
            return acc;
        }, {});
    }, [settings]);

    const settingsInfo = useMemo(() => {
        return settings.reduce((acc, item) => {
            acc[item.slug] = item.info || item.description || "";
            return acc;
        }, {});
    }, [settings]);

    const { data, setData, post, errors, processing } = useForm({
        enable_pickup_dropoff: settingsMap.enable_pickup_dropoff ?? true,
        auto_send_invoice_on_booking: settingsMap.auto_send_invoice_on_booking ?? true,
        auto_send_quote_on_booking: settingsMap.auto_send_quote_on_booking ?? false,
        enable_assign_staff: settingsMap.enable_assign_staff ?? true,
        show_send_invoice_checkbox: settingsMap.show_send_invoice_checkbox ?? false,
        show_send_quote_checkbox: settingsMap.show_send_quote_checkbox ?? false,
    });

    useEffect(() => {
        if (!isOpen) return;
        setData({
            enable_pickup_dropoff: settingsMap.enable_pickup_dropoff ?? true,
            auto_send_invoice_on_booking: settingsMap.auto_send_invoice_on_booking ?? true,
            auto_send_quote_on_booking: settingsMap.auto_send_quote_on_booking ?? false,
            enable_assign_staff: settingsMap.enable_assign_staff ?? true,
            show_send_invoice_checkbox: settingsMap.show_send_invoice_checkbox ?? false,
            show_send_quote_checkbox: settingsMap.show_send_quote_checkbox ?? false,
        });
    }, [isOpen, setData, settingsMap]);

    const handleChange = (name, value) => {
        setData(name, value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("admin.settings.store"), {
            onSuccess: () => onClose(),
        });
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] max-w-[720px]">
                <h2 className="text-xl font-bold mb-4">Edit Settings</h2>
                <form onSubmit={handleSubmit}>
                    <div className="flex flex-col w-full gap-4 mb-6">
                        <CustomCheckbox
                            label="Enable Pickup/Dropoff"
                            name="enable_pickup_dropoff"
                            checked={data.enable_pickup_dropoff}
                            onChange={handleChange}
                            helper={settingsInfo.enable_pickup_dropoff}
                            errors={errors}
                        />
                        <CustomCheckbox
                            label="Auto Send Invoice on Booking"
                            name="auto_send_invoice_on_booking"
                            checked={data.auto_send_invoice_on_booking}
                            onChange={handleChange}
                            helper={settingsInfo.auto_send_invoice_on_booking}
                            errors={errors}
                        />
                        <CustomCheckbox
                            label="Auto Send Quote on Booking"
                            name="auto_send_quote_on_booking"
                            checked={data.auto_send_quote_on_booking}
                            onChange={handleChange}
                            helper={settingsInfo.auto_send_quote_on_booking}
                            errors={errors}
                        />
                        <CustomCheckbox
                            label="Enable Assign Staff"
                            name="enable_assign_staff"
                            checked={data.enable_assign_staff}
                            onChange={handleChange}
                            helper={settingsInfo.enable_assign_staff}
                            errors={errors}
                        />
                        <CustomCheckbox
                            label="Show Send Invoice Checkbox"
                            name="show_send_invoice_checkbox"
                            checked={data.show_send_invoice_checkbox}
                            onChange={handleChange}
                            helper={settingsInfo.show_send_invoice_checkbox}
                            errors={errors}
                        />
                        <CustomCheckbox
                            label="Show Send Quote Checkbox"
                            name="show_send_quote_checkbox"
                            checked={data.show_send_quote_checkbox}
                            onChange={handleChange}
                            helper={settingsInfo.show_send_quote_checkbox}
                            errors={errors}
                        />
                    </div>
                    <div className="flex justify-center gap-4">
                        <OutlineButton title="Cancel" onClick={onClose} width="w-[180px]" />
                        <BlueButton title="Save" width="w-[180px]" onClick={handleSubmit} disabled={processing} type="submit" />
                    </div>
                </form>
            </div>
        </div>
    );
};

export default SettingsModal;
