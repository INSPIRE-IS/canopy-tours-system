import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import CustomInputText from "@/Components/CustomInputText";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomCheckbox from "@/Components/CustomCheckbox";

const Modal = ({ isOpen, onClose, mode, item = null, services }) => {
  const [checkDays, setCheckDays] = useState([]);
  const { data, setData, post, put, errors, reset, processing } = useForm({});
  console.log('item ', item);

  useEffect(() => {
    if (item && mode !== "create") {
      setData({ ...item });
    } else if (mode === "create") {
      reset();
    }
  }, [mode, item]);

  const handleChange = (target, value) =>  setData(target, value);

  const handleCheckChange = (day,value) => {
    setCheckDays((prev) =>
      prev.includes(day)
        ? prev.filter((d) => d !== day) // remove if already checked
        : [...prev, day]                // add if not checked
    );
  };
  const handleSubmit = (e) => {
    e.preventDefault();
    //console.log('checkDays ', checkDays);
    data.valid_days = checkDays.join(',');
    //console.log('data ', data);
    if (mode === "create") {
      post(route('admin.promotions.store'), { onSuccess: () => onClose() });
    } else if (mode === "edit") {
      put(route('admin.promotions.update', item.id), { onSuccess: () => onClose() });
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
      <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
        <h2 className="text-xl font-bold mb-4">
          {mode === "create" && "Promotion Create"}
          {mode === "edit" && "Promotion Edit"}
          {mode === "view" && "Promotion View"}
        </h2>

        <form onSubmit={handleSubmit}>
          <div className="flex flex-col gap-4">
            <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
              <CustomSelect
                label="Service"
                name="service_id"
                value={data.service_id}
                options={services}
                mode={mode}
                onChange={(e) => handleChange(e.target.name, e.target.value)}
                errors={errors}
              />              
              <CustomSelect
                label="Type"
                name="type"
                value={data.type}
                mode={mode}
                options={[
                  { id: 'percent', name: 'Percent' },
                  { id: 'fixed_value', name: 'Fixed Value' },
                  { id: 'graduated', name: 'Graduated (per pax)' },
                  { id: 'varied_child', name: 'Varied Child (by adults)' }
                ]}
                onChange={(e) => handleChange(e.target.name, e.target.value)}
                errors={errors}
              />
            </div>            
            <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
              <CustomInputText title="Name" name="name" value={data.name} mode={mode} onChange={(e) => {handleChange(e.target.name, e.target.value);}} errors={errors} />
              <CustomInputText title="Promo Code" name="promo_code" value={data.promo_code} mode={mode} onChange={(e) => {handleChange(e.target.name, e.target.value);}} errors={errors} />    
            </div>
                <span className="text-booking-card-title">Booking Dates</span>
            <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
              <CustomInputText title="Start Date (YYYY-MM-DD)" type="date" name="booking_start_date" value={data.booking_start_date} mode={mode} onChange={(e) => {handleChange(e.target.name, e.target.value);}} errors={errors} />  
              <CustomInputText title="End Date (YYYY-MM-DD)" type="date" name="booking_end_date" value={data.booking_end_date} mode={mode} onChange={(e) => {handleChange(e.target.name, e.target.value);}} errors={errors} />            
            </div>
            <span className="text-booking-card-title">Activity Dates</span>
            <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
              <CustomInputText title="Start Date (YYYY-MM-DD)" type="date" name="activity_start_date" value={data.activity_start_date} mode={mode} onChange={(e) => {handleChange(e.target.name, e.target.value);}} errors={errors} />
              <CustomInputText title="End Date (YYYY-MM-DD)" type="date" name="activity_end_date" value={data.activity_end_date} mode={mode} onChange={(e) => {handleChange(e.target.name, e.target.value);}} errors={errors} />            
            </div>
        

            <div className="flex flex-row w-[90%] items-center justify-evenly gap-2 mb-4">
              <CustomCheckbox
                  label="Mon"
                  name="1"
                  checked={checkDays.includes('1')}
                  mode={mode}
                  onChange={handleCheckChange}
                  errors={errors}
              />
              <CustomCheckbox
                  label="Tue"
                  name="2"
                  checked={checkDays.includes('2')}
                  mode={mode}
                  onChange={handleCheckChange}
                  errors={errors}
              />
              <CustomCheckbox
                  label="Wed"
                  name="3"
                  checked={checkDays.includes('3')}
                  mode={mode}
                  onChange={handleCheckChange}
                  errors={errors}
              />
              <CustomCheckbox
                  label="Thur"
                  name="4"
                  checked={checkDays.includes('4')}
                  mode={mode}
                  onChange={handleCheckChange}
                  errors={errors}
                  />
              <CustomCheckbox
                  label="Fri"
                  name="5"
                  checked={checkDays.includes('5')}
                  mode={mode}
                  onChange={handleCheckChange}
                  errors={errors}
              />
              <CustomCheckbox
                  label="Sat"
                  name="6"
                  checked={checkDays.includes('6')}
                  mode={mode}
                  onChange={handleCheckChange}
                  errors={errors}
              />
              <CustomCheckbox
                  label="Sun"
                  name="7"
                  checked={checkDays.includes('7')}
                  mode={mode}
                  onChange={handleCheckChange}
                  errors={errors}
              />
            </div>
            <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
              <CustomInputText title="Value" name="value" value={data.value} mode={mode} onChange={(e) => {handleChange(e.target.name, e.target.value);}} errors={errors} />
            </div>
            <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                            <CustomCheckbox
                  label="Internal Use Only"
                  name="internal_use_only"
                  checked={data.internal_use_only}
                  mode={mode}
                  onChange={handleChange}
                  errors={errors}
              />
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
