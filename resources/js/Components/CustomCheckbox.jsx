const CustomCheckbox = ({ label, helper = "", side="right", name, checked = false, mode, onChange, errors = {}, disabled = false }) => {

    return (
        <div className="mb-4">
            <label htmlFor={name} className={`flex items-center gap-2 relative ${disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer'}`}>
                {side === "left" && (<span className="text-booking-check-lbl-left mt-1.5">{label}</span>)}
                <span className="relative w-5 h-5 shrink-0">
                <input
                    type="checkbox"
                    id={name}
                    name={name}
                    checked={checked}
                    disabled={disabled}
                    onChange={(e) => onChange(name, e.target.checked)}                                  
                    className="peer w-full h-full appearance-none border border-gray-400 rounded-md checked:bg-[#31B7001A] checked:border-[#31B700] hover:border-black focus:outline-none focus:ring-0 focus:ring-offset-0 focus:ring-offset-white text-[#31B700] disabled:cursor-not-allowed disabled:bg-gray-100"
                />
                <svg
                    className="absolute inset-0 w-4 h-4 m-auto mt-[6px] pointer-events-none hidden peer-checked:block"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="3"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                >
                    <polyline points="20 6 9 17 4 12" />
                </svg>
                </span>
                {side === "right" && (<span className="text-booking-check-lbl-right mt-1.5">{label}</span>)}
                {helper && (
                    <span
                        title={helper}
                        aria-label={helper}
                        className="mt-1.5 inline-flex h-4 w-4 items-center justify-center rounded-full border border-gray-400 text-[10px] font-semibold leading-none text-gray-500 hover:border-gray-700 hover:text-gray-800"
                    >
                        i
                    </span>
                )}
            </label>
            {errors[name] && (
                <p className="text-checkbox-lbl text-red-500 mt-2">
                    {errors[name]}
                </p>
            )}
        </div>
    );
};

export default CustomCheckbox;
