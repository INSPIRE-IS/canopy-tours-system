import React from 'react';
import { Head } from '@inertiajs/react';

export default function BookingQuestionnaireExpired({ message, activityDate, activityOver, contactEmail }) {
  const formattedDate = activityDate
    ? new Date(`${activityDate}T00:00:00`).toLocaleDateString('en-ZA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      })
    : null;

  return (
    <>
      <Head title="Check-in Link Expired" />
      <div className="min-h-screen flex flex-col items-center justify-center bg-secondary-100 px-4 py-8">
        <div className="flex items-center justify-center w-full pb-8">
          <div className="flex items-center justify-center w-[118px] h-[106px]">
            <img
              src="/images/logo.png"
              alt="Canopy Tours"
              className="w-full h-full object-contain min-w-[100px] min-h-[90px]"
            />
          </div>
          <div className="flex items-center pl-2">
            <img
              src="/images/text.png"
              alt="Canopy Tours Text"
              className="w-24 h-24 md:w-[118px] md:h-[106px] object-contain"
            />
          </div>
        </div>

        <div className="flex flex-col bg-white w-full max-w-2xl items-center shadow-lg rounded-[20px] px-6 py-10 text-center">
          <h1 className="text-welcome-card-title-mb mb-4">Link Expired</h1>
          <p className="text-gray-700 text-base mb-4">
            {message || 'This check-in link is no longer valid.'}
          </p>
          {activityOver && formattedDate && (
            <p className="text-gray-600 text-sm">
              Activity date: {formattedDate}
            </p>
          )}
          {!activityOver && !contactEmail && (
            <p className="text-gray-600 text-sm">
              Please contact your booking agent to request a new check-in link.
            </p>
          )}
          {!activityOver && contactEmail && (
            <p className="text-gray-600 text-sm">
              Please contact {contactEmail} to request a new link.
            </p>
          )}
        </div>
      </div>
    </>
  );
}
