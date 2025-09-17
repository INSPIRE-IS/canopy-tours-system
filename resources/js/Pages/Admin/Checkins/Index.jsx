import Header from '@/Components/Header';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';
import { icons } from '../../../icons';
import CustomInputText from '@/Components/CustomInputText';
import BlueButton from '@/Components/BlueButton';
import WhiteButton from '@/Components/WhiteButton';
import CustomSelect from '@/Components/CustomSelect';
import ManifestCount from '@/Components/ManifestCount';
import CustomTimePicker from '@/Components/CustomTimePicker';
import axios from 'axios';
import { set } from 'date-fns';

export default function Dashboard({ auth, booking, booking_item, staff, typeGroup, typeReferral, typeConfirmation  }) {
    console.log("auth", auth);
    console.log("booking", booking);
    console.log("booking_item", booking_item);
    console.log("staff", staff);
    const [mode, setMode] = useState('create');
    const [activePerson, setActivePerson] = useState(null); 
    const [prevNext, setPrevNext] = useState(1);

    const [continents, setContinents] = useState([]);
    const [countries, setCountries] = useState([]);
    const [states, setStates] = useState([]);
    
    const [selectedContinent, setSelectedContinent] = useState(null);
    const [selectedCountry, setSelectedCountry] = useState(null);

    const { data, setData,post, put, isDirty, reset, processing, errors } = useForm(()=>
        {
            return {
            service_id: '',
            season_id: '',
            booking_id: '',
            customer_id: '',
            team_id: '',
            is_lead: '',
            first_name: '',
            email_address: '',           
            contact_number: '',
            identity_number: '',
            nationality_id: '',
            continent_id: '',
            country_id: '',
            province_id: '',
            date_of_birth: '',
            pully_number: '',
            gender_id:  '',
            type_group_id:  '',
            recieve_invite:  '',
            medical_detail:  '',
            helmet_size:  '',
            is_fit:  '',
            safety_guide_id:  '',
            lead_guide_id:  '',
            is_minor: '',
            children: [],
            }   
        }     
    );


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
    if (activePerson) {
        handlePrevNext(2);
        console.log('activePerson', activePerson);
        const manifest = activePerson ?? null;
        const customer = manifest?.customer ?? null;
        const questionnaire = customer?.booking_questionnaire ?? null;
        

        setData({
            service_id: booking_item.service_id,
            season_id: booking_item.season_id,
            booking_id: booking.id,
            booking_item_id: manifest?.booking_item_id,
            customer_id: customer?.id ?? '',
            team_id: booking_item.service.team_id,
            is_lead: booking_item?.customers.find(c => c?.pivot?.is_lead)?.id == customer?.id ? 1 : 0,
            first_name: customer?.full_name ?? '',
            email_address: customer?.email_address ?? '',            
            contact_number: customer?.contact_number ?? '',
            identity_number: questionnaire?.identity_number ?? '',
            nationality_id: customer?.nationality_id ?? '',
            continent_id: customer?.continent_id ?? '',
            country_id: customer?.country_id ?? '',
            province_id: customer?.province_id ?? '',
            // city: lead?.city ?? '',
            date_of_birth: customer?.date_of_birth ?? '',
            pully_number: questionnaire?.pully_number ?? '',
            gender_id: questionnaire?.gender_id ?? '',
            type_group_id: questionnaire?.type_group_id ?? '',
            recieve_invite: questionnaire?.recieve_invite ?? '',
            medical_detail: questionnaire?.medical_detail ?? '',
            helmet_size: questionnaire?.helmet_size ?? '',
            is_fit: questionnaire?.is_fit ?? '',
            safety_guide_id: questionnaire?.safety_guide_id ?? '',
            lead_guide_id: questionnaire?.lead_guide_id ?? '',
            is_minor: questionnaire?.is_minor ?? '',
            children: questionnaire?.children ?? [],
        })
    }
    }, [activePerson]);

    const handleAddPerson = (type) => {
        const adultsCount = booking_item?.customer_manifests?.filter(p => p.type === "adult").length;
        const childrenCount = booking_item?.customer_manifests?.filter(p => p.type === "child").length;

        if (type === "adult" && adultsCount >= booking_item.pax_adults) return;
        if (type === "child" && childrenCount >= booking_item.pax_children) return;
        handlePrevNext(2);
        setData({
            service_id: booking_item.service_id,
            season_id: booking_item.season_id,
            booking_id: booking.id,
            customer_id: '',
            team_id: booking_item.service.team_id,
            is_lead: '',
            first_name: '',
            email_address: '',            
            contact_number: '',
            identity_number: '',
            nationality_id: '',
            continent_id: '',
            country_id: '',
            province_id: '',
            date_of_birth: '',
            pully_number: '',
            gender_id: '',
            type_group_id: '',
            recieve_invite: '',
            medical_detail: '',
            helmet_size: '',
            is_fit: '',
            safety_guide_id: '',
            lead_guide_id: '',
            is_minor: '',
            children: [],
        })
    };


    const handlePrevNext = (number) => {
        console.log('number', number);
        setPrevNext(number);
        console.log('data', data);
    };

    const addChild = () => {
        setData('children', [...data.children, { child_name: '', child_age: '' }]);
    };

    const removeChild = (index) => {
        const updated = data.children.filter((_, i) => i !== index);
        setData('children', updated);
    };

    const updateChild = (index, field, value) => {
        const updated = [...data.children];
        updated[index][field] = value;
        setData('children', updated);
    };


    const handleSubmit = (e) => {
        e.preventDefault();
        console.log('booking', booking);
        console.log('data', data);
        post(route('admin.checkins.store'));
        //put(route('admin.checkins.store',{booking}));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Bookings</h2>}
        >
            <Head title="Dashboard" />
            {/* <header className="h-16 bg-white  border-b border-secondary-100 rounded-r-lg p-6 py-4 flex justify-between items-center">
                            <div className="flex items-center gap-4 ">
                                <div className={`icon-open flex md:hidden  items-center justify-between px-1 py-1 bg-secondary-100  relative rounded-[10px]`}>
                                    <button onClick={()=>{}} className="flex w-auto h-auto hover:bg-gray-200 rounded-sm transition cursor-pointer">
                                        <img src="/assets/icons/sidebar-left.svg" alt="My Icon" className="w-5 h-5" />         
                                    </button>
                                </div>
                                <span className="font-semibold text-lg text-gray-800 capitalize">test</span>
                            </div>
                        </header> */}
            <div className="bg-white p-6 ">
                {/* top */}
                <div className="w-full grid grid-cols-1 sm:grid-cols-2 mb-6 gap-6">
                    <span className="flex w-full flex-col items-start justify-start gap-2 ">
                        <span className="font-semibold text-lg text-gray-800 block text-start">{booking_item?.service?.name} - {booking_item?.season?.name}</span>
                        <span className="font-semibold text-lg text-gray-800 block text-start">{booking?.reference} | {booking_item?.season?.start_date}</span>
                        {/* <span className="font-semibold text-lg text-gray-800 block text-start">Basket #ACTB-5287-8905</span> */}
                    </span>
                    {/* <span className="flex w-full items-start justify-start gap-2 ">
                        <span className="font-semibold text-lg text-gray-800 block text-start">Staff Assigned</span>
                    </span> */}
                    { errors?.length > 0 && (
                        errors.forEach(element => {
                            <p className="text-red-500 text-sm mt-2">
                                {element}
                            </p>
                        })
                    )}
                </div>
                {/* buttons */}
                {prevNext == 1 && (
                <div className="bg-white mt-[41px]">
                    <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">
                        {/* <button type='button' onClick={() => handlePrevNext(2)} className="flex items-center justify-between w-full h-[57px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                            <span className="text-btn-outline-gray">Add New Adult</span>
                            <span className="flex items-center justify-center w-[36px] h-[36px] rounded-xl border border-primary-14 bg-[#23978D4D] cursor-pointer">
                                {icons.plus_alt}  
                            </span>                              
                        </button> */}
<div>
  {booking_item?.customer_manifests?.filter(p => p.type === "adult").length < booking_item.pax_adults ? (
    <button
      type="button"
      onClick={() => {
        handleAddPerson("adult"); // existing add logic
        // optionally auto-select the new adult
      }}
      className="flex items-center justify-between ..."
    >
      <span>Add New Adult</span>
      <span>{icons.plus_alt}</span>
    </button>
  ) : (
    <select
      onChange={(e) => {
        const personId = e.target.value;
        const selected = booking_item?.customer_manifests?.find(p => (p.id || p.customer_id) == personId);
        setActivePerson(selected); // open questionnaire for this person
      }}
      className="w-full border rounded p-2"
    >
      <option value="">Select Adult</option>
      {booking_item?.customer_manifests?.filter(p => p.type === "adult").map((adult, i) => {
        console.log('adult', adult);
        return (
        <option key={i} value={adult.id || adult.customer_id}>
          {adult.customer.full_name || "Unnamed Adult"}
        </option>
      )})}
    </select>
  )}
</div>
<div>
  {booking_item?.customer_manifests?.filter(p => p.type === "child").length < booking_item.pax_children ? (
    <button
      type="button"
      onClick={() => handleAddPerson("child")}
      className="flex items-center justify-between ..."
    >
      <span>Add New Child (requires guardian signature)</span>
      <span>{icons.plus_alt}</span>
    </button>
  ) : (
    <select
      onChange={(e) => {
        const personId = e.target.value;
        const selected = booking_item?.customer_manifests?.find(p => (p.id || p.customer_id) == personId);
        setActivePerson(selected); // open questionnaire for this person
      }}
      className="w-full border rounded p-2"
    >
      <option value="">Select Child</option>
      {booking_item?.customer_manifests?.filter(p => p.type === "child").map((child, i) => (
        <option key={i} value={child.id || child.customer_id}>
          {child.customer.full_name || "Unnamed Child"}
        </option>
      ))}
    </select>
  )}
</div>
                        {/* <button type='button' onClick={() => handlePrevNext(2)} className="flex items-center justify-between w-full h-[57px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                            <span className="text-btn-outline-gray">Add New Child (requires guardian signature)</span>
                            <span className="flex items-center justify-center w-[36px] h-[36px] rounded-xl border border-primary-14 bg-[#23978D4D] cursor-pointer">
                                {icons.plus_alt}  
                            </span>                     
                        </button> */}
                    </div>
                </div>
                )}
                {/* customer info */}
                {prevNext == 2 && (
                <div className=" overflow-hidden shadow-medium sm:rounded-lg">
                    <div className="flex flex-col justify-between items-center ">
                        
                        <span className="flex flex-col w-full px-6 py-6 gap-2">
                                           
                            <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <CustomInputText
                                    title="Full Name*"
                                    name="full_name"
                                    value={data.full_name}
                                    type="text"
                                    mode = {mode} 
                                    onChange={(e) => setData('full_name', e.target.value)}
                                    errors={errors}
                                /> 

                                <CustomInputText
                                    title="Email Address"
                                    name="email_address"
                                    value={data.email_address}
                                    type="email"
                                    mode = {mode}   
                                    onChange={(e) => setData('email_address', e.target.value)}
                                    errors={errors}
                                />
                            </div>
                            <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <CustomInputText
                                    title="contact_number*"
                                    name="contact_number"
                                    value={data.contact_number}
                                    mode = {mode}   
                                    type="text"                            
                                    onChange={(e) => setData('contact_number', e.target.value)}
                                    errors={errors}
                                />
                                <CustomInputText
                                    title="ID or Passport Number (optional)"
                                    name="identity_number"
                                    value={data.identity_number}
                                    type="text"
                                    mode = {mode}                    
                                    onChange={(e) => setData('identity_number', e.target.value)}
                                    errors={errors}
                                />
                            </div>
                            <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <CustomSelect
                                    label="Select Continent"
                                    name="continent_id"
                                    value={data.continent_id}
                                    mode = {mode}
                                    onChange={(e) => {setSelectedContinent(e.target.value); setData(e.target.name, e.target.value)}}
                                    error={errors.continent_id}
                                    options={continents}
                                /> 
                                <CustomSelect
                                    label="Country of Residence"
                                    name="country_id"
                                    value={data.country_id}
                                    mode = {mode}
                                    onChange={(e) => {setSelectedCountry(e.target.value); setData(e.target.name, e.target.value)}}
                                    error={errors.country_id}
                                    options={countries}
                                /> 

                            </div>
                            <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <CustomSelect
                                    label="Select Province"
                                    name="province_id"
                                    value={data.province_id}
                                    mode = {mode}
                                    onChange={(e) => setData(e.target.name, e.target.value)}
                                    error={errors.province_id}
                                    options={states}
                                />                                    
                                <CustomInputText
                                    title="Date of Birth"
                                    name="birth_date"
                                    type="date"
                                    value={data.birth_date}
                                    mode = {mode}
                                    onChange={(e) => setData('birth_date', e.target.value)}
                                    errors={errors}
                                />                                                                        
                            </div>
                            <div className="flex items-center justify-center gap-6">
                                <button type='button' onClick={() => handlePrevNext(1)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                                    <span className="text-btn-outline-gray">Prev</span>
                            
                                </button>
                                <button type='button' onClick={() => handlePrevNext(3)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                                    <span className="text-btn-outline-gray">Next</span>
                                </button>
                            </div>

                        </span>
                       
                    </div>
                </div>
                )}
                {/* pully number */} 
                {prevNext == 3 && (       
                <div className=" overflow-hidden shadow-medium sm:rounded-lg">
                    <div className="flex flex-col justify-between items-center ">
                        
                        <span className="flex flex-col w-full px-6 py-6 gap-2">
                            <CustomInputText
                                title="Pully Number"
                                name="pully_number"
                                value={data.pully_number}
                                height="h-[52px]"
                                type="text"
                                mmode = {mode}   
                                onChange={(e) => setData('pully_number', e.target.value)}
                                errors={errors}
                            />        
                            <div className="flex items-center justify-center gap-6">
                                <button type='button' onClick={() => handlePrevNext(2)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                                    <span className="text-btn-outline-gray">Prev</span>
                            
                                </button>
                                <button type='button' onClick={() => handlePrevNext(4)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                                    <span className="text-btn-outline-gray">Next</span>
                                </button>
                            </div>                                                               
                        </span>
                    </div>
                </div>
                )}
                {/* booking questionaiire */}
                {prevNext == 4 && (                        
                <div className=" overflow-hidden shadow-medium sm:rounded-lg">
                    <div className="flex flex-col justify-between items-center ">

                        <span className="flex flex-col w-full px-6 py-6 gap-2">
                                           
                            <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <CustomInputText
                                title="Gender"
                                name="gender_id"
                                value={data.gender_id}
                                height="h-[52px]"
                                type="text"
                                mode = {mode}  
                                onChange={(e) => setData('gender_id', e.target.value)}
                                errors={errors}
                            /> 
                                <CustomSelect
                                    label="Did you come as a"
                                    name="type_group_id"
                                    value={data.type_group_id}
                                    height="h-[52px]"
                                    onChange={(e) => setData(e.target.name, e.target.value)}
                                    error={errors.type_group_id}
                                    options={typeGroup}
                                /> 
                            </div>
                            <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <CustomSelect
                                    label="Would you like to recieve an invite for a FREE tour on your birthday, a certificate or
                                    any promotional material from us?"
                                    name="recieve_invite"
                                    value={data.recieve_invite}
                                    height="h-[52px]"
                                    onChange={(e) => setData(e.target.name, e.target.value)}
                                    error={errors.recieve_invite}
                                    options={typeConfirmation}
                                /> 
                                <CustomInputText
                                    title="Please let us know what medical conditions you have and confirm that your doctor 
                                    has agreed that you may partake in this activity?"
                                    name="medical_detail"
                                    value={data.medical_detail}
                                    height="h-[52px]"
                                    type="text"
                                    mode = {mode}                   
                                    onChange={(e) => setData(e.target.name, e.target.value)}
                                    errors={errors}
                                />

                            </div>
                            <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <CustomInputText
                                    title="Helmet size"
                                    name="helmet_size"
                                    value={data.helmet_size}
                                    height="h-[52px]"
                                    mode = {mode}
                                    onChange={(e) => setData(e.target.name, e.target.value)}
                                    errors={errors}
                                />
                                <CustomSelect
                                    label="I confirm that I am fit enough to walk in 600m in steep terrain?"
                                    name="is_fit"
                                    value={data.recieve_invite}
                                    height="h-[52px]"
                                    onChange={(e) => setData(e.target.name, e.target.value)}
                                    error={errors.is_fit}
                                    options={typeConfirmation}
                                />
                            </div>
                            <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <CustomSelect
                                    label="Safety Guide"
                                    name="safety_guide_id"
                                    value={data.safety_guide_id}
                                    height="h-[52px]"
                                    onChange={(e) => setData(e.target.name, e.target.value)}
                                    error={errors.safety_guide_id}
                                    options={staff}
                                /> 
                                <CustomSelect
                                    label="Lead Guide"
                                    name="lead_guide_id"
                                    value={data.lead_guide_id}
                                    height="h-[52px]"
                                    onChange={(e) => setData(e.target.name, e.target.value)}
                                    error={errors.lead_guide_id}
                                    options={staff}
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
                        </span>
                        
                    </div>
                </div>
                )}
                {/* signature */} 
                {prevNext == 5 && (                       
                <div className=" overflow-hidden shadow-medium sm:rounded-lg">
                    <div className="flex flex-col justify-between items-center ">

                        <span className="flex flex-col w-full px-6 py-6 gap-2"> 
                            <div className="w-full grid grid-cols-1 sm:grid-cols-2 gap-6">

                            <CustomSelect
                                label="Are you signing on behalf of any minors?"
                                name="is_minor"
                                value={data.is_minor}
                                onChange={(e) => setData(e.target.name, e.target.value)}
                                error={errors.is_minor}
                                options={typeConfirmation}
                            /> 
                            {data.is_minor === '1' && (
                                <button type="button" onClick={addChild} className="flex items-center mt-7 w-[132px] h-[48px] gap-2 px-4 py-2 rounded-[10px] border border-primary-14 text-primary-14 cursor-pointer">
                                    {icons.plus}
                                    <span className="text-btn-outline-gray">Add Child</span>
                                </button> 
                            )}
                            </div>
                            {data.is_minor === '1' && (                              
                            <>
                                {data.children.map((child, index) => (
                                    <div key={index} className="w-full items-start grid grid-cols-1 sm:grid-cols-2 gap-6">
                                        <CustomInputText
                                            title="Child Name"
                                            name={`children[${index}].child_name`}
                                            value={child.child_name}
                                            onChange={(e) => updateChild(index, 'child_name', e.target.value)}
                                            errors={errors}
                                        />
                                        <div className='flex w-[240px]'>
                                        <CustomInputText
                                            title="Child Age"
                                            name={`children[${index}].child_age`}
                                            value={child.child_age}
                                            width='w-[132px]'
                                            onChange={(e) => updateChild(index, 'child_age', e.target.value)}
                                        errors={errors}
                                        /> 
                                        <button type="button" onClick={() => removeChild(index)}
                                            className="bg-[#FCE4DF4D] text-red-500 flex items-center mt-7 w-[48px] h-[48px] gap-2 px-4 py-2 rounded-[10px] border border-primary-14 cursor-pointer"
                                        >
                                            {icons.cross}
                                        </button>                                                                                       
                                        </div>


                                    </div>
                                ))}                            
                            </>
                            )}
                            <span className="flex w-full px-6 py-6 gap-2">
                                Read Agreement Below by clicking Submit you agree to the terms and conditions of this agreement.
                            </span>
                            <span className="flex w-full px-6 py-6 gap-2">
                                TSITSIKAMMA CANOPY TOURS / MTO FORESTRY (PTY) LTD
                            PUBLIC AWARENESS DOCUMENT - INDEMNITY / DISCLAIMER / WAIVER
                            </span>
                            <span className="flex w-full px-6 py-6 gap-2">
                            1. In this agreement: <br/>
                            1.1 "Canopy Tours" means collectively and individually as the context dictates:
                            Stormsriver Adventures (Pty) Ltd t/a Tsitsikamma Canopy Tours, Stormsriver Adventures Holdings (Pty) Ltd, Cavalier Holdings CC, Tree Top Tours (Pty) Ltd t/a Canopy Tours South Africa, South African National Parks, and their subsidiary companies, affiliates, employees, directors, members, agents and shareholders.<br/>
                            2. "MTO" shall mean and include the following parties: MTO Forestry (Pty) Ltd, its holding company, its subsidiaries, as well as all affiliates, employees, directors, members, agents, shareholders, and any other form of representative of the aforegoing parties.<br/>
                            2.1 "Activity" means the tree canopy tours conducted by Canopy Tours and all related activities thereto (whether directly or indirectly) specifically including but not limited to, the use of and sliding along the cableway, climbing up and down platforms, walking along bridges and pathways and being conveyed by any motor vehicle.<br/>
                            2.2 Whereas the Activity is solely managed and provided by Canopy Tours;
                            MTO's sole interest in the Activity and Canopy Tours is the fact that Canopy Tours has been granted permission by MTO to utilize certain afforested portions
                            </span>
                            <div className="flex items-center justify-center gap-6">
                                <button type='button' onClick={() => handlePrevNext(4)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border border-primary-14 text-primary-14 cursor-pointer">
                                    <span className="text-btn-outline-gray">Prev</span>
                            
                                </button>
                                <button type='button' onClick={(e) => handleSubmit(e)} className="flex items-center justify-center w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-blue-gradient-overlay border-[#3863C6] text-white shadow-inset-white cursor-pointer">
                                    <span className="text-btn-outline-gray">Submit</span>
                                </button>
                            </div> 

                        </span>
                        
                    </div>
                </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
