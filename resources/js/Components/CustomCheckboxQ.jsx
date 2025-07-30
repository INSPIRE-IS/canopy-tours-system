const CustomCheckbox = ({ label, name, checked, onChange, errors }) => {
    return (
        <div>
            <div className="flex rounded-[10px] border border-[#F1F1F5] w-full sm:w-[672px] h-[60px]  ">
                <div className="flex items-center gap-[18px] p-3">
                    <input
                        type="checkbox"
                        id={name}
                        name={name}
                        checked={checked}
                        onChange={(e) => onChange(name, e.target.checked)}
                        className={`h-9 w-9 text-blue-600 ${errors[name] ? "border-red-500" : "border-gray-300"} focus:ring-blue-500 rounded-[10px]`}
                    />
                    <label htmlFor={name} className="text-gray-700">{label}</label>
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

export default CustomCheckbox;
