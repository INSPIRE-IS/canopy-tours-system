import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import React, { useState } from 'react';
import { icons } from '../../../icons';
import CustomInputText from '@/Components/CustomInputText';
import BlueButton from '@/Components/BlueButton';
import CustomCheckbox from '@/Components/CustomCheckbox';
// import { useRoute } from 'ziggy-js';
// import { Ziggy } from '@/ziggy'; // the generated file

export default function Dashboard({ auth, booking, lookups, mode, gateways, status_payments, type_pay_options, type_payments, updated_bies, users }) {
    console.log("auth", auth);
    // console.log("type_pay_options", type_pay_options);
    // console.log("type_payments", type_payments);
    const { data, setData, post, put, processing, errors, isDirty } = useForm({
        booking_id: booking.id,
        redeemer_name: booking.lead_name,
        type_pay_option_id: '',
        type_payment_id: '',
        gateway_id: 2,
        status_payment_id: 2,
        payment_options: {}, // store checkboxes here
        payment_methods: {}, // for type_payments
        ex_vat_amount: ((booking.grand_tot_due - booking.tot_paid)).toFixed(2),
        lookup_item_id: lookups.find(l => l.slug == 'vat_rate')?.id,
        vat: ((booking.grand_tot_due - booking.tot_paid) * lookups.find(l => l.slug == 'vat_rate')?.rate).toFixed(2),
        vat_amount: ((booking.grand_tot_due - booking.tot_paid) * lookups.find(l => l.slug == 'vat_rate')?.multiplier).toFixed(2),
        payment_date: booking.due_date,
        updated_by_id: auth.user.id,
        note: '',
        reference: booking.reference,
        meta: '',
        team_id: booking.team_id,
    });

    const handleChange = (target, value) => {
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
        }
        if (group == "payment_methods") {
            setData('type_payment_id', id);
        }
    };
    const handleSubmit = (e) => {
        e.preventDefault();
        console.log('booking', booking);
        console.log('data', data);
        if (mode === "create") {
            post(route("admin.booking-payments.store"), {
                onSuccess: () => onClose(),
            });
        } else if (mode === "edit") {
            put(route("admin.booking-payments.update", booking.id), {
                onSuccess: () => onClose(),
            });
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
                            {type_pay_options.map(({ id, name }) => (
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
                            {type_payments.map(({ id, name }) => (
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
                    </div>
                    <div className="flex flex-col w-full pr-[51px]  gap-2 ">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 ">
                            <CustomInputText
                                title="Amount"
                                name="vat_amount"
                                type='text'
                                value={data.vat_amount}
                                onChange={(e) => { handleChange(e.target.name, e.target.value) }}
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
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 ">
                            <CustomInputText
                                title="payment Note"
                                name="note"
                                type='text'
                                value={data.note}
                                onChange={(e) => { handleChange(e.target.name, e.target.value) }}
                                errors={errors}
                            />
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 ">
                            <BlueButton
                                title="Add Payment Now"
                                width="w-[169px]"
                                onClick={handleSubmit}
                                disabled={processing}
                                type="submit"
                            />
                        </div>
                    </div>

                </div>
            </div>

        </AuthenticatedLayout>
    );
}
