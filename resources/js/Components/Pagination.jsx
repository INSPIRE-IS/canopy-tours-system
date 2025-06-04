import React from "react";
import { Link } from "@inertiajs/react";

const Pagination = ({ links, dataParam }) => {
    const appendParam = (url) => {
        if (!url) return null; // Skip empty links (like separators)

        const urlObj = new URL(url, window.location.origin);

        // Conditionally append the data parameter
        if (dataParam) {
            urlObj.searchParams.set("data", dataParam);
        }

        return urlObj.toString();
    };

    return (
        <nav className="flex flex-col mb-96 lg:mb-2 lg:flex-row justify-between mt-4  lg:w-[60%] mx-auto">
            {links?.map((link, index) => (
                <Link
                    key={index}
                    href={appendParam(link.url)}
                    className={`px-3 py-2 rounded ${
                        link.active
                            ? "bg-[#F6C546] button-text1"
                            : "bg-[#F6C546] button-text1"
                    }`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                ></Link>
            ))}
        </nav>
    );
};

export default Pagination;
