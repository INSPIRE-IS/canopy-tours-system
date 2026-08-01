import React, { useEffect, useMemo, useRef, useState } from "react";

const normalize = (value) => String(value ?? "").toLowerCase().trim();

const getOptionValue = (option) => (typeof option === "object" ? option.id : option);
const getOptionLabel = (option) => (typeof option === "object" ? option.name : option);

const getSearchText = (option) => {
    if (typeof option !== "object") {
        return String(option ?? "");
    }

    return [
        option.name,
        option.label,
        option.id,
        option.value,
        option.phonecode,
        option.countryName,
        option.searchText,
    ].filter(Boolean).join(" ");
};

export default function SearchableCombobox({
    label,
    name,
    value = null,
    width = "w-full",
    containerWidth = "w-full",
    height = "h-[48px]",
    onChange,
    showOption = true,
    optionText = " -- Select an option --",
    errors = {},
    mode,
    options = [],
    placeholder = "Search...",
    inputMode,
}) {
    const wrapperRef = useRef(null);
    const [query, setQuery] = useState("");
    const [open, setOpen] = useState(false);

    const selectedOption = useMemo(() => {
        return options?.find((option) => String(getOptionValue(option)) === String(value ?? ""));
    }, [options, value]);

    const filteredOptions = useMemo(() => {
        const search = normalize(query);
        if (!search) {
            return options || [];
        }

        return (options || []).filter((option) => normalize(getSearchText(option)).includes(search));
    }, [options, query]);

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (!wrapperRef.current?.contains(event.target)) {
                setOpen(false);
                setQuery("");
            }
        };

        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    const selectOption = (option) => {
        const nextValue = getOptionValue(option);
        onChange?.({
            target: {
                name,
                value: nextValue,
            },
        });
        setQuery("");
        setOpen(false);
    };

    const clearSelection = () => {
        onChange?.({
            target: {
                name,
                value: "",
            },
        });
        setQuery("");
        setOpen(false);
    };

    const displayValue = open ? query : (selectedOption ? getOptionLabel(selectedOption) : "");
    const disabled = mode === "view";

    return (
        <div ref={wrapperRef} className={`relative mb-4 ${containerWidth}`}>
            {label && (
                <label
                    htmlFor={name}
                    className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]"
                >
                    {label}
                </label>
            )}

            <div className="relative">
                <input
                    id={name}
                    name={name}
                    type="text"
                    value={displayValue}
                    inputMode={inputMode}
                    placeholder={selectedOption ? "" : placeholder}
                    onFocus={() => {
                        setQuery("");
                        setOpen(true);
                    }}
                    onChange={(event) => {
                        setQuery(event.target.value);
                        setOpen(true);
                    }}
                    onKeyDown={(event) => {
                        if (event.key === "Escape") {
                            setOpen(false);
                            setQuery("");
                        }

                        if (event.key === "Enter" && filteredOptions.length > 0) {
                            event.preventDefault();
                            selectOption(filteredOptions[0]);
                        }
                    }}
                    disabled={disabled}
                    autoComplete="off"
                    className={`mt-1 block ${width} ${height} pr-10 ${errors?.[name] ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-[10px]
                        hover:border-black active:border-blue-600
                        focus:outline-none focus:ring-0 focus:border-black
                        disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 disabled:hover:border-gray-300
                    `}
                />

                <button
                    type="button"
                    className="absolute inset-y-0 right-0 mt-1 flex items-center px-3 text-gray-500 disabled:cursor-not-allowed"
                    onClick={() => !disabled && setOpen((current) => !current)}
                    disabled={disabled}
                    aria-label="Toggle options"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        className={`h-4 w-4 transition-transform ${open ? "rotate-180" : ""}`}
                    >
                        <path fillRule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clipRule="evenodd" />
                    </svg>
                </button>
            </div>

            {open && !disabled && (
                <div className="absolute z-50 mt-1 max-h-64 w-full overflow-y-auto rounded-[10px] border border-gray-200 bg-white shadow-lg">
                    {showOption && (
                        <button
                            type="button"
                            onMouseDown={(event) => event.preventDefault()}
                            onClick={clearSelection}
                            className="block w-full px-3 py-2 text-left text-sm text-gray-500 hover:bg-gray-100"
                        >
                            {optionText}
                        </button>
                    )}

                    {filteredOptions.length > 0 ? (
                        filteredOptions.map((option, index) => {
                            const optionValue = getOptionValue(option);
                            const optionLabel = getOptionLabel(option);
                            const active = String(optionValue) === String(value ?? "");

                            return (
                                <button
                                    key={`${optionValue}-${index}`}
                                    type="button"
                                    onMouseDown={(event) => event.preventDefault()}
                                    onClick={() => selectOption(option)}
                                    className={`block w-full px-3 py-2 text-left text-sm hover:bg-gray-100 ${active ? "bg-blue-50 text-[#3863C6]" : "text-gray-700"}`}
                                >
                                    {optionLabel}
                                </button>
                            );
                        })
                    ) : (
                        <div className="px-3 py-2 text-sm text-gray-500">No results found</div>
                    )}
                </div>
            )}

            {errors?.[name] && (
                <p className="text-red-500 text-sm mt-2">
                    {errors[name]}
                </p>
            )}
        </div>
    );
}
