import React, { useEffect, useMemo, useRef, useState } from 'react';
import CustomCodeSelect from '@/Components/CustomCodeSelect';
import CustomInputText from '@/Components/CustomInputText';

const normalizeCountries = (countries) => (countries || [])
    .filter(c => c?.phonecode)
    .map(c => ({
        id: String(c.phonecode),
        name: `+${c.phonecode}`,
    }));

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
    countries = [],
    error,
    defaultCountryCode = '27',
    name = 'phone',
}) {
    const phonecodeOptions = useMemo(() => normalizeCountries(countries), [countries]);
    const resolvedDefault = phonecodeOptions.find(o => o.id === String(defaultCountryCode))?.id
        || phonecodeOptions[0]?.id
        || String(defaultCountryCode);

    const [countryCode, setCountryCode] = useState(resolvedDefault);
    const [localNumber, setLocalNumber] = useState('');
    const initializedRef = useRef(false);
    const lastEmittedRef = useRef(value ?? '');

    useEffect(() => {
        if (!phonecodeOptions.length) return;
        if (initializedRef.current && value === lastEmittedRef.current) return;
        const parsed = splitPhoneNumber(value, phonecodeOptions, resolvedDefault);
        setCountryCode(parsed.code || resolvedDefault);
        setLocalNumber(parsed.local || '');
        initializedRef.current = true;
    }, [phonecodeOptions.length, value, resolvedDefault]);

    useEffect(() => {
        if (!initializedRef.current) return;
        const local = (localNumber || '').replace(/\D/g, '').replace(/^0+/, '');
        if (!local) {
            if (value) {
                lastEmittedRef.current = '';
                onChange?.('');
            }
            return;
        }
        const code = countryCode || resolvedDefault;
        const formatted = code ? `+${code}${local}` : local;
        if (value !== formatted) {
            lastEmittedRef.current = formatted;
            onChange?.(formatted);
        }
    }, [countryCode, localNumber, resolvedDefault, value, onChange]);

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
            <div className="flex flex-col sm:flex-row w-full gap-2">
                <CustomCodeSelect
                    label="Country Code"
                    name={`${name}_country`}
                    value={countryCode}
                    height="h-12"
                    onChange={(e) => setCountryCode(e.target.value)}
                    error={error}
                    options={phonecodeOptions}
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
                    onChange={(e) => setLocalNumber(e.target.value.replace(/\D/g, ''))}
                    errors={{ [name]: error }}
                />
            </div>
            <p className="text-xs text-gray-500 mt-2">
                Saved as: {value || '-'} (international format)
            </p>
        </div>
    );
}
