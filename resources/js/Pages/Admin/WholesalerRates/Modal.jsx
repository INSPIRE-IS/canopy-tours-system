import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomCheckbox from "@/Components/CustomCheckbox";

const Modal = ({ isOpen, onClose, mode, item = null, permissions, wholesalers, services, auth }) => {
  const [seasons, setSeasons] = useState([]);
  const dateOnly = (value) => value ? String(value).slice(0, 10) : "";
  const { data, setData, post, put, errors, reset, processing } = useForm({
    name: "",
    wholesaler_id: "",
    service_id: "",
    season_id: "",
    price_adult: "",
    price_child: "",
    price_guide: "",
    bill_back: false,
    is_prepaid_voucher: false,
    effective_from: "",
    effective_to: "",
    is_active: true,
    note: "",
    team_id: auth?.user?.team_id,
    created_by_id: auth?.user?.id,
    updated_by_id: auth?.user?.id,
  });

  useEffect(() => {
    if (item && mode !== "create") {
      setData({
        name: item?.name || "",
        wholesaler_id: item?.wholesaler_id || "",
        service_id: item?.service_id || "",
        season_id: item?.season_id || "",
        price_adult: item?.price_adult || "",
        price_child: item?.price_child || "",
        price_guide: item?.price_guide || "",
        bill_back: item?.bill_back || false,
        is_prepaid_voucher: item?.is_prepaid_voucher || false,
        effective_from: dateOnly(item?.effective_from),
        effective_to: dateOnly(item?.effective_to),
        is_active: item?.is_active ?? true,
        note: item?.note || "",
        team_id: auth?.user?.team_id,
        created_by_id: item.created_by_id || auth?.user?.id,
        updated_by_id: item.updated_by_id || auth?.user?.id,
      });
    } else if (mode === "create") {
      reset(); // Clear form for create mode
    }
  }, [mode, item?.id, isOpen]);

  useEffect(() => {
    if (!data?.service_id) {
      setSeasons([]);
      return;
    }

    const selectedService = services.find(s => s.id === parseInt(data.service_id));
    setSeasons(selectedService?.seasons || []);
  }, [data.service_id, services]);

  //const handleChange = (target, value) => setData(target, value);
  const handleChange = (target, value) => {
    setData(target, value);
    //setData("voucher_code", e.target.value);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (mode === "create") {
      post(route('admin.wholesaler-rates.store'), { onSuccess: () => onClose() });
    } else if (mode === "edit") {
      put(route('admin.wholesaler-rates.update', item.id), { onSuccess: () => onClose() });
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
      <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
        <h2 className="text-xl font-bold mb-4">
          {mode === "create" && "Wholesaler Rate Create"}
          {mode === "edit" && "Wholesaler Rate Edit"}
          {mode === "view" && "Wholesaler Rate View"}
        </h2>

        <form onSubmit={handleSubmit}>
          <div className="flex flex-col gap-4">
            <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
              <CustomSelect
                label="Wholesaler"
                name="wholesaler_id"
                value={data?.wholesaler_id ?? ""}
                //optionsUrl={route('wholesalers.options')}
                options={wholesalers}
                mode={mode}
                onChange={(e) => handleChange(e.target.name, e.target.value)}
                errors={errors}
              />
              <CustomSelect
                label="Service"
                name="service_id"
                value={data?.service_id ?? ""}
                onChange={(e) => handleChange(e.target.name, e.target.value)}
                errors={errors}
                options={services}
              />
              <CustomSelect
                label="Season"
                name="season_id"
                value={data?.season_id ?? ""}

                onChange={(e) => handleChange(e.target.name, e.target.value)}
                errors={errors}
                options={seasons}
              />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 w-full items-center gap-2 justify-between mb-4">
              <InputText title="Rate Name" field="name" value={data?.name ?? ""} mode={mode} handleChange={handleChange} errors={errors} placeholder="e.g. 2026 operator rate" />
              <InputText title="Effective From" field="effective_from" value={data?.effective_from ?? ""} type="date" mode={mode} handleChange={handleChange} errors={errors} />
              <InputText title="Effective To" field="effective_to" value={data?.effective_to ?? ""} type="date" mode={mode} handleChange={handleChange} errors={errors} />
              <InputText title="Adult Price" field="price_adult" value={data?.price_adult ?? ""} mode={mode} handleChange={handleChange} errors={errors} />
              <InputText title="Child Price" field="price_child" value={data?.price_child ?? ""} mode={mode} handleChange={handleChange} errors={errors} />
              <InputText title="Guide Price" field="price_guide" value={data?.price_guide ?? ""} mode={mode} handleChange={handleChange} errors={errors} />
              <CustomCheckbox label="Active" name="is_active" side="right" checked={data?.is_active ?? true} mode={mode} onChange={handleChange} errors={errors} disabled={mode === "view"} />
              <div>
                <CustomCheckbox label="Prepaid Voucher Partner" name="is_prepaid_voucher" side="right" checked={data?.is_prepaid_voucher ?? false} mode={mode} onChange={handleChange} errors={errors} disabled={mode === "view"} />
                <p className="mt-1 text-xs text-gray-500">
                  Use only for prepaid partner/voucher rates where the customer does not pay the site directly. Reporting keeps this rate; the customer-facing balance is marked paid.
                </p>
              </div>
              <div className="md:col-span-2">
                <InputText title="Notes" field="note" value={data?.note ?? ""} mode={mode} handleChange={handleChange} errors={errors} placeholder="Optional internal note" />
                <p className="mt-1 text-xs text-gray-500">
                  Date ranges protect old bookings. Existing bookings keep the saved rate snapshot and rate id.
                </p>
              </div>
            </div>

          </div>

          <div className="flex justify-center gap-4 mt-6">
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

export default Modal;
