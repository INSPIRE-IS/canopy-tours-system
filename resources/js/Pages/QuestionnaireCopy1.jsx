// resources/js/Pages/BookingLookup.jsx
import React, { useState, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import CustomButton from '@/Components/CustomButton';
import CustomCheckboxQ from '@/Components/CustomCheckboxQ';
import CustomInputText from '@/Components/CustomInputText';
import CustomSelect from '@/Components/CustomSelect';
import CustomRadio from '@/Components/CustomRadioQ';
import SignaturePad from '@/Components/SignaturePad';

export default function BookingLookup({ auth, booking, bookingItem, leadGuides, safetyGuides, typeGroup, typeReferral, typeConfirmation }) {
  console.log("auth", auth);
  const mode = 'create';
  console.log("booking", booking);
  console.log("bookingItem", bookingItem);
  console.log("typeGroup", typeGroup);
  console.log("typeReferral", typeReferral);
  console.log("typeConfirmation", typeConfirmation);
  const [prevNext, setPrevNext] = useState(1);
  const { data, setData, post, put, errors, reset, processing } = useForm({
    booking_id: booking.id,
    booking_item_id: bookingItem.id,
    team_id: booking?.team_id,
    lead_guide_id: "",
    safety_guide_id: "",
    type_group_id: "",
    full_name: "",
    age: "",
    referral_id: "",
    continent_id: "",
    country_id: "",
    province_id: "",
    medical_id: "",
    medical_detail: "",
    is_fit: "",
    is_agree: "",
    email_address: "",
    signature: "",
  });
  const [continents, setContinents] = useState([]);
  const [countries, setCountries] = useState([]);
  const [states, setStates] = useState([]);

  const [selectedContinent, setSelectedContinent] = useState(null);
  const [selectedCountry, setSelectedCountry] = useState(null);

  // On mount
  useEffect(() => {
    axios.get('/continents').then(res => setContinents(res.data));
  }, []);

  // When continent changes
  useEffect(() => {
    if (selectedContinent) {
      //alert('selectedContinent');
      axios.get(`/countries/${selectedContinent}`).then(res => setCountries(res.data));
    }
  }, [selectedContinent]);

  // When country changes
  useEffect(() => {
    if (selectedCountry) {
      axios.get(`/states/${selectedCountry}`).then(res => setStates(res.data));
    }
  }, [selectedCountry]);

  const handlePrevNext = (number) => {
    console.log('number', number);
    setPrevNext(number);
    console.log('data', data);
  };
  const handleChange = (target, value) => {

    //setData("name", e.target.value);
    if (target == "continent_id") {
      setSelectedContinent(value);
    }
    if (target == "country_id") {
      setSelectedCountry(value);
    }
    // if(target == "referral_id"){
    //     setSelectedReferral(value);
    // }
    setData(target, value);
    console.log('data', data);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    console.log('booking', booking);
    console.log('data', data);
    post(route('booking-questionnaire.store'));
    //put(route('admin.checkins.store',{booking}));
  };

  return (
    <>
      <Head title="Find Your Booking" />
      <div className="flex flex-col  h-screen items-center justify-center bg-secondary-100">
        <div className="flex items-center justify-center w-full sm:pb-[51px] ">
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
        <div className="flex flex-col bg-white h-[80%] w-full sm:w-[50%] items-center shadow-lg rounded-[20px]">
          {/* select lead guide */}
          {prevNext == 1 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Select your Lead Guide
                </span>
                {leadGuides?.map((staff, i) => (
                  <CustomRadio
                    key={staff.id}
                    label={staff.name}
                    name="lead_guide_id"
                    value={staff.id}
                    checked={data.lead_guide_id == staff.id.toString()}
                    onChange={handleChange}
                    errors={errors}
                  />
                ))}
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(2)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* select safety guide */}
          {prevNext == 2 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Select your Safety Guide
                </span>
                {safetyGuides?.map((staff, i) => (
                  <CustomRadio
                    key={staff.id}
                    label={staff.name}
                    name="safety_guide_id"
                    value={staff.id}
                    checked={data.safety_guide_id == staff.id.toString()}
                    onChange={handleChange}
                    errors={errors}
                  />
                ))}
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(1)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(3)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* select group */}
          {prevNext == 3 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  What kind of a group are you with?
                </span>
                <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  {typeGroup?.map((group, i) => (
                    <CustomRadio
                      key={group.id}
                      label={group.name}
                      name="type_group_id"
                      value={group.id}
                      checked={data.type_group_id == group.id.toString()}
                      onChange={handleChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(2)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(4)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* enter name */}
          {prevNext == 4 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Please enter your First and Last name
                </span>
                <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  <CustomInputText
                    name="full_name"
                    value={data.full_name}
                    type="text"
                    mode={mode}
                    onChange={(e) => setData(e.target.name, e.target.value)}
                    errors={errors}
                  />
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(3)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(5)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* enter date of birth */}
          {prevNext == 5 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Please enter your Age
                </span>
                <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  <CustomInputText
                    name="age"
                    value={data.age}
                    type="text"
                    mode={mode}
                    onChange={(e) => setData(e.target.name, e.target.value)}
                    errors={errors}
                  />
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(4)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(6)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* referral */}
          {prevNext == 6 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2 " >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Where did you hear about us
                </span>
                <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  {typeReferral?.map((referral, i) => (
                    <CustomRadio
                      key={referral.id}
                      label={referral.name}
                      name="referral_id"
                      value={referral.id}
                      checked={data.referral_id == referral.id.toString()}
                      onChange={handleChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(5)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(7)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* Continent */}
          {prevNext == 7 && (
            <div className="flex flex-col w-full sm:w-auto h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2 " >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Where are you from? Select your Continent
                </span>
                <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  {continents?.map((continent, i) => (
                    <CustomRadio
                      key={continent.id}
                      label={continent.name}
                      name="continent_id"
                      value={continent.id}
                      checked={data.continent_id == continent.id.toString()}
                      onChange={handleChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(6)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' onClick={() => handlePrevNext(8)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* Country */}
          {prevNext == 8 && (
            <div className="flex flex-col w-full sm:w-auto h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Country of Residence
                </span>
                <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  {countries?.map((country, i) => (
                    <CustomRadio
                      key={country.id}
                      label={country.name}
                      name="country_id"
                      value={country.id}
                      checked={data.country_id == country.id.toString()}
                      onChange={handleChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(7)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' onClick={() => handlePrevNext(9)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* Province */}
          {prevNext == 9 && (
            <div className="flex flex-col w-full sm:w-auto h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2  " >
                <span className="text-welcome-card-title text-center sm:w-full sm:px-[39px] mt-[42px] mb-[26px] ">
                  Select Province
                </span>
                <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  {states?.map((state, i) => (
                    <CustomRadio
                      key={state.id}
                      label={state.name}
                      name="province_id"
                      value={state.id}
                      checked={data.province_id == state.id.toString()}
                      onChange={handleChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(8)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' onClick={() => handlePrevNext(10)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* Medical */}
          {prevNext == 10 && (
            <div className="flex flex-col w-full sm:w-auto h-full mb-[26px]">

              <div className="flex flex-col w-full h-[90%] gap-2  ">
                <span className="text-welcome-card-title text-center sm:w-full px-7 sm:px-[39px] mt-[42px] mb-[26px]">
                  Dou you have any medical conditions we should be aware of?
                </span>

                <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  {typeConfirmation?.map((medical, i) => (
                    <CustomRadio
                      key={medical.id}
                      label={medical.name}
                      name="medical_id"
                      value={medical.id}
                      checked={data.medical_id == medical.id.toString()}
                      onChange={handleChange}
                      errors={errors}
                    />
                  ))}
                </div>
                <div className="h-auto w-[90%] sm:w-[672px] pl-7 sm:pl-0 overflow-x-auto">
                  <CustomInputText
                    title="Please let us know what medical conditions you have and confirm that your doctor 
                        has agreed that you may partake in this activity?"
                    name="medical_detail"
                    value={data.medical_detail}
                    height="h-[52px]"
                    type="text"
                    mode={mode}
                    onChange={(e) => setData(e.target.name, e.target.value)}
                  />
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(9)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' onClick={() => handlePrevNext(11)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* Fit */}
          {prevNext == 11 && (
            <div className="flex flex-col w-full sm:w-auto h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2  " >
                <span className="text-welcome-card-title text-center sm:w-full sm:px-[39px] mt-[42px] mb-[26px]">
                  I am fit enough to walk 1km in steep terrain
                </span>
                <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  {typeConfirmation?.map((fit, i) => (
                    <CustomRadio
                      key={fit.id}
                      label={fit.name}
                      name="is_fit"
                      value={fit.id}
                      checked={data.is_fit == fit.id.toString()}
                      onChange={handleChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(10)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' onClick={() => handlePrevNext(12)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>

            </div>
          )}
          {/* Agree */}
          {prevNext == 12 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2 overflow-x-auto" >
                <span className="text-welcome-card-title text-center w-[90%] sm:w-full mt-[42px] mb-[8px] px-6 sm:px-[39px]">
                  Please agree to the following terms and conditions
                </span>
                <span className="text-welcome-card-title text-center w-[90%] sm:w-full mb-[8px] px-6 sm:px-[39px]">
                  By accessing and using this website, you acknowledge and agree to the terms outlined in this Indemnity Agreement. This form serves to protect the website owners, administrators, and affiliated parties from any liability arising from the use of the site, its content, or any services provided. By proceeding, you confirm that you understand and accept full responsibility for any risks associated with your use of this website and waive any claims against its owners for potential losses, damages, or liabilities.
                </span>
                <div className="w-[90%] sm:w-full mb-[8px] px-6 sm:px-[39px]">
                  <CustomCheckboxQ
                    label="I agree to the terms and conditions"
                    name="is_agree"
                    value={data.is_agree}
                    checked={data.is_agree}
                    onChange={handleChange}
                    errors={errors}
                  />
                </div>

              </div>

              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(11)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' onClick={() => handlePrevNext(13)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>


            </div>
          )}
          {/* Email */}
          {prevNext == 13 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Please enter your email address
                </span>
                <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  <CustomInputText
                    name="email_address"
                    value={data.email_address}
                    type="text"
                    mode={mode}
                    onChange={(e) => setData(e.target.name, e.target.value)}
                    errors={errors}
                  />
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(12)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(14)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* signature */}
          {prevNext == 14 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Please sign below
                </span>
                <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  <SignaturePad
                    value={data.signature}
                    onChange={(value) => setData('signature', value)}
                  />
                  {errors.signature && <div className="text-red-500 text-sm">{errors.signature}</div>}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(13)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(15)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* submit */}
          {prevNext == 15 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] px-6 sm:px-[39px]">
                  Your forms are ready to submit
                </span>
                <span className=" text-center min-w-[90%] mb-[26px]">
                  <span className='block' ><span className='text-booking-heading capitalize'>booking reference</span> {booking?.reference}</span>
                  <span className='block'><span className='text-booking-subheading capitalize'>Service</span> {bookingItem?.service?.name}</span>
                </span>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 h-auto sm:w-full pl-7 sm:pl-0 overflow-x-auto mb-2">

                  <span className='flex items-center gap-2'><span className='text-booking-subheading capitalize'>lead guide</span> {leadGuides?.find(g => g.id == data?.lead_guide_id)?.name}</span>
                  <span className='flex items-center gap-2'><span className='text-booking-subheading capitalize'>safety guide</span> {safetyGuides?.find(g => g.id == data?.safety_guide_id)?.name}</span>
                  <span className='flex items-center gap-2'><span className='text-booking-subheading capitalize'>type group</span> {typeGroup?.find(g => g.id == data?.type_group_id)?.name}</span>
                  <span className='flex items-center gap-2'><span className='text-booking-subheading capitalize'>full name</span> {data?.full_name}</span>
                  <span className='flex items-center gap-2'><span className='text-booking-subheading capitalize'>age</span> {data?.age}</span>
                  <span className='flex items-center gap-2'><span className='text-booking-subheading capitalize'>referral</span> {typeReferral?.find(r => r.id == data?.referral_id)?.name}</span>
                  <span className='flex items-center gap-2'><span className='text-booking-subheading capitalize'>continent</span> {continents?.find(c => c.id == data?.continent_id)?.name}</span>
                  <span className='flex items-center gap-2'><span className='text-booking-subheading capitalize'>country</span> {countries?.find(c => c.id == data?.country_id)?.name}</span>
                  <span className='flex items-center gap-2'><span className='text-booking-subheading capitalize'>province</span> {states?.find(s => s.id == data?.province_id)?.name}</span>
                  <span className='flex items-center gap-2'><span className='text-booking-subheading capitalize'>any medical condition</span> {data?.medical_id ? "yes" : "no"}</span>
                  <span className='flex items-center gap-2'><span className='text-booking-subheading capitalize'>medical condition detail</span> {data?.medical_detail}</span>
                  <span className='flex items-center gap-2'><span className='text-booking-subheading capitalize'>fit to walk</span> {data?.is_fit ? "yes" : "no"}</span>
                  <span className='flex items-center gap-2'><span className='text-booking-subheading capitalize'>agree to terms</span> {data?.is_agree ? "yes" : "no"}</span>
                  <span className='flex items-center gap-2'><span className='text-booking-subheading capitalize'>email_address</span> {data?.email_address}</span>
                  <span className='flex items-center gap-2'>
                    <img src={data?.signature} alt="Signature preview" className="border w-64 h-auto" />
                  </span>
                </div>

              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(14)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button
                  type='button'
                  onClick={(e) => handleSubmit(e)}
                  disabled={processing}

                  className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Submit</span>
                </button>
              </div>
            </div>
          )}
          <div>


          </div>


        </div>
      </div>
    </>
  );
}
{/* <div> 
              <span className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                Where are you from? Select your Continent
              </span>
                <select onChange={e => setSelectedContinent(e.target.value)}>
                <option value="">Select Continent</option>
                {continents.map(c => (
                    <option key={c.id} value={c.id}>{c.name}</option>
                ))}
                </select>
</div>

<div> 
              <span className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                Select your Country
              </span>
                <select onChange={e => setSelectedCountry(e.target.value)}>
                <option value="">Select Country</option>
                {countries.map(c => (
                    <option key={c.id} value={c.id}>{c.name}</option>
                ))}
                </select>
</div>


<div> 
              <span className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                Select your province
              </span>
                <select>
                <option value="">Select State/Province</option>
                {states.map(s => (
                    <option key={s.id} value={s.id}>{s.name}</option>
                ))}
                </select>
</div>                 */}