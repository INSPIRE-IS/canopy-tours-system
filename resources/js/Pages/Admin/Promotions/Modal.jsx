import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import CustomInputText from "@/Components/CustomInputText";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomCheckbox from "@/Components/CustomCheckbox";
import GraduatedTable from "@/Components/GraduatedTable";
import VolumeDiscountEditor from "@/Components/VolumeDiscountEditor";

const Modal = ({ isOpen, onClose, mode, item = null, services }) => {

  const { data, setData, post, put, errors, reset, processing } = useForm({});
  const [checkDays, setCheckDays] = useState([]);
  const [checkTimes, setCheckTimes] = useState([]);
  const [seasons, setSeasons] = useState(null);
  const [inventories, setInventories] = useState(null);
  console.log('item ', item);
  console.log('services ', services);

  useEffect(() => {
    if (item && mode !== "create") {
      console.log('---------item-------', item);
      setData({ ...item });
      setCheckDays(item.valid_days ? item.valid_days.split(",") : []);
    } else if (mode === "create") {
      reset();
      setCheckDays([]);
      setCheckTimes([]);
      setSeasons([]);
      setInventories([]);
    }
  }, [mode, item]);

  useEffect(() => {
    if (data.service_id) {
      setCheckDays([]);
      setCheckTimes([]);
      setSeasons([]);
      setInventories([]);
      const service = services.find(s => s.id == data.service_id);
      console.log('seasons', service?.seasons);
      setSeasons(service?.seasons);
    };
  }, [data.service_id]);

  useEffect(() => {
    if (data.season_id) {
      setCheckDays([]);
      setCheckTimes([]);
      setInventories([]);
      const season = seasons.find(s => s.id == data.season_id);
      console.log('time_slots', season?.inventory?.inventory_time_slots);
      setInventories(season?.inventory?.inventory_time_slots);
    };
  }, [data.season_id]);

  const handleChange = (target, value) => setData(target, value);


  const handleSubmit = (e) => {
    e.preventDefault();
    //console.log('checkDays ', checkDays);
    data.valid_days = checkDays.join(',');
    data.valid_slots = checkTimes.join(',');
    // --- Validate booking & activity dates ---
    const { booking_start_date, booking_end_date, activity_start_date, activity_end_date } = data;

    if (booking_start_date && booking_end_date && booking_start_date > booking_end_date) {
      alert("Booking start date cannot be after booking end date.");
      return;
    }

    if (activity_start_date && activity_end_date && activity_start_date > activity_end_date) {
      alert("Activity start date cannot be after activity end date.");
      return;
    }

    // Make sure booking window is inside the chosen season
    const season = services.find(s => s.id == data.service_id)?.season;
    if (season) {
      if (booking_start_date < season.start_date || booking_end_date > season.end_date) {
        alert(`Booking dates must be within the season (${season.start_date} → ${season.end_date}).`);
        return;
      }
      if (activity_start_date < season.start_date || activity_end_date > season.end_date) {
        alert(`Activity dates must be within the season (${season.start_date} → ${season.end_date}).`);
        return;
      }
    }

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
                mode={mode} // 'percent', 'fixed_value', 'graduated', 'varied_child', 'volume_discount'
                options={[
                  { id: 'percent', name: 'Percent' },
                  { id: 'fixed_value', name: 'Fixed Value' },
                  { id: 'graduated', name: 'Graduated (per pax)' },
                  { id: 'varied_child', name: 'Varied Child (by adults)' },
                  { id: 'volume_discount', name: 'Volume Discount' },
                  { id: 'time_slot', name: 'Time Slot' },
                ]}
                onChange={(e) => handleChange(e.target.name, e.target.value)}
                errors={errors}
              />
            </div>
            <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
              <CustomInputText title="Name" name="name" value={data.name} mode={mode} onChange={(e) => { handleChange(e.target.name, e.target.value); }} errors={errors} />
              <CustomInputText title="Promo Code" name="promo_code" value={data.promo_code} mode={mode} onChange={(e) => { handleChange(e.target.name, e.target.value); }} errors={errors} />
            </div>
            <span className="text-booking-card-title">Booking Dates</span>
            <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
              <CustomInputText title="Start Date (YYYY-MM-DD)" type="date" name="booking_start_date" value={data.booking_start_date} mode={mode} onChange={(e) => { handleChange(e.target.name, e.target.value); }} errors={errors} />
              <CustomInputText title="End Date (YYYY-MM-DD)" type="date" name="booking_end_date" value={data.booking_end_date} mode={mode} onChange={(e) => { handleChange(e.target.name, e.target.value); }} errors={errors} />
            </div>
            <span className="text-booking-card-title">Activity Dates</span>
            <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
              <CustomInputText title="Start Date (YYYY-MM-DD)" type="date" name="activity_start_date" value={data.activity_start_date} mode={mode} onChange={(e) => { handleChange(e.target.name, e.target.value); }} errors={errors} />
              <CustomInputText title="End Date (YYYY-MM-DD)" type="date" name="activity_end_date" value={data.activity_end_date} mode={mode} onChange={(e) => { handleChange(e.target.name, e.target.value); }} errors={errors} />
            </div>
            {data?.type === 'time_slot' && (
              <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                <CustomSelect
                  label="Season"
                  name="season_id"
                  value={data.season_id}
                  options={seasons}
                  mode={mode}
                  onChange={(e) => handleChange(e.target.name, e.target.value)}
                  errors={errors}
                />
              </div>
            )}
            <div className="flex gap-2 flex-wrap mb-4">
              {["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"].map((day, idx) => (
                <CustomCheckbox
                  key={idx}
                  label={day}
                  name={(idx + 1).toString()}
                  checked={checkDays.includes((idx + 1).toString())}
                  onChange={(name, val) =>
                    setCheckDays((prev) =>
                      prev.includes(name) ? prev.filter((d) => d !== name) : [...prev, name]
                    )
                  }
                />
              ))}
            </div>
            <div className="flex gap-2 flex-wrap mb-4">
              {inventories?.map((time, idx) => {
                console.log('time', time);
                return (
                  <CustomCheckbox
                    key={idx}
                    label={time?.start_time.slice(0, 5)}
                    name={(idx + 1).toString()}
                    checked={checkTimes.includes((idx + 1).toString())}
                    onChange={(name, val) =>
                      setCheckTimes((prev) =>
                        prev.includes(name) ? prev.filter((d) => d !== name) : [...prev, name]
                      )
                    }
                  />
                )
              })}
            </div>
            {/* <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
              <CustomInputText title="Value" name="value" value={data.value} mode={mode} onChange={(e) => {handleChange(e.target.name, e.target.value);}} errors={errors} />
            </div> */}
            {data.type === 'percent' || data.type === 'time_slot' && (
              <CustomInputText
                title="Percent Off (%)"
                type="number"
                name="value"
                value={data.value}
                onChange={(e) => setData('value', e.target.value)}
                errors={errors}
              />
            )}

            {data.type === 'fixed_value' && (
              <CustomInputText
                title="Fixed Amount Off (R)"
                type="number"
                name="value"
                value={data.value}
                onChange={(e) => setData('value', e.target.value)}
                errors={errors}
              />
            )}

            {data.type === 'graduated' && (
              <GraduatedTable
                capacity={services.find(s => s.id == data.service_id)?.capacity || 0}
                lookup={data.params?.lookup || {}}
                onChange={(lookup) => setData('params', { ...data.params, lookup })}
              />
            )}

            {data.type === 'varied_child' && (
              <>
                <CustomInputText
                  title="Min Adults"
                  type="number"
                  name="min_adults"
                  value={data.params?.min_adults || ''}
                  onChange={(e) => setData('params', { ...data.params, min_adults: e.target.value })}
                  errors={errors}
                />
                <CustomInputText
                  title="Child Discount (%)"
                  type="number"
                  name="child_discount_percent"
                  value={data.params?.child_discount_percent || ''}
                  onChange={(e) => setData('params', { ...data.params, child_discount_percent: e.target.value })}
                  errors={errors}
                />
              </>
            )}

            {data.type === 'volume_discount' && (
              <VolumeDiscountEditor
                capacity={services.find(s => s.id == data.service_id)?.capacity || 0}
                tiers={data.params?.tiers || []}
                onChange={(tiers) => setData('params', { ...data.params, tiers })}
              />
            )}


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
