const CustomRadio = ({ label, name, value, outerWidth = 'w-full', checked, onChange, errors }) => {
    return (
        <div className={`flex w-full items-center justify-center`}>
            <div className={`flex rounded-[10px] ${outerWidth} border border-[#F1F1F5] sm:w-[672px] h-[60px] `}>
                <div className="flex items-center gap-[18px] p-3">
                    <input
                        type="radio"
                        id={`${name}-${value}`}
                        name={name}
                        checked={checked}
                        value={value}
                        onChange={(e) => onChange(name, e.target.value)}
                        className={`h-9 w-9 text-blue-600 ${errors?.[name] ? "border-red-500" : "border-gray-300"} focus:ring-blue-500 rounded-[10px]`}
                    />
                    <label htmlFor={`${name}-${value}`} className="text-gray-700">{label}</label>
                </div>
            </div>
            {errors[name] && (
                <p className="text-red-500 text-sm mt-2">
                    {errors[name]}
                </p>
            )}
        </div>
    );
};

export default CustomRadio;
