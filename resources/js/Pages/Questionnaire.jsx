// resources/js/Pages/BookingLookup.jsx
import React, { useState, useEffect } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import CustomButton from '@/Components/CustomButton';
import CustomCheckboxQ from '@/Components/CustomCheckboxQ';
import CustomInputText from '@/Components/CustomInputText';
import CustomSelect from '@/Components/CustomSelect';
import CustomRadio from '@/Components/CustomRadio';
import SignaturePad from '@/Components/SignaturePad';
import InputText from '@/Components/InputText';
import CustomCheckbox from '@/Components/CustomCheckbox';
import GreenButton from '@/Components/GreenButton';
import axios from 'axios';

export default function BookingLookup({ auth, booking, bookings, bookingItem, questions, leadGuides, safetyGuides, typeGroups, ageGroups, typeGender, typeReferral, typeConfirmation }) {
  // console.log("auth", auth);
  const mode = 'edit';
  // console.log("booking", booking);
  console.log("bookings", bookings);
  console.log("questions", questions);
  // console.log("bookingItem", bookingItem);
  // console.log("typeGroups", typeGroups);
  // console.log("typeReferral", typeReferral);
  // console.log("typeConfirmation", typeConfirmation);
  const [prevNext, setPrevNext] = useState(1);
  const [continents, setContinents] = useState([]);
  const [countries, setCountries] = useState([]);
  const [states, setStates] = useState([]);
  const [selectedContinent, setSelectedContinent] = useState(null);
  const [selectedCountry, setSelectedCountry] = useState(null);
  const [selectedState, setSelectedState] = useState(null);
  const [adults, setAdults] = useState([]);
  const [selected, setSelected] = useState(null);
  const activeSteps = questions
    .filter(q => q.enabled == 1)
    .sort((a, b) => a.sort_order - b.sort_order)
    .map(q => q.slug);
  console.log('activeSteps', activeSteps);
  const [currentSlug, setCurrentSlug] = useState(activeSteps[0]);

  const { data, setData, post, put, errors, reset, processing } = useForm({
    customer_manifests: [],
    // booking_id: booking.id,
    // booking_item_id: bookingItem.id,
    // team_id: booking?.team_id,
    // lead_guide_id: "",
    // safety_guide_id: "",
    // type_group_id: "",
    // full_name: "",
    // age_id: "",
    // referral_id: "",
    // continent_id: "",
    // country_id: "",
    // province_id: "",
    // medical_id: "",
    // medical_detail: "",
    // is_fit: false,
    // is_agree: false,
    // email_address: "",
    // signature: "",
    // gender: "",
    // visitor_type: "",
    // marketing_opt_in: null,
    // birthday_month: "",
    // birthday_day: "",
    // pregnant: null,
    // guardian_name: "",
    // lunch_option: "",
  });

  useEffect(() => {
    if (mode === "edit" && bookings?.customers?.length) {
      // Map API data into form shape
      const mapped = bookings.customers.map((item, i) => {
        const { customer, manifest, questionnaire } = item;

        // Convert questionnaire array → { slug: value }
        const qAnswers = questionnaire.reduce((acc, q) => {
          acc[q.slug] = q.answer ?? "";
          return acc;
        }, {});

        return {
          id: i,

          // 1️⃣ Base customer DB fields
          ...customer,

          // 2️⃣ Merge manifest (position, notes, etc.)
          ...manifest,

          // 3️⃣ Inject questionnaire answers dynamically
          ...qAnswers,
        };
      });
      console.log("mapped", mapped);
      const adults = mapped?.filter(p => p.type === "adult");
      setAdults(adults);
      setData('customer_manifests', mapped);
      // setSelected(1);
      console.log('--------------init data-------------', mapped);
    } else if (mode === "create") {
      reset(); // Clear form
    }
  }, [mode, bookings?.customers]);

  useEffect(() => {
    if (data.customer_manifests && data.customer_manifests.length > 0) {
      console.log('updated Adults', data.customer_manifests);
      const updatedAdults = data.customer_manifests
        .filter(p => p.type === "adult" && p.full_name != "")
        .map((p, i) => {
          console.log("p", p);
          return ({
            id: typeof p.customer_id == 'number' ? p.customer_id : i + 1,
            name: p.full_name || "Unnamed Adult"
          })
        });
      console.log("updatedAdults", updatedAdults);
      setAdults(updatedAdults);
    }

  }, [data.customer_manifests]);

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
    if (selectedCountry == "204") {
      axios.get(`/states/${selectedCountry}`).then(res => setStates(res.data));
    }
  }, [selectedCountry]);

  const handlePrevNext = (number, index) => {
    console.log('number', number);
    console.log('index', index);
    console.log('bookings', bookings);
    console.log('handle Prev Next ', data.customer_manifests);
    if (number == 1) {
      // setSelected(null);
      window.location.reload();
    }
    else if (number == 3) {
      setPrevNext(number);
      setSelectedContinent(null);
      setSelectedCountry(null);
      setSelectedState(null);
      setCountries([]);
      setStates([]);
    }
    else {
      if (data.customer_manifests[index].full_name == "") {
        alert("Please enter a name for this person");
        return;
      }
      // data[index].index = 1;
      setSelected(index);
      setPrevNext(number);
      const updated = [...data.customer_manifests];
      updated[index]['index'] = 1;
      setData("customer_manifests", updated);

      // axios.put(route("front.customer-manifests.edit", bookings?.customers[0].booking_item_id), {
      //   data: data,
      // })
      //   .then((response) => {
      //     console.log("Team changed successfully", response.data);

      //   })
      //   .catch((error) => {
      //     console.error("Error changing team", error);
      //   })
      //   .finally(() => {
      //     setSubmitting(false);
      //   });
    }
  };
  const isEnabled = (slug) => {
    if (!questions || !questions.length) return false;

    const q = questions.find(q => q.slug === slug);
    return q ? q.enabled == 1 : false;
  };
  const nextStep = () => {
    const idx = activeSteps.indexOf(currentSlug);
    if (idx < activeSteps.length - 1) {
      setCurrentSlug(activeSteps[idx + 1]);
    }
  };
  const prevStep = () => {
    const idx = activeSteps.indexOf(currentSlug);
    if (idx > 0) {
      setCurrentSlug(activeSteps[idx - 1]);
    }
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

  const handlePersonChange = (index, field, value) => {
    const updated = [...data.customer_manifests];
    updated[index][field] = value;
    setData("customer_manifests", updated);
    console.log('-------handle Person Change--------', updated);
  };

  const handleIndemnityChange = (field, value) => {
    if (selected != null) {
      const updated = [...data.customer_manifests];
      updated[selected][field] = value;
      setData("customer_manifests", updated);

      if (field == "continent_id") {
        setSelectedContinent(value);
      }
      if (field == "country_id") {
        setSelectedCountry(value);
      }
      if (field == "province_id") {
        setSelectedState(value);
      }


      console.log('-------handle Indemnity Change--------', updated);
    }
  };


  const handleSubmit = (e) => {
    e.preventDefault();
    console.log('booking', booking);
    console.log('handle Submit', data.customer_manifests);
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
          <div className="flex items-center pl-2 gap-2">
            <img
              src="/images/text.png"
              alt="Canopy Tours Text"
              className="w-24 h-24 md:w-[118px] md:h-[106px] object-contain"
            />
            <button onClick={() => router.get(route('booking-questionnaire.index'))} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-red-gradient-overlay border-[#C65468] text-white shadow-inset-white cursor-pointer">
              Close
            </button>
          </div>


        </div>
        <div className="flex flex-col bg-white h-[80%] w-full sm:w-[50%] items-center shadow-lg rounded-[20px]">

          {prevNext == 1 && (
            <div className="flex w-[90%] flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Select or add your Name
                </span>
                <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  {data?.customer_manifests?.map((person, index) => {
                    // console.log("person", person);
                    return (
                      <div key={index} className="flex w-full justify-between border p-3 mb-4 rounded-lg">
                        <div className="flex w-full flex-col justify-between items-center mb-2 ">
                          <h3 className="font-semibold">
                            {person?.type?.toUpperCase()} #{index + 1} {person.is_lead ? "(Lead)" : ""} {person.discount ? "(Discount) " + person.discount : ""}
                          </h3>
                          {/* <button
                                    type="button"
                                    onClick={() => handleRemovePerson(index)}
                                    className="text-red-500 hover:underline"
                                >
                                    Remove
                                </button> */}
                          <InputText
                            title="Full Name"
                            field="full_name"
                            value={person.full_name}
                            mode={mode}
                            handleChange={(field, value) => handlePersonChange(index, field, value)}
                            errors={errors}
                          />
                          {person.type === "child" && (
                            <div className="w-full mb-4">
                              <label
                                className="text-[#161618] font-poppins text-[12px] font-normal  leading-[130%]"
                              >
                                Parent
                              </label>
                              <select
                                id={parent}
                                name={parent}
                                value={person.parent_id || ""}
                                onChange={(e) => {
                                  const updated = data?.customer_manifests?.map((p, idx) =>
                                    idx === index ? { ...p, parent_id: e.target.value } : p
                                  );
                                  setData("customer_manifests", updated);
                                }}
                                className={`mt-1 block shadow-sm input-text rounded-[10px] border-gray-300
                                         hover:border-black  active:border-blue-600 w-full
                                            focus:outline-none focus:ring-0 focus:border-black
                                    `}
                                disabled={mode === "view"}

                              >
                                {adults?.map(adult => {
                                  // console.log("adult", adult);
                                  return (
                                    <option className="input-text rounded-[10px] border-gray-300" key={adult.id} value={adult.id}>
                                      {adult.name}
                                    </option>
                                  )
                                })}
                              </select>
                            </div>
                          )}
                          <div className="flex items-center justify-center gap-6">
                            {person.is_indemnity == 1 ? (
                              <button type='button' className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-red-gradient-overlay border-red-500 text-white shadow-inset-white cursor-pointer">
                                <span className="text-btn-outline-gray">Complete</span>
                              </button>
                            ) : (
                              <button type='button' onClick={() => handlePrevNext(2, index)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                                <span className="text-btn-outline-gray">Next</span>
                              </button>
                            )}

                          </div>
                        </div>
                      </div>
                    )
                  })}
                </div>
              </div>
            </div>
          )}



          {/* select group */}

          {/* select age group */}
          {prevNext == 2 && isEnabled('age_id') && (
            <div className="flex flex-col w-[70%] sm:w-full h-full mb-[26px]">
              <div className="flex flex-col w-[70%] sm:w-full h-[90%] gap-2">
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Please select your Age Group
                </span>
                <div className="h-auto w-[70%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  {ageGroups?.map((age, i) => (
                    <CustomRadio
                      key={age.id}
                      label={age.name}
                      name="age_id"
                      value={age.id}
                      checked={data.customer_manifests[selected].age_id == age.id.toString()}
                      onChange={handleIndemnityChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(1, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(3, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* Continent Country  Province*/}
          {prevNext == 3 && (
            <div className="flex flex-col w-[70%] sm:w-full h-full mb-[26px]">
              <div className="flex flex-col w-[70%] sm:w-full h-[90%] gap-2">
                {/* Continent */}
                {(continents.length > 0 && countries.length == 0) && (
                  <div className="flex flex-col w-[90%] sm:w-full h-[90%] gap-2 " >
                    <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                      Where are you from? Select your Continent
                    </span>
                    <div className="h-auto w-[70%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                      {continents?.map((continent, i) => (
                        <CustomRadio
                          key={continent.id}
                          label={continent.name}
                          name="continent_id"
                          value={continent.id}
                          checked={data.customer_manifests[selected].continent_id == continent.id.toString()}
                          onChange={handleIndemnityChange}
                          errors={errors}
                        />
                      ))}
                    </div>
                  </div>
                )}

                {/* Country */}
                {(countries.length > 0 && states.length == 0) && (
                  <div className="flex flex-col w-[90%] sm:w-full h-[90%] gap-2" >
                    <span className="text-welcome-card-title text-center w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                      Country of Residence
                    </span>
                    <div className="h-auto w-[70%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                      {countries?.map((country, i) => (
                        <CustomRadio
                          key={country.id}
                          label={country.name}
                          name="country_id"
                          value={country.id}
                          checked={data.customer_manifests[selected].country_id == country.id.toString()}
                          onChange={handleIndemnityChange}
                          errors={errors}
                        />
                      ))}
                    </div>
                  </div>
                )}

                {/* Province shows only if country == South Africa */}
                {(states.length > 0 && selectedCountry == "204") && (
                  <div className="flex flex-col w-full h-[90%] gap-2  " >
                    <span className="text-welcome-card-title text-center sm:w-full sm:px-[39px] mt-[42px] mb-[26px] ">
                      Select Province
                    </span>
                    <div className="h-auto w-[70%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                      {states?.map((state, i) => (
                        <CustomRadio
                          key={state.id}
                          label={state.name}
                          name="province_id"
                          value={state.id}
                          checked={data.customer_manifests[selected].province_id == state.id.toString()}
                          onChange={handleIndemnityChange}
                          errors={errors}
                        />
                      ))}
                    </div>
                  </div>
                )}
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(2, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' onClick={() => handlePrevNext(6, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}

          {/* Marketing source */}
          {prevNext == 6 && (
            <div className="flex flex-col w-[70%] sm:w-full h-full mb-[26px]">
              <div className="flex flex-col w-[70%] sm:w-full h-[90%] gap-2 " >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Where did you hear about us
                </span>
                <div className="h-auto w-[70%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  {typeReferral?.map((referral, i) => (
                    <CustomRadio
                      key={referral.id}
                      label={referral.name}
                      name="referral_id"
                      value={referral.id}
                      checked={data.customer_manifests[selected].referral_id == referral.id.toString()}
                      onChange={handleIndemnityChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(3, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(7, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* Visitor type (individual, couple, etc.) */}
          {prevNext == 7 && (
            <div className="flex flex-col w-[70%] sm:w-full h-full mb-[26px]">
              <div className="flex flex-col w-[70%] sm:w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  What kind of a group are you with?
                </span>
                <div className="h-autow-[70%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  {typeGroups?.map((group, i) => (
                    <CustomRadio
                      key={group.id}
                      label={group.name}
                      name="type_group_id"
                      value={group.id}
                      checked={data.customer_manifests[selected].type_group_id == group.id.toString()}
                      onChange={handleIndemnityChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(6, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(8, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* marketing opt-in */}
          {prevNext == 8 && (
            <div className="flex flex-col w-[70%] sm:w-full h-full mb-[26px]">

              <div className="flex flex-col w-[70%] sm:w-full h-[90%] gap-2  ">
                <span className="text-welcome-card-title text-center sm:w-full px-7 sm:px-[39px] mt-[42px] mb-[26px]">
                  Would you like us to email you a certificate and invite you for a free tour on your birthday?
                  (This also means you are happy to receive the occasional email from us regarding specials and promos on Canopy Tours around Southern Africa.)
                </span>

                <div className="h-auto w-[70%] sm:w-full pl-7 sm:pl-0 overflow-x-auto ">
                  {typeConfirmation?.map((marketing, i) => (
                    <CustomRadio
                      key={marketing.id}
                      label={marketing.name}
                      name="marketing_opt_in"
                      value={marketing.id}
                      checked={data.customer_manifests[selected].marketing_opt_in == marketing.id.toString()}
                      onChange={handleIndemnityChange}
                      errors={errors}
                    />
                  ))}
                </div>
                {data.customer_manifests[selected].marketing_opt_in == 1 && (
                  <div className="h-auto w-[70%] sm:w-full pl-7 sm:px-[39px] overflow-x-auto">
                    <CustomInputText
                      title="What email address should we use? "
                      name="email_address"
                      value={data.customer_manifests[selected].email_address}
                      height="h-[52px]"
                      type="text"
                      mode={mode}
                      onChange={(e) => handleIndemnityChange(e.target.name, e.target.value)}
                    />
                    <CustomInputText
                      title="Your birthday month?"
                      name="birthday_month"
                      value={data.customer_manifests[selected].birthday_month}
                      height="h-[52px]"
                      type="text"
                      mode={mode}
                      onChange={(e) => handleIndemnityChange(e.target.name, e.target.value)}
                    />
                    <CustomInputText
                      title="Your birthday day?"
                      name="birthday_day"
                      value={data.customer_manifests[selected].birthday_day}
                      height="h-[52px]"
                      type="text"
                      mode={mode}
                      onChange={(e) => handleIndemnityChange(e.target.name, e.target.value)}
                    />
                  </div>
                )}

              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(7, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' onClick={() => handlePrevNext(9, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}

          {/* Medical detail text only when YES */}
          {prevNext == 9 && (
            <div className="flex flex-col w-[70%] sm:w-full h-full mb-[26px]">

              <div className="flex flex-col w-[70%] sm:w-full h-[90%] gap-2  ">
                <span className="text-welcome-card-title text-center sm:w-full px-7 sm:px-[39px] mt-[42px] mb-[26px]">
                  Do you have any medical conditions we should be aware of?
                  Please let us know what medical conditions you have and confirm that your doctor
                  has agreed that you may partake in this activity?
                </span>

                <div className="h-auto w-[70%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  {typeConfirmation?.map((medical, i) => (
                    <CustomRadio
                      key={medical.id}
                      label={medical.name}
                      name="medical_id"
                      value={medical.id}
                      checked={data.customer_manifests[selected].medical_id == medical.id.toString()}
                      onChange={handleIndemnityChange}
                      errors={errors}
                    />
                  ))}
                </div>
                {data.customer_manifests[selected].medical_id == 1 && (

                  <div className="h-auto w-[70%] sm:w-full pl-7 sm:px-[39px] overflow-x-auto">
                    <CustomInputText
                      title="Please let us know what medical conditions you have and confirm that your doctor has agreed that you may partake in this activity?"
                      name="medical_detail"
                      value={data.customer_manifests[selected].medical_detail}
                      height="h-[52px]"
                      type="text"
                      mode={mode}
                      onChange={(e) => handleIndemnityChange(e.target.name, e.target.value)}
                    />
                  </div>
                )}
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(8, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' onClick={() => handlePrevNext(10, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* Gender */}
          {prevNext == 10 && (
            <div className="flex flex-col w-[70%] sm:w-full h-full mb-[26px]">
              <div className="flex flex-col w-[70%] sm:w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Gender?
                </span>
                <div className="h-auto w-[70%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  {typeGender?.map((gender, i) => (
                    <CustomRadio
                      key={gender.id}
                      label={gender.name}
                      name="gender"
                      value={gender.id}
                      checked={data.customer_manifests[selected].gender == gender.id.toString()}
                      onChange={handleIndemnityChange}
                      errors={errors}
                    />
                  ))}
                </div>
                {/* Pregnancy shows only if female */}
                {data.customer_manifests[selected].gender == "2" && (
                  <>
                    <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                      Pregnant?
                    </span>
                    <div className="h-auto w-[70%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                      {typeConfirmation?.map((pregnant, i) => (
                        <CustomRadio
                          key={pregnant.id}
                          label={pregnant.name}
                          name="pregnant"
                          value={pregnant.id}
                          checked={data.customer_manifests[selected].pregnant == pregnant.id.toString()}
                          onChange={handleIndemnityChange}
                          errors={errors}
                        />
                      ))}
                    </div>
                  </>
                )}

              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(9, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(11, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* Fit */}
          {prevNext == 11 && (
            <div className="flex flex-col w-[70%] sm:w-full h-full mb-[26px]">
              <div className="flex flex-col w-[70%] sm:w-full h-[90%] gap-2  " >
                <span className="text-welcome-card-title text-center sm:w-full sm:px-[39px] mt-[42px] mb-[26px]">
                  I am fit enough to walk 1km in steep terrain
                </span>
                <div className="h-auto w-[70%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  {typeConfirmation?.map((fit, i) => (
                    <CustomRadio
                      key={fit.id}
                      label={fit.name}
                      name="is_fit"
                      value={fit.id}
                      checked={data.customer_manifests[selected].is_fit == fit.id.toString()}
                      onChange={handleIndemnityChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(10, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' onClick={() => handlePrevNext(12, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>

            </div>
          )}
          {/* Agree */}
          {prevNext == 12 && (
            <div className="flex flex-col w-[70%] sm:w-full h-full mb-[26px]">
              <div className="flex flex-col w-[70%] sm:w-full h-[90%] gap-2 overflow-x-auto" >
                <span className="text-welcome-card-title text-center w-[90%] sm:w-full mt-[42px] mb-[8px] px-6 sm:px-[39px]">
                  Please agree to the following terms and conditions
                </span>
                <span className="text-welcome-card-title text-center w-[90%] sm:w-full mb-[8px] px-6 sm:px-[39px]">
                  By accessing and using this website, you acknowledge and agree to the terms outlined in this Indemnity Agreement. This form serves to protect the website owners, administrators, and affiliated parties from any liability arising from the use of the site, its content, or any services provided. By proceeding, you confirm that you understand and accept full responsibility for any risks associated with your use of this website and waive any claims against its owners for potential losses, damages, or liabilities.
                </span>
                <div className="w-[70%] sm:w-full mb-[8px] px-6 sm:px-[39px]">
                  <CustomCheckboxQ
                    label="I agree to the terms and conditions"
                    name="is_agree"
                    value={data.is_agree}
                    checked={data.customer_manifests[selected].is_agree}
                    onChange={handleIndemnityChange}
                    errors={errors}
                  />
                </div>

              </div>

              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(11, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' onClick={() => handlePrevNext(13, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* Lunch order (optional) */}
          {/* Full indemnity legal text from CTSA */}
          {/* Guardian shows only if age < 18 */}

          {/* signature */}
          {prevNext == 13 && (
            <div className="flex flex-col w-[70%] sm:w-full h-full mb-[26px]">
              <div className="flex flex-col w-[70%] sm:w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Please sign below
                </span>
                <div className="h-auto w-[70%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  <SignaturePad
                    value={data.customer_manifests[selected].signature}
                    onChange={(value) => handleIndemnityChange('signature', value)}
                  />
                  {errors.signature && <div className="text-red-500 text-sm">{errors.signature}</div>}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(12, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(14, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* submit */}
          {prevNext == 14 && (
            <div className="flex flex-col w-[70%] sm:w-full h-full mb-[26px]">
              <div className="flex flex-col w-[70%] sm:w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] px-6 sm:px-[39px]">
                  Your Indemnity is ready to submit
                </span>
                <span className=" text-center min-w-[90%] mb-[26px]">
                  <span className='block' ><span className='text-booking-heading capitalize'>booking reference</span> {booking?.reference}</span>
                  <span className='block'><span className='text-booking-subheading capitalize'>Service</span> {bookingItem?.service?.name}</span>
                </span>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 h-auto pl-7 sm:px-[12px] overflow-x-auto mb-2">

                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>lead guide</span> {leadGuides?.find(g => g.id == data.customer_manifests[selected]?.lead_guide_id)?.name}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>safety guide</span> {safetyGuides?.find(g => g.id == data.customer_manifests[selected]?.safety_guide_id)?.name}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>type group</span> {typeGroups?.find(g => g.id == data.customer_manifests[selected]?.type_group_id)?.name}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>full name</span> {data.customer_manifests[selected]?.full_name}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>age</span> {ageGroups?.find(g => g.id == data.customer_manifests[selected]?.age_id)?.name}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>referral</span> {typeReferral?.find(r => r.id == data.customer_manifests[selected]?.referral_id)?.name}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>continent</span> {continents?.find(c => c.id == data.customer_manifests[selected]?.continent_id)?.name}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>country</span> {countries?.find(c => c.id == data.customer_manifests[selected]?.country_id)?.name}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>province</span> {states?.find(s => s.id == data.customer_manifests[selected]?.province_id)?.name}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>any medical condition</span> {data.customer_manifests[selected]?.medical_id ? "yes" : "no"}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>medical condition detail</span> {data.customer_manifests[selected]?.medical_detail}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>fit to walk</span> {data.customer_manifests[selected]?.is_fit ? "yes" : "no"}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>agree to terms</span> {data.customer_manifests[selected]?.is_agree ? "yes" : "no"}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>email_address</span> {data.customer_manifests[selected]?.email_address}</span>
                  <span className='flex flex-col items-center gap-2'>
                    <img src={data.customer_manifests[selected]?.signature} alt="Signature preview" className="border w-64 h-auto" />
                  </span>
                </div>

              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(13, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
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


{/* enter name */ }
{/* {prevNext == 4 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Please enter your First and Last name
                </span>
                <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  <CustomInputText
                    name="full_name"
                    value={data.customer_manifests[selected].full_name}
                    type="text"
                    mode={mode}
                    onChange={(e) => handleIndemnityChange(e.target.name, e.target.value)}
                    errors={errors}
                  />
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(3, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(5, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )} */}

{/* Email only required if marketing opt-in is YES */ }
{/* {prevNext == 13 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Please enter your email address
                </span>
                <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  <CustomInputText
                    name="email_address"
                    value={data.customer_manifests[selected].email_address}
                    type="text"
                    mode={mode}
                    onChange={(e) => handleIndemnityChange(e.target.name, e.target.value)}
                    errors={errors}
                  />
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(12, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(14, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )} */}

{/* Birthday (month + day) */ }
{/* referral */ }
{/* {prevNext == 6 && (
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
                      checked={data.customer_manifests[selected].referral_id == referral.id.toString()}
                      onChange={handleIndemnityChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(5, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(7, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )} */}

{/* {prevNext == 4 && (
            <div className="flex flex-col w-full sm:w-auto h-full mb-[26px]">

              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(3, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' onClick={() => handlePrevNext(5, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          
          {prevNext == 5 && (
            <div className="flex flex-col w-full sm:w-auto h-full mb-[26px]">

              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(4, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' onClick={() => handlePrevNext(6, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )} */}

{/* select lead guide */ }
{/* {prevNext == 1 && (
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
                    onChange={handleIndemnityChange}
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
          )} */}
{/* select safety guide */ }
{/* {prevNext == 2 && (
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
                    onChange={handleIndemnityChange}
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
                    {prevNext == 4 && (
            <div className="flex flex-col w-full h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Please enter your email address
                </span>
                <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  <CustomInputText
                    name="email_address"
                    value={data.customer_manifests[selected].email_address}
                    type="text"
                    mode={mode}
                    onChange={(e) => handleIndemnityChange(e.target.name, e.target.value)}
                    errors={errors}
                  />
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(3, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(6, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}  
          
          */}