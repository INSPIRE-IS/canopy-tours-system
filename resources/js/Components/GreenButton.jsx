const GreenButton = ({
    title,
    onClick,
    disabled = false,
    icon = false,
    iconName,
    width,
    className = "",
    ...props
}) => {
    return (
        <button
            onClick={onClick}
            disabled={disabled}
            className={`flex items-center justify-center ${width} h-[40px] gap-2 px-4 py-2 rounded-[10px] border-[1px] 
        bg-[#31B700] border-[#3ee600] text-white shadow-inset-white 
        hover:border-green-500 hover:text-black
        cursor-pointer ${className}`}
            {...props}
        >
            {icon && iconName}
            <span className="text-btn-outline-gray">{title}</span>
        </button>
    );
};

export default GreenButton;