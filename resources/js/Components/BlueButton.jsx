const BlueButton = ({ title, onClick, disabled = false, icon=false, width, props}) => {
    return (
        <button onClick={onClick}
            className={`flex items-center justify-center ${width} h-[40px] gap-2 px-4 py-2 rounded-[10px] border-[1px] 
            bg-[#3863C6] border-[#3863C6] text-white shadow-inset-white 
            hover:border-black hover:text-black
            cursor-pointer`} disabled={disabled} {...props}>
                {icon && (
                <svg width="19" height="18" viewBox="0 0 19 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <g id="plus">
                    <g id="Vector">
                    <path fillRule="evenodd" clipRule="evenodd" d="M9.5 3C9.91421 3 10.25 3.33579 10.25 3.75V14.25C10.25 14.6642 9.91421 15 9.5 15C9.08579 15 8.75 14.6642 8.75 14.25V3.75C8.75 3.33579 9.08579 3 9.5 3Z" fill="currentColor"/>
                    <path fillRule="evenodd" clipRule="evenodd" d="M3.5 9C3.5 8.58579 3.83579 8.25 4.25 8.25H14.75C15.1642 8.25 15.5 8.58579 15.5 9C15.5 9.41421 15.1642 9.75 14.75 9.75H4.25C3.83579 9.75 3.5 9.41421 3.5 9Z" fill="currentColor"/>
                    </g>
                    </g>
                </svg>                    
                )}
            <span className="text-btn-outline-gray">{title}</span>
        </button>
    );
};

export default BlueButton;
