// resources/js/Pages/BookingLookup.jsx
import React, { useState, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import CustomButton from '@/Components/CustomButton';
import CustomCheckboxQ from '@/Components/CustomCheckboxQ';
import CustomInputText from '@/Components/CustomInputText';
import CustomSelect from '@/Components/CustomSelect';
import CustomRadio from '@/Components/CustomRadioQ';
import SignaturePad from '@/Components/SignaturePad';

export default function BookingLookup({ auth, booking, bookingItem, leadGuides, safetyGuides, typeGroup, typeReferral, typeConfirmation, questionnaire, team }) {
  // console.log("auth", auth);
  const mode = 'create';
  console.log("booking", booking);
  console.log("bookingItem", bookingItem);
  // console.log("typeGroup", typeGroup);
  // console.log("typeReferral", typeReferral);
  // console.log("typeConfirmation", typeConfirmation);
  const [step, setStep] = useState(0);

  const { data, setData, post, put, errors, reset, processing } = useForm({
    booking_id: null,
    booking_item_id: null,
    team_id: null,
    lead_guide_id: null,
    safety_guide_id: null,
    type_group_id: null,
    full_name: '',
    age: '',
    referral_id: null,
    continent_id: null,
    country_id: null,
    province_id: null,
    medical_id: null,
    medical_detail: "",
    is_fit: "",
    is_agree: "",
    email_address: '',
    signature: "",
  });

  useEffect(() => {
    console.log('bookingItem', bookingItem);
    console.log('booking', booking);
    if (booking && bookingItem && bookingItem.customers) {
      setData({
        booking_id: booking.id,
        booking_item_id: bookingItem.id,
        team_id: booking?.team_id,
        lead_guide_id: "",
        safety_guide_id: "",
        type_group_id: "",
        full_name: bookingItem.customers[0].full_name,
        age: bookingItem.customers[0].age,
        referral_id: "",
        continent_id: "",
        country_id: "",
        province_id: "",
        medical_id: "",
        medical_detail: "",
        is_fit: "",
        is_agree: "",
        email_address: bookingItem.customers[0].email_address,
        signature: "",
      });
    }
    console.log('data------', data);
  }, [booking, bookingItem]);



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
      // alert('selectedContinent');
      axios.get(`/countries/${selectedContinent}`).then(res => setCountries(res.data));
    }
  }, [selectedContinent]);

  // When country changes
  useEffect(() => {
    if (selectedCountry) {
      axios.get(`/states/${selectedCountry}`).then(res => setStates(res.data));
    }
  }, [selectedCountry]);

  const handleChange = (name, value) => {

    //setData("name", e.target.value);
    if (name == "continent_id") {
      setSelectedContinent(value);
    }
    if (name == "country_id") {
      setSelectedCountry(value);
    }
    // if(name == "referral_id"){
    //     setSelectedReferral(value);
    // }
    setData(name, value);
    console.log('data', data);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    // console.log('booking', booking);
    // console.log('data', data);
    // post(route('booking-questionnaire.store'))

    post(route("booking-questionnaire.store"), {
      onSuccess: (data) => { console.log(data); window.location.href = route('admin.bookings.index'); },
      onError: (err) => { console.log(err); }
      // ,data: {
      //     booking_id: data.booking_id,
      //     service_id: data.service_id,
      //     season_id: data.season_id,
      //     booking_date: data.booking_date,
      //     inventory_time_slot_id: data.inventory_time_slot_id,
      // },
    });

    //put(route('admin.checkins.store',{booking}));
  };
  const questions = questionnaire; // your flattened array

  const currentQuestion = questions[step];


  const renderQuestion = (q) => {
    //console.log('q', q);
    switch (q.type_input_id) {
      case 1: // text
        return (
          <div className="flex flex-col w-[90%] items-center">
            <CustomInputText
              name={q.slug}
              value={data[q.slug] || ""}
              type="text"
              mode="create"
              onChange={(e) => handleChange(e.target.name, e.target.value)}
              errors={errors}
            />
            <span className="w-[90%] text-md mt-2 text-gray-500">{q.description}</span>
            <span className="w-[90%] text-xs mt-2 text-red-500">{errors[q.slug]}</span>
          </div>
        );
      // case 2: // textarea
      //   return (
      //      <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
      //         <CustomInputText
      //           name={q.slug}
      //           value={data[q.slug] || ""}
      //           type="text"
      //           mode="create"
      //           onChange={(e) => handleChange(e.target.name, e.target.value)}
      //           errors={errors}
      //         />            
      //      </div>
      //   );
      case 3: // number
        return (
          <div className="flex flex-col w-[90%] items-center">
            <CustomInputText
              name={q.slug}
              value={data[q.slug] || ""}
              type="number"
              mode="create"
              onChange={(e) => handleChange(e.target.name, e.target.value)}
              errors={errors}
            />
            <span className="w-[90%] text-md mt-2 text-gray-500">{q.description}</span>
            <span className="w-[90%] text-xs mt-2 text-red-500">{errors[q.slug]}</span>
          </div>
        );

      case 7: // checkbox / yes-no
        return (
          <div className="flex flex-col w-full items-center">
            <div>7</div>
            <CustomCheckboxQ
              label={q.question}
              name={q.slug}
              checked={!!data[q.slug]}
              onChange={(e) => handleChange(q.slug, e.target.checked)}
              errors={errors}
            />
            <span className="w-[90%] text-md mt-2 text-gray-500">{q.description}</span>
            <span className="w-[90%] text-xs mt-2 text-red-500">{errors[q.slug]}</span>
          </div>
        );
      case 8: // select / radio
        return (
          <div className="flex flex-col w-full items-center">
            <div>8</div>
            <CustomRadio
              label={q.question}
              name={q.slug}
              value={data[q.slug]}
              onChange={(name, value) => handleChange(name, value)}
              errors={errors}
            />
            <span className="w-[90%] text-md mt-2 text-gray-500">{q.description}</span>
            <span className="w-[90%] text-xs mt-2 text-red-500">{errors[q.slug]}</span>
          </div>
        );
      case 10: // signature
        return (
          <div className="flex flex-col w-full items-center">
            <SignaturePad
              value={data[q.slug]}
              onChange={(value) => handleChange(q.slug, value)}
            />
            <span className="w-[90%] text-md mt-2 text-gray-500">{q.description}</span>
            <span className="w-[90%] text-xs mt-2 text-red-500">{errors[q.slug]}</span>
          </div>

        );
      case 11:
        return (
          <div className="flex flex-col w-full items-center">

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
            <span className="w-[90%] text-md mt-2 text-gray-500">{q.description}</span>
            <span className="w-[90%] text-xs mt-2 text-red-500">{errors[q.slug]}</span>
          </div>
        );
      case 12:
        return (
          <div className="flex flex-col w-full items-center">
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
            <span className="w-[90%] text-md mt-2 text-gray-500">{q.description}</span>
            <span className="w-[90%] text-xs mt-2 text-red-500">{errors[q.slug]}</span>
          </div>
        );
      case 13:
        return (
          <div className="flex flex-col w-full  items-center">
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
            <span className="w-[90%] text-md mt-2 text-gray-500">{q.description}</span>
            <span className="w-[90%] text-xs mt-2 text-red-500">{errors[q.slug]}</span>
          </div>
        );
      case 14:
        return (
          <div className="flex flex-col w-full items-center">
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
            <span className="w-[90%] text-md mt-2 text-gray-500">{q.description}</span>
            <span className="w-[90%] text-xs mt-2 text-red-500">{errors[q.slug]}</span>
          </div>
        );
      case 15:
        return (
          <div className="flex flex-col w-full items-center">
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
            <span className="w-[90%] text-md mt-2 text-gray-500">{q.description}</span>
            <span className="w-[90%] text-xs mt-2 text-red-500">{errors[q.slug]}</span>
          </div>
        );
      case 16:
        return (
          <div className="flex flex-col w-full items-center">
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
            <span className="w-[90%] text-md mt-2 text-gray-500">{q.description}</span>
            <span className="w-[90%] text-xs mt-2 text-red-500">{errors[q.slug]}</span>
          </div>
        );
      case 17:
        return (
          <div className="flex flex-col w-full items-center">
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
            <span className="w-[90%] text-md mt-2 text-gray-500">{q.description}</span>
            <span className="w-[90%] text-xs mt-2 text-red-500">{errors[q.slug]}</span>
          </div>
        );
      case 18:
        return (
          <div className="flex flex-col w-full items-center">
            {typeConfirmation?.map((conf, i) => (
              // <CustomRadio
              //   key={medical.id}
              //   label={medical.name}
              //   name="medical_id"
              //   value={medical.id}
              //   checked={data.medical_id == medical.id.toString()}
              //   onChange={handleChange}
              //   errors={errors}
              // />
              <CustomRadio
                key={conf.id}
                label={conf.name}
                name={q.slug}
                value={conf.id}
                checked={data[q.slug] == conf.id.toString()}
                onChange={handleChange}
                errors={errors}
              />
            ))}
            <span className="w-[90%] text-md mt-2 text-gray-500">{q.description}</span>
            <span className="w-[90%] text-xs mt-2 text-red-500">{errors[q.slug]}</span>
          </div>
        );
      default:
        return <p>none {q.type_input_id} {q.question}</p>;
    }
  };

  return (
    <>
      <Head title="Find Your Booking" />
      <div className="flex flex-col  h-screen items-center justify-center bg-secondary-100">
        <div className="flex items-center justify-center w-full sm:pb-[51px] ">
          {/* Logo Box */}
          <div className="flex items-center justify-center w-[118px] h-[106px] ">
            {team?.file_logo?.path != null ? (
              <img
                src={`/storage/${team?.file_logo?.path}`}
                alt="Canopy Tours"
                className="w-full h-full object-contain min-w-[100px] min-h-[90px]"
              />
            ) : (
              <img
                src="/images/logo.png"
                alt="Canopy Tours"
                className="w-full h-full object-contain min-w-[100px] min-h-[90px]"
              />
            )}
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
          <span className="text-welcome-card-title text-center mt-6 mb-4">
            {currentQuestion.question}
          </span>

          <div className="flex justify-center min-h-[500px] w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
            {renderQuestion(currentQuestion)}
          </div>

          <div className="flex items-center justify-between my-6 gap-2 ">
            {step > 0 && (
              <button
                type="button"
                onClick={() => setStep(step - 1)}
                className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer"
              >
                <span className="text-btn-outline-gray">Prev</span>
              </button>
            )}
            {step < questions.length - 1 ? (
              <button
                type="button"
                onClick={() => setStep(step + 1)}
                className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer"
              >
                <span className="text-btn-outline-gray">Next</span>
              </button>
            ) : (
              <button
                type="button"
                onClick={handleSubmit}
                disabled={processing}
                className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer"
              >
                <span className="text-btn-outline-gray">Submit</span>
              </button>
            )}
          </div>
        </div>
      </div>
    </>
  );
}