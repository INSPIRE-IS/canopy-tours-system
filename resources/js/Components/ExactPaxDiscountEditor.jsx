import React from "react";

const ExactPaxDiscountEditor = ({ capacity, rules = {}, onChange }) => {
  const maxPax = Math.max(0, Number(capacity) || 0);

  const handleRuleChange = (pax, field, value) => {
    const key = String(pax);
    const current = rules?.[key] || { type: "percent", value: "" };
    const nextRule = { ...current, [field]: value };
    const nextRules = { ...(rules || {}) };

    if (field === "value" && value === "") {
      delete nextRules[key];
    } else {
      nextRules[key] = {
        type: nextRule.type || "percent",
        value: nextRule.value ?? "",
      };
    }

    onChange(nextRules);
  };

  return (
    <div className="border rounded-lg p-4 mt-2">
      <h4 className="font-semibold mb-2">Exact Pax Discount</h4>
      <p className="text-xs text-gray-500 mb-3">
        Add values only for pax counts that should receive this promo. If a booking has any other pax count, the promo will not apply.
      </p>
      <table className="w-full border-collapse">
        <thead>
          <tr>
            <th className="border p-2 text-left">Pax</th>
            <th className="border p-2 text-left">Type</th>
            <th className="border p-2 text-left">Value</th>
          </tr>
        </thead>
        <tbody>
          {Array.from({ length: maxPax }, (_, i) => i + 1).map((pax) => {
            const rule = rules?.[String(pax)] || rules?.[pax] || {};
            return (
              <tr key={pax}>
                <td className="border p-2">{pax}</td>
                <td className="border p-2">
                  <select
                    className="w-full border rounded p-1"
                    value={rule.type || "percent"}
                    onChange={(e) => handleRuleChange(pax, "type", e.target.value)}
                  >
                    <option value="percent">%</option>
                    <option value="fixed_value">R</option>
                  </select>
                </td>
                <td className="border p-2">
                  <input
                    type="number"
                    min="0"
                    step="0.01"
                    className="w-full border rounded p-1"
                    value={rule.value ?? ""}
                    onChange={(e) => handleRuleChange(pax, "value", e.target.value)}
                  />
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
};

export default ExactPaxDiscountEditor;
