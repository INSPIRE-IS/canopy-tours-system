import React, { useEffect, useState } from "react";
import { router, useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomCheckbox from "@/Components/CustomCheckbox";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import InputRichText from "@/Components/InputRichText";

const TeamModal = ({ isOpen, onClose, mode, team = null, teams, permissions = {}, user = null }) => {
    // console.log("mode", mode);
    // console.log("teams", teams);
    const [logo, setLogo] = useState(null);
    const [image, setImage] = useState(null);
    const [invoiceQuoteIcon, setInvoiceQuoteIcon] = useState(null);

    const isAdminUser = !!user?.is_admin || user?.roles?.some((role) => role?.id === 1);
    const canManagePayfast = isAdminUser && !!permissions?.gateways_edit;
    const canPublishSite = !!permissions?.teams_publish;
    const payfast = team?.payfast || null;

    const [replacePayfastMerchantKey, setReplacePayfastMerchantKey] = useState(false);
    const [replacePayfastPassphrase, setReplacePayfastPassphrase] = useState(false);

    const { data, setData, post, put, errors, reset, processing, setError, clearErrors } = useForm({
        name: "",
        full_name: "",
        description: "",
        // file_logo: "",
        // file_image: "",
        color: "",
        reg_no: "",
        address: "",
        address_line_1: "",
        address_line_2: "",
        city: "",
        postal_code: "",
        address_code: "",
        telephone: "",
        email: "",
        website_url: "",
        account_owner: "",
        account_number: "",
        account_type: "",
        bank_name: "",
        bank_branch_name: "",
        branch_code: "",
        is_published: true,
        frontend_hold_enabled: true,
        frontend_hold_minutes: 15,
        frontend_unpaid_booking_expiry_hours: 3,
        invoice_note: "",
        vat_no: "",
        team_id: team?.team_id,
        owner_id: "",

        // PayFast (per-site)
        payfast_is_enabled: false,
        payfast_is_sandbox: true,
        payfast_merchant_id: "",
        payfast_merchant_key: "",
        payfast_passphrase: "",

    });
    useEffect(() => {
        if (team && mode !== "create") {
            setData({
                name: team.name || "",
                full_name: team.full_name || "",
                description: team.description || "",
                // file_logo: team.file_logo || "",
                // file_image: team.file_image || "",
                color: team.color || "",
                reg_no: team.reg_no || "",
                address: team.address || "",
                address_line_1: team.address_line_1 || "",
                address_line_2: team.address_line_2 || "",
                city: team.city || "",
                postal_code: team.postal_code || "",
                address_code: team.address_code || "",
                telephone: team.telephone || "",
                email: team.email || "",
                website_url: team.website_url || "",
                account_owner: team.account_owner || "",
                account_number: team.account_number || "",
                account_type: team.account_type || "",
                bank_name: team.bank_name || "",
                bank_branch_name: team.bank_branch_name || "",
                branch_code: team.branch_code || "",
                is_published: team.is_published ?? true,
                frontend_hold_enabled: team.frontend_hold_enabled ?? true,
                frontend_hold_minutes: team.frontend_hold_minutes ?? 15,
                frontend_unpaid_booking_expiry_hours: team.frontend_unpaid_booking_expiry_hours ?? 3,
                invoice_note: team.invoice_note || "",
                vat_no: team.vat_no || "",
                team_id: team.team_id,
                owner_id: team.owner_id,

                payfast_is_enabled: payfast?.is_enabled ?? false,
                payfast_is_sandbox: payfast?.is_sandbox ?? true,
                payfast_merchant_id: payfast?.merchant_id ?? "",
                payfast_merchant_key: "",
                payfast_passphrase: "",
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
            setData((prev) => ({
                ...prev,
                is_published: true,
                frontend_hold_enabled: true,
                frontend_hold_minutes: 15,
                frontend_unpaid_booking_expiry_hours: 3,
            }));
        }
        setLogo(null);
        setImage(null);
        setInvoiceQuoteIcon(null);
        setReplacePayfastMerchantKey(false);
        setReplacePayfastPassphrase(false);
    }, [mode, team]);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };

    const publicFileUrl = (file) => {
        const path = file?.path;
        if (!path) return null;

        if (/^https?:\/\//i.test(path)) {
            return path;
        }

        const cleanPath = String(path).replace(/^\/+/, "");
        if (cleanPath.startsWith("storage/") || cleanPath.startsWith("images/")) {
            return `/${cleanPath}`;
        }

        return `/storage/${cleanPath}`;
    };

    const handlePreviewInvoiceQuote = () => {
        if (!team?.id) return;
        window.open(route("admin.teams.invoice-preview", team.id), "_blank", "noopener,noreferrer");
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        console.log('data ', data);
        clearErrors();

        const requestConfig = {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        };

        const formData = new FormData();
        formData.append('name', data.name);
        formData.append('full_name', data.full_name);
        formData.append('description', data.description);
        // formData.append('logo', data.logo);
        // formData.append('image', data.image);
        formData.append('color', data.color);
        formData.append('reg_no', data.reg_no);
        formData.append('address', data.address);
        formData.append('address_line_1', data.address_line_1);
        formData.append('address_line_2', data.address_line_2);
        formData.append('city', data.city);
        formData.append('postal_code', data.postal_code);
        formData.append('address_code', data.address_code);
        formData.append('telephone', data.telephone);
        formData.append('email', data.email);
        formData.append('website_url', data.website_url);
        formData.append('account_owner', data.account_owner);
        formData.append('account_number', data.account_number);
        formData.append('account_type', data.account_type);
        formData.append('bank_name', data.bank_name);
        formData.append('bank_branch_name', data.bank_branch_name);
        formData.append('branch_code', data.branch_code);
        if (canPublishSite) {
            formData.append('is_published', data.is_published ? 1 : 0);
        }
        formData.append('frontend_hold_enabled', data.frontend_hold_enabled ? 1 : 0);
        formData.append('frontend_hold_minutes', data.frontend_hold_minutes ?? '');
        formData.append('frontend_unpaid_booking_expiry_hours', data.frontend_unpaid_booking_expiry_hours ?? 3);
        formData.append('invoice_note', data.invoice_note);
        formData.append('vat_no', data.vat_no);
        formData.append('team_id', data.team_id);

        if (canManagePayfast) {
            formData.append('payfast_is_enabled', data.payfast_is_enabled ? 1 : 0);
            formData.append('payfast_is_sandbox', data.payfast_is_sandbox ? 1 : 0);
            formData.append('payfast_merchant_id', data.payfast_merchant_id);

            const merchantKeyConfigured = !!payfast?.merchant_key_configured;
            if (replacePayfastMerchantKey || (!merchantKeyConfigured && data.payfast_merchant_key)) {
                formData.append('payfast_merchant_key', data.payfast_merchant_key);
            }

            const passphraseSet = !!payfast?.passphrase_set;
            if (replacePayfastPassphrase || (!passphraseSet && data.payfast_passphrase)) {
                // Passphrase can be intentionally blank (sandbox accounts often have no passphrase).
                formData.append('payfast_passphrase', data.payfast_passphrase);
            }
        }


        // File (if present)
        if (logo) {
            formData.append('logo', logo);
        }
        if (image) {
            formData.append('image', image);
        }
        if (invoiceQuoteIcon) {
            formData.append('invoice_quote_icon', invoiceQuoteIcon);
        }

        for (let pair of formData.entries()) {
            console.log(pair[0] + ':', pair[1]);
        }
        console.log('mode', mode);

        if (mode === "create") {
            axios.post(route("admin.teams.store"), formData, requestConfig)
                .then((res) => {
                    console.log('---------------------------------------', res.data);
                    router.reload({
                        only: ["teams"],
                        preserveScroll: true,
                        onSuccess: () => onClose(),
                    });
                })
                .catch(error => {
                    if (error?.response?.status === 422 && error?.response?.data?.errors) {
                        setError(error.response.data.errors);
                        return;
                    }
                    console.error(error.response ?? error);
                });
        } else {
            formData.append('_method', 'PUT');
            axios.post(route('admin.teams.update', team.id), formData, requestConfig)
                .then((res) => {
                    console.log('---------------------------------------', res.data);
                    router.reload({
                        only: ["teams"],
                        preserveScroll: true,
                        onSuccess: () => onClose(),
                    });
                })
                .catch(error => {
                    if (error?.response?.status === 422 && error?.response?.data?.errors) {
                        setError(error.response.data.errors);
                        return;
                    }
                    console.error(error.response ?? error);
                });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
                <div className="flex items-center justify-between gap-4 mb-4">
                    <h2 className="text-xl font-bold">
                        {mode === "create" && "Create Site"}
                        {mode === "edit" && "Edit Site"}
                        {mode === "view" && "View Site"}
                    </h2>
                    {mode !== "create" && team?.id && (
                        <OutlineButton
                            title="Preview Invoice/Quote"
                            width="w-[220px]"
                            onClick={handlePreviewInvoiceQuote}
                            props={{ type: "button" }}
                        />
                    )}
                </div>
                <form onSubmit={handleSubmit}>


                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Name"
                            field="name"
                            value={data.name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Full Name"
                            field="full_name"
                            value={data.full_name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>

                    {canPublishSite && (
                        <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                            <CustomCheckbox
                                label="Publish"
                                name="is_published"
                                checked={!!data.is_published}
                                onChange={handleChange}
                                errors={errors}
                                disabled={mode === "view"}
                            />
                            <div className="text-xs text-red-500 mb-4">
                                If disabled, this site will be hidden from the front team dropdown direct and access will return 404.
                            </div>
                        </div>
                    )}

                    <label className="block font-semibold mb-1">Public Checkout Hold</label>
                    <span className="border-bottom border-[1px] border-black flex flex-row w-full items-center gap-2 justify-start mb-4"></span>
                    <div className="flex flex-row w-full items-start gap-6 justify-start mb-4">
                        <div className="w-1/2 pt-6">
                            <CustomCheckbox
                                label="Enable frontend checkout hold"
                                name="frontend_hold_enabled"
                                checked={!!data.frontend_hold_enabled}
                                onChange={handleChange}
                                errors={errors}
                                disabled={mode === "view"}
                            />
                            <p className="text-xs text-gray-500">
                                Reserves the selected places while a guest completes checkout.
                            </p>
                        </div>
                        <div className="w-1/2">
                            <InputText
                                title="Hold duration (minutes)"
                                field="frontend_hold_minutes"
                                type="number"
                                min="1"
                                max="120"
                                value={data.frontend_hold_minutes}
                                mode={mode}
                                handleChange={handleChange}
                                errors={errors}
                                disabled={!data.frontend_hold_enabled || mode === "view"}
                            />
                        </div>
                    </div>

                    <div className="flex flex-row w-full items-start gap-6 justify-start mb-4">
                        <div className="w-1/2">
                            <InputText
                                title="Unpaid booking expiry (hours)"
                                field="frontend_unpaid_booking_expiry_hours"
                                type="number"
                                min="0"
                                value={data.frontend_unpaid_booking_expiry_hours}
                                mode={mode}
                                handleChange={handleChange}
                                errors={errors}
                            />
                        </div>
                        <p className="w-1/2 pt-6 text-xs text-gray-500">
                            Starts when the provisional booking is created or PayFast is opened. Use 0 to keep unpaid frontend bookings and their reserved capacity until staff resolve them.
                        </p>
                    </div>

                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputRichText
                            title="Description"
                            field="description"
                            value={data.description}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <label className="block font-semibold mb-1">Site/Invoice/Quote Detail</label>
                    <span className="border-bottom border-[1px] border-black flex flex-row w-full items-center gap-2 justify-start mb-4">

                    </span>

                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        {/* <InputText
                            title="Color"
                            field="color"
                            value={data.color}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        /> */}

                        <InputText
                            title="Legacy Address"
                            field="address"
                            value={data.address}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Address Line 1"
                            field="address_line_1"
                            value={data.address_line_1}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Address Line 2"
                            field="address_line_2"
                            value={data.address_line_2}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="City"
                            field="city"
                            value={data.city}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Postal Code"
                            field="postal_code"
                            value={data.postal_code}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Address Code"
                            field="address_code"
                            value={data.address_code}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Reg No"
                            field="reg_no"
                            value={data.reg_no}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Vat No"
                            field="vat_no"
                            value={data.vat_no}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Telephone"
                            field="telephone"
                            value={data.telephone}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Email"
                            field="email"
                            value={data.email}
                            type="email"
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Website"
                            field="website_url"
                            value={data.website_url}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Account Owner"
                            field="account_owner"
                            value={data.account_owner}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Account Number"
                            field="account_number"
                            value={data.account_number}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />

                        <InputText
                            title="Account Type"
                            field="account_type"
                            value={data.account_type}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />

                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Bank Name"
                            field="bank_name"
                            value={data.bank_name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Bank Branch Name"
                            field="bank_branch_name"
                            value={data.bank_branch_name}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                        <InputText
                            title="Branch Code"
                            field="branch_code"
                            value={data.branch_code}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <InputText
                            title="Invoice Note"
                            field="invoice_note"
                            value={data.invoice_note}
                            mode={mode}
                            handleChange={handleChange}
                            errors={errors}
                        />
                    </div>

                    {canManagePayfast && (
                        <>
                            <label className="block font-semibold mb-1">PayFast Gateway</label>
                            <span className="border-bottom border-[1px] border-black flex flex-row w-full items-center gap-2 justify-start mb-4"></span>

                            {payfast && (
                                <p className="text-xs text-gray-500 mb-4">
                                    Current config: {payfast?.scope === 'team' ? 'Team-specific' : 'Default'} ({payfast?.is_sandbox ? 'Sandbox' : 'Live'})
                                </p>
                            )}

                            <div className="flex flex-row w-full items-center gap-8 justify-start mb-2">
                                <CustomCheckbox
                                    label="Enable PayFast"
                                    name="payfast_is_enabled"
                                    checked={!!data.payfast_is_enabled}
                                    mode={mode}
                                    onChange={handleChange}
                                    errors={errors}
                                    disabled={mode === "view"}
                                />
                                <CustomCheckbox
                                    label="Sandbox"
                                    name="payfast_is_sandbox"
                                    checked={!!data.payfast_is_sandbox}
                                    mode={mode}
                                    onChange={handleChange}
                                    errors={errors}
                                    disabled={mode === "view"}
                                />
                            </div>

                            <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                                <InputText
                                    title="Merchant ID"
                                    field="payfast_merchant_id"
                                    value={data.payfast_merchant_id}
                                    mode={mode}
                                    handleChange={handleChange}
                                    errors={errors}
                                />
                            </div>

                            <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                                {payfast?.merchant_key_configured && !replacePayfastMerchantKey ? (
                                    <div className="mb-3 w-full">
                                        <label className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]">
                                            Merchant Key
                                        </label>
                                        <div className="mt-1 flex items-center justify-between w-full h-[42px] px-3 bg-[#FBF9F7] border border-gray-300 rounded-[10px]">
                                            <span className="text-sm text-gray-500 break-all">
                                                {payfast?.merchant_key || 'Configured'}
                                            </span>
                                            {mode !== "view" && (
                                                <OutlineButton
                                                    title="Replace"
                                                    width="w-[140px]"
                                                    onClick={() => setReplacePayfastMerchantKey(true)}
                                                    props={{ type: "button" }}
                                                />
                                            )}
                                        </div>
                                    </div>
                                ) : (
                                    <InputText
                                        title="Merchant Key"
                                        field="payfast_merchant_key"
                                        value={data.payfast_merchant_key}
                                        mode={mode}
                                        handleChange={handleChange}
                                        errors={errors}
                                        type="password"
                                        autoComplete="new-password"
                                        placeholder="Paste new key to replace"
                                    />
                                )}
                            </div>

                            <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                                {payfast?.passphrase_set && !replacePayfastPassphrase ? (
                                    <div className="mb-3 w-full">
                                        <label className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]">
                                            Passphrase (optional)
                                        </label>
                                        <div className="mt-1 flex items-center justify-between w-full h-[42px] px-3 bg-[#FBF9F7] border border-gray-300 rounded-[10px]">
                                            <span className="text-sm text-gray-500 break-all">
                                                {payfast?.passphrase || 'Configured'}
                                            </span>
                                            {mode !== "view" && (
                                                <OutlineButton
                                                    title="Replace"
                                                    width="w-[140px]"
                                                    onClick={() => setReplacePayfastPassphrase(true)}
                                                    props={{ type: "button" }}
                                                />
                                            )}
                                        </div>
                                    </div>
                                ) : (
                                    <InputText
                                        title="Passphrase (optional)"
                                        field="payfast_passphrase"
                                        value={data.payfast_passphrase}
                                        mode={mode}
                                        handleChange={handleChange}
                                        errors={errors}
                                        type="password"
                                        autoComplete="new-password"
                                        placeholder="Leave blank if your PayFast account does not use a passphrase"
                                    />
                                )}
                            </div>
                        </>
                    )}
                    <label className="block font-semibold mb-1">Site Icon</label>
                    <span className="border-bottom border-[1px] border-black flex flex-row w-full items-center gap-2 justify-start mb-4">

                    </span>

                    {mode !== "create" && team?.file_logo?.path && (
                        <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                            <label className="block font-semibold mb-1">Current Icon</label>
                            {team?.file_logo?.path != null ? (
                                <img
                                    src={publicFileUrl(team?.file_logo)}
                                    alt={team?.file_logo.file_name}
                                    className="w-32 h-auto rounded shadow"
                                />
                            ) : (
                                <a
                                    href={publicFileUrl(team?.file_logo)}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-blue-600 underline"
                                >
                                    Download {team?.file_logo.file_name}
                                </a>
                            )}


                        </div>
                    )}
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <label className="block font-semibold mb-1">Upload New Site Icon (optional)</label>
                        <input type="file" onChange={e => setLogo(e.target.files[0])} />
                    </div>
                    <label className="block font-semibold mb-1">Invoice/Quote Icon</label>
                    <span className="border-bottom border-[1px] border-black flex flex-row w-full items-center gap-2 justify-start mb-4">

                    </span>
                    {mode !== "create" && team?.file_invoice_quote_icon?.path && (
                        <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                            <label className="block font-semibold mb-1">Current Invoice/Quote Icon</label>
                            <img
                                src={publicFileUrl(team?.file_invoice_quote_icon)}
                                alt={team?.file_invoice_quote_icon.file_name}
                                className="w-full max-w-[420px] h-auto rounded shadow"
                            />
                        </div>
                    )}
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <label className="block font-semibold mb-1">Upload New Invoice/Quote Icon (optional)</label>
                        <input type="file" onChange={e => setInvoiceQuoteIcon(e.target.files[0])} />
                    </div>
                    <label className="block font-semibold mb-1">Site Main Image</label>
                    <span className="border-bottom border-[1px] border-black flex flex-row w-full items-center gap-2 justify-start mb-4">

                    </span>
                    {mode !== "create" && team?.file_image && (
                        <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                            <label className="block font-semibold mb-1">Current Image</label>
                            {team?.file_image?.path != null ? (
                                <img
                                    src={publicFileUrl(team?.file_image)}
                                    alt={team?.file_image.file_name}
                                    className="w-32 h-auto rounded shadow"
                                />
                            ) : (
                                <a
                                    href={publicFileUrl(team?.file_image)}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-blue-600 underline"
                                >
                                    Download {team?.file_image.file_name}
                                </a>
                            )}
                        </div>
                    )}
                    <div className="flex flex-row w-full items-center gap-2 justify-start mb-4">
                        <label className="block font-semibold mb-1">Upload New Image (optional)</label>
                        <input type="file" onChange={e => setImage(e.target.files[0])} />
                    </div>


                    <div className="flex justify-center gap-4">
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

export default TeamModal;
