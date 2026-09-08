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

const DEFAULT_INDEMNITY_DOCUMENT = {
  title: 'Digital Indemnity / Disclaimer / Waiver',
  definitions: [
    'In this document "Canopy Tours" means and includes the operating company, site operator, land/property owners, holding and subsidiary companies, affiliates, employees, directors, members, agents, shareholders, contractors, and representatives connected to the activity.',
    'The "activity" means and includes Canopy Tours and all related activities, whether directly or indirectly, including but not limited to use of the cableway, climbing up and down platforms, walking along pathways, and being conveyed by any motor vehicle.',
  ],
  terms: [
    'I acknowledge that I am aware of and appreciate the real dangers and risks associated with the activity, including that the activity may take place at considerable height and speed above the ground, where falling is an inherent danger. I also acknowledge risks including reptiles, insects, bodily harm, injury, and death. I accept and acknowledge that I participate in the activity entirely at my own risk.',
    'I understand that I need to be in good health to participate in the activity. I declare that I am in good health, that I am not pregnant, and that I am prepared to act responsibly at all times.',
    'I waive any and all claims of whatsoever cause or nature, howsoever arising against Canopy Tours, which I might have arising out of any harm, injury, death, or loss suffered while participating in, or related directly or indirectly to, the activity, whether arising from an act, omission, or negligence on the part of Canopy Tours.',
    'I undertake to indemnify and hold harmless Canopy Tours from any and all claims of whatsoever cause or nature which may be made by my spouse, children, dependants, or any other party who may suffer damages, including but not limited to personal injury, death, loss of support, or any other loss, arising from any cause whatsoever, including any act, omission, or negligence on the part of Canopy Tours.',
    'Without limiting the above, I acknowledge that Canopy Tours shall not be liable to me or any of my dependants for any injury, illness, death, harm, loss, loss of support, consequential loss, damage, or damages of whatsoever nature from any cause whatsoever that may be sustained as a result of my participation in the activity, including negligence.',
    'Any claims, whether in contract, delict, or otherwise, instituted against Canopy Tours shall be governed by the laws of South Africa and may only be instituted in a South African court, to the exclusion of all other jurisdictions.',
    'Without limiting the above clauses, I, together with my heirs, successors in title, executors, and administrators, release Canopy Tours from any and all liability and/or claims that could accrue to me or them due to illness, injury, death, and/or loss of or damage to property, irrespective of whether such claim or claims arose as a result of any act or omission, whether by negligence or gross negligence, or any other cause whatsoever.',
    'In the event of injury, Canopy Tours may at its discretion and without prejudice or admission of liability arrange and pay for emergency medical treatment for and on behalf of myself, my spouse, common-law partner, my children whether minor or adult, relatives, and/or any person accompanying me. I acknowledge that I will be directly responsible to reimburse Canopy Tours for these expenses.',
    'I allow Canopy Tours to use any pictures and/or video taken of me for any purposes they wish, including marketing, promotions, and social media.',
    'I agree not to smoke or light any flame whatsoever for the duration of the tour, from the time I leave reception for the tour until I return to reception after the tour, including the drive up to the start of the zip-line.',
  ],
};

const IndemnityTermsPreview = ({ document = DEFAULT_INDEMNITY_DOCUMENT, compact = false }) => (
  <div className={`rounded-xl border border-gray-200 bg-white text-left text-gray-700 ${compact ? 'max-h-64 overflow-y-auto p-4 text-xs' : 'p-5 text-sm'}`}>
    <h3 className="mb-3 text-center font-semibold text-gray-900">{document.title}</h3>
    <div className="space-y-3">
      {(document.definitions || []).map((definition, index) => (
        <p key={`definition-${index}`}>{definition}</p>
      ))}
      <ol className="list-decimal space-y-3 pl-5">
        {(document.terms || []).map((term, index) => (
          <li key={`term-${index}`}>{term}</li>
        ))}
      </ol>
    </div>
  </div>
);

export default function BookingLookup({ auth, booking, bookings, bookingItem, questions = [], leadGuides, safetyGuides, typeGroups, ageGroups, typeGender, typeReferral, typeConfirmation, indemnityDocument = DEFAULT_INDEMNITY_DOCUMENT, canSendIndemnityEmail = true, team }) {
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
  const KIOSK_STORAGE_KEY = 'checkin_kiosk_url';
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

  const { data, setData, post, put, errors, reset, processing, isDirty, transform } = useForm({
    customer_manifests: [],
    return_to: null,
    send_indemnity_email: false,
    signing_place: old?.signing_place ?? team?.name ?? '',
  });

  const markSelectedManifest = (manifests, selectedIndex) => (
    (manifests || []).map((manifest, index) => ({
      ...manifest,
      index: index === selectedIndex ? 1 : 0,
    }))
  );

  useEffect(() => {
    if (mode === "edit" && bookings?.customers?.length) {
      if (old?.customer_manifests?.length) {
        const restored = old.customer_manifests;
        const selectedIndex = restored.findIndex(p => p.index == 1);
        const normalized = selectedIndex >= 0 ? markSelectedManifest(restored, selectedIndex) : restored;
        setData('customer_manifests', normalized);
        const restoredAdults = normalized
          .filter(p => p.type === "adult" && p.full_name != "")
          .map((p, i) => ({
            id: typeof p.customer_id == 'number' ? p.customer_id : i + 1,
            name: p.full_name || "Unnamed Adult"
          }));
        setAdults(restoredAdults);
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
      const params = new URLSearchParams(window.location.search);
      const targetCustomerId = params.get('customer');
      let selectedIndex = -1;
      if (targetCustomerId) {
        selectedIndex = mapped.findIndex((person) => String(person.customer_id) === String(targetCustomerId));
        if (selectedIndex >= 0) {
          setSelected(selectedIndex);
        }
      }
      setData('customer_manifests', selectedIndex >= 0 ? markSelectedManifest(mapped, selectedIndex) : mapped);
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
    const returnTo = params.get('return_to');

    setData('return_to', returnTo || null);

    if (returnTo === 'kiosk') {
      const fallbackKioskUrl = route('admin.checkins.kiosk');
      if (!localStorage.getItem(KIOSK_STORAGE_KEY)) {
        localStorage.setItem(KIOSK_STORAGE_KEY, fallbackKioskUrl);
      }
    }

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
        ...(returnTo ? { return_to: returnTo } : {}),
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

  const returnToKiosk = () => {
    const kioskUrl = localStorage.getItem(KIOSK_STORAGE_KEY) || route('admin.checkins.kiosk');
    window.location.href = kioskUrl;
  };

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
      onConfirm: () => data.return_to === 'kiosk' ? returnToKiosk() : router.get(route('front.welcome')),
    });
  };

  const handleCancelParticipantQuestionnaire = () => {
    setPrevNext(1);
    setSelected(null);
    setData((current) => ({
      ...current,
      customer_manifests: markSelectedManifest(current.customer_manifests, null),
    }));
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
            setData((current) => ({
              ...current,
              customer_manifests: markSelectedManifest(current.customer_manifests, null),
            }));
          },
        });
        return;
      }
      setPrevNext(1);
      setSelected(null);
      setData((current) => ({
        ...current,
        customer_manifests: markSelectedManifest(current.customer_manifests, null),
      }));
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
      setData((current) => ({
        ...current,
        customer_manifests: markSelectedManifest(current.customer_manifests, index),
      }));
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
  const confirmationLabel = (value) => resolveOptionLabel(typeConfirmation, value) || '-';
  const formatDisplayDate = (value) => value ? String(value).slice(0, 10) : '-';
  const formatDisplayTime = (value) => value ? String(value).slice(0, 5) : '-';

  const questionStepsBase = [1, 2, 3, 6, 7, 8, 9, 10, 11, 12, 13, 14];
  let questionSteps = isEnabled('age_id')
    ? questionStepsBase
    : questionStepsBase.filter((step) => step !== 2);
  if (!hasLunch) {
    questionSteps = questionSteps.filter((step) => step !== 12);
  }

  const selectedManifest = selected != null ? data.customer_manifests?.[selected] : null;
  const isMinorParticipant = String(selectedManifest?.age_id ?? '') === '1' || selectedManifest?.type === 'child';
  const activityDate = bookingItem?.booking_inventory?.date ?? bookingItem?.booking_inventory?.activity_date ?? null;
  const activityTime = bookingItem?.booking_inventory?.inventory_time_slot?.start_time ?? bookingItem?.booking_inventory?.time ?? null;
  const declarationRows = [
    { label: 'Medical condition', value: isEnabled('medical_id') ? confirmationLabel(selectedManifest?.medical_id) : 'Not captured' },
    { label: 'Medical details', value: isEnabled('medical_detail') ? (selectedManifest?.medical_detail || '-') : 'Not captured' },
    { label: 'Fit to walk', value: isEnabled('is_fit') ? confirmationLabel(selectedManifest?.is_fit) : 'Not captured' },
    {
      label: 'Pregnancy',
      value: isEnabled('pregnant')
        ? (String(selectedManifest?.gender ?? '') === '2' ? confirmationLabel(selectedManifest?.pregnant) : 'Not applicable')
        : 'Not captured',
    },
    {
      label: 'Parent / guardian consent',
      value: isMinorParticipant
        ? (selectedManifest?.parent_name || selectedManifest?.guardian_name || selectedManifest?.parent_id || 'Required but not captured as a signed statement')
        : 'Not applicable',
    },
    { label: 'Photo / video consent', value: 'Included in point 9 - confirm if this should be separate' },
  ];
  const selectedEmail = selectedManifest?.email_address?.trim() || '';
  const signingPlace = data.signing_place?.trim() || team?.name || 'Canopy Tours';
  const signatureLabel = isMinorParticipant ? 'Parent / guardian signature' : 'Signature';
  const signingDate = new Intl.DateTimeFormat('en-ZA', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(new Date());
  const capturedAnswerRows = [
    { label: 'Type group', value: resolveOptionLabel(typeGroups, selectedManifest?.type_group_id) },
    { label: 'Full name', value: selectedManifest?.full_name },
    { label: 'Age', value: resolveOptionLabel(ageGroups, selectedManifest?.age_id) },
    { label: 'Referral', value: resolveOptionLabel(typeReferral, selectedManifest?.referral_id) },
    { label: 'Continent', value: resolveOptionLabel(continents, selectedManifest?.continent_id) },
    { label: 'Country', value: resolveOptionLabel(countries, selectedManifest?.country_id) },
    { label: 'Province', value: resolveOptionLabel(states, selectedManifest?.province_id) },
    ...(hasLunch ? [{ label: 'Lunch option', value: resolveOptionLabel(lunchOptions, selectedManifest?.lunch_option) }] : []),
    ...declarationRows,
  ];
  const sendIndemnityEmailBlocked = Boolean(data.send_indemnity_email) && (!selectedEmail || !canSendIndemnityEmail);
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
  const isPregnancyBlocked = String(selectedManifest?.gender ?? '') === '2' && String(selectedManifest?.pregnant ?? '') === '1';
  const isFitBlocked = String(selectedManifest?.is_fit ?? '') === '2';
  const isStep10Disabled = !selectedManifest?.gender || (selectedManifest?.gender == "2" && !selectedManifest?.pregnant) || isPregnancyBlocked;
  const isStep11Disabled = !selectedManifest?.is_fit || isFitBlocked;
  const isStep12Disabled = hasLunch && !selectedManifest?.lunch_option;
  const isStep13Disabled = selectedManifest?.is_agree !== true;
  const isStep14Disabled = !selectedManifest?.signature;
  const isSubmitDisabled = processing || sendIndemnityEmailBlocked;

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
    setHasUserChanges(true);
    setData((current) => {
      const updated = (current.customer_manifests || []).map((person, idx) => (
        idx === index ? { ...person, [field]: value } : person
      ));
      console.log('-------handle Person Change--------', updated);
      return { ...current, customer_manifests: updated };
    });
  };

  const handleIndemnityChange = (field, value) => {
    if (selected != null) {
      setHasUserChanges(true);
      setData((current) => {
        const updated = (current.customer_manifests || []).map((person, index) => {
          if (index !== selected) return person;

          const nextPerson = { ...person, [field]: value, index: 1 };
          if (field === 'medical_id' && String(value) !== '1') {
            nextPerson.medical_detail = '';
          }
          if (field === 'gender' && String(value) !== '2') {
            nextPerson.pregnant = '';
          }

          return nextPerson;
        });
        console.log('-------handle Indemnity Change--------', updated);
        return { ...current, customer_manifests: markSelectedManifest(updated, selected) };
      });

      if (field == "continent_id") {
        setSelectedContinent(value);
      }
      if (field == "country_id") {
        setSelectedCountry(value);
      }
      if (field == "province_id") {
        setSelectedState(value);
      }
    }
  };


  const handleSubmit = (e) => {
    e.preventDefault();
    console.log('booking', booking);
    console.log('handle Submit', data.customer_manifests);
    transform((payload) => {
      const selectedManifest = payload.customer_manifests?.[selected] ?? null;
      return {
        ...payload,
        selected_customer_id: selectedManifest?.customer_id ?? null,
        selected_booking_item_id: selectedManifest?.booking_item_id ?? null,
        customer_manifests: markSelectedManifest(payload.customer_manifests, selected),
      };
    });
    post(route('booking-questionnaire.store'), {
      onFinish: () => transform((payload) => payload),
    });
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
                    {isPregnancyBlocked && (
                      <div className="mx-6 sm:mx-[39px] rounded-xl border border-red-200 bg-red-50 p-4 text-center text-sm text-red-700">
                        This activity cannot proceed for guests who are pregnant. Please urgently discuss this with staff before proceeding with the questionnaire.
                        <button
                          type="button"
                          onClick={handleCancelParticipantQuestionnaire}
                          className="ml-1 font-semibold underline"
                        >
                          Cancel questionnaire
                        </button>
                      </div>
                    )}
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
                {isFitBlocked && (
                  <div className="mx-6 sm:mx-[39px] rounded-xl border border-red-200 bg-red-50 p-4 text-center text-sm text-red-700">
                    This activity cannot proceed if you are not fit enough to walk 1km in steep terrain. Please urgently discuss this with staff before proceeding with the questionnaire.
                    <button
                      type="button"
                      onClick={handleCancelParticipantQuestionnaire}
                      className="ml-1 font-semibold underline"
                    >
                      Cancel questionnaire
                    </button>
                  </div>
                )}
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
                <div className="w-full mb-[8px] px-6 sm:px-[39px]">
                  <IndemnityTermsPreview document={indemnityDocument} compact />
                </div>
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
                <div className="flex flex-col gap-4 px-6 sm:px-[12px] mb-4">
                  <IndemnityTermsPreview document={indemnityDocument} />
                  <div className="rounded-xl border border-gray-200 bg-white p-5 text-left text-sm text-gray-700">
                    <h3 className="mb-3 text-center font-semibold text-gray-900">Captured Digital Acceptance</h3>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <span><strong>Full name:</strong> {selectedManifest?.full_name || '-'}</span>
                      <span><strong>Email:</strong> {selectedManifest?.email_address || '-'}</span>
                      <span><strong>Booking reference:</strong> {booking?.reference || '-'}</span>
                      <span><strong>Service:</strong> {bookingItem?.service?.name || '-'}</span>
                      <span><strong>Activity date:</strong> {formatDisplayDate(activityDate)}</span>
                      <span><strong>Activity time:</strong> {formatDisplayTime(activityTime)}</span>
                      <span><strong>Signing date:</strong> {signingDate}</span>
                      <span><strong>Signed at:</strong> {signingPlace}</span>
                      <span><strong>Agreement:</strong> {selectedManifest?.is_agree ? 'Accepted' : 'Not accepted'}</span>
                    </div>
                    <div className="mt-4">
                      <label className="text-booking-subheading capitalize">Signed at</label>
                      <input
                        type="text"
                        value={data.signing_place}
                        onChange={(event) => setData('signing_place', event.target.value)}
                        className="mt-1 block w-full h-[48px] border-gray-300 shadow-sm input-text rounded-[10px]"
                        placeholder={team?.name || 'Canopy Tours'}
                      />
                    </div>
                    <div className="mt-4">
                      <h4 className="mb-2 font-semibold text-gray-900">{signatureLabel}</h4>
                      {selectedManifest?.signature ? (
                        <img src={selectedManifest.signature} alt={`${signatureLabel} preview`} className="border rounded-lg bg-white w-64 h-auto" />
                      ) : (
                        <span className="text-booking-subheading capitalize">Signature Not Found</span>
                      )}
                    </div>
                    <h4 className="mt-4 mb-2 font-semibold text-gray-900">Check-in Answers / Declarations</h4>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      {capturedAnswerRows.map((row) => (
                        <span key={row.label}><strong>{row.label}:</strong> {row.value || '-'}</span>
                      ))}
                    </div>
                  </div>
                  {canSendIndemnityEmail && (
                    <div className="rounded-xl border border-gray-200 bg-white p-4 text-left text-sm text-gray-700">
                      <CustomCheckbox
                        label="Email me a copy of the signed indemnity PDF"
                        name="send_indemnity_email"
                        checked={Boolean(data.send_indemnity_email)}
                        onChange={(name, value) => setData(name, value)}
                        errors={errors}
                      />
                      <label className="text-booking-subheading capitalize">Email address</label>
                      <input
                        type="email"
                        value={selectedEmail}
                        readOnly
                        className="mt-1 block w-full h-[48px] border-gray-300 shadow-sm input-text rounded-[10px] bg-gray-50"
                        placeholder="No email captured"
                      />
                      {Boolean(data.send_indemnity_email) && !selectedEmail && (
                        <p className="mt-2 text-sm text-red-600">An email address is required when the email copy option is selected.</p>
                      )}
                      {errors.send_indemnity_email && (
                        <p className="mt-2 text-sm text-red-600">{errors.send_indemnity_email}</p>
                      )}
                    </div>
                  )}
                </div>
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' disabled={isStep14Disabled} onClick={() => handlePrevNext(14, selected)} className={getNextButtonClass(isStep14Disabled)} >
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button
                  type='button'
                  onClick={(e) => handleSubmit(e)}
                  disabled={isSubmitDisabled}

                  className={`flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] ${isSubmitDisabled ? 'border-primary-14 text-primary-14 cursor-not-allowed opacity-50' : 'bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer'}`}>
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
