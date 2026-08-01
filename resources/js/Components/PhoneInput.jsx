import React, { useEffect, useMemo, useRef, useState } from 'react';
import CustomInputText from '@/Components/CustomInputText';
import SearchableCombobox from '@/Components/SearchableCombobox';

const normalizeCountries = (countries) => (countries || [])
    .filter(c => c?.phonecode)
    .map(c => {
        const code = String(c.phonecode);
        const label = c?.name ? `${code} - ${c.name}` : code;
        return {
            id: code,
            name: label,
            countryId: c?.id ?? null,
            countryName: c?.name ?? '',
            phonecode: c?.phonecode ?? null,
        };
    });

const splitPhoneNumber = (value, phonecodeOptions, fallbackCode) => {
    if (!value) {
        return { code: fallbackCode, local: '' };
    }
    let digits = value.trim();
    if (digits.startsWith('+')) {
        digits = digits.slice(1);
    }
    digits = digits.replace(/\D/g, '');
    if (digits.startsWith('00')) {
        digits = digits.slice(2);
    }
    if (digits.startsWith('0')) {
        return { code: fallbackCode, local: digits.replace(/^0+/, '') };
    }
    const sortedCodes = phonecodeOptions
        .map(o => o.id)
        .sort((a, b) => b.length - a.length);
    for (const code of sortedCodes) {
        if (digits.startsWith(code)) {
            return { code, local: digits.slice(code.length) };
        }
    }
    return { code: fallbackCode, local: digits };
};

export default function PhoneInput({
    label = 'Phone',
    value,
    onChange,
    onCountryChange,
    countries = [],
    error,
    defaultCountryCode = '27',
    name = 'phone',
    placeholder = 'e.g. 0762412888 or 762412888',
    stacked = false,
}) {
    const phonecodeOptions = useMemo(() => normalizeCountries(countries), [countries]);
    const resolvedDefault = phonecodeOptions.find(o => o.id === String(defaultCountryCode))?.id
        || phonecodeOptions[0]?.id
        || String(defaultCountryCode);

    const [countryCode, setCountryCode] = useState(resolvedDefault);
    const [localNumber, setLocalNumber] = useState('');
    const initializedRef = useRef(false);
    const lastEmittedRef = useRef(value ?? '');
    const syncingRef = useRef(false);
    const lastCountryRef = useRef(null);

    const debug = useMemo(() => {
        try {
            return window?.localStorage?.getItem('debug_phone') === '1';
        } catch {
            return false;
        }
    }, []);

    const dbg = (...args) => {
        if (!debug) return;
        // eslint-disable-next-line no-console
        console.log('[PhoneInput]', ...args);
    };

    useEffect(() => {
        if (!phonecodeOptions.length) return;
        if (initializedRef.current && value === lastEmittedRef.current) return;
        const parsed = splitPhoneNumber(value, phonecodeOptions, resolvedDefault);
        if (initializedRef.current && parsed.code === countryCode && parsed.local === localNumber) {
            return;
        }
        syncingRef.current = true;
        dbg('sync from value', { value, parsed, resolvedDefault });
        setCountryCode(parsed.code || resolvedDefault);
        setLocalNumber(parsed.local || '');
        initializedRef.current = true;
    }, [phonecodeOptions.length, value, resolvedDefault, countryCode, localNumber]);

    useEffect(() => {
        if (!initializedRef.current) return;
        if (syncingRef.current) {
            syncingRef.current = false;
            return;
        }
        const raw = (localNumber || '').replace(/\D/g, '');
        // Allow users to type a local leading "0" (common in SA) without the input wiping itself.
        // Only strip leading zeros once at least one non-zero digit is present.
        const local = raw.length > 1 ? raw.replace(/^0+/, '') : raw;
        if (!local || local === '0') {
            dbg('emit skipped', { countryCode, localNumber, raw, local, reason: 'empty_or_single_zero' });
            return;
        }
        const code = countryCode || resolvedDefault;
        const formatted = code ? `+${code}${local}` : local;
        if (value !== formatted) {
            lastEmittedRef.current = formatted;
            dbg('emit', { countryCode: code, localNumber, raw, local, formatted });
            onChange?.(formatted);
        }
    }, [countryCode, localNumber, resolvedDefault, value, onChange]);

    useEffect(() => {
        if (!onCountryChange) return;
        const selected = phonecodeOptions.find(o => o.id === String(countryCode));
        if (selected?.countryId === lastCountryRef.current) return;
        lastCountryRef.current = selected?.countryId ?? null;
        onCountryChange(selected || null);
    }, [countryCode, onCountryChange, phonecodeOptions]);

    const allowPhoneKey = (e) => {
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        const allowed = [
            'Backspace',
            'Delete',
            'ArrowLeft',
            'ArrowRight',
            'Home',
            'End',
            'Tab',
        ];
        if (allowed.includes(e.key)) return;
        if (!/^\d$/.test(e.key)) {
            e.preventDefault();
        }
    };

    return (
        <div className="w-full">
            <div className={`flex w-full gap-2 ${stacked ? 'flex-col' : 'flex-col sm:flex-row'}`}>
                <SearchableCombobox
                    label="Country Code"
                    name={`${name}_country`}
                    value={countryCode}
                    height="h-12"
                    containerWidth={stacked ? 'w-full' : 'w-[40%]'}
                    onChange={(e) => setCountryCode(e.target.value)}
                    errors={{ [`${name}_country`]: error }}
                    options={phonecodeOptions}
                    placeholder="Search code or country"
                    inputMode="search"
                    showOption={false}
                />
                <CustomInputText
                    title={label}
                    id={name}
                    name={name}
                    type="text"
                    inputMode="tel"
                    value={localNumber}
                    height="h-12"
                    onKeyDown={allowPhoneKey}
                    onChange={(e) => {
                        const next = e.target.value.replace(/\D/g, '');
                        dbg('local change', { prev: localNumber, next });
                        setLocalNumber(next);
                    }}
                    errors={{ [name]: error }}
                    placeholder={placeholder}
                />
            </div>
            <p className="text-xs text-gray-500 mt-2">
                Saved as: {value || '-'} (international format)
            </p>
            <p className="text-xs text-gray-500 mt-1">
                Tip: You can type with or without a leading 0; we&apos;ll save it as +{countryCode || resolvedDefault}.
            </p>
        </div>
    );
}
