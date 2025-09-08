import React from "react";

const VolumeDiscountEditor = ({ capacity, tiers = [], onChange }) => {
  const handleTierChange = (index, field, value) => {
    const updated = tiers.map((t, i) =>
      i === index ? { ...t, [field]: value } : t
    );
    onChange(updated);
  };

  const addTier = () => {
    onChange([...tiers, { pax: "", type: "percent", value: "" }]);
  };

  const removeTier = (index) => {
    onChange(tiers.filter((_, i) => i !== index));
  };

  return (
    <div className="border rounded-lg p-4 mt-2">
      <h4 className="font-semibold mb-2">Volume Discounts</h4>
      {tiers.map((tier, index) => (
        <div key={index} className="flex gap-2 items-center mb-2">
          <input
            type="number"
            className="w-20 border rounded p-1"
            placeholder="Pax"
            max={capacity}
            value={tier.pax}
            onChange={(e) => handleTierChange(index, "pax", e.target.value)}
          />
          <select
            className="border rounded p-1"
            value={tier.type}
            onChange={(e) => handleTierChange(index, "type", e.target.value)}
          >
            <option value="percent">%</option>
            <option value="fixed_value">R</option>
          </select>
          <input
            type="number"
            className="w-24 border rounded p-1"
            placeholder="Value"
            value={tier.value}
            onChange={(e) => handleTierChange(index, "value", e.target.value)}
          />
          <button
            type="button"
            className="text-red-500"
            onClick={() => removeTier(index)}
          >
            ✕
          </button>
        </div>
      ))}
      <button
        type="button"
        className="bg-blue-500 text-white rounded px-3 py-1 mt-2"
        onClick={addTier}
      >
        + Add Tier
      </button>
    </div>
  );
};

export default VolumeDiscountEditor;
