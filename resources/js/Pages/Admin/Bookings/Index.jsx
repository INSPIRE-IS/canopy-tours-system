import BookingToolsModal from '@/Components/BookingToolsModal';
import BookingRateBreakdown from '@/Components/BookingRateBreakdown';
import CustomerToolsModal from '@/Components/CustomerToolsModal';
import GuestEmailModal from '@/Components/GuestEmailModal';
import BookingNotesModal from '@/Components/BookingNotesModal';
import CheckInOptionsModal from '@/Components/CheckInOptionsModal';
import FeedbackOptionsModal from '@/Components/FeedbackOptionsModal';
import Header from '@/Components/Header';
import { icons } from '@/icons';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import React, { useEffect, useMemo, useRef, useState } from 'react';
import ManifestsModal from './ManifestsModal';
import ManifestModal from './ManifestModal';
import EditTimeSlotModal from './EditTimeSlotModal';
import ViewBookingModal from './ViewBookingModal';
import NavSelect from '@/Components/NavSelect';
import DatePicker from '@/Components/DatePicker';
import WhiteButton from '@/Components/WhiteButton';
import { addDays, addSeconds, endOfDay, endOfWeek, format, isAfter, isBefore, isSameDay, isValid, isWithinInterval, parse, parseISO, startOfDay, startOfWeek } from 'date-fns';
import ConfirmModal from '@/Components/ConfirmModal';
import Modal from '@/Components/Modal';
import { useToast } from '@/Context/ToastContext';
import CustomDatePicker from '@/Components/CustomDatePicker';
// import { useRoute } from 'ziggy-js';
// import { Ziggy } from '@/ziggy'; // the generated file

export default function Dashboard({ auth, bookings, activities, lookups, services, closeouts = [] }) {
    const VIEW_MODES = {
        DAY: 'day',
        RANGE: 'range',
        WEEK: 'week',
    };

    // Keep the legacy dropdown-style search code in place (disabled for now),
    // but the primary UX is: filter the bookings table to matching results.
    const ENABLE_SEARCH_DROPDOWN = false;
    const { props } = usePage();
    const { flash } = props;
    const { showToast } = useToast();
    console.log("bookings", bookings)
    const [filteredBookings, setFilteredBookings] = useState(bookings);
    const [expanded, setExpanded] = useState(null); // track which booking is expanded
    const [customerToolsTarget, setCustomerToolsTarget] = useState(null); // { customer, booking, position }
    const [bookingToolsTarget, setBookingToolsTarget] = useState(null); // { customer, position }
    const [guestEmailTarget, setGuestEmailTarget] = useState(null); // { customer, position }
    const [bookingNotesTarget, setBookingNotesTarget] = useState(null);
    const [isEmailModalOpen, setIsEmailModalOpen] = useState(false);
    const [isViewBookingModalOpen, setIsViewBookingModalOpen] = useState(false);
    const [isEditTimeSlotModalOpen, setIsEditTimeSlotModalOpen] = useState(false);
    const [isManifestModalOpen, setIsManifestModalOpen] = useState(false);
    const [isManifestsModalOpen, setIsManifestsModalOpen] = useState(false);
    const [selectedBooking, setSelectedBooking] = useState(null);
    const [linkedActivitiesTarget, setLinkedActivitiesTarget] = useState(null);
    const [selectedManifests, setSelectedManifests] = useState(null);
    const [selectedManifest, setSelectedManifest] = useState(null);
    const today = format(new Date(), 'yyyy-MM-dd');
    const [selectedDate, setSelectedDate] = useState(() => {
        return sessionStorage.getItem('bookings.selectedDate') || format(new Date(), 'yyyy-MM-dd');
    });
    const [rangeStart, setRangeStart] = useState(() => {
        return sessionStorage.getItem('bookings.rangeStart') || format(new Date(), 'yyyy-MM-dd');
    });
    const [rangeEnd, setRangeEnd] = useState(() => {
        return sessionStorage.getItem('bookings.rangeEnd') || format(new Date(), 'yyyy-MM-dd');
    });
    const [viewMode, setViewMode] = useState(() => {
        return sessionStorage.getItem('bookings.viewMode') || VIEW_MODES.DAY;
    });
    const [selectedServiceId, setSelectedServiceId] = useState(() => {
        return sessionStorage.getItem('bookings.selectedServiceId') || '0';
    });
    const [showEmptySlots, setShowEmptySlots] = useState(() => {
        return sessionStorage.getItem('bookings.showEmptySlots') === 'true';
    });
    const [showCancelledBookings, setShowCancelledBookings] = useState(() => {
        return new URLSearchParams(window.location.search).get('include_cancelled') === '1';
    });
    const [showServices, setShowServices] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [searchOpen, setSearchOpen] = useState(false);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [confirmAction, setConfirmAction] = useState(null); // { type: 'booking' | 'slot', booking }
    const [slotRows, setSlotRows] = useState([]);
    const [viewDocsIndex, setViewDocsIndex] = useState(null);
    const [checkInTarget, setCheckInTarget] = useState(null);
    const [feedbackTarget, setFeedbackTarget] = useState(null);
    const servicesRef = useRef();
    const searchRef = useRef();
    useEffect(() => {
        if (flash?.success) {
            showToast(flash.success, "success");
        }
        if (flash?.error) {
            showToast(flash.error, "error");
        }
    }, [flash, showToast]);
    const openBookingToolsModal = (event, booking) => {
        event.preventDefault();
        console.log("booking----------tool-------- ", booking)

        const rect = event.currentTarget.getBoundingClientRect();
        setBookingToolsTarget({
            booking,
            position: {
                top: rect.top,
                bottom: rect.bottom,
                left: rect.left,
                right: rect.right,
            }
        });
    };
    const openCustomerToolsModal = (event, booking, customer, indexKey) => {
        event.preventDefault();

        const rect = event.currentTarget.getBoundingClientRect();
        setCustomerToolsTarget({
            booking,
            customer,
            indexKey,
            position: {
                top: rect.top,
                bottom: rect.bottom,
                left: rect.left,
                right: rect.right,
            }
        });
    };

    const handleConfirmCancel = () => {
        if (!confirmAction?.booking) {
            setConfirmOpen(false);
            return;
        }

        if (confirmAction.type === 'reinstate') {
            router.post(route('admin.bookings.reinstate', confirmAction.booking.id), {
                force_capacity_override: !!confirmAction.forceCapacityOverride,
            }, {
                onSuccess: () => {
                    setConfirmAction(null);
                    setConfirmOpen(false);
                    setSelectedBooking(null);
                    setShowCancelledBookings(false);
                    router.visit(route('admin.bookings.index'));
                },
                onError: () => {
                    setConfirmAction(null);
                    setConfirmOpen(false);
                    setSelectedBooking(null);
                }
            });
            return;
        }

        if (confirmAction.type === 'slot') {
            const payload = {
                booking_date: confirmAction.booking.booking_date,
                inventory_time_slot_id: confirmAction.booking.inventory_time_slot_id,
                service_id: confirmAction.booking.service_id,
            };
            router.post(route('admin.bookings.cancel-slot'), payload, {
                onSuccess: () => {
                    setConfirmAction(null);
                    setConfirmOpen(false);
                    setSelectedBooking(null);
                    router.visit(route('admin.bookings.index'));
                },
                onError: () => {
                    setConfirmAction(null);
                    setConfirmOpen(false);
                    setSelectedBooking(null);
                }
            });
            return;
        }

        router.delete(route('admin.bookings.destroy', confirmAction.booking.id), {
            onSuccess: () => {
                setConfirmAction(null);
                setConfirmOpen(false);
                setSelectedBooking(null);
                router.visit(route('admin.bookings.index'));
            },
            onError: () => {
                setConfirmAction(null);
                setConfirmOpen(false);
                setSelectedBooking(null);
            }
        });
    };

    const getSelectedBookingDate = () => {
        if (viewMode === VIEW_MODES.RANGE) {
            return rangeStart || today;
        }
        return selectedDate || today;
    };

    const normalizeBookingDate = (bookingDate) => {
        if (!bookingDate) return null;
        const parsed = parseISO(bookingDate);
        return isValid(parsed) ? parsed : null;
    };

    const getBookingsForDate = (dateStr) => {
        if (!dateStr) return [];
        const target = parseISO(dateStr);
        if (!isValid(target)) return [];
        return (Array.isArray(bookings) ? bookings : []).filter((booking) => {
            if (selectedServiceId !== '0' && Number(booking.service_id) !== Number(selectedServiceId)) {
                return false;
            }
            const bookingDate = normalizeBookingDate(booking.booking_date);
            return bookingDate && isSameDay(bookingDate, target);
        });
    };

    const targetDate = getSelectedBookingDate();
    const targetDateObj = targetDate ? parseISO(targetDate) : null;
    const isTargetDateValid = targetDateObj && isValid(targetDateObj);
    const isPastDate = isTargetDateValid ? isBefore(targetDateObj, startOfDay(new Date())) : false;
    const isFutureDate = isTargetDateValid ? isAfter(targetDateObj, startOfDay(new Date())) : false;
    const bookingsForTargetDate = getBookingsForDate(targetDate);
    const hasBookingsForTargetDate = bookingsForTargetDate.length > 0;
    const hasCheckinForTargetDate = bookingsForTargetDate.some((booking) =>
        (booking?.customers || []).some((cust) => cust?.is_indemnity)
    );
    const checkinDisabled = !hasBookingsForTargetDate || isPastDate;
    const feedbackDisabled = !hasBookingsForTargetDate || isFutureDate || !hasCheckinForTargetDate;

    const openCheckInOptions = (booking) => {
        if (!booking?.booking_item_id || !booking?.reference) {
            showToast('Booking item not found for check-in.', 'error');
            return;
        }
        setCheckInTarget({
            booking_item_id: booking.booking_item_id,
            reference: booking.reference,
            lead_name: booking.lead_name,
            lead_phone: booking.lead_phone,
            lead_email: booking.lead_email,
        });
    };

    const openFeedbackOptions = (booking) => {
        if (!booking?.booking_item_id || !booking?.reference) {
            showToast('Booking item not found for feedback.', 'error');
            return;
        }

        const indemnityCustomer = booking?.customers?.find((cust) => cust?.is_indemnity);
        if (!indemnityCustomer) {
            showToast('Feedback links are available after check-in is completed.', 'error');
            return;
        }

        setFeedbackTarget({
            booking_item_id: booking.booking_item_id,
            reference: booking.reference,
            customer_id: indemnityCustomer.customer_id,
            lead_name: booking.lead_name,
            lead_phone: booking.lead_phone,
            lead_email: booking.lead_email,
        });
    };


    function BookingToolClick(tool, booking) {

        console.log("-------tool clicked-------", tool);

        if (tool === 'remove_resource') {
            router.get(route('admin.bookings.remove_resource'));
        }


        if (tool === 'cancel_bookings') {
            setConfirmOpen(true);
            setSelectedBooking(booking);
            setConfirmAction({ type: 'slot', booking });
        }
        if (tool === 'reinstate_booking') {
            setConfirmOpen(true);
            setSelectedBooking(booking);
            setConfirmAction(buildReinstateAction(booking));
        }
    };

    const ViewBookingClick = (booking) => {
        //router.get(route('admin.bookings.edit', booking.id));
        setSelectedBooking(booking);
        setIsViewBookingModalOpen(true);
    };

    const CustomerToolClick = (tool, booking, customer) => {
        if (tool === 'check_in') {
            openCheckInOptions(booking);
        }
        if (tool === 'feedback') {
            openFeedbackOptions(booking);
        }
        if (tool === 'resend_invoice') {
            router.get(route('admin.invoices.resend-invoice', { booking_id: booking.id }));
        }
        if (tool === 'add_payment') {
            router.get(route('admin.booking-payments.create', booking.id));
        }
        if (tool === 'manifest') {
            setSelectedManifests(booking?.customers);
            setSelectedManifest(customer);
            setIsManifestModalOpen(true);
        }
        if (tool === 'customer_invoice') {
            if (!customer?.files || customer.files.length === 0) {
                prompt('No invoice found');
                return;
            }

            const file = customer.files.find(f => f.table_name == 'invoices');
            if (!file) {
                prompt('No invoice found');
                return;
            }
            const fileUrl = `${file.path}`;
            window.open(fileUrl, '_blank', 'noopener,noreferrer');
        }
        if (tool === 'edit_booking') {
            router.get(route('admin.bookings.edit', {
                booking: booking.id,
                booking_item_id: booking.booking_item_id,
            }));
        }
        if (tool === 'edit_timeslot') {
            //router.get(route('admin.bookings.edit', booking.id));
            // setSelectedBooking({
            //     ...booking,
            //     booking_id: booking?.booking_id ?? booking?.id,
            // });
            setSelectedBooking(booking);
            setIsEditTimeSlotModalOpen(true);
        }

        if (tool === 'message_guest') {
            setGuestEmailTarget(booking);
            setIsEmailModalOpen(true);
        }
        if (tool === 'booking_notes') {
            setBookingNotesTarget(booking);
        }
        // if (tool === 'message_staff') {
        //     router.get(route('admin.messenger.createTopic'));
        // }
        if (tool === 'check_in_progress') {
            openCheckInOptions(booking);
        }
        if (tool === 'manifest_edit') {
            //router.get(route('admin.bookings.manifest_edit'));
            setSelectedManifests(booking.customers);
            setIsManifestsModalOpen(true);
        }
        if (tool === 'cancel_booking') {
            setConfirmOpen(true);
            setSelectedBooking(booking);
            setConfirmAction({ type: 'booking', booking });
        }
        if (tool === 'reinstate_booking') {
            setConfirmOpen(true);
            setSelectedBooking(booking);
            setConfirmAction(buildReinstateAction(booking));
        }
    };

    const buildReinstateAction = (booking) => {
        const activeSlotPax = Number(booking?.active_slot_pax || 0);
        const bookingPax = Number(booking?.bookingPax || 0);
        const maxPax = Number(booking?.maxPax || 0);
        const projectedPax = activeSlotPax + bookingPax;
        const exceedsCapacity = maxPax > 0 && projectedPax > maxPax;

        return {
            type: 'reinstate',
            booking,
            forceCapacityOverride: exceedsCapacity,
            capacityWarning: exceedsCapacity
                ? `Reinstating this booking will take the slot to ${projectedPax}/${maxPax} pax. Tick the override confirmation to reinstate anyway.`
                : null,
        };
    };

    const getTimeRange = (startTime, duration) => {
        if (!startTime || !duration) {
            return { start: null, end: null };
        }

        const start = parse(startTime, 'HH:mm:ss', new Date());
        const [hours, minutes, seconds] = duration.split(':').map((v) => Number(v || 0));
        const totalSeconds = (hours * 3600) + (minutes * 60) + seconds;
        const end = addSeconds(start, totalSeconds);
        return {
            start: format(start, 'HH:mm'),
            end: format(end, 'HH:mm'),
        };
    };

    const formatTimeValue = (timeStr) => {
        if (!timeStr) return null;
        return timeStr.slice(0, 5);
    };

    const timeToMinutes = (timeStr) => {
        if (!timeStr) return Number.MAX_SAFE_INTEGER;
        const [hours, minutes] = timeStr.split(':').map((v) => Number(v || 0));
        return (hours * 60) + minutes;
    };

    const isDateWithinSeason = (dateStr, season) => {
        if (!dateStr || !season?.start_date || !season?.end_date) return false;
        const date = parseISO(dateStr);
        const start = parseISO(season.start_date);
        const end = parseISO(season.end_date);
        if (!isValid(date) || !isValid(start) || !isValid(end)) return false;
        return isWithinInterval(date, { start: startOfDay(start), end: endOfDay(end) });
    };

    const isDateInRange = (dateStr, startStr, endStr) => {
        if (!dateStr || !startStr || !endStr) return false;
        const date = parseISO(dateStr);
        const start = parseISO(startStr);
        const end = parseISO(endStr);
        if (!isValid(date) || !isValid(start) || !isValid(end)) return false;
        return isWithinInterval(date, { start: startOfDay(start), end: endOfDay(end) });
    };

    const getIsoDay = (dateStr) => {
        const date = parseISO(dateStr);
        return isValid(date) ? Number(format(date, 'i')) : null;
    };

    const isSlotClosedOut = (serviceId, seasonId, dateStr, slotId) => {
        const isoDay = getIsoDay(dateStr);
        return closeouts.some((closeout) => {
            if (Number(closeout.service_id) !== Number(serviceId) || Number(closeout.season_id) !== Number(seasonId)) {
                return false;
            }
            if (!isDateInRange(dateStr, closeout.start_date, closeout.end_date)) {
                return false;
            }

            const blockedSlots = closeout.closeout_day_time_slots || [];
            if (blockedSlots.length === 0) {
                return true;
            }

            return blockedSlots.some((blockedSlot) => {
                return Number(blockedSlot.day_id) === Number(isoDay)
                    && Number(blockedSlot.time_slot_id) === Number(slotId);
            });
        });
    };

    const getAvailableSlotsForDate = (service, season, dateStr) => {
        const inventory = season?.inventory;
        if (!inventory) return [];

        const allSlots = inventory.inventory_time_slots || [];
        const slotById = new Map(allSlots.map((slot) => [Number(slot.id), slot]));
        const overrideRanges = (inventory.inventory_slot_ranges || [])
            .filter((range) => isDateInRange(dateStr, range.start_date, range.end_date));

        let slots = [];
        if (overrideRanges.length > 0) {
            slots = overrideRanges
                .map((range) => {
                    const slot = slotById.get(Number(range.time_slot_id));
                    if (!slot) return null;
                    return {
                        ...slot,
                        maxPax: range.capacity_override ?? service.capacity ?? 0,
                    };
                })
                .filter(Boolean);
        } else {
            const isoDay = getIsoDay(dateStr);
            const allowedDays = (inventory.inventory_type_days || []).map((day) => Number(day.type_day_id));
            if (!allowedDays.includes(Number(isoDay))) {
                return [];
            }

            slots = allSlots
                .filter((slot) => !slot.is_override_only)
                .map((slot) => ({
                    ...slot,
                    maxPax: service.capacity ?? 0,
                }));
        }

        const uniqueSlots = Array.from(new Map(slots.map((slot) => [Number(slot.id), slot])).values());
        return uniqueSlots.filter((slot) => !isSlotClosedOut(service.id, season.id, dateStr, slot.id));
    };

    const handleExportCSV = (bookings) => {
        const headers = [
            "Booking ID",
            "Reference",
            "Lead Name",
            "Service",
            "Time",
            "Booking Date",
            "Booking Pax",
            "Total Price",
            "Total Paid",
            "Total Due",
            "Customer Name",
            "Customer Email",
            "Customer Phone",
            "Customer Age",
            "Customer Type",
            "Customer Price",
            "Customer Paid",
            "Customer Due",
            "Notes",
        ];

        const rows = [];

        bookings.forEach((booking) => {
            // Booking-level row
            rows.push([
                booking.id,
                booking.reference,
                booking.lead_name,
                booking.service,
                booking.time,
                booking.booking_date,
                booking.bookingPax,
                booking.price,
                booking.paid,
                booking.due,
                "", "", "", "", "", "", "", "", "" // empty customer columns
            ]);

            // Customer rows indented
            booking.customers.forEach((cust, idx) => {
                rows.push([
                    "", // blank booking ID for nesting effect
                    booking.reference, // keep reference if you want traceability
                    idx === 0 ? "Customers:" : "", // only label once
                    "", "", "", "", "", "", "",
                    cust.full_name || "Unknown Customer",
                    cust.email_address || "Unknown Email",
                    cust.contact_number || "Unknown Phone",
                    cust.age || "Unknown Age",
                    cust.type || "Unknown Type",
                    cust.price,
                    cust.paid,
                    cust.due,
                    cust.notes || "None",
                ]);
            });
        });

        const csvContent =
            "data:text/csv;charset=utf-8," +
            [headers, ...rows]
                .map((e) =>
                    e.map((v) => `"${v ?? ""}"`).join(",")
                )
                .join("\n");

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "bookings_export.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (servicesRef.current && !servicesRef.current.contains(event.target)) {
                setShowServices(false);
            }
            if (searchRef.current && !searchRef.current.contains(event.target)) {
                setSearchOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const searchResults = useMemo(() => {
        const term = (searchQuery || '').trim().toLowerCase();
        if (!term) return [];
        const digits = term.replace(/\D/g, '');

        const matches = (Array.isArray(bookings) ? bookings : []).filter((booking) => {
            const haystack = [
                booking?.reference,
                booking?.lead_name,
                booking?.lead_email,
                booking?.service,
                booking?.booking_date,
                booking?.time_start,
            ]
                .filter(Boolean)
                .map((v) => String(v).toLowerCase());

            if (haystack.some((v) => v.includes(term))) {
                return true;
            }

            if (digits) {
                const leadDigits = String(booking?.lead_phone || '').replace(/\D/g, '');
                if (leadDigits && leadDigits.includes(digits)) {
                    return true;
                }
            }

            const customers = Array.isArray(booking?.customers) ? booking.customers : [];
            return customers.some((cust) => {
                const custHaystack = [
                    cust?.full_name,
                    cust?.email_address,
                ]
                    .filter(Boolean)
                    .map((v) => String(v).toLowerCase());
                if (custHaystack.some((v) => v.includes(term))) {
                    return true;
                }
                if (digits) {
                    const custDigits = String(cust?.contact_number || '').replace(/\D/g, '');
                    return custDigits && custDigits.includes(digits);
                }
                return false;
            });
        });

        const parseDate = (dateStr) => {
            if (!dateStr) return 0;
            const parsed = parseISO(dateStr);
            return isValid(parsed) ? parsed.getTime() : 0;
        };
        const parseTime = (timeStr) => {
            if (!timeStr) return 0;
            const [hours, minutes] = String(timeStr).split(':');
            return (Number(hours || 0) * 60) + Number(minutes || 0);
        };

        matches.sort((a, b) => {
            const dateDiff = parseDate(b?.booking_date) - parseDate(a?.booking_date);
            if (dateDiff !== 0) return dateDiff;
            return parseTime(b?.time_start) - parseTime(a?.time_start);
        });

        return matches;
    }, [bookings, searchQuery]);

    const handleFilterBookings = (activity_id) => {
        setSelectedServiceId(activity_id);
    };

    const isSearchActive = (searchQuery || '').trim().length >= 3;

    const applyFilters = () => {
        // When searching, we intentionally ignore the current date/day/week/range view.
        // Search is "global" across this site's bookings.
        if (isSearchActive) {
            let next = Array.isArray(searchResults) ? searchResults : [];
            if (selectedServiceId !== '0') {
                next = next.filter((booking) => Number(booking.service_id) === Number(selectedServiceId));
            }
            return next;
        }

        let next = Array.isArray(bookings) ? bookings : [];
        if (selectedServiceId !== '0') {
            next = next.filter(booking => Number(booking.service_id) === Number(selectedServiceId));
        }

        if (viewMode === VIEW_MODES.DAY) {
            const selected = selectedDate ? parseISO(selectedDate) : null;
            const day = selected ? startOfDay(selected) : null;
            next = next.filter(booking => {
                const bookingDate = normalizeBookingDate(booking.booking_date);
                return bookingDate && day && isSameDay(bookingDate, day);
            });
        } else if (viewMode === VIEW_MODES.RANGE) {
            const startDate = rangeStart ? parseISO(rangeStart) : null;
            const endDate = rangeEnd ? parseISO(rangeEnd) : null;
            const start = startDate && endDate && startDate > endDate ? endDate : startDate;
            const end = startDate && endDate && startDate > endDate ? startDate : endDate;
            next = next.filter(booking => {
                const bookingDate = normalizeBookingDate(booking.booking_date);
                if (!bookingDate || !start || !end) return false;
                return isWithinInterval(bookingDate, {
                    start: startOfDay(start),
                    end: endOfDay(end),
                });
            });
        } else if (viewMode === VIEW_MODES.WEEK) {
            const selected = selectedDate ? parseISO(selectedDate) : new Date();
            const weekStart = startOfWeek(selected, { weekStartsOn: 1 });
            const weekEnd = endOfWeek(selected, { weekStartsOn: 1 });
            next = next.filter(booking => {
                const bookingDate = normalizeBookingDate(booking.booking_date);
                return bookingDate
                    ? isWithinInterval(bookingDate, {
                        start: startOfDay(weekStart),
                        end: endOfDay(weekEnd),
                    })
                    : false;
            });
        }

        return next;
    };

    const buildSlotRows = () => {
        const items = Array.isArray(filteredBookings) ? filteredBookings : [];
        const grouped = new Map();

        items.forEach((item) => {
            const key = `${item.booking_date}|${item.inventory_time_slot_id}|${item.service_id}`;
            const existing = grouped.get(key) || {
                key,
                booking_date: item.booking_date,
                inventory_time_slot_id: item.inventory_time_slot_id,
                service_id: item.service_id,
                service: item.service,
                time_start: item.time_start,
                time_end: item.time_end,
                totalPax: 0,
                maxPax: item.maxPax ?? 0,
                totalPrice: 0,
                totalPaid: 0,
                totalDue: 0,
                cancelledCount: 0,
                bookings: [],
            };

            if (item.is_cancelled) {
                existing.cancelledCount += 1;
            } else {
                existing.totalPax += Number(item.bookingPax || 0);
                existing.totalPrice += Number(item.price || 0);
                existing.totalPaid += Number(item.paid || 0);
                existing.totalDue += Number(item.due || 0);
            }
            existing.bookings.push(item);
            grouped.set(key, existing);
        });

        if (!isSearchActive && viewMode === VIEW_MODES.DAY && showEmptySlots) {
            const day = selectedDate || today;
            services?.forEach((service) => {
                const activeSeason = service?.seasons?.find((season) => isDateWithinSeason(day, season));
                if (!activeSeason) return;
                const slots = getAvailableSlotsForDate(service, activeSeason, day);
                slots.forEach((slot) => {
                    const key = `${day}|${slot.id}|${service.id}`;
                    if (grouped.has(key)) return;
                    const { start, end } = getTimeRange(slot.start_time, service.duration);
                    grouped.set(key, {
                        key,
                        booking_date: day,
                        inventory_time_slot_id: slot.id,
                        service_id: service.id,
                        service: service.name,
                        time_start: start,
                        time_end: end,
                        totalPax: 0,
                        maxPax: slot.maxPax ?? service.capacity ?? 0,
                        totalPrice: 0,
                        totalPaid: 0,
                        totalDue: 0,
                        cancelledCount: 0,
                        bookings: [],
                    });
                });
            });
        }

        const rows = Array.from(grouped.values())
            .filter((row) => (isSearchActive ? row.bookings.length > 0 : showEmptySlots || row.bookings.length > 0));
        rows.forEach((row) => {
            row.bookings.sort((a, b) => Number(!!a.is_cancelled) - Number(!!b.is_cancelled));
        });
        rows.sort((a, b) => {
            const aDate = a.booking_date || '';
            const bDate = b.booking_date || '';
            if (aDate !== bDate) {
                // Search results: newest bookings first.
                if (isSearchActive) {
                    return bDate.localeCompare(aDate);
                }
                // Range/week: show in chronological order.
                if (viewMode !== VIEW_MODES.DAY) {
                    return aDate.localeCompare(bDate);
                }
            }

            const aTime = timeToMinutes(a.time_start);
            const bTime = timeToMinutes(b.time_start);
            if (aTime !== bTime) return aTime - bTime;
            return (a.service || '').localeCompare(b.service || '');
        });
        return rows;
    };

    useEffect(() => {
        setFilteredBookings(applyFilters());
    }, [bookings, selectedServiceId, viewMode, selectedDate, rangeStart, rangeEnd, searchQuery]);

    useEffect(() => {
        setSlotRows(buildSlotRows());
    }, [filteredBookings, services, closeouts, viewMode, selectedDate, showEmptySlots, showCancelledBookings]);

    useEffect(() => {
        sessionStorage.setItem('bookings.selectedDate', selectedDate || '');
    }, [selectedDate]);

    useEffect(() => {
        sessionStorage.setItem('bookings.rangeStart', rangeStart || '');
    }, [rangeStart]);

    useEffect(() => {
        sessionStorage.setItem('bookings.rangeEnd', rangeEnd || '');
    }, [rangeEnd]);

    useEffect(() => {
        sessionStorage.setItem('bookings.viewMode', viewMode || '');
    }, [viewMode]);

    useEffect(() => {
        sessionStorage.setItem('bookings.selectedServiceId', selectedServiceId || '0');
    }, [selectedServiceId]);

    useEffect(() => {
        sessionStorage.setItem('bookings.showEmptySlots', showEmptySlots ? 'true' : 'false');
    }, [showEmptySlots]);

    const handleShowCancelledChange = (checked) => {
        setShowCancelledBookings(checked);
        router.get(
            route('admin.bookings.index'),
            checked ? { include_cancelled: 1 } : {},
            { preserveScroll: true, preserveState: true, replace: true }
        );
    };

    const handleTodayClick = () => {
        // "Today" should reset the UI into a consistent state regardless of current view.
        setViewMode(VIEW_MODES.DAY);
        setSelectedDate(today);
        setRangeStart(today);
        setRangeEnd(today);
    };

    const handleCloseModal = () => {
        setGuestEmailTarget(null);
        setBookingNotesTarget(null);
        setIsEditTimeSlotModalOpen(false);
        setIsViewBookingModalOpen(false);
        setSelectedBooking(null);
        setIsEmailModalOpen(false);
        window.location.reload();
    };
    const handleCheveronDateChange = (direction) => {
        const baseDate = selectedDate ? parseISO(selectedDate) : new Date();
        const nextDate = addDays(baseDate, direction);
        setSelectedDate(format(nextDate, 'yyyy-MM-dd'));
    };

    const overallTotals = useMemo(() => {
        const rows = Array.isArray(slotRows) ? slotRows : [];
        return rows.reduce(
            (acc, row) => {
                acc.pax += Number(row?.totalPax || 0);
                acc.price += Number(row?.totalPrice || 0);
                acc.paid += Number(row?.totalPaid || 0);
                acc.due += Number(row?.totalDue || 0);
                return acc;
            },
            { pax: 0, price: 0, paid: 0, due: 0 }
        );
    }, [slotRows]);
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Bookings</h2>}
        >
            <Head title="Dashboard" />
            <div className="bg-white min-h-[80%] p-6 ">
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <span>
                        <span className="font-semibold text-lg text-gray-800 block text-start">Welcome Back, {auth?.user?.name}</span>
                        {/* <span className="font-semibold text-lg text-gray-800 block text-start">We have 2 Pending Tasks and 5 New Bookings.</span> */}

                    </span>
                    <div className="flex flex-wrap items-center gap-4">
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="flex flex-wrap items-center gap-2">
                                <WhiteButton
                                    title="Today"
                                    width="w-[90px]"
                                    textColor={selectedDate === today ? 'text-white' : 'text-primary-14'}
                                    className={selectedDate === today ? 'bg-blue-gradient-overlay border-[#3863C6]' : ''}
                                    onClick={handleTodayClick}
                                />
                                <span className='flex flex-row gap-1'>
                                    <button className='px-3 py-2 border border-gray-400 rounded-lg ' onClick={() => handleCheveronDateChange(-1)}>{icons.cheveron_left}</button>
                                    <WhiteButton
                                        title="Day"
                                        width="w-[90px]"
                                        textColor={viewMode === VIEW_MODES.DAY ? 'text-white' : 'text-primary-14'}
                                        className={viewMode === VIEW_MODES.DAY ? 'bg-blue-gradient-overlay border-[#3863C6]' : ''}
                                        onClick={() => setViewMode(VIEW_MODES.DAY)}
                                    />
                                    <button className='px-3 py-2 border border-gray-400 rounded-lg ' onClick={() => handleCheveronDateChange(1)}>{icons.cheveron_right}</button>
                                </span>
                                <WhiteButton
                                    title="Date Range"
                                    width="w-[120px]"
                                    textColor={viewMode === VIEW_MODES.RANGE ? 'text-white' : 'text-primary-14'}
                                    className={viewMode === VIEW_MODES.RANGE ? 'bg-blue-gradient-overlay border-[#3863C6]' : ''}
                                    onClick={() => setViewMode(VIEW_MODES.RANGE)}
                                />
                                <WhiteButton
                                    title="Week"
                                    width="w-[90px]"
                                    textColor={viewMode === VIEW_MODES.WEEK ? 'text-white' : 'text-primary-14'}
                                    className={viewMode === VIEW_MODES.WEEK ? 'bg-blue-gradient-overlay border-[#3863C6]' : ''}
                                    onClick={() => setViewMode(VIEW_MODES.WEEK)}
                                />
                            </div>

                        </div>
                        <div className="flex gap-2">
                            <button onClick={() => router.get(route('admin.bookings.create'))} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-[10px] border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                                <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                                <span className="text-btn-outline-gray">New Booking</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    if (!hasBookingsForTargetDate) {
                                        showToast('No bookings for the selected date. Please choose a date with bookings.', 'info');
                                        return;
                                    }
                                    if (isPastDate) {
                                        showToast('Check-in is only available for today or future dates.', 'info');
                                        return;
                                    }
                                    router.get(route('booking-questionnaire.index', { booking_date: targetDate }));
                                }}
                                disabled={checkinDisabled}
                                className={`flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-[10px] border-[3px] bg-red-gradient-overlay border-[#C65468] text-white shadow-inset-white ${checkinDisabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}`}
                            >
                                <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                                <span className="text-btn-outline-gray">Check in</span>
                            </button>
                            <button
                                onClick={() => {
                                    if (!hasBookingsForTargetDate) {
                                        showToast('No bookings for the selected date. Please choose a date with bookings.', 'info');
                                        return;
                                    }
                                    if (isFutureDate) {
                                        showToast('Feedback is available after the activity date has passed.', 'info');
                                        return;
                                    }
                                    if (!hasCheckinForTargetDate) {
                                        showToast('Feedback links are available after check-in is completed for at least one guest.', 'info');
                                        return;
                                    }
                                    router.get(route('front.feedback-questionnaire.index', { booking_date: targetDate }));
                                }}
                                disabled={feedbackDisabled}
                                className={`flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-[10px] border-[3px] bg-red-gradient-overlay border-[#C65468] text-white shadow-inset-white ${feedbackDisabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}`}
                            >
                                <img src="/assets/icons/plus.svg" alt="bookings" className="w-5 h-5" />
                                <span className="text-btn-outline-gray">Feedback</span>
                            </button>
                        </div>
                    </div>
                </div>
                {/* bottom */}
                <div className="bg-white min-h-[500px] ">
                    <div className="bg-white overflow-visible min-h-[500px] shadow-sm sm:rounded-lg">
                        <div className="flex justify-between items-center px-4 pt-4 pb-[9px] ">
                            <span className='flex items-center gap-2'>
                                <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                                <span className="font-semibold text-lg text-gray-800 block text-start">Bookings</span>
                            </span>
                            <span className="flex gap-2">

                                {viewMode === VIEW_MODES.DAY && (
                                    <div className="w-[200px]">
                                        <CustomDatePicker
                                            label="Date"
                                            name='selected_date'
                                            value={selectedDate}
                                            onChange={(e) => { setSelectedDate(e.target.value); }}

                                        />
                                    </div>
                                )}
                                {viewMode === VIEW_MODES.RANGE && (
                                    <div className="flex flex-wrap gap-3">
                                        <div className="w-[200px]">
                                            <CustomDatePicker
                                                label="Start Date"
                                                name='range_start'
                                                value={rangeStart}
                                                onChange={(e) => { setRangeStart(e.target.value); }}

                                            />
                                        </div>
                                        <div className="w-[200px]">
                                            <CustomDatePicker
                                                label="End Date"
                                                name='range_end'
                                                value={rangeEnd}
                                                onChange={(e) => { setRangeEnd(e.target.value); }}

                                            />
                                        </div>
                                    </div>
                                )}
                                {viewMode === VIEW_MODES.WEEK && (
                                    <div className="text-[#161618] font-poppins text-[14px] font-semibold leading-[130%] h-[40px] px-4 py-2 rounded-[10px] border-[3px] border-primary-14 cursor-pointer">
                                        Week of {format(startOfWeek(selectedDate ? parseISO(selectedDate) : new Date(), { weekStartsOn: 1 }), 'yyyy-MM-dd')} to {format(endOfWeek(selectedDate ? parseISO(selectedDate) : new Date(), { weekStartsOn: 1 }), 'yyyy-MM-dd')}
                                    </div>
                                )}
                                <label className="flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="checkbox"
                                        checked={showEmptySlots}
                                        onChange={(e) => setShowEmptySlots(e.target.checked)}
                                    />
                                    Show empty slots
                                </label>
                                <label className="flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="checkbox"
                                        checked={showCancelledBookings}
                                        onChange={(e) => handleShowCancelledChange(e.target.checked)}
                                    />
                                    Show cancelled bookings
                                </label>

                                <div className="relative" ref={servicesRef}>
                                    <button onClick={() => setShowServices(!showServices)}
                                        className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-[10px] border-[3px] border-primary-14 text-primary-14 cursor-pointer">
                                        <img src="/assets/icons/filter.svg" alt="filter" className="w-5 h-5" />
                                        <span className="text-btn-outline-gray">Filter</span>
                                    </button>
                                    {showServices && (
                                        <div className="absolute top-full mt-2 w-64 bg-white border rounded shadow-lg p-4 z-50">
                                            <h3 className="text-sm font-semibold text-gray-700 mb-2">Choose a Service</h3>
                                            <NavSelect key="0" name={'All'} onChange={(e) => handleFilterBookings('0')} />
                                            {activities?.map((activity, i) => {
                                                return (
                                                    <NavSelect key={i} name={activity.name} onChange={() => handleFilterBookings(activity.id)} />
                                                )
                                            })}
                                        </div>
                                    )}
                                </div>

                                <div className="relative" ref={searchRef}>
                                    <div className="flex items-center w-[280px] h-[40px] gap-2 px-4 py-2 rounded-[10px] border-[3px] border-primary-14 text-primary-14 bg-white hover:border-black focus-within:border-black">
                                        <img src="/assets/icons/search-normal.svg" alt="search" className="w-5 h-5" />
                                        <input
                                            type="text"
                                            value={searchQuery}
                                            placeholder="Search guest / reference (min 3)"
                                            onChange={(e) => {
                                                setSearchQuery(e.target.value);
                                                if (ENABLE_SEARCH_DROPDOWN) {
                                                    setSearchOpen(true);
                                                }
                                            }}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Escape') {
                                                    if (ENABLE_SEARCH_DROPDOWN) {
                                                        setSearchOpen(false);
                                                    }
                                                    setSearchQuery('');
                                                    return;
                                                }
                                                if (e.key === 'Enter') {
                                                    e.preventDefault();
                                                    // In-table filtering is the primary UX; Enter does not auto-open a booking.
                                                }
                                            }}
                                            className="w-full min-w-0 appearance-none bg-transparent border-0 p-0 m-0 shadow-none text-[#161618] placeholder:text-gray-400 focus:outline-none focus:ring-0 focus:ring-offset-0"
                                        />
                                        {searchQuery?.trim() && (
                                            <button
                                                type="button"
                                                className="text-gray-400 hover:text-gray-700"
                                                onClick={() => {
                                                    setSearchQuery('');
                                                    setSearchOpen(false);
                                                }}
                                                aria-label="Clear search"
                                            >
                                                ×
                                            </button>
                                        )}
                                    </div>

                                    {ENABLE_SEARCH_DROPDOWN && searchOpen && searchQuery.trim() && (
                                        <div className="absolute top-full mt-2 w-[520px] bg-white border rounded shadow-lg z-50 overflow-hidden">
                                            <div className="px-4 py-2 text-xs text-gray-500 border-b">
                                                Searching across all bookings for this site.
                                            </div>
                                            {searchResults.length === 0 ? (
                                                <div className="px-4 py-3 text-sm text-gray-700">
                                                    No results found.
                                                </div>
                                            ) : (
                                                <div className="max-h-[320px] overflow-y-auto">
                                                    {searchResults.map((result) => {
                                                        const timeLabel = result?.time_start ? formatTimeValue(result.time_start) : '';
                                                        const dateLabel = result?.booking_date || '';
                                                        const when = [dateLabel, timeLabel].filter(Boolean).join(' ');
                                                        return (
                                                            <button
                                                                key={`${result.id}-${result.booking_item_id}`}
                                                                type="button"
                                                                className="w-full text-left px-4 py-3 hover:bg-gray-50 border-b last:border-b-0"
                                                                onClick={() => {
                                                                    setSearchOpen(false);
                                                                    setSearchQuery('');
                                                                    ViewBookingClick(result);
                                                                }}
                                                            >
                                                                <div className="flex justify-between gap-3">
                                                                    <div className="min-w-0">
                                                                        <div className="text-sm font-semibold text-gray-900 truncate">
                                                                            {result?.lead_name || 'Unknown guest'}
                                                                        </div>
                                                                        <div className="text-xs text-gray-600 truncate">
                                                                            {result?.lead_email || result?.lead_phone || '—'}
                                                                        </div>
                                                                    </div>
                                                                    <div className="text-right shrink-0">
                                                                        <div className="text-xs text-gray-600">{when || '—'}</div>
                                                                        <div className="text-xs font-semibold text-gray-900">{result?.reference || ''}</div>
                                                                    </div>
                                                                </div>
                                                                <div className="mt-1 text-xs text-gray-600 truncate">
                                                                    {result?.service || ''}
                                                                </div>
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>

                                <button onClick={() => handleExportCSV(filteredBookings)} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-[10px] border-[3px] border-primary-14 text-primary-14 cursor-pointer">
                                    <img src="/assets/icons/export.svg" alt="export" className="w-5 h-5" />
                                    <span className="text-btn-outline-gray">Export</span>
                                </button>
                            </span>
                        </div>
                        <div className="flex justify-between items-center px-4 pb-[9px] ">
                            {/* table */}

                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50 text-left text-gray-500">
                                    <tr >
                                        <th className="w-[164px] p-3 flex justify-between items-center gap-2">
                                            <span className='flex items-center gap-2'>
                                                <span className="w-5 h-5 block border border-gray-500 rounded-md"></span>
                                                Date Time
                                            </span>
                                        </th>
                                        <th className="w-[95px] p-3">PAX</th>
                                        <th className="w-[268px] p-3">Service</th>
                                        <th className="w-[132px] p-3">Price</th>
                                        <th className="w-[111px] p-3">Paid</th>
                                        <th className="w-[107px] p-3">Due</th>
                                        <th className="w-[185px] p-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {slotRows?.map((row, i) => {
                                        const startLabel = formatTimeValue(row.time_start);
                                        const dateLabel = row.booking_date || '';
                                        const timeLabel = startLabel
                                            ? `${dateLabel} ${startLabel}`.trim()
                                            : `${dateLabel}`.trim() || 'Time N/A';
                                        const nextRow = slotRows?.[i + 1];
                                        const isLastInTimeGroup = !nextRow || formatTimeValue(nextRow.time_start) !== startLabel;
                                        const isRowExpanded = isSearchActive || expanded === row.key;
                                        return (
                                            <React.Fragment key={row.key}>
                                                <tr className="hover:bg-gray-50">
                                                    <td className="w-[164px] p-3">
                                                        <div className="flex justify-between items-center gap-2">
                                                            <div className="flex items-center gap-2">
                                                                <span className="w-5 h-5 block border border-gray-500 rounded-md"></span>
                                                                {timeLabel}
                                                            </div>
                                                            <img
                                                                src={`/assets/icons/${isRowExpanded ? 'arrow-down' : 'arrow-left'}.svg`}
                                                                alt="toggle"
                                                                className={`w-5 h-5 ${isSearchActive ? 'cursor-default opacity-60' : 'cursor-pointer'}`}
                                                                onClick={() => {
                                                                    if (isSearchActive) return;
                                                                    setExpanded(isRowExpanded ? null : row.key);
                                                                }}
                                                            />
                                                        </div>
                                                    </td>
                                                    <td className="w-[95px] p-3">
                                                        <div>{row.totalPax} ({row.maxPax})</div>
                                                        {row.cancelledCount > 0 && (
                                                            <span className="mt-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
                                                                {row.cancelledCount} cancelled
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="w-[268px] p-3">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <span>{row.service}</span>
                                                            {row.bookings?.find(booking => Number(booking.linked_activity_count || 0) > 1) && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => setLinkedActivitiesTarget(row.bookings.find(booking => Number(booking.linked_activity_count || 0) > 1))}
                                                                    className="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700 hover:bg-blue-200"
                                                                >
                                                                    Linked {row.bookings.find(booking => Number(booking.linked_activity_count || 0) > 1)?.linked_activity_count}
                                                                </button>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="w-[132px] p-3">R{Number(row.totalPrice || 0).toFixed(2)}</td>
                                                    <td className="w-[111px] p-3">R{Number(row.totalPaid || 0).toFixed(2)}</td>
                                                    <td className="w-[107px] p-3">
                                                        <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                                        ${row.totalDue > 0 ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'}`}>
                                                            - {Number(row.totalDue || 0) === 0 ? 'R0.00' : `R${Number(row.totalDue || 0).toFixed(2)}`}
                                                        </span>
                                                    </td>
                                                    <td className="w-[185px] p-3 flex gap-2">
                                                        {Number(row.totalPax || 0) < Number(row.maxPax || 0) && (
                                                            <button
                                                                type='button'
                                                                onClick={() => router.get(route('admin.bookings.create', {
                                                                    service_id: row.service_id,
                                                                    booking_date: row.booking_date,
                                                                    start_time: row.inventory_time_slot_id,
                                                                    view_mode: 'day',
                                                                    start_date: row.booking_date,
                                                                }))}
                                                                className="btn-tool"
                                                            >
                                                                {icons.plus_alt} Add
                                                            </button>
                                                        )}
                                                        {row.bookings.length > 0 && (
                                                            <button
                                                                type='button'
                                                                onClick={(e) => openBookingToolsModal(e, {
                                                                    ...row.bookings[0],
                                                                    active_slot_pax: row.totalPax,
                                                                    maxPax: row.maxPax,
                                                                })}
                                                                className="btn-tool"
                                                            >
                                                                {icons.tools} Tools
                                                            </button>
                                                        )}
                                                    </td>
                                                </tr>

                                                {isRowExpanded && (
                                                    row.bookings.length > 0 ? (
                                                        <>
                                                            {row.bookings.map((booking, index) => (
                                                                <React.Fragment key={`${row.key}-${index}`}>
                                                                    <tr className={booking.is_cancelled ? 'bg-amber-50' : 'bg-gray-50'}>
                                                                        <td className="w-[164px] p-3">
                                                                            {booking.is_cancelled ? (
                                                                                <span className="text-gray-700">{booking.reference}</span>
                                                                            ) : (
                                                                                <Link
                                                                                    href={route('admin.bookings.add', {
                                                                                        booking: booking.id,
                                                                                        booking_item_id: booking.booking_item_id,
                                                                                    })}
                                                                                    className="text-blue-600 hover:underline"
                                                                                >
                                                                                    {booking.reference}
                                                                                </Link>
                                                                            )}
                                                                            {booking.is_cancelled && (
                                                                                <span className="ml-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
                                                                                    Cancelled
                                                                                </span>
                                                                            )}
                                                                        </td>
                                                                        <td className="w-[95px] p-3">{booking.bookingPax}</td>
                                                                        <td className="w-[268px] p-3">
                                                                            <div className="text-sm text-gray-800 font-medium">{booking.lead_name ?? 'Unknown Lead'}</div>
                                                                            <div className="text-sm text-gray-600">{booking.lead_phone ?? 'Unknown Phone'}</div>
                                                                        </td>
                                                                        <td className="w-[132px] p-3">R{Number(booking.price || 0).toFixed(2)}</td>
                                                                        <td className="w-[111px] p-3">R{Number(booking.paid || 0).toFixed(2)}</td>
                                                                        <td className="w-[107px] p-3">
                                                                            <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${booking.due > 0 ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'}`}>
                                                                                - {Number(booking.due || 0) === 0 ? 'R0.00' : `R${Number(booking.due || 0).toFixed(2)}`}
                                                                            </span>
                                                                        </td>
                                                                        <td className="w-[185px] p-3">
                                                                            <div className="flex flex-col lg:flex-row lg:items-center gap-2">
                                                                                <button
                                                                                    type="button"
                                                                                    onClick={() => router.get(route('admin.bookings.add', {
                                                                                        booking: booking.id,
                                                                                        booking_item_id: booking.booking_item_id,
                                                                                    }))}
                                                                                    className={`btn-tool ${booking.is_cancelled ? 'opacity-50 cursor-not-allowed' : ''}`}
                                                                                    disabled={booking.is_cancelled}
                                                                                >
                                                                                    {icons.eye} View
                                                                                </button>
                                                                                <div
                                                                                    className="relative"
                                                                                    onClick={() =>
                                                                                        setViewDocsIndex(viewDocsIndex === `${row.key}-${index}` ? null : `${row.key}-${index}`)
                                                                                    }
                                                                                >
                                                                                    <button type='button' className="btn-tool">{icons.docs} Docs</button>
                                                                                    <div>
                                                                                        {viewDocsIndex === `${row.key}-${index}` && (
                                                                                            <div
                                                                                                className="absolute right-0 mt-3 w-72 bg-white border border-gray-200 rounded-2xl shadow-lg z-50 overflow-hidden"
                                                                                            >
                                                                                                <div className="flex justify-between items-center mb-2">
                                                                                                    <h3 className="text-tool-card-title">Docs</h3>
                                                                                                    <button type='button'
                                                                                                        onClick={(e) => {
                                                                                                            e.stopPropagation();
                                                                                                            setViewDocsIndex(null);
                                                                                                        }}
                                                                                                        className="text-gray-400 hover:text-gray-600 text-sm">{icons.close}</button>
                                                                                                </div>

                                                                                                <div className="max-h-64 overflow-y-auto">
                                                                                                    {booking?.customers?.[0]?.files?.length > 0 ? (
                                                                                                        booking.customers[0].files.map((file) => (
                                                                                                            <a
                                                                                                                key={file.id}
                                                                                                                href={file.path}
                                                                                                                target="_blank"
                                                                                                                rel="noopener noreferrer"
                                                                                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                                                                            >
                                                                                                                {file.file_name}
                                                                                                            </a>
                                                                                                        ))
                                                                                                    ) : (
                                                                                                        <p className="p-4 text-gray-500 text-sm">No documents uploaded.</p>
                                                                                                    )}
                                                                                                </div>
                                                                                            </div>
                                                                                        )}
                                                                                    </div>
                                                                                </div>
                                                                                <button
                                                                                    type="button"
                                                                                    onClick={(e) => openCustomerToolsModal(e, {
                                                                                        ...booking,
                                                                                        active_slot_pax: row.totalPax,
                                                                                        maxPax: row.maxPax,
                                                                                    }, booking?.customers?.[0], `${row.key}-${index}`)}
                                                                                    className="btn-tool"
                                                                                >
                                                                                    {icons.tools} Tools
                                                                                </button>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                    {booking.rate_breakdown && (
                                                                        <tr className={booking.is_cancelled ? 'bg-amber-50' : 'bg-gray-50'}>
                                                                            <td colSpan={7} className="px-3 pb-4 pt-0">
                                                                                <div className="rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">
                                                                                    <BookingRateBreakdown breakdown={booking.rate_breakdown} compact />
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    )}
                                                                </React.Fragment>
                                                            ))}
                                                            <tr className="bg-gray-50">
                                                                <td className="w-[164px] p-3 font-semibold">Totals:</td>
                                                                <td className="w-[95px] p-3 font-semibold">{row.totalPax}</td>
                                                                <td className="w-[268px] p-3"></td>
                                                                <td className="w-[132px] p-3 font-semibold">R{Number(row.totalPrice || 0).toFixed(2)}</td>
                                                                <td className="w-[111px] p-3 font-semibold">R{Number(row.totalPaid || 0).toFixed(2)}</td>
                                                                <td className="w-[107px] p-3 font-semibold">R{Number(row.totalDue || 0).toFixed(2)}</td>
                                                                <td className="w-[185px] p-3"></td>
                                                            </tr>
                                                        </>
                                                    ) : (
                                                        <tr className="bg-gray-50">
                                                            <td colSpan={7} className="p-4 text-sm text-gray-500">No bookings in this slot.</td>
                                                        </tr>
                                                    )
                                                )}
                                                {isLastInTimeGroup && (
                                                    <tr className="bg-white">
                                                        <td colSpan={7} className="p-0">
                                                            <div className="border-b-2 border-gray-200 mt-2"></div>
                                                        </td>
                                                    </tr>
                                                )}
                                            </React.Fragment>
                                        );
                                    })}
                                    <tr className="bg-white border-t-2 border-gray-200">
                                        <td className="w-[164px] p-3 font-semibold">Totals:</td>
                                        <td className="w-[95px] p-3 font-semibold">{overallTotals.pax}</td>
                                        <td className="w-[268px] p-3"></td>
                                        <td className="w-[132px] p-3 font-semibold">R{Number(overallTotals.price || 0).toFixed(2)}</td>
                                        <td className="w-[111px] p-3 font-semibold">R{Number(overallTotals.paid || 0).toFixed(2)}</td>
                                        <td className="w-[107px] p-3 font-semibold">
                                            <span className={`${overallTotals.due > 0 ? 'text-red-600' : 'text-gray-800'}`}>
                                                R{Number(overallTotals.due || 0).toFixed(2)}
                                            </span>
                                        </td>
                                        <td className="w-[185px] p-3"></td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>

                {bookingToolsTarget && (
                    <BookingToolsModal
                        booking={bookingToolsTarget.booking}
                        position={bookingToolsTarget.position}
                        onClose={() => setBookingToolsTarget(null)}
                        BookingToolClick={BookingToolClick}
                    />
                )}

                {customerToolsTarget && (
                    <CustomerToolsModal
                        index={customerToolsTarget.indexKey}
                        booking={customerToolsTarget.booking}
                        customer={customerToolsTarget.customer}
                        position={customerToolsTarget.position}
                        onClose={() => setCustomerToolsTarget(null)}
                        CustomerToolClick={(tool, _index, booking, customer) => CustomerToolClick(tool, booking, customer)}
                    />
                )}

                {confirmOpen && (
                    <ConfirmModal
                        title={confirmAction?.type === 'reinstate'
                            ? 'Reinstate this booking?'
                            : confirmAction?.type === 'slot'
                                ? 'Cancel all bookings in this slot?'
                                : 'Cancel this booking?'}
                        message={confirmAction?.type === 'reinstate'
                            ? (confirmAction.capacityWarning || 'This will restore the cancelled booking if the time slot still has enough capacity.')
                            : confirmAction?.type === 'slot'
                                ? 'This will cancel all bookings for this date, time slot, and service.'
                                : 'This action can be reversed later by showing cancelled bookings and reinstating the booking.'}
                        confirmText={confirmAction?.type === 'reinstate' ? 'Yes, reinstate' : 'Yes, cancel'}
                        cancelText={confirmAction?.type === 'reinstate' ? 'Keep Cancelled' : 'Keep Booking'}
                        requireConfirmationCheckbox={!!confirmAction?.forceCapacityOverride}
                        confirmationLabel="I understand this reinstate will exceed the current slot capacity and want to override it."
                        onConfirm={handleConfirmCancel}
                        onClose={() => {
                            setConfirmOpen(false);
                            setConfirmAction(null);
                        }}
                    />
                )}
                <ViewBookingModal
                    isOpen={isViewBookingModalOpen}
                    onClose={handleCloseModal}
                    mode='edit'
                    booking={selectedBooking}
                    services={services}
                />
                {linkedActivitiesTarget && (
                    <Modal show={!!linkedActivitiesTarget} onClose={() => setLinkedActivitiesTarget(null)} maxWidth="2xl">
                        <div className="p-6">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <h2 className="text-lg font-semibold text-gray-900">
                                        Linked Activities
                                    </h2>
                                    <p className="text-sm text-gray-500">
                                        {linkedActivitiesTarget.reference} shares one booking, payment, and invoice.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setLinkedActivitiesTarget(null)}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    {icons.close}
                                </button>
                            </div>
                            <div className="mt-5 space-y-3">
                                {(linkedActivitiesTarget.linked_activities || []).map((activity, index) => (
                                    <div key={activity.booking_item_id || index} className="rounded-[10px] border border-gray-200 bg-white p-4">
                                        <div className="flex flex-wrap items-start justify-between gap-4">
                                            <div>
                                                <div className="text-xs font-semibold text-gray-500">Activity {index + 1}</div>
                                                <div className="font-semibold text-gray-900">{activity.service || 'Service'}</div>
                                                <div className="text-sm text-gray-600">
                                                    {[activity.booking_date, formatTimeValue(activity.time_start)].filter(Boolean).join(' ')}
                                                </div>
                                                <div className="mt-1 text-sm text-gray-600">
                                                    {activity.bookingPax || 0} pax | R{Number(activity.price || 0).toFixed(2)}
                                                </div>
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                <button
                                                    type="button"
                                                    className="btn-tool"
                                                    onClick={() => router.get(route('admin.bookings.add', {
                                                        booking: activity.id,
                                                        booking_item_id: activity.booking_item_id,
                                                    }))}
                                                >
                                                    {icons.eye} View
                                                </button>
                                                <button
                                                    type="button"
                                                    className="btn-tool"
                                                    onClick={() => router.get(route('admin.bookings.edit', {
                                                        booking: activity.id,
                                                        booking_item_id: activity.booking_item_id,
                                                    }))}
                                                >
                                                    Edit
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </Modal>
                )}
                <EditTimeSlotModal
                    isOpen={isEditTimeSlotModalOpen}
                    onClose={handleCloseModal}
                    mode='edit'
                    booking={selectedBooking}
                    services={services}
                />

                <GuestEmailModal
                    isOpen={isEmailModalOpen}
                    onClose={handleCloseModal}
                    mode='create'
                    booking={guestEmailTarget}
                />
                <BookingNotesModal
                    isOpen={!!bookingNotesTarget}
                    onClose={() => setBookingNotesTarget(null)}
                    booking={bookingNotesTarget}
                />

                <ManifestModal
                    isOpen={isManifestModalOpen}
                    onClose={handleCloseModal}
                    mode="edit"
                    customers={selectedManifests}
                    customer={selectedManifest}
                    tot_adults='0'
                    tot_children='0'
                />

                <ManifestsModal
                    isOpen={isManifestsModalOpen}
                    onClose={handleCloseModal}
                    mode="edit"
                    customers={selectedManifests}
                    tot_adults='0'
                    tot_children='0'
                />

                <CheckInOptionsModal
                    isOpen={!!checkInTarget}
                    onClose={() => setCheckInTarget(null)}
                    booking={checkInTarget}
                    contextLabel="booking"
                />

                <FeedbackOptionsModal
                    isOpen={!!feedbackTarget}
                    onClose={() => setFeedbackTarget(null)}
                    booking={feedbackTarget}
                    contextLabel="booking"
                />


            </div>
        </AuthenticatedLayout>
    );
}
