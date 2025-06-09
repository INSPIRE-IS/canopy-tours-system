import React, { useState, useEffect, useRef } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Html5QrcodeScanner } from 'html5-qrcode';
import CustomButton from '@/Components/CustomButton';

export default function BookingLookup() {
  const [showScanner, setShowScanner] = useState(false);
  const [isWelcome, setIsWelcome] = useState(true);
  const inputRef = useRef(null);
    const { data, setData, get, post, put, errors, reset, processing } = useForm({
        booking_id: "",
    });
    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
    };

  useEffect(() => {
    if (showScanner) {
      const scanner = new Html5QrcodeScanner(
        'qr-reader',
        { fps: 10, qrbox: { width: 250, height: 250 } },
        false
      );

      scanner.render(
        (decodedText) => {
          alert(`Booking ID: ${decodedText}`);
          
          setData('booking_id', decodedText);
          setShowScanner(false); // close scanner on success
          setTimeout(() => inputRef.current?.focus(), 100); // focus input
        },
        (errorMessage) => {
          // console.warn('QR Scan error:', errorMessage);
        }
      );

      return () => scanner.clear();
    }
  }, [showScanner]);

  const handleSubmit = (e) => {
      e.preventDefault();
      alert(`Searching booking for ID: ${data.booking_id}`);        
      if (data.booking_id) {
          get(route("questionnaire.create"), {
              onSuccess: () => {
                  reset();
              },
          });
      }else{
          alert('Please enter a booking ID');
      }
  };


  return (
    <>
      <Head title="Find Your Booking" />
      <div className="min-h-screen flex flex-col items-center justify-center bg-secondary-100">
        <div className="min-h-screen flex flex-col items-center justify-center bg-secondary-100">
            <div className="flex items-center justify-center w-full pb-[51px]">
              {/* Logo Box */}
              <div className="flex items-center justify-center w-[118px] h-[106px] ">
              <img
                src="/images/logo.png"
                alt="Canopy Tours"
                className="w-full h-full object-contain min-w-[100px] min-h-[90px]"
              />
              </div>

              {/* Text Logo */}
              <div className="flex items-center pl-2">
                <img
                  src="/images/text.png"
                  alt="Canopy Tours Text"
                  className="w-24 h-24 md:w-[118px] md:h-[106px] object-contain"
                />
              </div>
            </div>

          <div className="bg-secondary-100  overflow-hidden w-full max-w-5xl flex flex-col md:flex-row gap-[22px] mb-[47px]">
            {/* Image Column */}
            <div className="md:w-[448px] hidden md:block">
              <img
                src="/images/questionnaire.jpg" // Put your actual image here
                alt="Zipline adventure"
                className="w-full h-full object-cover shadow-lg rounded-[20px]"
              />
            </div>
            {/* Form Column */}            
            {isWelcome ? (

            <div className="flex bg-white items-center justify-center md:w-[642px] w-full h-[560px] shadow-lg rounded-[20px]">
              <CustomButton
                title="Touch here to get started!"
                bgColor="bg-[#31B700]"
                textColor="text-white"
                hoverColor="bg-green-700"
                hoverTextColor="text-white"
                type="button"
                icon={
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                  <path fillRule="evenodd" clipRule="evenodd" d="M9 3C9.41421 3 9.75 3.33579 9.75 3.75V14.25C9.75 14.6642 9.41421 15 9 15C8.58579 15 8.25 14.6642 8.25 14.25V3.75C8.25 3.33579 8.58579 3 9 3Z" fill="white"/>
                  <path fillRule="evenodd" clipRule="evenodd" d="M3 9C3 8.58579 3.33579 8.25 3.75 8.25H14.25C14.6642 8.25 15 8.58579 15 9C15 9.41421 14.6642 9.75 14.25 9.75H3.75C3.33579 9.75 3 9.41421 3 9Z" fill="white"/>
                </svg>                  
                }
                onClick={() => setIsWelcome(false)}
              />
            </div>
      ):(

            
            <div className="flex flex-col bg-white items-center justify-start md:w-[642px] w-full h-[560px] shadow-lg rounded-[20px]">


              <h2 className="text-welcome-card-title w-[415px] mt-[42px] mb-[26px] px-[39px]">
                Please enter or scan your booking ID or reference code
              </h2>
              <p className="text-welcome-card-title-sub w-[493px] mb-[28px]">
                You can find your booking ID or QR code in your booking confirmation email.
                Alternatively ask a staff member for assistance.
              </p>

              <input
                name="booking_id"
                value={data.booking_id}
                onChange={(e) => handleChange(e.target.name, e.target.value)}
                type="text"
                placeholder="Enter your booking ID"
                className="w-[389px] h-[60px] px-5 py-4 border rounded-md mb-4 focus:outline-none focus:ring-2 focus:ring-green-500"
              />

              <CustomButton
                title="Find my Booking"
                bgColor="bg-[#31B700]"
                textColor="text-white"
                hoverColor="bg-green-700"
                hoverTextColor="text-white"
                type="button"
                icon={
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 11 11" fill="none">
                <path fillRule="evenodd" clipRule="evenodd" d="M1.64749 4.97127C1.64749 4.0895 1.99774 3.24385 2.62118 2.62034C3.24462 1.99684 4.09019 1.64656 4.97187 1.64656C5.85355 1.64656 6.69911 1.99684 7.32256 2.62034C7.946 3.24385 8.29624 4.0895 8.29624 4.97127C8.29624 5.85303 7.946 6.69869 7.32256 7.32219C6.69911 7.94569 5.85355 8.29597 4.97187 8.29597C4.09019 8.29597 3.24462 7.94569 2.62118 7.32219C1.99774 6.69869 1.64749 5.85303 1.64749 4.97127ZM7.85599 9.02065C6.84046 9.74434 5.59006 10.0599 4.35278 9.90477C3.11549 9.74963 1.98169 9.13511 1.17623 8.18309C0.370768 7.23107 -0.0475282 6.01108 0.00429916 4.76506C0.0561265 3.51904 0.574292 2.33801 1.45603 1.45618C2.33777 0.574349 3.51869 0.0561321 4.76458 0.00429959C6.01048 -0.0475329 7.23035 0.370805 8.18227 1.17635C9.1342 1.98189 9.74865 3.11581 9.90378 4.35321C10.0589 5.59062 9.74337 6.84114 9.01975 7.85678L10.7379 9.57403C10.8188 9.64942 10.8837 9.74034 10.9287 9.84135C10.9737 9.94237 10.9979 10.0514 10.9999 10.162C11.0018 10.2726 10.9815 10.3824 10.9401 10.4849C10.8987 10.5875 10.837 10.6806 10.7588 10.7588C10.6806 10.837 10.5875 10.8986 10.485 10.9401C10.3824 10.9815 10.2726 11.0018 10.1621 10.9999C10.0515 10.9979 9.94247 10.9737 9.84147 10.9287C9.74046 10.8837 9.64956 10.8188 9.57418 10.7379L7.85599 9.02065Z" fill="white"/>
                </svg>                 
                }
                onClick={(e) => handleSubmit(e)}
              />
              <CustomButton
                title="Scan a Booking QR code"
                bgColor="bg-white"
                textColor="text-green-600"
                hoverColor="bg-green-50"
                hoverTextColor="text-white"
                type="button"
                icon={
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                  <g clipPath="url(#clip0_169_4715)">
                  <path fillRule="evenodd" clipRule="evenodd" d="M1.73483 1.73483C1.80516 1.66451 1.90054 1.625 2 1.625H4C4.34518 1.625 4.625 1.34518 4.625 1C4.625 0.654822 4.34518 0.375 4 0.375H2C1.56902 0.375 1.1557 0.546205 0.850951 0.850951C0.546205 1.1557 0.375 1.56902 0.375 2V4C0.375 4.34518 0.654822 4.625 1 4.625C1.34518 4.625 1.625 4.34518 1.625 4V2C1.625 1.90054 1.66451 1.80516 1.73483 1.73483ZM9.375 1C9.375 0.654822 9.65482 0.375 10 0.375H12C12.431 0.375 12.8443 0.546205 13.149 0.850951C13.4538 1.1557 13.625 1.56902 13.625 2V4C13.625 4.34518 13.3452 4.625 13 4.625C12.6548 4.625 12.375 4.34518 12.375 4V2C12.375 1.90054 12.3355 1.80516 12.2652 1.73483C12.1948 1.66451 12.0995 1.625 12 1.625H10C9.65482 1.625 9.375 1.34518 9.375 1ZM2.5 4C2.5 3.17157 3.17157 2.5 4 2.5H6C6.82843 2.5 7.5 3.17157 7.5 4V6C7.5 6.82843 6.82843 7.5 6 7.5H4C3.17157 7.5 2.5 6.82843 2.5 6V4ZM3.75 9.375C3.75 9.02982 3.47018 8.75 3.125 8.75C2.77982 8.75 2.5 9.02982 2.5 9.375V10.875C2.5 11.2202 2.77982 11.5 3.125 11.5H4.625C4.97018 11.5 5.25 11.2202 5.25 10.875C5.25 10.5298 4.97018 10.25 4.625 10.25H3.75V9.375ZM5 9.25C5 8.90482 5.27982 8.625 5.625 8.625H7.125C7.47018 8.625 7.75 8.90482 7.75 9.25V10.75C7.75 11.0952 7.47018 11.375 7.125 11.375C6.77982 11.375 6.5 11.0952 6.5 10.75V9.875H5.625C5.27982 9.875 5 9.59518 5 9.25ZM10 3.125C10 2.77982 9.72018 2.5 9.375 2.5C9.02982 2.5 8.75 2.77982 8.75 3.125V4.625C8.75 4.97018 9.02982 5.25 9.375 5.25H10.875C11.2202 5.25 11.5 4.97018 11.5 4.625C11.5 4.27982 11.2202 4 10.875 4H10V3.125ZM8.75 6.625C8.75 6.27982 9.02982 6 9.375 6H10.875C11.2202 6 11.5 6.27982 11.5 6.625V8.125C11.5 8.47018 11.2202 8.75 10.875 8.75C10.5298 8.75 10.25 8.47018 10.25 8.125V7.25H9.375C9.02982 7.25 8.75 6.97018 8.75 6.625ZM10 9.375C10 9.02982 9.72018 8.75 9.375 8.75C9.02982 8.75 8.75 9.02982 8.75 9.375V10.875C8.75 11.2202 9.02982 11.5 9.375 11.5H10.875C11.2202 11.5 11.5 11.2202 11.5 10.875C11.5 10.5298 11.2202 10.25 10.875 10.25H10V9.375ZM13 9.375C13.3452 9.375 13.625 9.65482 13.625 10V12C13.625 12.431 13.4538 12.8443 13.149 13.149C12.8443 13.4538 12.431 13.625 12 13.625H10C9.65482 13.625 9.375 13.3452 9.375 13C9.375 12.6548 9.65482 12.375 10 12.375H12C12.0995 12.375 12.1948 12.3355 12.2652 12.2652C12.3355 12.1948 12.375 12.0995 12.375 12V10C12.375 9.65482 12.6548 9.375 13 9.375ZM1.625 10C1.625 9.65482 1.34518 9.375 1 9.375C0.654822 9.375 0.375 9.65482 0.375 10V12C0.375 12.431 0.546205 12.8443 0.850951 13.149C1.1557 13.4538 1.56902 13.625 2 13.625H4C4.34518 13.625 4.625 13.3452 4.625 13C4.625 12.6548 4.34518 12.375 4 12.375H2C1.90054 12.375 1.80516 12.3355 1.73483 12.2652C1.66451 12.1948 1.625 12.0995 1.625 12V10Z" fill="#40BC12"/>
                  </g>
                  <defs>
                  <clipPath id="clip0_169_4715">
                  <rect width="14" height="14" fill="white"/>
                  </clipPath>
                  </defs>
                  </svg>                
                }
                onClick={() => setShowScanner(true)}
              />
            </div>
      )}

          </div>  

        </div>

      </div>
      {/* QR Scanner Modal */}
      {showScanner && (
        <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
          <div className="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
            <button
              onClick={() => setShowScanner(false)}
              className="absolute top-2 right-2 text-gray-500 hover:text-red-500 text-lg"
            >
              ✖
            </button>
            <h2 className="text-center font-semibold mb-4">Scan QR Code</h2>
            <div id="qr-reader" className="w-full" />
          </div>
        </div>
      )}

    </>
  );
}
