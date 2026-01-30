import React, { useEffect, useState } from "react";
import { router, useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomTextarea from "@/Components/CustomTextarea";
import parse from "html-react-parser";
import CustomSelect from "@/Components/CustomSelect";
import CustomInputText from "@/Components/CustomInputText";


const VoucherModal = ({ isOpen, onClose, mode, voucher = null, services, auth, team_id }) => {
    // console.log("mode", mode);
    console.log("voucher", voucher);
    // console.log("roles", roles);
    const [seasons, setSeasons] = useState([]);

    const { data, setData, post, put, errors, reset, processing } = useForm({
        voucher_code: "",
        note: "",
        amount: "",
        expire_date: "",
        service_id: "",
        season_id: "",
        team_id: team_id,
        created_by_id: auth?.user?.id,
        updated_by_id: auth?.user?.id,
    });

    useEffect(() => {
        if (voucher && mode !== "create") {
            setData({
                voucher_code: voucher?.voucher_code || "",
                amount: voucher?.amount || "",
                note: voucher?.note || "",
                expire_date: voucher?.expire_date || "",
                service_id: voucher?.service_id || "",
                season_id: voucher?.season_id || "",
                team_id: team_id,
                created_by_id: voucher.created_by_id || auth?.user?.id,
                updated_by_id: voucher.updated_by_id || auth?.user?.id,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode, voucher, team_id]);

    useEffect(() => {
        const selectedService = services.find(s => {
            console.log('service', s);
            console.log('service_id ', data.service_id);
            const selected = s.id === parseInt(data.service_id);
            console.log(selected);

            return selected;
        });
        console.log(selectedService);
        const seasons = selectedService ? setSeasons(selectedService.seasons) : [];
        console.log('seasons', selectedService?.seasons);

    }, [data.service_id]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("voucher_code", e.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "create") {
            post(route("admin.vouchers.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("admin.vouchers.update", voucher.id), {
                onSuccess: () => onClose(),
            });
        }
    };



    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "create" && "Create Voucher"}
                    {mode === "edit" && "Edit Voucher"}
                    {mode === "view" && "View Voucher"}
                </h2>
                <form onSubmit={handleSubmit}>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">

                        <CustomSelect
                            label="Service"
                            name="service_id"
                            value={data.service_id}
                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            errors={errors}
                            options={services}
                        />
                        <CustomSelect
                            label="Season"
                            name="season_id"
                            value={data.season_id}

                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            errors={errors}
                            options={seasons}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <CustomInputText
                            title="Voucher"
                            id="voucher_code"
                            name="voucher_code"
                            type='text'
                            value={data.voucher_code}
                            onChange={(e) => { handleChange(e.target.name, e.target.value); }}
                            errors={errors}
                        />
                        <CustomInputText
                            title="Amount"
                            id="amount"
                            name="amount"
                            type='number'
                            value={data.amount}
                            onChange={(e) => { handleChange(e.target.name, e.target.value); }}
                            errors={errors}
                        />
                    </div>

                    <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <CustomInputText
                            title="Note"
                            id="note"
                            name="note"
                            type='text'
                            value={data.note}
                            onChange={(e) => { handleChange(e.target.name, e.target.value); }}
                            errors={errors}
                        />
                        <CustomInputText
                            title="Expire Date"
                            id="expire_date"
                            name="expire_date"
                            type='date'
                            value={data.expire_date}

                            onChange={(e) => { handleChange(e.target.name, e.target.value); }}
                            errors={errors}
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

export default VoucherModal;
