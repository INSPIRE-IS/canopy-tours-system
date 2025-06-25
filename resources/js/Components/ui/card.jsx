import React from 'react';

const Card = ({ image, service, season, price, badge, gift, onBook, onGift }) => (
  <div className="border rounded-xl overflow-hidden shadow bg-white">
    <img src={image} alt={season} className="w-full h-48 object-cover" />
    <div className="p-4">
      <span className="text-xs font-semibold px-2 py-1 rounded-full bg-[#C8E6C9] text-black">{service}</span>
      <h3 className="text-lg font-bold mt-1">{season}</h3>
      <p className="text-blue-600 font-semibold mt-1">{price}</p>
      {badge && <div className="text-xs text-gray-600 mt-2">{badge}</div>}
      <div className="flex gap-2 mt-4">
        <button onClick={onBook} className="bg-blue-100 text-blue-600 px-3 py-1 rounded text-sm border border-blue-300">
          Book Now
        </button>
        {gift && (
          <button onClick={onGift} className="bg-green-100 text-green-600 px-3 py-1 rounded text-sm border border-green-300">
            Buy Gift Voucher
          </button>
        )}
      </div>
    </div>
  </div>
);

export default Card;
