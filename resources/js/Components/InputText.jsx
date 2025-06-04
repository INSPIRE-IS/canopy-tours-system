const InputText = ({ title, field, value,mode, handleChange, errors }) => {
    return (
        <div className="mb-4 w-full">
            <label
                htmlFor="name"
                className="block input-header"
            >
                {title}
            </label>
            <input
                type="text"
                id={field}
                name={field}
                value={value}
                onChange={(e) => handleChange(field, e.target.value)}
                className={`mt-1 block w-full ${errors.name ? "border-red-500" : "border-gray-300"} shadow-sm input-text rounded-xl`}
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
