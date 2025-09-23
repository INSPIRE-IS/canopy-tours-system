// const WhiteButton = ({ title, onClick, disabled = false, icon=false, iconName='', textColor='text-black', width,height="h-[40px]", props }) => {
//     return (
//         <button onClick={onClick}
//                 className={`flex items-center justify-center ${width} ${height} gap-2 px-4 py-2 rounded-[10px] border border-primary-14
//                 bg-white-gradient-overlay text-black shadow-inset-white 
//                 hover:border-black hover:text-blackcursor-pointer`}
//                 disabled={disabled} {...props}>
//                     {icon && (
//                         iconName
//                     )}
//             <span className={`text-btn-white ${textColor}`}>{title}</span>
//         </button>
//     );
// };

// export default WhiteButton;


const WhiteButton = ({
    title,
    onClick,
    disabled = false,
    icon = false,
    iconName,
    textColor = 'text-black',
    width,
    height = "h-[40px]",
    className = "",
    ...props
}) => {
    return (
        <button
            onClick={onClick}
            disabled={disabled}
            className={`flex items-center justify-center ${width} ${height} gap-2 px-4 py-2 rounded-[10px] border border-primary-14
                bg-white-gradient-overlay ${textColor} shadow-inset-white 
                hover:border-black hover:text-black cursor-pointer ${className}`}
            {...props}
        >
            {icon && iconName}
            <span className={`text-btn-white ${textColor}`}>{title}</span>
        </button>
    );
};

export default WhiteButton;