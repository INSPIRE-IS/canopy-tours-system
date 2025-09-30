const OutlineButton = ({ title, onClick, width, props }) => {
    return (
        <button onClick={onClick}
            className={`flex items-center justify-center ${width} h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] 
            bg-white border-[#979797] text-[#7C7C7C] shadow-inset-white 
            hover:border-black hover:text-black
            cursor-pointer`} {...props} >
            <span className="text-btn-outline-gray">{title}</span>
        </button>
    );
};

export default OutlineButton;
