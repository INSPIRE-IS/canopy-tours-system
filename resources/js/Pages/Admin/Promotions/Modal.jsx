import React, { useEffect } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";

const Modal = ({ isOpen, onClose, mode, item = null, permissions }) => {
  const { data, setData, post, put, errors, reset, processing } = useForm({});

  useEffect(() => {
    if (item && mode !== "create") {
      setData({ ...item });
    } else if (mode === "create") {
      reset();
    }
  }, [mode, item]);

  const handleChange = (target, value) => setData(target, value);

  const handleSubmit = (e) => {
    e.preventDefault();
    if (mode === "create") {
      post(route('promotions.store'), { onSuccess: () => onClose() });
    } else if (mode === "edit") {
      put(route('promotions.update', item.id), { onSuccess: () => onClose() });
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
      <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
        <h2 className="text-xl font-bold mb-4">
          {mode === "create" && "Create"}
          {mode === "edit" && "Edit"}
          {mode === "view" && "View"}
        </h2>

        <form onSubmit={handleSubmit}>
<div className="flex flex-col gap-4">
            <InputText title="Name" field="name" value={data.name} mode={mode} handleChange={handleChange} errors={errors} />
            <InputText title="Promo Code" field="promo_code" value={data.promo_code} mode={mode} handleChange={handleChange} errors={errors} />
            <CustomSelect
              title="Type"
              field="type"
              value={data.type}
              options={[
                { value: 'percent', label: 'Percent' },
                { value: 'fixed_value', label: 'Fixed Value' },
                { value: 'graduated', label: 'Graduated (per pax)' },
                { value: 'varied_child', label: 'Varied Child (by adults)' }
              ]}
              mode={mode}
              handleChange={handleChange}
              errors={errors}
            />
            <InputText title="Value" field="value" value={data.value} mode={mode} handleChange={handleChange} errors={errors} />
            <CustomSelect
              title="Service"
              field="service_id"
              value={data.service_id}
              optionsUrl={route('services.options')}
              mode={mode}
              handleChange={handleChange}
              errors={errors}
            />
            <InputText title="Start Date (YYYY-MM-DD)" field="start_date" value={data.start_date} mode={mode} handleChange={handleChange} errors={errors} />
            <InputText title="End Date (YYYY-MM-DD)" field="end_date" value={data.end_date} mode={mode} handleChange={handleChange} errors={errors} />
            <InputText title="Valid Days (mon,tue,wed,...)" field="valid_days" value={data.valid_days} mode={mode} handleChange={handleChange} errors={errors} />
            <CustomSelect
              title="Internal Use Only"
              field="internal_use_only"
              value={data.internal_use_only}
              options={[
                { value: 0, label: 'No' },
                { value: 1, label: 'Yes' }
              ]}
              mode={mode}
              handleChange={handleChange}
              errors={errors}
            />
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
