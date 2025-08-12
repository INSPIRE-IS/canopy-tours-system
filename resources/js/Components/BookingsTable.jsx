import React from "react";

export default function BookingsTable({ chartTitle, fields, data }) {
    // Helper to format the column name
    const formatHeader = (field) => {
        if (field === "created_at") {
            return "Date Created";
        }
        return field
            .split("_")
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(" ");
    };

    return (
        <div className="flex-1 bg-white p-4 shadow rounded-lg overflow-x-auto">
            <h3 className="text-lg font-bold mb-4">{chartTitle}</h3>
            <table className="min-w-full border border-gray-200 text-sm">
                <thead className="bg-gray-50">
                    <tr>
                        {Object.keys(fields || {}).map((field, i) => (
                            <th key={i} className="px-4 py-2 border">
                                {formatHeader(field)}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {data?.length > 0 ? (
                        data.map((row, i) => (
                            <tr key={i} className="odd:bg-white even:bg-gray-50">
                                {Object.entries(fields).map(([field, relation], j) => (
                                    <td key={j} className="px-4 py-2 border">
                                        {relation
                                            ? row[field]?.[relation] ?? ""
                                            : row[field] ?? ""}
                                    </td>
                                ))}
                            </tr>
                        ))
                    ) : (
                        <tr>
                            <td
                                colSpan={Object.keys(fields || {}).length}
                                className="text-center p-4"
                            >
                                No entries found
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}
