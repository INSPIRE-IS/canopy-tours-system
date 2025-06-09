const InputText = ({ title, field, value,mode,type="text", handleChange, errors }) => {
    return (
        <div className="mb-4 w-full">
            <label
                htmlFor={field}
                className="block input-header"
            >
                {title}
            </label>
            <input
                type={type}
                id={field}
                name={field}
                value={value}
                onChange={(e) => handleChange(field, e.target.value)}
                className={`mt-1 block w-full ${errors.name ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-xl
                hover:border-black  active:border-blue-600 
                focus:outline-none focus:ring-0 focus:border-black
                `}
                disabled={mode === "view"}
                required
            />
            {errors.name && (
                <p className="text-red-500 text-sm mt-2">
                    {errors.name}
                </p>
            )}
        </div>

    );
};

export default InputText;
