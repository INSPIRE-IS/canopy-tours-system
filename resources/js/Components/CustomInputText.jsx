const CustomInputText = ({ title, name, value, mode, type = "text", width = "w-full", height = "h-[48px]", onChange, errors = {}, ...props }) => {
    return (
        <div className="mb-4 w-full">
            <label
                htmlFor={name}
                className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]"
            >
                {title}
            </label>
            <input
                type={type}
                id={name}
                name={name}
                value={value ?? ""}
                onChange={onChange}
                className={`mt-1 block ${width} ${height}  ${errors?.[name] ? "border-red-500" : "border-gray-300"}  shadow-sm input-text rounded-[10px]
                hover:border-black  active:border-blue-600 
                focus:outline-none focus:ring-0 focus:border-black
                `}
                disabled={mode === "view"}
                required
                {...props}
            />
            {errors?.[name] && (
                <p className="text-red-500 text-sm mt-2">
                    {errors[name]}
                </p>
            )}
        </div>

    );
};

export default CustomInputText;
