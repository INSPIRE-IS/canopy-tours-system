import React, { useEffect, useState } from "react";
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";
import axios from "axios";

const PromoModal = ({ isOpen, onClose, serviceId, bookingDate, onSelect }) => {
  const [promotions, setPromotions] = useState([]);
  useEffect(() => {
    if (isOpen && serviceId && bookingDate) {
      axios
        .get(route("admin.promotions.valid"), {
          params: { service_id: serviceId, booking_date: bookingDate },
        })
        .then((res) => { setPromotions(res.data); console.log('promotions', res.data); })
        .catch((err) => console.error(err));
      //   router.get(
      //     route("admin.promotions.valid"),
      //     {service_id: serviceId, booking_date: bookingDate},
      //     {
      //       preserveState: true,
      //       onSuccess: (page) => {
      //         console.log('page', page);
      //         setPromotions(page.props.promotions || []);
      //       },
      //       onError: (error) => {
      //         console.error('Failed to load promotions:', error);
      //       },
      //     }
      //   );
    }
  }, [isOpen, serviceId, bookingDate]);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
      <div className="bg-white rounded-lg shadow-lg p-6 w-[40%] max-h-[80%] overflow-y-auto">
        <h2 className="text-xl font-bold mb-4">Promotions | Booking Date: {bookingDate}</h2>

        {/* {promotions.length === 0 ? (
          <p className="text-gray-600">No valid promotions found for this service/date.</p>
        ) : (
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700">Select a promotion:</label>
            <select
              className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              onChange={(e) => onSelect(e.target.value)}
            >
              <option value="">Select a promotion...</option>
              {promotions.map((promo) => (
                <option key={promo.id} value={promo.promo_code}>
                  {promo.name}
                </option>
              ))}
            </select>
          </div>
        )} */}
        {promotions?.length === 0 ? (
          <p className="text-gray-600">No valid promotions found for this service and date.</p>
        ) : (
          <div className="mb-2">
            <ul className="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-6space-y-3">
              {promotions?.map((promo) => {
                console.log('promo', promo);
                let color = "opacity-50 bg-gray-200 cursor-not-allowed";

                if (promo.status === "active" && promo.is_valid) {
                  color = "bg-blue-200 hover:bg-blue-300 cursor-pointer";
                } else if (promo.status === "upcoming") {
                  color = "bg-yellow-200 opacity-75 cursor-wait";
                } else if (promo.status === "expired") {
                  color = "bg-red-200 opacity-75 cursor-not-allowed";
                }

                return (
                  <li
                    key={promo.id}
                    className={`p-3 border rounded  ${color}`}
                    onClick={() => {
                      if (promo.is_valid) {
                        onSelect(promo.promo_code);
                        onClose();
                      }
                    }}
                  >
                    <div className="font-semibold">{promo.name}</div>
                    <div className="text-sm text-gray-600">
                      Type: {promo.type} | Status: {promo.status} {promo.is_valid ? "(valid)" : "(not valid for selected activity date)"}
                    </div>
                    <div className="text-sm text-gray-500">
                      Booking {promo.booking_start_date.split('T')[0]} → {promo.booking_end_date.split('T')[0]}
                    </div>
                    <div className="text-sm text-gray-500">
                      Activity {promo.activity_start_date.split('T')[0]} → {promo.activity_end_date.split('T')[0]}
                    </div>
                    <div className="text-sm text-gray-600">
                      Code: <span className="font-mono">{promo.promo_code}</span>
                    </div>
                  </li>
                )
              })}
            </ul>
          </div>
        )}

        <div className="flex justify-end gap-3 mt-6">
          <OutlineButton title="Close" onClick={onClose} />
        </div>
      </div>
    </div>
  );
};

export default PromoModal;
