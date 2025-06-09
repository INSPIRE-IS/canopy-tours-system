import React from 'react';

const Card = ({ image, title, category, price, badge, gift }) => (
  <div className="border rounded-xl overflow-hidden shadow bg-white">
    <img src={image} alt={title} className="w-full h-48 object-cover" />
    <div className="p-4">
      <span className="text-sm text-green-700 font-semibold">{category}</span>
      <h3 className="text-lg font-bold mt-1">{title}</h3>
      <p className="text-blue-600 font-semibold mt-1">{price}</p>
      {badge && <div className="text-xs text-gray-600 mt-2">{badge}</div>}
      <div className="flex gap-2 mt-4">
        <button className="bg-green-500 text-white px-3 py-1 rounded text-sm">Book Now</button>
        {gift && (
          <button className="bg-blue-100 text-blue-600 px-3 py-1 rounded text-sm border border-blue-300">
            Buy Gift Voucher
          </button>
        )}
      </div>
    </div>
  </div>
);

export default Card;
