// resources/js/Pages/BookingLookup.jsx
import React, { useState, useEffect } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import CustomButton from '@/Components/CustomButton';
import CustomCheckboxQ from '@/Components/CustomCheckboxQ';
import CustomInputText from '@/Components/CustomInputText';
import CustomSelect from '@/Components/CustomSelect';
import CustomRadio from '@/Components/CustomRadio';
import SignaturePad from '@/Components/SignaturePad';
import InputText from '@/Components/InputText';
import CustomCheckbox from '@/Components/CustomCheckbox';
import GreenButton from '@/Components/GreenButton';
import ConfirmModal from '@/Components/ConfirmModal';
import axios from 'axios';

export default function BookingLookup({ auth, booking, bookings, bookingItem, questions, leadGuides, safetyGuides, typeGroups, ageGroups, typeGender, typeReferral, typeConfirmation }) {
  const { props } = usePage();
  const { flash, old } = props;
  const mode = 'edit';
  console.log("bookings", bookings);
  console.log("questions", questions);

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
  const SIGNED_STORAGE_KEY = 'booking_questionnaire_signed';
  const [signedParams, setSignedParams] = useState(null);
  const [linkExpiresAt, setLinkExpiresAt] = useState(null);
  const [linkTimeLeft, setLinkTimeLeft] = useState(null);
  const [linkExpired, setLinkExpired] = useState(false);
  const [hasUserChanges, setHasUserChanges] = useState(false);
  const [confirmConfig, setConfirmConfig] = useState(null);
  const monthOptions = [
    { id: 1, name: 'January' },
    { id: 2, name: 'February' },
    { id: 3, name: 'March' },
    { id: 4, name: 'April' },
    { id: 5, name: 'May' },
    { id: 6, name: 'June' },
    { id: 7, name: 'July' },
    { id: 8, name: 'August' },
    { id: 9, name: 'September' },
    { id: 10, name: 'October' },
    { id: 11, name: 'November' },
    { id: 12, name: 'December' },
  ];
  const dayOptions = Array.from({ length: 31 }, (_, i) => ({ id: i + 1, name: String(i + 1) }));
  const [isComplete, setIsComplete] = useState(false);

  const { data, setData, post, put, errors, reset, processing, isDirty } = useForm({
    customer_manifests: [],
  });

  useEffect(() => {
    if (mode === "edit" && bookings?.customers?.length) {
      if (old?.customer_manifests?.length) {
        const restored = old.customer_manifests;
        setData('customer_manifests', restored);
        const restoredAdults = restored
          .filter(p => p.type === "adult" && p.full_name != "")
          .map((p, i) => ({
            id: typeof p.customer_id == 'number' ? p.customer_id : i + 1,
            name: p.full_name || "Unnamed Adult"
          }));
        setAdults(restoredAdults);
        const selectedIndex = restored.findIndex(p => p.index == 1);
        if (selectedIndex >= 0) {
          setSelected(selectedIndex);
        }
        return;
      }
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

          // 1 Base customer DB fields
          ...customer,

          // 2 Merge manifest (position, notes, etc.)
          ...manifest,

          // 3 Inject questionnaire answers dynamically
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

  useEffect(() => {
    if (typeof window === 'undefined') return;
    if (!window.location.pathname.startsWith('/front/booking-questionnaire/checkin')) return;

    const params = new URLSearchParams(window.location.search);
    const signature = params.get('signature');
    const expires = params.get('expires');
    const bookingItemId = params.get('booking_item_id') ?? bookingItem?.id;
    const reference = params.get('reference') ?? booking?.reference;

    const hasSignedParams = signature && expires && bookingItemId && reference;
    if (params.get('complete')) {
      setIsComplete(true);
      return;
    }

    if (hasSignedParams) {
      const payload = {
        signature,
        expires,
        booking_item_id: String(bookingItemId),
        reference: String(reference),
      };
      sessionStorage.setItem(SIGNED_STORAGE_KEY, JSON.stringify(payload));
      setSignedParams(payload);
      const expiresMs = Number(expires) * 1000;
      if (Number.isFinite(expiresMs)) {
        setLinkExpiresAt(expiresMs);
      }
      return;
    }

    const stored = sessionStorage.getItem(SIGNED_STORAGE_KEY);
    if (!stored) return;

    try {
      const saved = JSON.parse(stored);
      if (!saved?.signature || !saved?.expires || !saved?.booking_item_id || !saved?.reference) {
        return;
      }
      setSignedParams(saved);
      const savedExpiresMs = Number(saved.expires) * 1000;
      if (Number.isFinite(savedExpiresMs)) {
        setLinkExpiresAt(savedExpiresMs);
      }
      const isExpired = Number.isFinite(savedExpiresMs) ? savedExpiresMs <= Date.now() : false;
      if (isExpired) return;

      const query = new URLSearchParams(saved).toString();
      if (query && window.location.search !== `?${query}`) {
        window.location.replace(`${window.location.pathname}?${query}`);
      }
    } catch (error) {
      return;
    }
  }, [bookingItem?.id, booking?.reference]);

  useEffect(() => {
    if (!linkExpiresAt) return;
    const updateTimeLeft = () => {
      const msLeft = linkExpiresAt - Date.now();
      setLinkTimeLeft(msLeft);
      setLinkExpired(msLeft <= 0);
    };
    updateTimeLeft();
    const intervalId = setInterval(updateTimeLeft, 30000);
    return () => clearInterval(intervalId);
  }, [linkExpiresAt]);

  const formatTimeLeft = (ms) => {
    if (ms === null || ms === undefined) return null;
    const totalSeconds = Math.max(0, Math.floor(ms / 1000));
    const totalMinutes = Math.floor(totalSeconds / 60);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    if (hours > 0) {
      return `${hours}h ${minutes}m`;
    }
    return `${minutes}m`;
  };

  const timeLeftLabel = formatTimeLeft(linkTimeLeft);
  const hasUnsavedChanges = isDirty && hasUserChanges;

  const openConfirm = (config) => {
    setConfirmConfig(config);
  };

  const closeConfirm = () => {
    setConfirmConfig(null);
  };

  const handleClose = () => {
    const message = hasUnsavedChanges
      ? 'You have unsaved changes. Are you sure you want to close this questionnaire and return to the welcome screen?'
      : 'Are you sure you want to close this questionnaire and return to the welcome screen?';
    openConfirm({
      title: 'Close questionnaire?',
      message,
      confirmText: 'Close',
      cancelText: 'Stay',
      onConfirm: () => router.get(route('front.welcome')),
    });
  };

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
    console.log('data', data);
    console.log('bookings', bookings);
    console.log('handle Prev Next ', data.customer_manifests);
    if (number == 1) {
      if (hasUnsavedChanges) {
        openConfirm({
          title: 'Return to start?',
          message: 'You have unsaved changes. Are you sure you want to return to the start of the questionnaire?',
          confirmText: 'Return',
          cancelText: 'Stay',
          onConfirm: () => {
            setPrevNext(1);
            setSelected(null);
          },
        });
        return;
      }
      setPrevNext(1);
      setSelected(null);
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
        openConfirm({
          title: 'Name required',
          message: 'Please enter a name for this person. Return to the start of the questionnaire?',
          confirmText: 'Back to start',
          cancelText: 'Stay',
          onConfirm: () => {
            setPrevNext(1);
            setSelected(null);
          },
        });
        return;
      }
      setSelected(index);
      setPrevNext(number);
      const updated = [...data.customer_manifests];
      updated[index]['index'] = 1;
      setData("customer_manifests", updated);
    }
  };
  const isEnabled = (slug) => {
    if (!questions || !questions.length) return false;

    const q = questions.find(q => q.slug === slug);
    return q ? q.enabled == 1 : false;
  };

  const getQuestionOptions = (slug) => {
    if (!questions || !questions.length) return [];
    const q = questions.find(q => q.slug === slug);
    const options = Array.isArray(q?.options) ? q.options : [];
    return options
      .map((option, index) => ({
        id: option?.id ?? option?.value ?? option?.slug ?? option?.name,
        name: option?.name ?? option?.label ?? option?.id ?? '',
        sort: option?.sort ?? option?.order ?? null,
        show: option?.show,
        index,
      }))
      .filter((option) => option.id !== null && option.id !== undefined && option.name)
      .filter((option) => option.show !== 0)
      .sort((a, b) => {
        const aSort = a.sort ?? a.index;
        const bSort = b.sort ?? b.index;
        return aSort - bSort;
      });
  };

  const resolveOptionLabel = (options, value) => {
    if (!options || !options.length || value === null || value === undefined) return value;
    const match = options.find((option) => String(option.id) === String(value));
    return match?.name ?? value;
  };

  const lunchOptions = getQuestionOptions('lunch_option');
  const hasLunch = isEnabled('lunch_option') && lunchOptions.length > 0;

  const questionStepsBase = [1, 2, 3, 6, 7, 8, 9, 10, 11, 12, 13, 14];
  let questionSteps = isEnabled('age_id')
    ? questionStepsBase
    : questionStepsBase.filter((step) => step !== 2);
  if (!hasLunch) {
    questionSteps = questionSteps.filter((step) => step !== 12);
  }

  const selectedManifest = selected != null ? data.customer_manifests?.[selected] : null;
  const isStep2Disabled = !selectedManifest?.age_id;
  const isContinentStep = continents.length > 0 && countries.length === 0;
  const isCountryStep = countries.length > 0 && states.length === 0;
  const isProvinceStep = states.length > 0 && selectedCountry == "204";
  const baseQuestionIndex = questionSteps.indexOf(prevNext) + 1;
  const hasContinent = Boolean(selectedManifest?.continent_id);
  const requiresProvince = selectedCountry == "204" || selectedManifest?.country_id == "204";
  const locationExtra = hasContinent ? (requiresProvince ? 2 : 1) : 0;
  const medicalExtra = selectedManifest?.medical_id == 1 ? 1 : 0;
  const pregnancyExtra = selectedManifest?.gender == "2" ? 1 : 0;
  const conditionalExtra = medicalExtra + pregnancyExtra;
  const totalQuestions = questionSteps.length + locationExtra + conditionalExtra;
  let locationOffset = 0;
  if (prevNext === 3) {
    if (isCountryStep) locationOffset = 1;
    if (isProvinceStep) locationOffset = 2;
  } else if (prevNext > 3) {
    locationOffset = locationExtra;
  }
  let conditionalOffset = 0;
  if (prevNext > 9) conditionalOffset += medicalExtra;
  if (prevNext > 10) conditionalOffset += pregnancyExtra;
  const questionIndex = baseQuestionIndex + locationOffset + conditionalOffset;
  const questionBadgeLabel = questionIndex > 0 ? `${questionIndex}/${totalQuestions}` : null;
  const isStep3Disabled = isContinentStep
    ? !selectedManifest?.continent_id
    : isCountryStep
      ? !selectedManifest?.country_id
      : isProvinceStep
        ? !selectedManifest?.province_id
        : false;
  const isStep6Disabled = !selectedManifest?.referral_id;
  const isStep7Disabled = !selectedManifest?.type_group_id;
  const requiresMarketingDetails = selectedManifest?.marketing_opt_in == 1;
  const hasMarketingDetails = Boolean(selectedManifest?.email_address?.trim() && selectedManifest?.birthday_month && selectedManifest?.birthday_day);
  const isStep8Disabled = !selectedManifest?.marketing_opt_in || (requiresMarketingDetails && !hasMarketingDetails);
  const requiresMedicalDetail = selectedManifest?.medical_id == 1;
  const hasMedicalDetail = Boolean(selectedManifest?.medical_detail?.trim());
  const isStep9Disabled = !selectedManifest?.medical_id || (requiresMedicalDetail && !hasMedicalDetail);
  const isStep10Disabled = !selectedManifest?.gender || (selectedManifest?.gender == "2" && !selectedManifest?.pregnant);
  const isStep11Disabled = !selectedManifest?.is_fit;
  const isStep12Disabled = hasLunch && !selectedManifest?.lunch_option;
  const isStep13Disabled = selectedManifest?.is_agree !== true;
  const isStep14Disabled = !selectedManifest?.signature;

  const getNextButtonClass = (disabled) => (
    `flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px]
    ${disabled
      ? "border-primary-14 text-primary-14 cursor-not-allowed opacity-50"
      : "bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer"
    }`
  );

  const getWideNextButtonClass = (disabled) => (
    `flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px]
    ${disabled
      ? "border-primary-14 text-primary-14 cursor-not-allowed opacity-50"
      : "bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer"
    }`
  );


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
    setHasUserChanges(true);
    setData(target, value);
    console.log('data', data);
  };

  const handlePersonChange = (index, field, value) => {
    const updated = [...data.customer_manifests];
    updated[index][field] = value;
    setHasUserChanges(true);
    setData("customer_manifests", updated);
    console.log('-------handle Person Change--------', updated);
  };

  const handleIndemnityChange = (field, value) => {
    if (selected != null) {
      const updated = [...data.customer_manifests];
      updated[selected][field] = value;
      setHasUserChanges(true);
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
      <div className="flex flex-col min-h-screen items-center justify-start sm:justify-center bg-secondary-100 px-4 py-6">
        <div className="flex flex-wrap items-center justify-center w-full gap-4 sm:pb-[51px]">
          {/* Logo Box */}
          <div className="flex items-center justify-center w-[118px] h-[106px] shrink-0">
            <img
              src="/images/logo.png"
              alt="Canopy Tours"
              className="w-full h-full object-contain min-w-[100px] min-h-[90px]"
            />
          </div>

          {/* Text Logo */}
          <div className="flex flex-wrap items-center gap-2">
            <img
              src="/images/text.png"
              alt="Canopy Tours Text"
              className="w-24 h-24 md:w-[118px] md:h-[106px] object-contain"
            />
            <button onClick={handleClose} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-red-gradient-overlay border-[#C65468] text-white shadow-inset-white cursor-pointer">
              Close
            </button>
          </div>


        </div>
        <div className="flex flex-col bg-white w-full max-w-4xl sm:w-[80%] md:w-[60%] sm:h-[80%] max-h-[70vh] items-center shadow-lg rounded-[20px] overflow-hidden relative">
          {questionBadgeLabel && (
            <div className="absolute top-4 right-4 z-10 rounded-full border border-slate-300 bg-slate-50 px-3 py-1 text-xs text-slate-600">
              {questionBadgeLabel}
            </div>
          )}
          {confirmConfig && (
            <ConfirmModal
              title={confirmConfig.title}
              message={confirmConfig.message}
              confirmText={confirmConfig.confirmText}
              cancelText={confirmConfig.cancelText}
              onConfirm={() => {
                const action = confirmConfig.onConfirm;
                closeConfirm();
                if (action) action();
              }}
              onClose={closeConfirm}
            />
          )}
          {(flash?.error || flash?.success) && (
            <div className="w-full px-4 pt-4">
              <div
                className={`rounded-lg border px-4 py-3 text-sm ${flash?.error
                  ? 'border-red-200 bg-red-50 text-red-700'
                  : 'border-green-200 bg-green-50 text-green-700'
                  }`}
              >
                {flash?.error || flash?.success}
              </div>
            </div>
          )}
          {isComplete && (
            <div className="w-full px-4 pt-4">
              <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                Check-in complete. Thank you! You can close this window when you are finished.
              </div>
            </div>
          )}

          {prevNext == 1 && (
            <div className="flex w-[90%] flex-col h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full flex-1 overflow-y-auto scrollbar-elegant gap-2" >
                <span className="text-welcome-card-title-mb text-center w-full sm:w-[90%] mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Select or add your Name
                </span>
                <div className="h-auto w-full sm:w-[90%] pl-7 sm:pl-0 overflow-x-auto">
                  {data?.customer_manifests?.map((person, index) => {
                    // console.log("person", person);
                    const nameMissing = !person?.full_name?.trim();
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
                              <button type='button' disabled={nameMissing} onClick={() => handlePrevNext(2, index)} className={getWideNextButtonClass(nameMissing)} >
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
            <div className="flex flex-col w-[90%] h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full flex-1 overflow-auto scrollbar-elegant gap-2 mb-2">
                <span className="text-welcome-card-title-mb text-center w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Please select your Age Group
                </span>
                <div className="h-auto w-full pl-7 sm:pl-0">
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
                <button type='button' disabled={isStep2Disabled} onClick={() => handlePrevNext(3, selected)} className={getNextButtonClass(isStep2Disabled)} >
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* Continent Country  Province*/}
          {prevNext == 3 && (
            <div className="flex flex-col w-[90%] h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full flex-1 overflow-y-auto scrollbar-elegant gap-2 mb-2">
                {/* Continent */}
                {(continents.length > 0 && countries.length == 0) && (
                  <div className="flex flex-col w-full flex-1 overflow-y-auto gap-2 " >
                    <span className="text-welcome-card-title-mb text-center w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                      Where are you from? Select your Continent
                    </span>
                    <div className="h-auto w-full pl-7 sm:pl-0 overflow-x-auto scrollbar-elegant">
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
                  <div className="flex flex-col w-[90%] flex-1 overflow-y-auto gap-2 mb-2" >
                    <span className="text-welcome-card-title-mb text-center w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                      Country of Residence
                    </span>
                    <div className="h-auto w-full pl-7 sm:pl-0 overflow-x-auto scrollbar-elegant">
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
                  <div className="flex flex-col w-[90%] flex-1 overflow-y-auto gap-2 mb-2 " >
                    <span className="text-welcome-card-title-mb text-center w-full sm:px-[39px] mt-[42px] mb-[26px] ">
                      Select Province
                    </span>
                    <div className="h-auto w-full pl-7 sm:pl-0 overflow-x-auto scrollbar-elegant">
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
                <button type='button' disabled={isStep3Disabled} onClick={() => handlePrevNext(6, selected)} className={getNextButtonClass(isStep3Disabled)} >
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}

          {/* Marketing source */}
          {prevNext == 6 && (
            <div className="flex flex-col w-[90%] h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full sm:w-[90%] flex-1 overflow-y-auto  gap-2 mb-2 " >
                <span className="text-welcome-card-title-mb text-center w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Where did you hear about us
                </span>
                <div className="h-auto w-full  pl-7 sm:pl-0 overflow-x-auto scrollbar-elegant">
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
                <button type='button' disabled={isStep6Disabled} onClick={() => handlePrevNext(7, selected)} className={getNextButtonClass(isStep6Disabled)} >
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* Visitor type (individual, couple, etc.) */}
          {prevNext == 7 && (
            <div className="flex flex-col w-[90%] h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full flex-1 overflow-y-auto scrollbar-elegant gap-2 mb-2" >
                <span className="text-welcome-card-title-mb text-center w-full sm:w-[90%] mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  What kind of a group are you with?
                </span>
                <div className="h-auto w-full pl-7 sm:pl-0 overflow-x-auto scrollbar-elegant">
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
                <button type='button' disabled={isStep7Disabled} onClick={() => handlePrevNext(8, selected)} className={getNextButtonClass(isStep7Disabled)} >
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* marketing opt-in */}
          {prevNext == 8 && (
            <div className="flex flex-col w-[90%] h-full min-h-0 mb-[26px]">

              <div className="flex flex-col w-full flex-1 overflow-auto scrollbar-elegant gap-2  ">
                <span className="text-welcome-card-title-mb text-center sm:w-full px-7 sm:px-[39px] mt-[42px] mb-[26px]">
                  Would you like us to email you a certificate and invite you for a free tour on your birthday?
                  (This also means you are happy to receive the occasional email from us regarding specials and promos on Canopy Tours around Southern Africa.)
                </span>

                <div className="flex flex-row items-center justify-center h-auto w-full pl-7 sm:pl-0">
                  {typeConfirmation?.map((marketing, i) => (
                    <div key={marketing.id ?? i} className="w-[100%] sm:w-[50%] flex p-3">
                      <CustomRadio
                        key={marketing.id}
                        label={marketing.name}
                        name="marketing_opt_in"
                        value={marketing.id}
                        checked={data.customer_manifests[selected].marketing_opt_in == marketing.id.toString()}
                        onChange={handleIndemnityChange}
                        errors={errors}
                      />
                    </div>
                  ))}
                </div>
                {data.customer_manifests[selected].marketing_opt_in == 1 && (
                  <div className="h-auto w-full pl-7 sm:px-[39px]">
                    <CustomInputText
                      title="What email address should we use? "
                      name="email_address"
                      value={data.customer_manifests[selected].email_address}
                      height="h-[52px]"
                      type="email"
                      mode={mode}
                      onChange={(e) => handleIndemnityChange(e.target.name, e.target.value)}
                    />
                    <CustomSelect
                      label="Your birthday month?"
                      name="birthday_month"
                      width="w-full"
                      height="h-[52px]"
                      value={data.customer_manifests[selected].birthday_month}
                      options={monthOptions}
                      onChange={(e) => handleIndemnityChange(e.target.name, e.target.value)}
                      errors={errors}
                      mode={mode}
                    />
                    <CustomSelect
                      label="Your birthday day?"
                      name="birthday_day"
                      width="w-full"
                      height="h-[52px]"
                      value={data.customer_manifests[selected].birthday_day}
                      options={dayOptions}
                      onChange={(e) => handleIndemnityChange(e.target.name, e.target.value)}
                      errors={errors}
                      mode={mode}
                    />
                  </div>
                )}

              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(7, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' disabled={isStep8Disabled} onClick={() => handlePrevNext(9, selected)} className={getNextButtonClass(isStep8Disabled)} >
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}

          {/* Medical detail text only when YES */}
          {prevNext == 9 && (
            <div className="flex flex-col w-[90%] h-full min-h-0 mb-[26px]">

              <div className="flex flex-col w-full flex-1 overflow-auto scrollbar-elegant gap-2 mb-2 ">
                <span className="text-welcome-card-title-mb text-center sm:w-full px-7 sm:px-[39px] mt-[42px] mb-[26px]">
                  Do you have any medical conditions we should be aware of?
                </span>
                <div className="flex flex-row items-center justify-center h-auto w-full pl-7 sm:pl-0">
                  {typeConfirmation?.map((medical, i) => (
                    <div key={medical.id ?? i} className="w-[100%] sm:w-[50%] flex p-3">
                      <CustomRadio
                        key={medical.id}
                        label={medical.name}
                        name="medical_id"
                        value={medical.id}
                        checked={data.customer_manifests[selected].medical_id == medical.id.toString()}
                        onChange={handleIndemnityChange}
                        errors={errors}
                      />
                    </div>
                  ))}
                </div>
                {data.customer_manifests[selected].medical_id == 1 && (

                  <div className="h-auto w-full pl-7 sm:px-[39px]">
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
                <button type='button' disabled={isStep9Disabled} onClick={() => handlePrevNext(10, selected)} className={getNextButtonClass(isStep9Disabled)} >
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* Gender */}
          {prevNext == 10 && (
            <div className="flex flex-col w-[90%] h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full flex-1 overflow-auto scrollbar-elegant gap-2 mb-2" >
                <span className="text-welcome-card-title-mb text-center w-full sm:w-[90%] mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Gender?
                </span>
                <div className="flex flex-row items-center justify-center h-auto w-full pl-7 sm:pl-0">
                  {typeGender?.map((gender, i) => (
                    <div key={gender.id ?? i} className="w-[100%] sm:w-[50%] flex p-3">
                      <CustomRadio
                        key={gender.id}
                        label={gender.name}
                        name="gender"
                        value={gender.id}
                        checked={data.customer_manifests[selected].gender == gender.id.toString()}
                        onChange={handleIndemnityChange}
                        errors={errors}
                      />
                    </div>
                  ))}
                </div>
                {/* Pregnancy shows only if female */}
                {data.customer_manifests[selected].gender == "2" && (
                  <>
                    <span className="text-welcome-card-title-mb text-center w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                      Pregnant?
                    </span>
                    <div className="flex flex-row items-center justify-center h-auto w-full pl-7 sm:pl-0 overflow-x-auto">
                      {typeConfirmation?.map((pregnant, i) => (
                        <div key={pregnant.id ?? i} className="w-[100%] sm:w-[50%] flex p-3">
                          <CustomRadio
                            key={pregnant.id}
                            label={pregnant.name}
                            name="pregnant"
                            value={pregnant.id}
                            checked={data.customer_manifests[selected].pregnant == pregnant.id.toString()}
                            onChange={handleIndemnityChange}
                            errors={errors}
                          />
                        </div>
                      ))}
                    </div>
                  </>
                )}

              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(9, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' disabled={isStep10Disabled} onClick={() => handlePrevNext(11, selected)} className={getNextButtonClass(isStep10Disabled)} >
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* Fit */}
          {prevNext == 11 && (
            <div className="flex flex-col w-[90%] h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full flex-1 overflow-auto scrollbar-elegant gap-2 mb-2 " >
                <span className="text-welcome-card-title text-center sm:w-full sm:px-[39px] mt-[42px] mb-[26px]">
                  I am fit enough to walk 1km in steep terrain
                </span>
                <div className="flex flex-row items-center justify-center h-auto w-fullpl-7 sm:pl-0">
                  {typeConfirmation?.map((fit, i) => (
                    <div key={fit.id ?? i} className="w-[100%] sm:w-[50%] flex p-3">
                      <CustomRadio
                        key={fit.id}
                        label={fit.name}
                        name="is_fit"
                        value={fit.id}
                        checked={data.customer_manifests[selected].is_fit == fit.id.toString()}
                        onChange={handleIndemnityChange}
                        errors={errors}
                      />
                    </div>
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(10, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' disabled={isStep11Disabled} onClick={() => handlePrevNext(hasLunch ? 12 : 13, selected)} className={getNextButtonClass(isStep11Disabled)} >
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>

            </div>
          )}
          {/* Lunch option */}
          {prevNext == 12 && hasLunch && (
            <div className="flex flex-col w-[90%] h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full flex-1 overflow-auto scrollbar-elegant gap-2 mb-2">
                <span className="text-welcome-card-title-mb text-center w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Please select your lunch option
                </span>
                <div className="h-auto w-full pl-7 sm:pl-0 overflow-x-auto scrollbar-elegant">
                  {lunchOptions.map((option) => (
                    <CustomRadio
                      key={option.id}
                      label={option.name}
                      name="lunch_option"
                      value={option.id}
                      checked={data.customer_manifests[selected].lunch_option == option.id?.toString()}
                      onChange={handleIndemnityChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(11, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' disabled={isStep12Disabled} onClick={() => handlePrevNext(13, selected)} className={getNextButtonClass(isStep12Disabled)} >
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* Agree */}
          {prevNext == 13 && (
            <div className="flex flex-col w-[90%] h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full flex-1 overflow-auto scrollbar-elegant gap-2 mb-2" >
                <span className="text-welcome-card-title-mb text-center w-full mt-[42px] mb-2 px-6 sm:px-[39px]">
                  Please agree to the following terms and conditions
                </span>
                <span className="text-welcome-card-title-mb text-center w-full mb-[8px] px-6 sm:px-[39px]">
                  By accessing and using this website, you acknowledge and agree to the terms outlined in this Indemnity Agreement. This form serves to protect the website owners, administrators, and affiliated parties from any liability arising from the use of the site, its content, or any services provided. By proceeding, you confirm that you understand and accept full responsibility for any risks associated with your use of this website and waive any claims against its owners for potential losses, damages, or liabilities.
                </span>
                <div className="w-[100%] sm:w-full mb-[8px] px-6 sm:px-[39px]">
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
                <button type='button' onClick={() => handlePrevNext(hasLunch ? 12 : 11, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button'
                  disabled={isStep13Disabled}
                  onClick={() => handlePrevNext(14, selected)}
                  className={`flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px]
                    ${!isStep13Disabled
                      ? "bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer"
                      : "border-primary-14 text-primary-14 cursor-not-allowed opacity-50"
                    }`}
                >
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* Full indemnity legal text from CTSA */}
          {/* Guardian shows only if age < 18 */}

          {/* signature */}
          {prevNext == 14 && (
            <div className="flex flex-col w-[90%] h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full flex-1 overflow-auto scrollbar-elegant gap-2 mb-2" >
                <span className="text-welcome-card-title-mb text-center w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Please sign below
                </span>
                <div className="h-auto w-full pl-7 sm:pl-0 mb-2">
                  <SignaturePad
                    value={data.customer_manifests[selected].signature}
                    onChange={(value) => handleIndemnityChange('signature', value)}
                  />
                  {errors.signature && <div className="text-red-500 text-sm">{errors.signature}</div>}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(13, selected)} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' disabled={isStep14Disabled} onClick={() => handlePrevNext(15, selected)} className={getNextButtonClass(isStep14Disabled)} >
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {/* submit */}
          {prevNext == 15 && (
            <div className="flex flex-col  w-[90%] h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full flex-1 overflow-auto scrollbar-elegant gap-2 mb-2" >
                <span className="text-welcome-card-title-mb text-center w-full mt-[42px] px-6 sm:px-[39px]">
                  Your Indemnity is ready to submit
                </span>
                <span className="flex flex-col w-full text-center mb-[26px]">
                  <span className='block' ><span className='text-booking-heading capitalize'>booking reference</span> {booking?.reference}</span>
                  <span className='block'><span className='text-booking-subheading capitalize'>Service</span> {bookingItem?.service?.name}</span>
                </span>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 h-auto pl-7 sm:px-[12px] mb-2">

                  {/* <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>lead guide</span> {leadGuides?.find(g => g.id == data.customer_manifests[selected]?.lead_guide_id)?.name}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>safety guide</span> {safetyGuides?.find(g => g.id == data.customer_manifests[selected]?.safety_guide_id)?.name}</span> */}
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>type group</span> {typeGroups?.find(g => g.id == data.customer_manifests[selected]?.type_group_id)?.name}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>full name</span> {data.customer_manifests[selected]?.full_name}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>age</span> {ageGroups?.find(g => g.id == data.customer_manifests[selected]?.age_id)?.name}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>referral</span> {typeReferral?.find(r => r.id == data.customer_manifests[selected]?.referral_id)?.name}</span>
                  {hasLunch && (
                    <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>lunch option</span> {resolveOptionLabel(lunchOptions, data.customer_manifests[selected]?.lunch_option)}</span>
                  )}
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>continent</span> {continents?.find(c => c.id == data.customer_manifests[selected]?.continent_id)?.name}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>country</span> {countries?.find(c => c.id == data.customer_manifests[selected]?.country_id)?.name}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>province</span> {states?.find(s => s.id == data.customer_manifests[selected]?.province_id)?.name}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>any medical condition</span> {data.customer_manifests[selected]?.medical_id ? "yes" : "no"}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>medical condition detail</span> {data.customer_manifests[selected]?.medical_detail}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>fit to walk</span> {data.customer_manifests[selected]?.is_fit ? "yes" : "no"}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>agree to terms</span> {data.customer_manifests[selected]?.is_agree ? "yes" : "no"}</span>
                  <span className='flex flex-col items-center gap-2'><span className='text-booking-subheading capitalize'>email_address</span> {data.customer_manifests[selected]?.email_address}</span>
                  <span className='flex flex-col items-center gap-2'>
                    {data.customer_manifests[selected]?.signature ?
                      (<img src={data.customer_manifests[selected]?.signature} alt="Signature preview" className="border w-64 h-auto" />)
                      :
                      (<span className='text-booking-subheading capitalize'>Signature Not Found</span>)
                    }
                  </span>
                </div>

              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' disabled={isStep14Disabled} onClick={() => handlePrevNext(14, selected)} className={getNextButtonClass(isStep14Disabled)} >
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
            <div className="flex flex-col h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full flex-1 overflow-y-auto scrollbar-elegant gap-2" >
                <span className="text-welcome-card-title text-center w-full sm:w-[90%] mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Please enter your First and Last name
                </span>
                <div className="h-auto w-full sm:w-[90%] pl-7 sm:pl-0 overflow-x-auto">
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
            <div className="flex flex-col h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full flex-1 overflow-y-auto scrollbar-elegant gap-2" >
                <span className="text-welcome-card-title text-center w-full sm:w-[90%] mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Please enter your email address
                </span>
                <div className="h-auto w-full sm:w-[90%] pl-7 sm:pl-0 overflow-x-auto">
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
            <div className="flex flex-col h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full flex-1 overflow-y-auto scrollbar-elegant gap-2 " >
                <span className="text-welcome-card-title text-center w-full sm:w-[90%] mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Where did you hear about us
                </span>
                <div className="h-auto w-full sm:w-[90%] pl-7 sm:pl-0 overflow-x-auto">
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
            <div className="flex flex-col w-full sm:w-auto h-full min-h-0 mb-[26px]">

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
            <div className="flex flex-col w-full sm:w-auto h-full min-h-0 mb-[26px]">

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
            <div className="flex flex-col h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full flex-1 overflow-y-auto scrollbar-elegant gap-2" >
                <span className="text-welcome-card-title text-center w-full sm:w-[90%] mt-[42px] mb-[26px] px-6 sm:px-[39px]">
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
            <div className="flex flex-col h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full flex-1 overflow-y-auto scrollbar-elegant gap-2" >
                <span className="text-welcome-card-title text-center w-full sm:w-[90%] mt-[42px] mb-[26px] px-6 sm:px-[39px]">
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
            <div className="flex flex-col w-full h-full min-h-0 mb-[26px]">
              <div className="flex flex-col w-full flex-1 overflow-y-auto scrollbar-elegant gap-2" >
                <span className="text-welcome-card-title text-center w-full sm:w-[90%] mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Please enter your email address
                </span>
                <div className="h-auto w-full sm:w-[90%] pl-7 sm:pl-0 overflow-x-auto">
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

