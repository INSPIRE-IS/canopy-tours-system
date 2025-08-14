import React, { useEffect } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";

const Modal = ({ isOpen, onClose, mode, item = null, permissions, services }) => {
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
      post(route('discounts.store'), { onSuccess: () => onClose() });
    } else if (mode === "edit") {
      put(route('discounts.update', item.id), { onSuccess: () => onClose() });
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

                    <CustomSelect
                        label="Service"
                        name="service_id"
                        value={data.service_id}
                        mode={mode}
                        onChange={(e) => handleChange(e.target.name, e.target.value)}
                        error={errors.service_id}
                        options={services}
                    />
                    <CustomSelect
                        label="Applies To"
                        name="applies_to"
                        value={data.applies_to}
                        mode={mode}
                        onChange={(e) => handleChange(e.target.name, e.target.value)}
                        error={errors.applies_to}
                        options={[
                { id: 'line_item', name: 'Line Item' },
                { id: 'invoice', name: 'Invoice' }
              ]}
                    />
              

            <InputText title="% Off" field="percent_off" value={data.percent_off} mode={mode} handleChange={handleChange} errors={errors} />
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
