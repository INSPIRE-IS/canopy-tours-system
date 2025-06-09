// resources/js/Pages/BookingLookup.jsx
import React, { useState, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import CustomButton from '@/Components/CustomButton';
import CustomCheckboxQ from '@/Components/CustomCheckboxQ';

export default function BookingLookup() {
    const { data, setData, post, put, errors, reset, processing } = useForm({
        service_id: "",
        season_name: "",
        start_date: "",
        end_date: "",
        rake_rate_adult: "",
        rake_rate_child: "",
        rake_rate_guide: "",
        allow_internal_price_override: 0,
        day_slots: [1,2,3,4,5,6,7],
        time_slots: [],
        capacity: "",
        allow_internal_override: "",
        status: 0,
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
        axios.get(`/countries/${selectedContinent}`).then(res => setCountries(res.data));
    }
    }, [selectedContinent]);

    // When country changes
    useEffect(() => {
    if (selectedCountry) {
        axios.get(`/states/${selectedCountry}`).then(res => setStates(res.data));
    }
    }, [selectedCountry]);



    useEffect(() => {

            // setData({
            //     service_id: inventory.service_id || "",
            //     season_name: inventory.season_name || "",
            //     start_date: inventory.start_date || "",
            //     end_date: inventory.end_date || "",
            //     rake_rate_adult: inventory.end_date || "",
            //     rake_rate_child: inventory.end_date || "",
            //     rake_rate_guide: inventory.end_date || "",
            //     allow_internal_price_override: inventory.end_date || 0,
            //     day_slots: inventory.day_slots || [1,2,3,4,5,6,7],
            //     time_slots: inventory.day_slots || [],// Load existing time_slots
            //     capacity: inventory.capacity || "",
            //     allow_internal_override: inventory.allow_internal_override || "",
            //     status: inventory.status || "",
            //     updated_by_id: inventory.updated_by_id || "",
            //     //roles: inventory.roles.map((role) => role.id),

            // });

    }, []);

    const handleChange = (target, value) => {
        setData(target, value);
        //setData("name", e.target.value);
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
      }else{
          alert('Please enter a booking ID');
      }
  };

  return (
    <>
      <Head title="Find Your Booking" />
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
            <div className="flex flex-col bg-white items-center justify-start md:w-[1109px] w-full shadow-lg rounded-[20px]">


              <h2 className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                Select your Lead Guide
              </h2>

                <div className="flex rounded-[10px] border border-[#F1F1F5] w-[672px] h-[60px]  ">
                    <CustomCheckboxQ
                        label="Allow Internal Time Slot Override"
                        name="allow_internal_override"
                        checked={data.allow_internal_override}
                        onChange={handleChange}
                        errors={errors}
                    />
                </div>
              <h2 className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                Select your Safety Guide
              </h2>

              <h2 className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                What kind of a group are you with?
              </h2>


              <h2 className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                Please enter your First and Last name
              </h2>


              <h2 className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                Please enter your Date of Birth
              </h2>




              <h2 className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                Where did you hear about us
              </h2>

              <h2 className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                Where are you from?
              </h2>
                <select onChange={e => setSelectedContinent(e.target.value)}>
                <option value="">Select Continent</option>
                {continents.map(c => (
                    <option key={c.id} value={c.id}>{c.name}</option>
                ))}
                </select>

                <select onChange={e => setSelectedCountry(e.target.value)}>
                <option value="">Select Country</option>
                {countries.map(c => (
                    <option key={c.id} value={c.id}>{c.name}</option>
                ))}
                </select>

                <select>
                <option value="">Select State/Province</option>
                {states.map(s => (
                    <option key={s.id} value={s.id}>{s.name}</option>
                ))}
                </select>

              <h2 className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                Select your province
              </h2>

              <h2 className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                Dou you have any medical conditions we should be aware of?
              </h2>
              <h2 className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                I am fit enough to walk 1km in steep terrain
              </h2>
                            <h2 className="text-welcome-card-title w-[544px] mt-[42px] mb-[26px] px-[39px]">
                Please agree to the following terms and conditions
              </h2>
              <p className="text-welcome-card-title-sub w-[856px] mb-[28px]">
By accessing and using this website, you acknowledge and agree to the terms outlined in this Indemnity Agreement. This form serves to protect the website owners, administrators, and affiliated parties from any liability arising from the use of the site, its content, or any services provided. By proceeding, you confirm that you understand and accept full responsibility for any risks associated with your use of this website and waive any claims against its owners for potential losses, damages, or liabilities.
              </p>



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

            </div>
      </div>


    </>
  );
}
