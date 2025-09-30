import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomInputText from "@/Components/CustomInputText";
import CustomSelect from "@/Components/CustomSelect";

const EditTimeSlotModal = ({ isOpen, onClose, mode, booking, services }) => {
    // console.log("mode", mode);
    console.log("booking", booking);
    const [selectedService, setSelectedService] = useState(null);
    const [selectedSeason, setSelectedSeason] = useState(null);
    const [seasons, setSeasons] = useState([]);

    const [timeslots, setTimeslots] = useState([]);
    const [selectedSlot, setSelectedSlot] = useState(null); // stores {date: string, day: string, time: string }
    const [selectedDate, setSelectedDate] = useState(null);
    const [activityDate, setActivityDate] = useState(() => new Date(booking?.booking_date));
    const [selectedTime, setSelectedTime] = useState(null);
    const parseDate = (str) => {
        const [datePart] = str.split(" "); // strip time if present
        const [y, m, d] = datePart.split("-").map(Number);
        return new Date(y, m - 1, d); // local midnight
    };
    // console.log("roles", roles);
    const { data, setData, post, put, errors, reset, processing } = useForm({
        booking_id: '',
        service_id: '',
        season_id: '',
        booking_date: '',
        inventory_time_slot_id: '',
        start_time: '',
    });
    useEffect(() => {
        if (booking) {
            //reset();
            const customer = booking.customers.find(c => c.is_lead);
            console.log('customer ', customer);
            setData({
                booking_id: booking.id,
                service_id: booking.service_id,
                season_id: booking.season_id,
                booking_date: booking.booking_date,
                inventory_time_slot_id: booking.inventory_time_slot_id,
            });
        }
    }, [mode, booking]);

    useEffect(() => {
        if (data.service_id && data?.booking_date) {
            console.log('service_id', data.service_id);
            console.log('booking_date', data.booking_date);

            const selectedDate = parseDate(data.booking_date);
            const service = services.find(s => {
                console.log('service', s);
                // console.log('service_id ', data.service_id);
                const selected = s.id === parseInt(data.service_id);
                // console.log(selected);
                setSelectedSeason([]);
                return selected;
            });
            console.log('-------------selectedService---------', service);
            setSelectedService(service ?? null);
            const seasons = service ? service.seasons : [];
            setSeasons(seasons);
            const season = seasons?.find(s => {
                const start = new Date(s.start_date);
                const end = new Date(s.end_date);
                const selected = selectedDate >= start && selectedDate <= end;
                return selected;
            });
            setSelectedSeason(season)
            console.log('-------------selectedSeason---------', season);
            if (season) {

                const formattedTimeSlots = [
                    // { id: 0, name: "All" },
                    ...(season?.inventory?.inventory_time_slots?.map(slot => ({
                        id: slot.id,
                        name: slot.start_time.slice(0, 5), // e.g., '08:00' from '08:00:00'
                    })) || [])
                ];
                console.log('-------------formattedTimeSlots---------', formattedTimeSlots);
                setTimeslots(formattedTimeSlots);
            } else {
                setTimeslots([]);
            }
        }

    }, [data.service_id, data.booking_date]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (mode === "edit") {
            post(route("admin.bookings.send-email"), {
                onSuccess: () => onClose(),
                onError: (err) => { console.log(err); },
                data: {
                    booking_id: data.booking_id,
                    service_id: data.service_id,
                    season_id: data.season_id,
                    booking_date: data.booking_date,
                    inventory_time_slot_id: data.inventory_time_slot_id,
                },
            });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <h2 className="text-xl font-bold mb-4">
                    {mode === "edit" && "Edit Timeslot"}
                </h2>
                <form onSubmit={handleSubmit}>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <CustomSelect
                            label="Service"
                            name="service_id"
                            value={data.service_id}
                            mode={'view'}
                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            error={errors.service_id}
                            options={services}
                        />
                        <CustomSelect
                            label="Season"
                            name="season_id"
                            value={data.season_id}
                            mode={'view'}
                            onChange={(e) => { handleChange(e.target.name, e.target.value); setActivityId(e.target.value) }}
                            error={errors.season_id}
                            options={seasons}
                        />
                        <CustomInputText
                            title="Booking Date"
                            name="booking_date"
                            type='date'
                            value={data.booking_date}
                            mode={'view'}
                            onChange={(e) => { handleChange(e.target.name, e.target.value); setSelectedDate(e.target.value); }}
                            errors={errors}
                        />
                        <CustomSelect
                            label="Start Time"
                            name="start_time"
                            value={data.start_time}

                            onChange={(e) => {
                                setData('start_time', e.target.value);
                                setSelectedTime(e.target.value);
                                if (e.target.value == 0) {
                                    setSelectedSlot(null);
                                    setData('selected_slot', []);
                                    //setSelectedDate(cell.date);                                    
                                } else {
                                    console.log('--------date--------', activityDate.toLocaleDateString());
                                    console.log('--------scheduleData.days--------', scheduleData.days);
                                    const selectedDay = new Date(activityDate).getDate(); // 11
                                    const day = scheduleData.days.find(d =>
                                        (typeof d === "string" ? d : d.day)?.endsWith(selectedDay.toString().padStart(2, "0"))
                                    );

                                    const slot = timeslots.find(s => s.id === Number(e.target.value));
                                    console.log("value:", e.target.value); // "Thu 11"
                                    console.log("timeslots:", timeslots); // "Thu 11"
                                    console.log("day:", day); // "Thu 11"
                                    console.log("slot:", slot); // "Thu 11"
                                    setSelectedSlot({ activityDate: data?.booking_date, day: day, time: slot.name });
                                    setData('selected_slot', { activityDate: data?.booking_date, day: day, time: slot.name });
                                    console.log("SelectedSlot--------------------:", selectedSlot);
                                    console.log("sDatalot:-----------------------", data);
                                }
                            }}
                            error={errors.start_time}
                            options={timeslots}
                        />
                    </div>


                    <div className="flex justify-center gap-4">
                        <OutlineButton title="Cancel" onClick={onClose} width="w-[180px]" />
                        {mode !== "view" && (
                            <BlueButton title={mode === "edit" ? "Submit" : ""} width="w-[180px]" onClick={handleSubmit} disabled={processing} type="submit" />
                        )}
                    </div>
                </form>
            </div>
        </div>
    );
};

export default EditTimeSlotModal;
