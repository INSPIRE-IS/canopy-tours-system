const ManifestCount = ({ title, value, className='' }) => {
    return (
        <div className="mb-4 w-full  gap-5 ">
            <span
                htmlFor={name}
                className="text-[#161618] font-poppins text-[12px] font-normal  leading-[130%]"
            >
                {title}
            </span>
            <span
                className={`mt-1 block w-[61px] h-[48px] rounded-[10px] border border-gray-300 shadow-sm 
                text-[#161618] font-geist text-[14px] font-medium leading-[150%] tracking-[-0.28px] 
                 px-3 py-3 text-center ${
                className}`}

            >{value}</span>
        </div>

    );
};

export default ManifestCount;
