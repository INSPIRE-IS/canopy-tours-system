
const CustomButton = ({ title, bgColor, textColor, hoverColor, hoverTextColor, type = "button", width = "w-[389px]", height = "h-[60px]", icon = [icon], onClick }) => {
    return (

        <button onClick={onClick} className={`inline-flex items-center justify-center gap-2 border border-[#31B700]
           ${width} ${height} ${bgColor} hover:${hoverColor} hover:${hoverTextColor}  px-6  py-[21px] rounded-[10px] mb-3 transition`}
            type={type}
        >
            {icon}
            <span className={`flex ${textColor} items-center text-btn-green`}>{title}</span>
        </button>
    );
};
export default CustomButton;
