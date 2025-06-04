const SearchBar = ({ search, handleSearchChange, handleSearchSubmit }) => {
    return (
        <span className="flex items-center justify-between w-auto h-[40px] text-primary-14 hover:text-black active:text-blue-600 relative">
            <input
                type="text"
                placeholder="Search Here"
                value={search}
                onChange={handleSearchChange}
                onKeyDown={(e) => e.key === 'Enter' && handleSearchSubmit(search)}
                aria-label="Search input"
                className="w-full h-full text-btn-outline-gray pl-10 rounded-xl border-[3px] border-primary-14
                bg-white transition-all
                hover:border-black  active:border-blue-600 
                focus:outline-none focus:ring-0 focus:border-black"
            />
            <button
                type="button"
                className="absolute right-3 top-1/2 transform -translate-y-1/2 p-1 "
                onClick={() => handleSearchSubmit(search)}
            >
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g id="vuesax/linear/search-normal">
                <g id="search-normal">
                <path id="Vector" d="M9.58329 17.5C13.9555 17.5 17.5 13.9556 17.5 9.58332C17.5 5.21107 13.9555 1.66666 9.58329 1.66666C5.21104 1.66666 1.66663 5.21107 1.66663 9.58332C1.66663 13.9556 5.21104 17.5 9.58329 17.5Z" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" strokeLinejoin="round"/>
                <path id="Vector_2" d="M18.3333 18.3333L16.6666 16.6667" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                </g>
                </g>
            </svg>
            </button>
        </span>
    );
};

export default SearchBar;
