// resources/js/Pages/BookingLookup.jsx
import React, { useState, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import CustomButton from '@/Components/CustomButton';
import CustomCheckboxQ from '@/Components/CustomCheckboxQ';
import CustomInputText from '@/Components/CustomInputText';
import CustomSelect from '@/Components/CustomSelect';
import CustomRadio from '@/Components/CustomRadio';

export default function BookingLookup({ auth, bookingId, leadGuides, safetyGuides, typeGroup, typeReferral, typeConfirmation }) {
  console.log("auth", auth);
  const mode = 'create';
  console.log("typeGroup", typeGroup);
  console.log("typeConfirmation", typeConfirmation);
  const [prevNext, setPrevNext] = useState(1);
  const { data, setData, post, put, errors, reset, processing } = useForm({
    booking_id: bookingId,
    lead_guide_id: "",
    safety_guide_id: "",
    group_id: "",
    full_name: "",
    birth_date: "",
    referral_id: "",
    continent_id: "",
    country_id: "",
    province_id: "",
    medical_id: "",
    medical_detail: "",
    is_fit: "",
    is_agree: "",
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
      alert('selectedContinent');
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
    setData(target, value);
    //setData("name", e.target.value);
    console.log('data', data);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    alert(`Searching booking for ID: ${data.booking_id}`);
    if (data.booking_id) {
      get(route("questionnaire.create"), {
        onSuccess: () => {
          reset();
          onClose();
        },
      });
    } else {
      alert('Please enter a booking ID');
    }
  };

  return (
    <>
      <Head title="Find Your Booking" />
      <div className="flex flex-col h-screen items-center justify-center bg-secondary-100">
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
        <div className="flex flex-col bg-white h-[80%] w-[50%] items-center shadow-lg rounded-[20px]">
          {prevNext == 1 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-full gap-2" >
                <span className="text-welcome-card-title mt-[42px] mb-[26px] px-[39px]">
                  Select your Lead Guide
                </span>
                {leadGuides?.map((staff, i) => (
                  <CustomRadio
                    key={staff.id}
                    label={staff.name}
                    name="lead_guide_id"
                    value={staff.id}
                    checked={data.lead_guide_id === staff.id.toString()}
                    onChange={handleChange}
                    errors={errors}
                  />
                ))}
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(2)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}

          {prevNext == 2 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-full gap-2" >
                <span className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                  Select your Safety Guide
                </span>
                {safetyGuides?.map((staff, i) => (
                  <CustomCheckboxQ
                    key={staff.id}
                    label={staff.name}
                    name="lead_guide_id"
                    value={staff.id}
                    checked={data.lead_guide_id}
                    onChange={handleChange}
                    errors={errors}
                  />
                ))}
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(1)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(3)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}

          {prevNext == 3 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-full gap-2" >
                <span className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                  What kind of a group are you with?
                </span>
                {typeGroup?.map((group, i) => (
                  <CustomCheckboxQ
                    key={group.id}
                    label={group.name}
                    name="group_id"
                    value={group.id}
                    checked={data.group_id}
                    onChange={handleChange}
                    errors={errors}
                  />
                ))}
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(2)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(4)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}


          {prevNext == 4 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-full gap-2" >
                <span className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                  Please enter your First and Last name
                </span>
                <CustomInputText
                  name="full_name"
                  value={data.full_name}
                  type="text"
                  mode={mode}
                  onChange={(e) => setData('full_name', e.target.value)}
                  errors={errors}
                />
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(3)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(5)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}

          {prevNext == 5 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-full gap-2" >
                <span className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                  Please enter your Date of Birth
                </span>
                <CustomInputText
                  name="full_name"
                  value={data.full_name}
                  type="date"
                  mode={mode}
                  onChange={(e) => setData('full_name', e.target.value)}
                  errors={errors}
                />
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(4)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(6)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}




          {prevNext == 6 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-full gap-2" >
                <span className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                  Where did you hear about us
                </span>
                {typeReferral?.map((referral, i) => (
                  <CustomCheckboxQ
                    key={referral.id}
                    label={referral.name}
                    name="referral_id"
                    value={referral.id}
                    checked={data.referral_id}
                    onChange={handleChange}
                    errors={errors}
                  />
                ))}
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(5)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(7)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
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
          {prevNext == 7 && (
            <div className="flex flex-col h-full mb-[26px]">
              <div className="flex flex-col w-full h-full gap-2" >
                <span className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                  Where are you from? Select your Continent
                </span>
                {continents?.map((continent, i) => (
                  <CustomCheckboxQ
                    key={continent.id}
                    label={continent.name}
                    name="continent_id"
                    value={data.continent_id}
                    checked={data.continent_id}
                    onChange={(e) => { setSelectedContinent(e.target.value); handleChange }}
                    errors={errors}
                  />
                ))}

                <CustomSelect
                  name="continent_id"
                  value={data.continent_id}
                  mode={mode}
                  onChange={(e) => { setSelectedContinent(e.target.value); setData(e.target.name, e.target.value) }}
                  error={errors.continent_id}
                  options={continents}
                />
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(6)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>
                </button>
                <button type='button' onClick={() => handlePrevNext(8)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {prevNext == 8 && (
            <div className="flex flex-col w-[670px] gap-2">
              <CustomSelect
                label="Country of Residence"
                name="country_id"
                value={data.country_id}
                mode={mode}
                onChange={(e) => { setSelectedCountry(e.target.value); setData(e.target.name, e.target.value) }}
                error={errors.country_id}
                options={countries}
              />
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(7)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(9)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}
          {prevNext == 9 && (
            <div className="flex flex-col w-[670px] gap-2">
              <CustomSelect
                label="Select Province"
                name="province_id"
                value={data.province_id}
                mode={mode}
                onChange={(e) => setData(e.target.name, e.target.value)}
                error={errors.province_id}
                options={states}
              />
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(8)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(10)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}





          {prevNext == 10 && (
            <div>
              <span className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                Dou you have any medical conditions we should be aware of?
              </span>
              <div className="flex flex-col gap-2">
                {typeConfirmation?.map((staff, i) => (
                  <CustomCheckboxQ
                    key={staff.id}
                    label={staff.name}
                    name="lead_guide_id"
                    value={staff.id}
                    checked={data.lead_guide_id}
                    onChange={handleChange}
                    errors={errors}
                  />
                ))}
              </div>
              <CustomInputText
                title="Please let us know what medical conditions you have and confirm that your doctor 
                                    has agreed that you may partake in this activity?"
                name="medical_detail"
                value={data.medical_detail}
                height="h-[52px]"
                type="text"
                mode={mode}
                onChange={(e) => setData(e.target.name, e.target.value)}
                errors={errors}
              />
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(9)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(11)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>
            </div>
          )}

          {prevNext == 11 && (
            <div>
              <span className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                I am fit enough to walk 1km in steep terrain
              </span>
              <div className="flex flex-col gap-2">
                {typeConfirmation?.map((staff, i) => (
                  <CustomCheckboxQ
                    key={staff.id}
                    label={staff.name}
                    name="lead_guide_id"
                    value={staff.id}
                    checked={data.lead_guide_id}
                    onChange={handleChange}
                    errors={errors}
                  />
                ))}
              </div>
              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(10)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
                <button type='button' onClick={() => handlePrevNext(12)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                  <span className="text-btn-outline-gray">Next</span>
                </button>
              </div>

            </div>
          )}

          {prevNext == 12 && (
            <div>
              <span className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                Please agree to the following terms and conditions
              </span>
              <p className="text-welcome-card-title-sub w-[856px] mb-[28px]">
                By accessing and using this website, you acknowledge and agree to the terms outlined in this Indemnity Agreement. This form serves to protect the website owners, administrators, and affiliated parties from any liability arising from the use of the site, its content, or any services provided. By proceeding, you confirm that you understand and accept full responsibility for any risks associated with your use of this website and waive any claims against its owners for potential losses, damages, or liabilities.
              </p>

              <div className="flex items-center justify-center gap-6">
                <button type='button' onClick={() => handlePrevNext(11)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                  <span className="text-btn-outline-gray">Prev</span>

                </button>
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
          )}
          <div>
          </div>
        </div>
      </div>
    </>
  );
}
