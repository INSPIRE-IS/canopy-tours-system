import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import CustomInputText from "@/Components/CustomInputText";
import CustomSelect from "@/Components/CustomSelect";
import { icons } from "@/icons";
import BookingRateBreakdown from "@/Components/BookingRateBreakdown";

const ViewBookingModal = ({ isOpen, onClose, mode, booking, services }) => {
    // console.log("mode", mode);
    console.log("================booking==============", booking);
    const [selectedService, setSelectedService] = useState(null);
    const [selectedSeason, setSelectedSeason] = useState(null);
    const [seasons, setSeasons] = useState([]);

    const [timeslots, setTimeslots] = useState([]);
    const [selectedSlot, setSelectedSlot] = useState(null); // stores {date: string, day: string, time: string }
    const [selectedDate, setSelectedDate] = useState(null);
    const [activityDate, setActivityDate] = useState(null);// () => new Date(booking?.booking_date)
    const [selectedTime, setSelectedTime] = useState(null);
    const days = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31'];

    const parseDate = (str) => {
        const [datePart] = str.split(" "); // strip time if present
        const [y, m, d] = datePart.split("-").map(Number);
        return new Date(y, m - 1, d); // local midnight
    };
    // console.log("roles", roles);
    const { data, setData, post, put, errors, reset, processing } = useForm({
        bookingPax: '',
        booking_date: "",
        customers: [],
        due: null,
        dueStatus: false,
        due_date: "",
        id: null,
        inventory_time_slot_id: null,
        lead_id: null,
        lead_name: "",
        maxPax: null,
        paid: null,
        price: null,
        rate_breakdown: null,
        reference: "",
        season_id: null,
        service: "",
        service_id: null,
        time: "",
    });
    useEffect(() => {
        if (booking) {
            //reset();
            const customer = booking?.customers?.find(c => c.is_lead);
            console.log('customer ', customer);
            setData({
                bookingPax: booking.bookingPax,
                booking_date: booking.booking_date,
                customers: booking.customers ?? [],
                due: booking.due,
                dueStatus: booking.dueStatus,
                due_date: booking.due_date,
                id: booking.id,
                inventory_time_slot_id: booking.inventory_time_slot_id,
                lead_id: booking.lead_id,
                lead_name: booking.lead_name,
                maxPax: booking.maxPax,
                paid: booking.paid,
                price: booking.price,
                rate_breakdown: booking.rate_breakdown,
                reference: booking.reference,
                season_id: booking.season_id,
                service: booking.service,
                service_id: booking.service_id,
                start_time: booking.time,
            });
            setActivityDate(new Date(booking.booking_date));
        }
    }, [mode, booking]);

    useEffect(() => {
        if (data.service_id && data?.booking_date && Array.isArray(services)) {
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
        console.log('data', data);
        return;

        if (mode === "edit") {
            post(route("admin.bookings.update-timeslot"), {
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
                    #{data?.id ?? ""} Booking {data?.reference ?? ""} For {data?.bookingPax ?? 0} Clients on {data?.booking_date ?? ""}
                </h2>
                <form onSubmit={handleSubmit}>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
                                    const selectedDay = new Date(activityDate).getDate();
                                    console.log('selectedDay', selectedDay.toString().padStart(2, "0"));
                                    const day = days.find(d =>
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
                        <CustomInputText
                            title="Lead Name"
                            name="lead_name"
                            type='text'
                            value={data.lead_name}
                            mode={'view'}
                            onChange={(e) => { handleChange(e.target.name, e.target.value); }}
                            errors={errors}
                        />

                        <CustomInputText
                            title="Price"
                            name="price"
                            type='text'
                            value={data.price}
                            mode={'view'}
                            onChange={(e) => { handleChange(e.target.name, e.target.value); }}
                            errors={errors}
                        />
                        <CustomInputText
                            title="Paid"
                            name="paid"
                            type='text'
                            value={data.paid}
                            mode={'view'}
                            onChange={(e) => { handleChange(e.target.name, e.target.value); }}
                            errors={errors}
                        />
                        <CustomInputText
                            title="Due"
                            name="due"
                            type='text'
                            value={data.due}
                            mode={'view'}
                            onChange={(e) => { handleChange(e.target.name, e.target.value); }}
                            errors={errors}
                            disabled={true}
                        />
                        <div className="col-span-1 md:col-span-3">
                            <BookingRateBreakdown breakdown={data.rate_breakdown} />
                        </div>
                        <CustomInputText
                            title="Due Date"
                            name="due_date"
                            type='date'
                            value={data.due_date}
                            mode={'view'}
                            onChange={(e) => { handleChange(e.target.name, e.target.value); setSelectedDate(e.target.value); }}
                            errors={errors}
                        />
                    </div>
                    <div className="grid grid-cols-1 gap-4">
                        {(data.customers || []).map((customer, index) => (
                            <div key={index} className="border p-3 mb-2 rounded">
                                <div><span className="font-bold capitalize">{customer.type}</span> </div>
                                <div className="grid grid-cols-3 space-y-1">
                                    <div><div className="font-bold">full_name</div> {customer.full_name}</div>
                                    <div><div className="font-bold">email_address</div> {customer.email_address}</div>
                                    <div><div className="font-bold">contact_number</div> {customer.contact_number}</div>
                                    <div><span className="font-bold">is_indemnity </span> {customer.is_indemnity ? "Yes" : "No"}</div>
                                    <div><span className="font-bold">age</span> {customer.age}</div>

                                    <div><span className="font-bold">price</span> {customer.price}</div>
                                    <div><span className="font-bold">Outstanding</span> {customer.due}</div>
                                    <div><span className="font-bold">discount</span> {customer.discount}</div>
                                    <div><span className="font-bold">booking_discount_item_id</span> {customer.booking_discount_item_id}</div>
                                    <div><span className="font-bold">booking_item_id</span> {customer.booking_item_id}</div>
                                    <div><span className="font-bold">discount_id</span> {customer.discount_id}</div>
                                </div>
                                <div><span className="font-bold">notes</span> {customer.notes ? customer.notes : "No notes"}</div>
                                <div className="flex w-full bg-gray-100 justify-start items-center gap-2 border p-3 my-2 rounded">
                                    {customer.files?.map((file, index) => (
                                        <span key={index} >
                                            <a href={`/storage/${file.path}`} target="_blank" rel="noopener noreferrer" className="bg-white btn-tool">{icons.docs} {file.file_name}</a>
                                        </span>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                    <div className="flex justify-center gap-4">
                        <OutlineButton title="Close" onClick={onClose} width="w-[180px]" />
                        {/* {mode !== "view" && (
                            <BlueButton title={mode === "edit" ? "Submit" : ""} width="w-[180px]" onClick={handleSubmit} disabled={processing} type="submit" />
                        )} */}
                    </div>
                </form>
            </div>
        </div>
    );
};

export default ViewBookingModal;
