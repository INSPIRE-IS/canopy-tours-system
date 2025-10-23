import React, { useState, useEffect, useRef } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Html5QrcodeScanner } from 'html5-qrcode';
import CustomButton from '@/Components/CustomButton';
import CustomSelect from '@/Components/CustomSelect';
import ManifestModal from './ManifestModal';
import ManifestsModal from './ManifestsModal';

export default function BookingLookup({ bookings }) {
  //console.log('bookings', bookings);

  const [BookingOptions, setBookingOptions] = useState([]);
  const [isManifestModalOpen, setIsManifestModalOpen] = useState(false);
  const [isManifestsModalOpen, setIsManifestsModalOpen] = useState(false);
  const [selectedManifests, setSelectedManifests] = useState(null);
  const [selectedManifest, setSelectedManifest] = useState(null);

  const { data, setData, get, post, put, errors, reset, processing } = useForm({
    booking_id: null,
  });

  useEffect(() => {
    if (bookings) {
      const bookingOptions = bookings.map(booking => {
        return {
          id: booking.id,
          name: booking.lead_name + ' - ' + booking.reference,
        };
      });
      //console.log('bookingOptions', bookingOptions);
      setBookingOptions(bookingOptions);
    }
  }, [bookings]);


  const handleChange = (target, value) => {
    // console.log('target', target);
    // console.log('value', value);
    setData(target, value);
    //setData("name", e.target.value);
  };



  const handleSubmit = async (e) => {
    e.preventDefault();
    console.log('data', data);
    console.log('bookings', bookings);
    if (!data.booking_id) {
      alert('Please select a booking');
      return;
    }
    const booking = bookings.find(b => b.id.toString() === data.booking_id);
    console.log('booking', booking);
    if (!booking) {
      alert('Booking not found');
      return;
    }
    if (booking.lead_name) {
      setSelectedManifests(booking.customers);
      setIsManifestsModalOpen(true);
      return;
    }
  };
  const handleCloseModal = () => {
    setIsManifestModalOpen(false);
    setIsManifestsModalOpen(false);
    setSelectedManifests(null);
    setSelectedManifest(null);
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

            <div className="flex flex-col bg-white items-center justify-start md:w-[642px] w-full h-[560px] shadow-lg rounded-[20px]">


              <h2 className="text-welcome-card-title w-[415px] mt-[42px] mb-[26px] px-[39px]">
                Please Select your Booking
              </h2>
              <div className="flex flex-col w-[85%] items-center justify-center">
                <CustomSelect
                  title="Select your Booking"
                  name="booking_id"
                  value={data.booking_id}
                  options={BookingOptions}
                  onChange={(e) => handleChange(e.target.name, e.target.value)}
                  type="text"
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
                      <path fillRule="evenodd" clipRule="evenodd" d="M1.64749 4.97127C1.64749 4.0895 1.99774 3.24385 2.62118 2.62034C3.24462 1.99684 4.09019 1.64656 4.97187 1.64656C5.85355 1.64656 6.69911 1.99684 7.32256 2.62034C7.946 3.24385 8.29624 4.0895 8.29624 4.97127C8.29624 5.85303 7.946 6.69869 7.32256 7.32219C6.69911 7.94569 5.85355 8.29597 4.97187 8.29597C4.09019 8.29597 3.24462 7.94569 2.62118 7.32219C1.99774 6.69869 1.64749 5.85303 1.64749 4.97127ZM7.85599 9.02065C6.84046 9.74434 5.59006 10.0599 4.35278 9.90477C3.11549 9.74963 1.98169 9.13511 1.17623 8.18309C0.370768 7.23107 -0.0475282 6.01108 0.00429916 4.76506C0.0561265 3.51904 0.574292 2.33801 1.45603 1.45618C2.33777 0.574349 3.51869 0.0561321 4.76458 0.00429959C6.01048 -0.0475329 7.23035 0.370805 8.18227 1.17635C9.1342 1.98189 9.74865 3.11581 9.90378 4.35321C10.0589 5.59062 9.74337 6.84114 9.01975 7.85678L10.7379 9.57403C10.8188 9.64942 10.8837 9.74034 10.9287 9.84135C10.9737 9.94237 10.9979 10.0514 10.9999 10.162C11.0018 10.2726 10.9815 10.3824 10.9401 10.4849C10.8987 10.5875 10.837 10.6806 10.7588 10.7588C10.6806 10.837 10.5875 10.8986 10.485 10.9401C10.3824 10.9815 10.2726 11.0018 10.1621 10.9999C10.0515 10.9979 9.94247 10.9737 9.84147 10.9287C9.74046 10.8837 9.64956 10.8188 9.57418 10.7379L7.85599 9.02065Z" fill="white" />
                    </svg>
                  }
                  onClick={(e) => handleSubmit(e)}
                />
              </div>

            </div>

          </div>

        </div>

      </div >

      <ManifestModal
        isOpen={isManifestModalOpen}
        onClose={handleCloseModal}
        mode="edit"
        customers={selectedManifests}
        customer={selectedManifest}
        tot_adults='0'
        tot_children='0'
      />

      <ManifestsModal
        isOpen={isManifestsModalOpen}
        onClose={handleCloseModal}
        mode="edit"
        customers={selectedManifests}
        tot_adults='0'
        tot_children='0'
      />

    </>
  );
}
