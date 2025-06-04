const CustomCheckbox = ({ label, name, checked, onChange, errors }) => {
    return (
        <div className="mb-4">
            <div className="flex items-center">
                <input
                    type="checkbox"
                    id={name}
                    name={name}
                    checked={checked}
                    onChange={(e) => onChange(name, e.target.checked)}
                    className={`h-5 w-5 text-blue-600 ${errors[name] ? "border-red-500" : "border-gray-300"} focus:ring-blue-500 rounded-sm`}
                />
                <label htmlFor={name} className="ml-2 text-gray-700">{label}</label>
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
