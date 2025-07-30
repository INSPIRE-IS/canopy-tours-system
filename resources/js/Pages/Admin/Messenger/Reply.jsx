import { useState } from 'react';
import { usePage, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import CustomTextarea from '@/Components/CustomTextarea';


export default function Index({ auth, users, unreads }) {
    const mode = 'create';
    const [selectedSeason, setSelectedSeason] = useState(null);
    const { data, setData,post, put, isDirty, reset, processing, errors } = useForm(()=>
        {
            return {
            topic_id: '',
            content: '',
            }   
        }     
    );
    const handleSubmit = (e) => {
        e.preventDefault();

        post(route('admin.messenger.reply'));
        //put(route('admin.checkins.store',{booking}));
    };


  return (
    // <AuthenticatedLayout role={props.auth.role}>
    <AuthenticatedLayout

            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Roles</h2>}
    >
        <Head title="Roles" />
        {/* <header className="bg-white shadow-md px-6 py-4 flex justify-between items-center">
            <div className="flex items-center gap-4">

                <span className="font-semibold text-lg text-gray-800">Roles, </span>
            </div>
        </header> */}
            <div className="bg-white p-6 ">
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <p>
                        <button type='button' onClick={() => router.get(route('admin.messenger.index'))} 
                        className="flex items-center justify-center w-full h-[48px] gap-2 px-4 py-2 rounded-[10px] border border-primary-14 text-primary-14 cursor-pointer">
                            <span className="text-btn-outline-gray">Cancel</span>
                        </button>
                    </p>
                </div>
                {/* bottom */}
                <div className="bg-white rounded-[10px] border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                        <span className='flex items-center gap-2'>
                            <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                            <span className="font-semibold text-lg text-gray-800 block text-start">Messages</span>
                        </span>
                    </div>
                      <div className="flex w-full items-center justify-center gap-2">
                          {/* table */}

                          <div className="flex flex-col w-[60%]  mb-4">
                                  <CustomTextarea
                                      title="content*"
                                      name="content"
                                      value={data.content}
                                      mode = {mode} 
                                      onChange={(e) => setData(e.target.name, e.target.value)}
                                      errors={errors}
                                  />
                            <button type="submit" 
                              className="flex items-center justify-center w-full h-[57px] gap-2 px-4 py-2 rounded-[10px] border border-primary-14 text-primary-14 cursor-pointer" 
                              onClick={handleSubmit}><span className="text-btn-outline-gray">Submit</span>
                            </button>

                          </div>
                      </div>


                </div>
            </div>
          </div>



    </AuthenticatedLayout>
  );
}
