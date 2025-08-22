import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomCheckbox from "@/Components/CustomCheckbox";

const Modal = ({ isOpen, onClose, mode, item = null, permissions, services }) => {
  const [checkServices, setCheckServices] = useState([]);
  console.log('item ', item);
  const { data, setData, post, put, errors, reset, processing } = useForm({});

  useEffect(() => {
    if (item && mode !== "create") {
      setData({ ...item });
      if (item.services) {
        setCheckServices(item.services.map(service => service.id));
      }
    } else if (mode === "create") {
      reset();
      setCheckServices([]);
    }
  }, [mode, item]);

  const handleChange = (target, value) => setData(target, value);

  const handleCheckChange = (day,value) => {
    setCheckServices((prev) =>
      prev.includes(day)
        ? prev.filter((d) => d !== day) // remove if already checked
        : [...prev, day]                // add if not checked
    );
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    console.log('checkServices ', checkServices);
    data.service_ids = checkServices;//.join(',');
    //console.log('data ', data);
    if (mode === "create") {
      post(route('admin.discounts.store'), { onSuccess: () => onClose() });
    } else if (mode === "edit") {
      put(route('admin.discounts.update', item.id), { onSuccess: () => onClose() });
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
      <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
        <h2 className="text-xl font-bold mb-4">
          {mode === "create" && "Discount Create"}
          {mode === "edit" && "Discount Edit"}
          {mode === "view" && "Discount View"}
        </h2>
        <form onSubmit={handleSubmit}>
          <div className="flex flex-col gap-4">
            <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
              {/* <CustomSelect
                  label="Service"
                  name="service_id"
                  value={data.service_id}
                  mode={mode}
                  onChange={(e) => handleChange(e.target.name, e.target.value)}
                  error={errors.service_id}
                  options={services}
              /> */}
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
              <InputText title="Name" field="name" height="h-[48px]" value={data.name} mode={mode} handleChange={handleChange} errors={errors} />                         
            </div>
            <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">

              <InputText title="% Off" field="percent_off" height="h-[48px]" width="w-[50%]" value={data.percent_off} mode={mode} handleChange={handleChange} errors={errors} />              
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-4">
                  {services.map((service, i) => (
                    <CustomCheckbox
                        key={i}
                        label={service.name}
                        name={service.id}
                        checked={checkServices.includes(service.id)}
                        mode={mode}
                        onChange={handleCheckChange}
                        errors={errors}
                    />
                ))}

              {/* <CustomCheckbox
                  label="Mon"
                  name="mon"
                  checked={checkDays.includes('mon')}
                  mode={mode}
                  onChange={handleCheckChange}
                  errors={errors}
              /> */}
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
