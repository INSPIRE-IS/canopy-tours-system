import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { icons } from '../../../icons';
import CustomInputText from '@/Components/CustomInputText';
import BlueButton from '@/Components/BlueButton';
import CustomCheckbox from '@/Components/CustomCheckbox';
import Modal from '@/Components/Modal';
import OutlineButton from '@/Components/OutlineButton';
import { useToast } from "@/Context/ToastContext";
// import { useRoute } from 'ziggy-js';
// import { Ziggy } from '@/ziggy'; // the generated file

export default function Dashboard({ auth, booking, latest_payment, lookups, mode, gateways, status_payments, type_pay_options, type_payments, updated_bies, users }) {
    console.log("auth", auth);
    const { props } = usePage();
    const { flash } = props;
    const { showToast } = useToast();
    // console.log("type_pay_options", type_pay_options);
    // console.log("type_payments", type_payments);
    const payOptionList = Array.isArray(type_pay_options) ? type_pay_options : [];
    const paymentMethodList = Array.isArray(type_payments) ? type_payments : [];

    // We currently keep certain legacy/unused options seeded in the DB, but hide them in the admin UI.
    // This avoids breaking existing records while keeping the payment screen simple.
    const hiddenPayOptionSlugs = new Set(['split_payments']);
    const hiddenPaymentMethodSlugs = new Set(['masterpass', 'paypal', 'snapscan', 'zapper']);

    const visiblePayOptions = payOptionList.filter((option) => {
        const slug = (option.slug || '').toLowerCase();
        return !hiddenPayOptionSlugs.has(slug);
    });

    const visiblePaymentMethods = paymentMethodList.filter((method) => {
        const slug = (method.slug || '').toLowerCase();
        return !hiddenPaymentMethodSlugs.has(slug);
    });

    const lookupList = Array.isArray(lookups) ? lookups : [];
    const vatLookup = lookupList.find(l => l.slug === 'vat_rate') || {};
    const vatRate = Number(vatLookup.rate || 0);
    const vatMultiplier = Number(vatLookup.multiplier || (1 + vatRate));
    const previousShortPaymentNote = latest_payment?.short_payment_note || latest_payment?.note || '';
    const previousAdditionalPaymentNote = latest_payment?.additional_payment_note || '';
    const latestPaymentExVat = Number(latest_payment?.ex_vat_amount || 0);
    const latestPaymentVat = Number(latest_payment?.vat_amount || 0);
    const latestPaymentAdditional = Number(latest_payment?.additional_amount || 0);
    const latestPaymentBookingAmount = latestPaymentVat >= latestPaymentExVat && latestPaymentExVat > 0
        ? latestPaymentExVat
        : latestPaymentExVat + latestPaymentVat;
    const latestPaymentTotalReceived = latestPaymentBookingAmount + latestPaymentAdditional;
    const calculateVatFields = (total) => {
        const paymentTotal = Number(total || 0);
        const exVat = vatMultiplier > 0 ? paymentTotal / vatMultiplier : paymentTotal;
        const vatAmount = paymentTotal - exVat;

        return {
            vat: vatRate.toFixed(2),
            vat_amount: vatAmount.toFixed(2),
        };
    };
    const outstandingAmount = Math.max(0, Number(booking.grand_tot_due) - Number(booking.tot_paid || 0));
    const { data, setData, post, put, processing, errors, isDirty } = useForm({
        booking_id: booking.id,
        redeemer_name: booking.lead_name,
        type_pay_option_id: '',
        type_payment_id: '',
        gateway_id: 2,
        status_payment_id: 2,
        payment_options: {}, // store checkboxes here
        payment_methods: {}, // for type_payments
        ex_vat_amount: outstandingAmount.toFixed(2),
        lookup_item_id: vatLookup.id,
        ...calculateVatFields(outstandingAmount),
        additional_amount: '0.00',
        payment_date: booking.due_date,
        updated_by_id: auth.user.id,
        short_payment_note: '',
        additional_payment_note: '',
        note: '',
        reference: booking.reference,
        meta: '',
        team_id: booking.team_id,
    });

    const [showPayLinkModal, setShowPayLinkModal] = useState(false);
    const [payLinkSending, setPayLinkSending] = useState(false);

    const payOnlineOption = type_pay_options.find((option) => {
        const slug = (option.slug || '').toLowerCase();
        return slug === 'pay_online' || /pay\s*online/i.test(option.name || '');
    });
    const payOfflineOption = type_pay_options.find((option) => {
        const slug = (option.slug || '').toLowerCase();
        return slug === 'pay_offline' || /pay\s*offline/i.test(option.name || '');
    });
    const creditCardOption = type_payments.find((option) => {
        const slug = (option.slug || '').toLowerCase();
        return slug === 'credit_card' || /credit\s*card/i.test(option.name || '');
    });
    const cashOption = type_payments.find((option) => {
        const slug = (option.slug || '').toLowerCase();
        return slug === 'cash' || /cash/i.test(option.name || '');
    });
    const isPayOnline = !!payOnlineOption && data.type_pay_option_id === payOnlineOption.id;

    const amountDue = outstandingAmount;
    const isFullyPaid = amountDue <= 0;
    const paymentAmount = Number(data.ex_vat_amount || 0);
    const additionalAmount = Number(data.additional_amount || 0);
    const totalReceived = paymentAmount + additionalAmount;
    const isAdjustedPayment = Math.abs(paymentAmount - amountDue) > 0.01;
    const hasAdditionalPayment = additionalAmount > 0.01;
    const requiresShortPaymentNote = isAdjustedPayment;
    const requiresAdditionalPaymentNote = hasAdditionalPayment;
    const leadEmail = booking?.customer?.email_address || booking?.lead_email || '';
    useEffect(() => {
        if (flash?.success) {
            showToast(flash.success, "success");
        }
        if (flash?.error) {
            showToast(flash.error, "error");
        }
    }, [flash, showToast]);
    useEffect(() => {
        if (!payOfflineOption || data.type_pay_option_id) return;
        setData((prev) => ({
            ...prev,
            payment_options: {
                ...prev.payment_options,
                [payOfflineOption.id]: true,
            },
            type_pay_option_id: payOfflineOption.id,
            ...(cashOption
                ? {
                    payment_methods: {
                        ...prev.payment_methods,
                        [cashOption.id]: true,
                    },
                    type_payment_id: cashOption.id,
                }
                : {}),
        }));
    }, [payOfflineOption, cashOption, data.type_pay_option_id, setData]);
    const handleChange = (target, value) => {
        if (target === 'ex_vat_amount') {
            setData((prev) => ({
                ...prev,
                ex_vat_amount: value,
                ...calculateVatFields(value),
            }));
            return;
        }
        setData(target, value);
        console.log('target----- ', target);
        console.log('value----- ', value);
        console.log('data', data);
    };

    // handle checkbox change (exclusive per group)
    // const handleExclusiveChange = (groupKeys, selectedKey) => {
    //     // reset all group keys to false
    //     const reset = groupKeys.reduce((acc, key) => {
    //         acc[key] = false;
    //         return acc;
    //     }, {});

    //     // set only the selected one to true
    //     setData({
    //         ...data,
    //         ...reset,
    //         [selectedKey]: true,
    //     });
    // };
    const handleCheckboxChange = (group, id) => {
        // reset whole group to false
        const reset = Object.keys(data[group]).reduce((acc, key) => {
            acc[key] = false;
            return acc;
        }, {});

        // set only the selected one to true
        setData(group, {
            ...reset,
            [id]: true,
        });
        if (group == "payment_options") {
            setData('type_pay_option_id', id);
            const paymentReset = Object.keys(data.payment_methods).reduce((acc, key) => {
                acc[key] = false;
                return acc;
            }, {});
            if (payOnlineOption && creditCardOption && id === payOnlineOption.id) {
                setData('payment_methods', {
                    ...paymentReset,
                    [creditCardOption.id]: true,
                });
                setData('type_payment_id', creditCardOption.id);
            }
            if (payOfflineOption && cashOption && id === payOfflineOption.id) {
                setData('payment_methods', {
                    ...paymentReset,
                    [cashOption.id]: true,
                });
                setData('type_payment_id', cashOption.id);
            }
        }
        if (group == "payment_methods") {
            setData('type_payment_id', id);
        }
    };
    const handleSubmit = (e) => {
        e.preventDefault();
        console.log('booking', booking);
        console.log('data', data);
        if (requiresShortPaymentNote && !String(data.short_payment_note || '').trim()) {
            showToast('Please add a short/adjusted payment note.', 'error');
            return;
        }
        if (requiresAdditionalPaymentNote && !String(data.additional_payment_note || '').trim()) {
            showToast('Please add an additional amount note.', 'error');
            return;
        }
        if (mode === "create") {
            post(route("admin.booking-payments.store"), {
                onSuccess: () => console.log('success'),
            });
        } else if (mode === "edit") {
            put(route("admin.booking-payments.update", booking.id), {
                onSuccess: () => console.log('success'),
            });
        }
    };
    const handleSendPayLink = async () => {
        setPayLinkSending(true);
        try {
            await axios.post(route('admin.booking-payments.paylink', booking.id));
            showToast('PayLink sent to the client.', 'success');
            setShowPayLinkModal(false);
        } catch (error) {
            console.error('PayLink send failed:', error);
            showToast('Failed to send PayLink. Please try again.', 'error');
        } finally {
            setPayLinkSending(false);
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Submit Payment</h2>}
        >
            <Head title="Dashboard" />

            <div className="bg-white p-6 ">
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <span>
                        {isDirty && (<p className="text-yellow-600 text-sm mt-1">You have unsaved changes</p>)}
                    </span>
                </div>
                {/* bottom */}

                <div className="flex flex-col items-center ">
                    <span className='flex items-center justify-between w-full py-4 border-b border-gray-300'>
                        <span className="text-booking-card-heading block text-start">Submit Payment </span>
                    </span>
                    <div className="flex flex-col w-full pr-[51px]  gap-2 ">
                        <div className="flex flex-wrap gap-4 items-center justify-start ">
                            {/* {type_pay_options.map(({ id, name, slug }) => (
                                <CustomCheckbox
                                    key={id}
                                    label={name}
                                    name={slug}
                                    checked={data[slug] || false}
                                    onChange={() =>
                                        handleExclusiveChange(
                                            type_pay_options.map(o => o.slug), // group keys
                                            slug
                                        )
                                    }
                                    errors={errors}
                                />
                            ))}  */}
                            {visiblePayOptions.map(({ id, name }) => (
                                <CustomCheckbox
                                    key={id}
                                    label={name}
                                    name={id}
                                    checked={data.payment_options[id] || false}
                                    onChange={(checked) => handleCheckboxChange("payment_options", id, checked)}
                                    errors={errors}
                                />
                            ))}

                        </div>
                        {!isPayOnline && (
                            <div className="flex flex-wrap gap-4 items-center justify-start ">
                                {/* {type_payments.map(({ id, name, slug }) => (
                                    <CustomCheckbox
                                        key={id}
                                        label={name}
                                        name={slug}
                                        checked={data[slug] || false}
                                        onChange={() =>
                                            handleExclusiveChange(
                                                type_payments.map(p => p.slug), // group keys
                                                slug
                                            )
                                        }
                                        errors={errors}
                                    />
                                ))} */}
                                {visiblePaymentMethods.map(({ id, name }) => (
                                    <CustomCheckbox
                                        key={id}
                                        label={name}
                                        name={id}
                                        checked={data.payment_methods[id] || false}
                                        onChange={(checked) => handleCheckboxChange("payment_methods", id, checked)}
                                        errors={errors}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                    {isPayOnline ? (
                        <div className="flex flex-col w-full pr-[51px] gap-4">
                            <div className="flex items-center gap-4">
                                <OutlineButton
                                    title="Send PayLink"
                                    onClick={() => setShowPayLinkModal(true)}
                                    props={{ type: 'button', disabled: isFullyPaid }}
                                />
                                {isFullyPaid && (
                                    <span className="text-sm text-red-500">Booking already paid.</span>
                                )}
                            </div>
                            <p className="text-sm text-gray-600">
                                Send a secure PayLink to the client. They will complete payment via PayFast.
                            </p>
                        </div>
                    ) : (
                        <div className="flex flex-col w-full pr-[51px]  gap-2 ">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 ">
                                <div>
                                    <CustomInputText
                                        title="Booking Payment Amount"
                                        name="ex_vat_amount"
                                        type='text'
                                        value={data.ex_vat_amount}
                                        onChange={(e) => { handleChange(e.target.name, e.target.value) }}
                                        errors={errors}
                                    />
                                    <p className="text-xs text-gray-500 -mt-2">
                                        Amount allocated to this booking balance. Outstanding balance: R{amountDue.toFixed(2)}.
                                    </p>
                                </div>
                                <div>
                                    <CustomInputText
                                        title={requiresShortPaymentNote ? "Short / Adjusted Payment Note (required)" : "Short / Adjusted Payment Note"}
                                        name="short_payment_note"
                                        type='text'
                                        value={data.short_payment_note}
                                        onChange={(e) => { handleChange(e.target.name, e.target.value) }}
                                        errors={errors}
                                    />
                                    <p className="text-xs text-gray-500 -mt-2">
                                        Required when the booking payment amount is less than or different from the outstanding balance.
                                    </p>
                                </div>

                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 ">
                                <CustomInputText
                                    title="Voucher Code"
                                    name="voucher_code"
                                    type='text'
                                    value={data.voucher_code}
                                    onChange={(e) => { handleChange(e.target.name, e.target.value) }}
                                    errors={errors}
                                />

                                <CustomInputText
                                    title="Redeemer Name"
                                    name="redeemer_name"
                                    type='text'
                                    value={data.redeemer_name}
                                    onChange={(e) => { handleChange(e.target.name, e.target.value); }}
                                    errors={errors}
                                />

                                <CustomInputText
                                    title="Payment Date"
                                    name="payment_date"
                                    type='date'
                                    value={data.payment_date}

                                    onChange={(e) => { handleChange(e.target.name, e.target.value); }}
                                    errors={errors}
                                />
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 ">
                                <div>
                                    <CustomInputText
                                        title="Additional Amount"
                                        name="additional_amount"
                                        type='text'
                                        value={data.additional_amount}
                                        onChange={(e) => { handleChange(e.target.name, e.target.value) }}
                                        errors={errors}
                                    />
                                    <p className="text-xs text-gray-500 -mt-2">
                                        Optional non-booking amount such as tip, food, drinks, or merchandise. Not added to booking VAT/invoice balance.
                                    </p>
                                </div>
                                <div>
                                    <CustomInputText
                                        title={requiresAdditionalPaymentNote ? "Additional Amount Note (required)" : "Additional Amount Note"}
                                        name="additional_payment_note"
                                        type='text'
                                        value={data.additional_payment_note}
                                        onChange={(e) => { handleChange(e.target.name, e.target.value) }}
                                        errors={errors}
                                    />
                                    <p className="text-xs text-gray-500 -mt-2">
                                        Required when recording tips, food, drinks, merchandise, or other non-booking amounts.
                                    </p>
                                </div>
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 ">
                                <div className="flex flex-col justify-center rounded-md border border-gray-200 bg-gray-50 px-4 py-3">
                                    <span className="text-xs uppercase tracking-wide text-gray-500">Total Received</span>
                                    <span className="text-lg font-semibold text-gray-900">R{totalReceived.toFixed(2)}</span>
                                </div>

                            </div>


                            {latest_payment && (
                                <div className="rounded-md border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600">
                                    <div className="font-medium text-gray-900">Previous payment context</div>
                                    <div>Total received: R{latestPaymentTotalReceived.toFixed(2)}</div>
                                    {previousShortPaymentNote && <div>Short/adjusted note: {previousShortPaymentNote}</div>}
                                    {previousAdditionalPaymentNote && <div>Additional note: {previousAdditionalPaymentNote}</div>}
                                </div>
                            )}
                            <div className="flex flex-row items-center w-full gap-4 ">
                                <BlueButton
                                    title="Add Payment Now"
                                    width="w-[169px]"
                                    onClick={handleSubmit}
                                    disabled={processing || (isFullyPaid && !hasAdditionalPayment)}
                                    type="submit"
                                />
                                {isFullyPaid && !hasAdditionalPayment && (
                                    <span className="text-sm text-red-500">Booking already paid. Add an additional amount to record extras.</span>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            <Modal show={showPayLinkModal} onClose={() => setShowPayLinkModal(false)} maxWidth="md">
                <div className="p-6 space-y-4">
                    <div>
                        <h3 className="text-lg font-semibold">Send PayFast PayLink</h3>
                        <p className="text-sm text-gray-600">Confirm the payment details below.</p>
                    </div>

                    <div className="space-y-2 text-sm">
                        <div className="flex justify-between gap-4">
                            <span className="text-gray-600">Booking Reference</span>
                            <span className="font-medium text-right">{booking.reference}</span>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-gray-600">Lead Name</span>
                            <span className="font-medium text-right">{booking.lead_name}</span>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-gray-600">Lead Email</span>
                            <span className="font-medium text-right">{leadEmail || '—'}</span>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-gray-600">Total</span>
                            <span className="font-semibold text-right">R{amountDue.toFixed(2)}</span>
                        </div>
                    </div>

                    {!leadEmail && (
                        <div className="text-sm text-red-600">
                            This booking does not have a customer email address. Please update the lead customer email first.
                        </div>
                    )}

                    <div className="flex justify-end gap-3 pt-2">
                        <OutlineButton
                            title="Cancel"
                            onClick={() => setShowPayLinkModal(false)}
                            props={{ type: 'button', disabled: payLinkSending }}
                        />
                        <BlueButton
                            title={payLinkSending ? 'Sending...' : 'Send'}
                            onClick={handleSendPayLink}
                            disabled={payLinkSending || isFullyPaid || !leadEmail}
                            props={{ type: 'button' }}
                            width="w-[140px]"
                        />
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
