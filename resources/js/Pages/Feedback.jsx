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

export default function BookingLookup({ auth, booking, bookings, bookingItem, questions, leadGuides, arrivals, safetyBrifings, drive, areaKnowledge, comfortSafety }) {
  const { props } = usePage();
  const { old } = props;
  // console.log("auth", auth);
  const mode = 'edit';
  // console.log("booking", booking);
  console.log("bookings", bookings);
  console.log("leadGuides", leadGuides);
  // console.log("questions", questions);
  // console.log("arrivals", arrivals);
  // console.log("safetyBrifings", safetyBrifings);
  // console.log("drive", drive);
  // console.log("areaKnowledge", areaKnowledge);
  // console.log("comfortSafety", comfortSafety);

  const [prevNext, setPrevNext] = useState(1);
  const [adults, setAdults] = useState([]);
  const [selected, setSelected] = useState(null);
  const SIGNED_STORAGE_KEY = 'feedback_questionnaire_signed';
  const [signedParams, setSignedParams] = useState(null);
  const [linkExpiresAt, setLinkExpiresAt] = useState(null);
  const [linkTimeLeft, setLinkTimeLeft] = useState(null);
  const [linkExpired, setLinkExpired] = useState(false);
  const [hasUserChanges, setHasUserChanges] = useState(false);
  const [confirmConfig, setConfirmConfig] = useState(null);


  const activeSteps = questions
    .filter(q => q.enabled == 1)
    .sort((a, b) => a.sort_order - b.sort_order)
    .map(q => q.slug);
  activeSteps.push('submit');
  console.log('activeSteps', activeSteps);

  const [currentSlug, setCurrentSlug] = useState(activeSteps[0]);

  const { data, setData, post, put, errors, reset, processing, isDirty } = useForm({
    feedback: [],
  });

  useEffect(() => {
    if (bookings?.customers?.length) {
      if (old?.feedback?.length) {
        const restored = old.feedback;
        setData('feedback', restored);
        const restoredAdults = restored
          .filter(p => p.type === "adult" && p.full_name != "")
          .map((p, i) => ({
            id: typeof p.customer_id == 'number' ? p.customer_id : 0,
            name: p.full_name || "Unnamed Adult",
            is_feedback: p.is_feedback,
            is_indemnity: p.is_indemnity,
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

          // 1️⃣ Base customer DB fields
          ...customer,

          // 2️⃣ Merge manifest (position, notes, etc.)
          ...manifest,

          // 3️⃣ Inject questionnaire answers dynamically
          ...qAnswers,
        };
      });
      console.log("mapped", mapped);
      // const adults = mapped?.filter(p => p.type === "adult");
      // setAdults(adults);
      setData('feedback', mapped);
      // setSelected(1);
      console.log('--------------init data-------------', mapped);
    } else {
      alert('Please select a booking');
    }
  }, [mode, bookings?.customers]);

  useEffect(() => {
    if (data.feedback && data.feedback.length > 0) {
      console.log('updated Adults', data.feedback);
      const updatedAdults = data.feedback
        .filter(p => p.type === "adult" && p.full_name != "")
        .map((p, i) => {
          console.log("p", p);
          return ({
            id: typeof p.customer_id == 'number' ? p.customer_id : 0,// i + 1,
            name: p.full_name || "Unnamed Adult",
            is_feedback: p.is_feedback,
            is_indemnity: p.is_indemnity,
          })
        });
      console.log("updatedAdults", updatedAdults);
      setAdults(updatedAdults);
    }

  }, [data.feedback]);

  useEffect(() => {
    if (typeof window === 'undefined') return;
    if (!window.location.pathname.startsWith('/front/feedback-questionnaire/feedback')) return;

    const params = new URLSearchParams(window.location.search);
    const signature = params.get('signature');
    const expires = params.get('expires');
    const bookingItemId = params.get('booking_item_id') ?? bookingItem?.id;
    const reference = params.get('reference') ?? booking?.reference;
    const customerId = params.get('customer_id');

    const hasSignedParams = signature && expires && bookingItemId && reference;

    if (hasSignedParams) {
      const payload = {
        signature,
        expires,
        booking_item_id: String(bookingItemId),
        reference: String(reference),
      };
      if (customerId) {
        payload.customer_id = String(customerId);
      }
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

  const handleRefreshLink = () => {
    const bookingItemId = signedParams?.booking_item_id ?? bookingItem?.id;
    const reference = signedParams?.reference ?? booking?.reference;
    if (!bookingItemId || !reference) return;
    const params = {
      booking_item_id: bookingItemId,
      reference,
    };
    if (signedParams?.customer_id) {
      params.customer_id = signedParams.customer_id;
    }
    router.get(route('front.feedback-questionnaire.signed', params));
  };

  const timeLeftLabel = formatTimeLeft(linkTimeLeft);
  const canRefreshLink = Boolean((signedParams?.booking_item_id ?? bookingItem?.id) && (signedParams?.reference ?? booking?.reference));
  const hasUnsavedChanges = isDirty && hasUserChanges;

  const openConfirm = (config) => {
    setConfirmConfig(config);
  };

  const closeConfirm = () => {
    setConfirmConfig(null);
  };

  const handleClose = () => {
    const message = hasUnsavedChanges
      ? 'You have unsaved changes. Are you sure you want to close this feedback form and return to the welcome screen?'
      : 'Are you sure you want to close this feedback form and return to the welcome screen?';
    openConfirm({
      title: 'Close feedback?',
      message,
      confirmText: 'Close',
      cancelText: 'Stay',
      onConfirm: () => router.get(route('front.feedback-questionnaire.index')),
    });
  };

  const isEnabled = (slug) => {
    if (!questions || !questions.length) return false;

    const q = questions.find(q => q.slug === slug);
    return q ? q.enabled == 1 : false;
  };
  const nextStep = (index) => {
    const idx = activeSteps.indexOf(currentSlug);
    if (idx < activeSteps.length - 1) {
      setCurrentSlug(activeSteps[idx + 1]);
    }
  };
  const prevStep = (index) => {
    const idx = activeSteps.indexOf(currentSlug);
    if (idx > 0) {
      setCurrentSlug(activeSteps[idx - 1]);
    }
  };

  const handleIndemnityChange = (field, value) => {
    const updated = [...data.feedback];
    setHasUserChanges(true);
    if (field == "customer_id") {
      console.log('customer_id-----------------------------', value);
      setSelected(value);
      updated.forEach((p, i) => {
        if (i == value) {
          updated[i][field] = p.customer_id;//value.toString();
          updated[i]['index'] = 1;
          setSelected(i);
        } else {
          updated[i]['index'] = 0;
        }
      });
      setData("feedback", updated);
    }
    else if (selected != null) {
      updated[selected][field] = value;
      setData("feedback", updated);
    }
    console.log('-------handle Indemnity Change--------', updated);
  };


  const handleSubmit = (e) => {
    e.preventDefault();
    console.log('booking', booking);
    console.log('handle Submit', data.feedback);
    post(route('front.feedback-questionnaire.store'));
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
            <button onClick={handleClose} className="flex items-center w-auto h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-red-gradient-overlay border-[#C65468] text-white shadow-inset-white cursor-pointer">
              Close
            </button>
          </div>


        </div>
        <div className="flex flex-col bg-white h-[80%] w-full sm:w-[50%] items-center shadow-lg rounded-[20px]">
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
          {linkExpiresAt && (
            <div className="w-full px-4 pt-4">
              <div
                className={`rounded-lg border px-4 py-3 text-sm flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between ${linkExpired
                  ? 'border-amber-200 bg-amber-50 text-amber-800'
                  : 'border-blue-200 bg-blue-50 text-blue-700'
                  }`}
              >
                <span>
                  {linkExpired
                    ? 'This feedback link has expired. You can refresh it to continue.'
                    : `This feedback link expires in ${timeLeftLabel || 'a few minutes'}.`}
                </span>
                {canRefreshLink && (
                  <button
                    type="button"
                    onClick={handleRefreshLink}
                    className="inline-flex items-center justify-center rounded-lg border border-current px-3 py-1 text-sm font-medium hover:bg-white/60"
                  >
                    Refresh link
                  </button>
                )}
              </div>
            </div>
          )}

          {currentSlug === "customer_id" && (
            <div className="flex w-[90%] flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center min-w-[90%] sm:w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Select your Name
                </span>
                <div className="h-auto w-[90%] sm:w-full pl-7 sm:pl-0 overflow-x-auto">
                  {adults?.map((person, i) => {
                    console.log('person', person);
                    console.log('selected', data?.feedback[selected]?.customer_id);
                    if (person.is_feedback == null) return null;
                    return (
                      // <span>{person.full_name}</span>
                      <CustomRadio
                        key={i}
                        label={person.name}
                        name="customer_id"
                        value={i}
                        mode={person.is_feedback}
                        checked={data?.feedback[selected]?.customer_id == person.id.toString()}
                        onChange={handleIndemnityChange}
                        errors={errors}
                      />
                    )
                  })}
                </div>
                <div className="flex items-center justify-center gap-6">
                  <div className="flex items-center justify-center gap-6">
                    <button
                      type='button'
                      onClick={() => nextStep(selected)}
                      disabled={selected == null}
                      className={`flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white ${selected == null ? "cursor-not-allowed" : "cursor-pointer"} `}>
                      <span className="text-btn-outline-gray">Next</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          )}
          {/* select lead guide */}
          {currentSlug === "guide_id" && (
            <div className="flex flex-col w-[90%] h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Select your Lead Guide
                </span>
                <div className="h-auto w-full pl-7 sm:pl-0 overflow-x-auto">
                  {leadGuides?.map((guide, i) => (
                    <CustomRadio
                      key={guide.id}
                      label={guide.name}
                      name="guide_id"
                      value={guide.id}
                      checked={data.feedback[selected].guide_id == guide.id.toString()}
                      onChange={handleIndemnityChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <div className="flex items-center justify-center gap-6">
                  <button type='button' onClick={() => prevStep(selected)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                    <span className="text-btn-outline-gray">Prev</span>

                  </button>
                  <button type='button' onClick={() => nextStep(selected)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                    <span className="text-btn-outline-gray">Next</span>
                  </button>
                </div>
              </div>
            </div>
          )}
          {/* received upon arrival */}
          {currentSlug === "reception_id" && (
            <div className="flex flex-col w-[90%] h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2">
                <span className="text-welcome-card-title text-center w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  Were you well received upon arrival?
                </span>
                <div className="h-auto w-full pl-7 sm:pl-0 overflow-x-auto">
                  {arrivals?.map((reception, i) => (
                    <CustomRadio
                      key={reception.id}
                      label={reception.name}
                      name="reception_id"
                      value={reception.id}
                      checked={data.feedback[selected].reception_id == reception.id.toString()}
                      onChange={handleIndemnityChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <div className="flex items-center justify-center gap-6">
                  <button type='button' onClick={() => prevStep(selected)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                    <span className="text-btn-outline-gray">Prev</span>

                  </button>
                  <button type='button' onClick={() => nextStep(selected)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                    <span className="text-btn-outline-gray">Next</span>
                  </button>
                </div>
              </div>
            </div>
          )}
          {/* safety briefing*/}
          {currentSlug === "briefing_id" && (
            <div className="flex flex-col w-[90%] h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2">
                <div className="flex flex-col w-full h-[90%] gap-2 " >
                  <span className="text-welcome-card-title text-center w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                    How was the safety briefing?
                  </span>
                  <div className="h-auto w-full pl-7 sm:pl-0 overflow-x-auto">
                    {safetyBrifings?.map((briefing, i) => (
                      <CustomRadio
                        key={briefing.id}
                        label={briefing.name}
                        name="briefing_id"
                        value={briefing.id}
                        checked={data.feedback[selected].briefing_id == briefing.id.toString()}
                        onChange={handleIndemnityChange}
                        errors={errors}
                      />
                    ))}
                  </div>
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <div className="flex items-center justify-center gap-6">
                  <button type='button' onClick={() => prevStep(selected)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                    <span className="text-btn-outline-gray">Prev</span>

                  </button>
                  <button type='button' onClick={() => nextStep(selected)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                    <span className="text-btn-outline-gray">Next</span>
                  </button>
                </div>
              </div>
            </div>
          )}

          {/* drive */}
          {currentSlug === "experience_id" && (
            <div className="flex flex-col w-[90%] h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2 " >
                <span className="text-welcome-card-title text-center w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  So how about that 4x4 drive?
                </span>
                <div className="h-auto w-full pl-7 sm:pl-0 overflow-x-auto">
                  {drive?.map((experience, i) => (
                    <CustomRadio
                      key={experience.id}
                      label={experience.name}
                      name="experience_id"
                      value={experience.id}
                      checked={data.feedback[selected].experience_id == experience.id.toString()}
                      onChange={handleIndemnityChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <div className="flex items-center justify-center gap-6">
                  <button type='button' onClick={() => prevStep(selected)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                    <span className="text-btn-outline-gray">Prev</span>

                  </button>
                  <button type='button' onClick={() => nextStep(selected)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                    <span className="text-btn-outline-gray">Next</span>
                  </button>
                </div>
              </div>
            </div>
          )}
          {/* guide's knowledge */}
          {currentSlug === "knowledge_id" && (
            <div className="flex flex-col w-[90%] sm:w-full h-full mb-[26px]">
              <div className="flex flex-col w-full h-[90%] gap-2" >
                <span className="text-welcome-card-title text-center w-full mt-[42px] mb-[26px] px-6 sm:px-[39px]">
                  How would you rate your guide's knowledge of the area?
                </span>
                <div className="h-auto w-full pl-7 sm:pl-0 overflow-x-auto">
                  {areaKnowledge?.map((knowledge, i) => (
                    <CustomRadio
                      key={knowledge.id}
                      label={knowledge.name}
                      name="knowledge_id"
                      value={knowledge.id}
                      checked={data.feedback[selected].knowledge_id == knowledge.id.toString()}
                      onChange={handleIndemnityChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <div className="flex items-center justify-center gap-6">
                  <button type='button' onClick={() => prevStep(selected)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                    <span className="text-btn-outline-gray">Prev</span>

                  </button>
                  <button type='button' onClick={() => nextStep(selected)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                    <span className="text-btn-outline-gray">Next</span>
                  </button>
                </div>
              </div>
            </div>
          )}
          {/* comfortable and safe */}
          {currentSlug === "comfort_id" && (
            <div className="flex flex-col w-[90%] sm:w-full h-full mb-[26px]">

              <div className="flex flex-col w-full h-[90%] gap-2  ">
                <span className="text-welcome-card-title text-center sm:w-full px-7 sm:px-[39px] mt-[42px] mb-[26px]">
                  Did you feel comfortable and safe with your guides?
                </span>

                <div className="h-auto w-full pl-7 sm:pl-0 overflow-x-auto ">
                  {comfortSafety?.map((comfort, i) => (
                    <CustomRadio
                      key={comfort.id}
                      label={comfort.name}
                      name="comfort_id"
                      value={comfort.id}
                      checked={data.feedback[selected].comfort_id == comfort.id.toString()}
                      onChange={handleIndemnityChange}
                      errors={errors}
                    />
                  ))}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <div className="flex items-center justify-center gap-6">
                  <button type='button' onClick={() => prevStep(selected)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                    <span className="text-btn-outline-gray">Prev</span>

                  </button>
                  <button type='button' onClick={() => nextStep(selected)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                    <span className="text-btn-outline-gray">Next</span>
                  </button>
                </div>
              </div>
            </div>
          )}
          {/* comments */}
          {currentSlug === "comment" && (
            <div className="flex flex-col w-[90%] h-full mb-[26px]">

              <div className="flex flex-col w-full h-[90%] gap-2  ">
                <span className="text-welcome-card-title text-center w-full px-7 sm:px-[39px] mt-[42px] mb-[26px]">
                  Comments or Suggestions
                </span>
                <div className="h-auto w-full pl-7 sm:px-[39px] overflow-x-auto">
                  <CustomInputText
                    title="Any comments, suggestions or cool quotes?"
                    name="comment"
                    value={data.feedback[selected].comment}
                    height="h-[52px]"
                    type="text"
                    mode={mode}
                    onChange={(e) => handleIndemnityChange(e.target.name, e.target.value)}
                  />
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <div className="flex items-center justify-center gap-6">
                  <button type='button' onClick={() => prevStep(selected)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                    <span className="text-btn-outline-gray">Prev</span>

                  </button>
                  <button type='button' onClick={() => nextStep(selected)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
                    <span className="text-btn-outline-gray">Next</span>
                  </button>
                </div>
              </div>
            </div>
          )}
          {/* submit */}
          {currentSlug === "submit" && (
            <div className="flex flex-col w-[90%] h-full mb-[26px] ">
              <div className="flex flex-col w-full h-[80%] overflow-x-auto no-scrollbar gap-2 mb-2" >
                <span className="text-welcome-card-title text-center w-full mt-[42px] px-6 sm:px-[39px] ">
                  Your Feedback is ready to submit
                </span>
                <span className=" text-center[90%] mb-[26px]">
                  <span className='block' ><span className='text-booking-heading capitalize'>booking reference</span> {booking?.reference}</span>
                  <span className='block'><span className='text-booking-subheading capitalize'>Service</span> {bookingItem?.service?.name}</span>
                </span>
                <div className="flex items-center justify-center h-[90%]" >
                  <div className="grid grid-cols-1 sm:grid-cols-1 gap-4 h-full pl-7 sm:px-[12px] ">
                    <span className='flex flex-col items-start gap-2'><span className='text-booking-subheading capitalize'>full name</span> {data.feedback[selected]?.full_name}</span>
                    <span className='flex flex-col items-start gap-2'><span className='text-booking-subheading capitalize'>lead guide</span> {leadGuides?.find(g => g.id == data.feedback[selected]?.guide_id)?.name}</span>
                    <span className='flex flex-col items-start gap-2'><span className='text-booking-subheading capitalize'>Were you well received upon arrival?</span> {arrivals?.find(g => g.id == data.feedback[selected]?.reception_id)?.name}</span>
                    <span className='flex flex-col items-start gap-2'><span className='text-booking-subheading capitalize'>How was the safety briefing?</span> {safetyBrifings?.find(g => g.id == data.feedback[selected]?.briefing_id)?.name}</span>
                    <span className='flex flex-col items-start gap-2'><span className='text-booking-subheading capitalize'>So how about that 4x4 drive?</span> {drive?.find(r => r.id == data.feedback[selected]?.experience_id)?.name}</span>
                    <span className='flex flex-col items-start gap-2'><span className='text-booking-subheading capitalize'>How would you rate your guide's knowledge of the area?</span> {areaKnowledge?.find(c => c.id == data.feedback[selected]?.knowledge_id)?.name}</span>
                    <span className='flex flex-col items-start gap-2'><span className='text-booking-subheading capitalize'>Did you feel comfortable and safe with your guides?</span> {comfortSafety?.find(c => c.id == data.feedback[selected]?.comfort_id)?.name}</span>
                    <span className='flex flex-col items-start gap-2'><span className='text-booking-subheading capitalize'>Any comments, suggestions or cool quotes?</span> {data.feedback[selected]?.comment}</span>
                    <span className='flex flex-col items-start gap-2'><span className='text-booking-subheading capitalize'>Thanks! We look forward to hanging out with you soon!</span></span>
                  </div>
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => prevStep(selected)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button
                  type='button'
                  onClick={(e) => handleSubmit(e)}
                  disabled={processing}

                  className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
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
