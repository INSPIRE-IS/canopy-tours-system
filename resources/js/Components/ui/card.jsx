import { icons } from '@/icons';
import React from 'react';

const Card = ({ image, service, season, price,badge, tag, promo, gift, onBook, onGift }) => {
  
  console.log('promo', promo);
  return(
  <div className="border rounded-xl overflow-hidden shadow bg-white">
    {/* Image with dark overlay */}
    <div className="relative">
      <img src={image} alt={season} className="w-full h-48 object-cover" />
      {promo && (
        <div className="absolute inset-0 bg-black bg-opacity-40 flex flex-col items-center justify-center">
          <span 
            className="text-md font-bold text-white px-3 py-2 rounded" title={promo?.promo_code}
          >{promo?.name}</span>
          <span 
            className="text-sm font-bold text-white px-3 py-2 rounded"
          >{promo?.value} %</span>
          <span 
            className="text-xl font-bold text-white px-3 py-2 rounded"
          >Booking now</span>
          <span 
            className="text-sm font-bold text-white px-3 py-2 rounded"
          >{promo?.booking_start_date} to {promo?.booking_end_date}</span>
        </div>
      )}
    </div>
    <div className="p-4">

      <span className="text-xs font-semibold px-2 py-1 rounded-full bg-[#C8E6C9] text-black">{tag}</span>
      <h3 className="text-lg font-bold mt-1">{service}</h3>
      <p className="text-[#31B700] font-semibold mt-1">R{price}</p>
      {badge && <span className="text-xs text-gray-600 px-2 py-1 rounded-[10px] bg-[#EBEBEB] mt-2">{badge}</span>}
      <div className="flex flex-col w-full gap-2 mt-4">
        <button onClick={onBook} className="flex gap-2 items-center justify-start bg-[#31B700] text-[#FFFFFF] px-3 py-1 rounded-[10px] text-sm border border-[#31B700]">
          {icons.ticket} Book Now
        </button>
        {!gift && (
          <button onClick={onGift} className="flex gap-2 items-center justify-start bg-white text-[#4D81E7] px-3 py-1 rounded-[10px] text-sm border border-[#F1F1F5]">
            {icons.gift} Buy Gift Voucher
          </button>
        )}
      </div>
    </div>
  </div>
)};

export default Card;
