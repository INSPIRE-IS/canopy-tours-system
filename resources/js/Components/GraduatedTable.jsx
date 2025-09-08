import React from "react";
import CustomInputText from "@/Components/CustomInputText";

const GraduatedTable = ({ capacity, lookup = {}, onChange }) => {
  const handleChange = (pax, value) => {
    const updated = { ...lookup, [pax]: value };
    onChange(updated);
  };

  return (
    <div className="border rounded-lg p-4 mt-2">
      <h4 className="font-semibold mb-2">Graduated Pricing</h4>
      <table className="w-full border-collapse">
        <thead>
          <tr>
            <th className="border p-2 text-left">Pax</th>
            <th className="border p-2 text-left">Total Price</th>
          </tr>
        </thead>
        <tbody>
          {Array.from({ length: capacity }, (_, i) => i + 1).map((pax) => (
            <tr key={pax}>
              <td className="border p-2">{pax}</td>
              <td className="border p-2">
                <input
                  type="number"
                  className="w-full border rounded p-1"
                  value={lookup[pax] || ""}
                  onChange={(e) => handleChange(pax, e.target.value)}
                />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

export default GraduatedTable;
