import React, { useEffect } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";

const WholesalerContactModal = ({ isOpen, onClose, mode, wholesalercontact = null, wholesalers, team }) => {
    // console.log("mode", mode);
    console.log("wholesalercontact", wholesalercontact);
    // console.log("roles", roles);
    const { data, setData, post, put, errors, reset, processing } = useForm({
        name: "",
        email: "",
        phone: "",
        type: "",
        wholesaler_id: null,
    });
    useEffect(() => {
        if (wholesalercontact && mode !== "create") {
            setData({
                name: wholesalercontact.name || "",
                email: wholesalercontact.email || "",
                phone: wholesalercontact.phone || "",
                type: wholesalercontact.type || "",
                wholesaler_id: wholesalercontact.wholesaler_id || null,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode, wholesalercontact, team]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("admin.wholesaler-contacts.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("admin.wholesaler-contacts.update", wholesalercontact.id), {
                onSuccess: () => onClose(),
            });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Wholesaler Contact"}
                    {mode === "edit" && "Edit Wholesaler Contact"}
                    {mode === "view" && "View Wholesaler Contact"}
                </h2>
                <form onSubmit={handleSubmit}>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <CustomSelect
                            label="Wholesaler"
                            name="wholesaler_id"
                            value={data.wholesaler_id}
                            mode={mode}
                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            error={errors.wholesaler_id}
                            options={wholesalers}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Name"
                            field="name"
                            value={data.name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <InputText
                            title="Email"
                            field="email"
                            type="email"
                            value={data.email}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Phone"
                            field="phone"
                            type="tel"
                            value={data.phone}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <CustomSelect
                            label="Type"
                            name="type"
                            value={data.type}
                            mode={mode}
                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            error={errors.type}
                            options={["manager", "director", "accountant"]}
                        />
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

export default WholesalerContactModal;
