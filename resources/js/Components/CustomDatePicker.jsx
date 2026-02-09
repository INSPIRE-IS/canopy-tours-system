const CustomDatePicker = ({ label, name, value, mode, type = "date", width = "w-full", height = "h-[40px]", onChange, errors = {}, ...props }) => {
    return (
        <div className="flex flex-row items-center w-full gap-2">
            <label
                htmlFor={name}
                className="text-[#161618] font-poppins text-[12px] font-semibold leading-[130%]"
            >
                {label}
            </label>
            <input
                type={type}
                id={name}
                name={name}
                value={value ?? ""}
                onChange={onChange}
                className={` ${width} ${height}  ${errors?.[name] ? "border-red-500" : "border-gray-300"}  
                    w-auto h-[40px] rounded-xl border-[3px] border-primary-14 text-primary-14 cursor-pointer
                `}
                disabled={mode === "view"}
                required
                {...props}
            />
            {/* <input
                type='date'
                id='selected_date'
                name='selected_date'
                value={selectedDate ? format(selectedDate, 'yyyy-MM-dd') : ''}
                onChange={(e) => { setSelectedDate(e.target.value); }}
                className={`w-auto h-[40px] rounded-xl border-[3px] border-primary-14 text-primary-14 cursor-pointer`}
            /> */}
            {errors?.[name] && (
                <p className="text-red-500 text-sm mt-2">
                    {errors[name]}
                </p>
            )}
        </div>

    );
};

export default CustomDatePicker;
