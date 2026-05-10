import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import CustomInputText from "@/Components/CustomInputText";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomCheckbox from "@/Components/CustomCheckbox";
import GraduatedTable from "@/Components/GraduatedTable";
import VolumeDiscountEditor from "@/Components/VolumeDiscountEditor";
import { icons } from "@/icons";

const Modal = ({ isOpen, onClose, mode, item = null, services }) => {

  const { data, setData, post, put, errors, reset, processing } = useForm({});
  const [checkDays, setCheckDays] = useState([]);
  const [checkTimes, setCheckTimes] = useState([]);
  const [seasons, setSeasons] = useState(null);
  const [inventories, setInventories] = useState(null);
  const [showInfo, setShowInfo] = useState(false);
  const infoIcon = React.cloneElement(icons.info, {
    width: 16,
    height: 16,
    className: "w-4 h-4"
  });
  const skipServiceResetRef = React.useRef(false);
  const skipSeasonResetRef = React.useRef(false);
  const normalizeDateValue = (value) => {
    if (!value) return "";
    if (typeof value === "string") {
      return value.split("T")[0];
    }
    if (value instanceof Date) {
      return value.toISOString().slice(0, 10);
    }
    return value;
  };
  const normalizeParams = (params) => {
    if (!params) return {};
    if (typeof params === "string") {
      try {
        const parsed = JSON.parse(params);
        return parsed && typeof parsed === "object" ? parsed : {};
      } catch (e) {
        return {};
      }
    }
    if (typeof params === "object") {
      return params;
    }
    return {};
  };
  console.log('item ', item);
  console.log('services ', services);

  useEffect(() => {
    if (!isOpen) {
      setShowInfo(false);
    }
  }, [isOpen]);

  useEffect(() => {
    if (item && mode !== "create") {
      console.log('---------item-------', item);
      skipServiceResetRef.current = true;
      skipSeasonResetRef.current = true;
      const normalizedItem = {
        ...item,
        params: normalizeParams(item.params),
        booking_start_date: normalizeDateValue(item.booking_start_date),
        booking_end_date: normalizeDateValue(item.booking_end_date),
        activity_start_date: normalizeDateValue(item.activity_start_date),
        activity_end_date: normalizeDateValue(item.activity_end_date),
        internal_use_only: !!item.internal_use_only,
      };
      setData(normalizedItem);
      const daySlots = Array.isArray(item.promo_day_time_slots) ? item.promo_day_time_slots : [];
      const dayIds = Array.from(new Set(daySlots.map(slot => String(slot.day_id)).filter(Boolean)));
      const slotIds = Array.from(new Set(daySlots.filter(slot => slot.time_slot_id != null).map(slot => String(slot.time_slot_id))));
      const hasDayOnly = daySlots.some(slot => slot.time_slot_id == null);
      setCheckDays(dayIds);
      setCheckTimes(hasDayOnly ? [] : slotIds);
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
      if (!skipServiceResetRef.current) {
        setCheckDays([]);
        setCheckTimes([]);
      }
      setSeasons([]);
      setInventories([]);
      const service = services.find(s => s.id == data.service_id);
      console.log('seasons', service?.seasons);
      setSeasons(service?.seasons);
      skipServiceResetRef.current = false;
    };
  }, [data.service_id]);

  useEffect(() => {
    if (data.season_id) {
      if (!skipSeasonResetRef.current) {
        setCheckTimes([]);
      }
      setInventories([]);
      const season = seasons?.find(s => s.id == data.season_id);
      console.log('time_slots', season?.inventory?.inventory_time_slots);
      setInventories(season?.inventory?.inventory_time_slots);
      skipSeasonResetRef.current = false;
    };
  }, [data.season_id, seasons]);

  const handleChange = (target, value) => setData(target, value);

  const now = new Date();
  const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")}`;
  const isPastBookingStart = data.booking_start_date && data.booking_start_date < todayStr;

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

    if (mode === "create" && booking_start_date && booking_start_date < todayStr) {
      alert("Booking start date cannot be in the past.");
      return;
    }

    // Make sure activity window is inside a season for the selected service
    if (activity_start_date && activity_end_date) {
      const service = services.find(s => s.id == data.service_id);
      const season = service?.seasons?.find((s) =>
        activity_start_date >= s.start_date && activity_end_date <= s.end_date
      );

      if (!season) {
        alert("Activity dates must fall within a season for the selected service.");
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
        <div className="flex items-start justify-between gap-4 mb-4">
          <div className="flex-1">
            <h2 className="text-xl font-bold">
              {mode === "create" && "Promotion Create"}
              {mode === "edit" && "Promotion Edit"}
              {mode === "view" && "Promotion View"}
            </h2>
            {showInfo && (
              <div
                id="promotion-info-panel"
                className="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-600"
              >
                <div className="text-[12px] font-semibold text-gray-700 mb-2">
                  Promo type fields + business logic
                </div>
                <div className="grid gap-3">
                  <div>
                    <div className="font-semibold text-gray-700">Percent</div>
                    <div>Fields: Value (%).</div>
                    <div>Logic: Percent off booking total.</div>
                  </div>
                  <div>
                    <div className="font-semibold text-gray-700">Fixed Value</div>
                    <div>Fields: Value (R).</div>
                    <div>Logic: Fixed amount off booking total, or per person when "Apply to" is set to Per person.</div>
                  </div>
                  <div>
                    <div className="font-semibold text-gray-700">Graduated</div>
                    <div>Fields: Lookup table (pax count → total).</div>
                    <div>Logic: Sets booking total to lookup value for matched pax. Generally the larger the group the more discount.</div>
                  </div>
                  <div>
                    <div className="font-semibold text-gray-700">Varied Child</div>
                    <div>Fields: Min adults + Child discount %, or tiers.</div>
                    <div>Logic: Child-only discount when min adults/tier matched.</div>
                  </div>
                  <div>
                    <div className="font-semibold text-gray-700">Volume Discount</div>
                    <div>Fields: Tiers (pax range, type, value).</div>
                    <div>Logic: Best tier applies; percent or fixed value off booking total.</div>
                  </div>
                  <div>
                    <div className="font-semibold text-gray-700">Time Slot</div>
                    <div>Fields: Season, Days, optional Time Slots, Value (%).</div>
                    <div>Logic: Percent off booking total for selected days; no slots = all slots.</div>
                  </div>
                </div>
                <div className="text-[12px] text-gray-500 mt-2">
                  Day/time filters: if no days are selected, the promo applies to all days. If days are selected and
                  no time slots are checked, it applies to every slot for those days.
                </div>
              </div>
            )}
          </div>
          <button
            type="button"
            onClick={() => setShowInfo((prev) => !prev)}
            className="flex items-center justify-center w-[32px] h-[32px] bg-[#2D99FE] rounded-full p-1"
            aria-label="Toggle promotion info"
            aria-expanded={showInfo}
            aria-controls="promotion-info-panel"
            title="Promotion type info"
          >
            {infoIcon}
          </button>
        </div>

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
            {mode !== "create" && isPastBookingStart && (
              <p className="text-xs text-amber-600 -mt-2">
                Booking start date is in the past. This is allowed on edits. The promo is active as long as today falls within the booking window.
              </p>
            )}
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
                const slotId = time?.id != null ? String(time.id) : null;
                return (
                  <CustomCheckbox
                    key={idx}
                    label={time?.start_time.slice(0, 5)}
                    name={slotId}
                    checked={slotId ? checkTimes.includes(slotId) : false}
                    onChange={(name, val) =>
                      setCheckTimes((prev) =>
                        prev.includes(name) ? prev.filter((d) => d !== name) : [...prev, name]
                      )
                    }
                  />
                )
              })}
            </div>
            {data?.type === 'time_slot' && (
              <p className="text-xs text-gray-500 -mt-2">Leave time slots empty to include every slot.</p>
            )}
            {/* <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
              <CustomInputText title="Value" name="value" value={data.value} mode={mode} onChange={(e) => {handleChange(e.target.name, e.target.value);}} errors={errors} />
            </div> */}
            {(data.type === 'percent' || data.type === 'time_slot') && (
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
              <>
                <CustomInputText
                  title="Fixed Amount Off (R)"
                  type="number"
                  name="value"
                  value={data.value}
                  onChange={(e) => setData('value', e.target.value)}
                  errors={errors}
                />
                <CustomSelect
                  label="Apply To"
                  name="fixed_value_mode"
                  value={data.params?.fixed_value_mode || 'booking'}
                  mode={mode}
                  options={[
                    { id: 'booking', name: 'Booking total' },
                    { id: 'per_pax', name: 'Per person (adults + children)' },
                  ]}
                  onChange={(e) => setData('params', { ...(data.params || {}), fixed_value_mode: e.target.value })}
                  errors={errors}
                />
                <p className="text-xs text-gray-500 -mt-2">
                  Booking total: value is subtracted once. Per person: value is multiplied by pax count (adults + children).
                </p>
              </>
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
              <>
                <VolumeDiscountEditor
                  capacity={services.find(s => s.id == data.service_id)?.capacity || 0}
                  tiers={data.params?.tiers || []}
                  onChange={(tiers) => setData('params', { ...data.params, tiers })}
                />
                <p className="text-xs text-gray-500 -mt-2">Fixed value tiers apply to booking total.</p>
              </>
            )}

            {errors?.params && (
              <p className="text-red-500 text-sm mt-2">{errors.params}</p>
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
