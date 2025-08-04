import { usePage, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

import CustomTextarea from '@/Components/CustomTextarea';
import RichTextDisplay from '@/Components/RichTextDisplay';
import InputRichText from '@/Components/InputRichText';

export default function Index({ auth, topic, unreads }) {
const props = usePage();
console.log('auth show',auth)
const mode = 'create';
const { data, setData,post, put, isDirty, reset, processing, errors } = useForm(()=>
        {

            return {
            content: '',
            }   
        }     
    );
    const handleChange = (target, value) => {
        setData(target, value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        console.log('data ', data);
        // return;
        post(route('admin.messenger.reply', [topic?.id]));
        //put(route('admin.checkins.store',{booking}));
    };

  return (
    // <AuthenticatedLayout role={props.auth.role}>
    <AuthenticatedLayout

            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Messages</h2>}
    >
        <Head title="Messages" />
            <div className="bg-white p-6 ">
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <p>
                        <button type='button' onClick={() => router.get(route('admin.messenger.index'))} 
                        className="flex items-center justify-center w-full h-[48px] gap-2 px-4 py-2 rounded-[10px] border border-primary-14 text-primary-14 cursor-pointer">
                            <span className="text-btn-outline-gray">Return</span>
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
                        <div className="flex flex-col w-auto px-4 py-6 gap-2 border-b border-gray-200">


                                <span className='flex items-center pl-[300px] gap-2'>From: { topic?.creator?.name } { topic?.creator?.surname }</span>
                                <span className='flex items-center gap-2'>To: { topic?.receiver?.name } { topic?.receiver?.surname }</span>

                                <span className='flex items-center gap-2 mt-4'>Subject: {topic?.subject}</span>                                
                                <div className="flex flex-col items-start gap-2 mt-2">
                                    {topic?.messages?.map((message, i) => {
                                        // format the created_at to date and time
                                        console.log('message', message);
                                        const date = new Date(message?.created_at);
                                        const formattedDate = date.toLocaleDateString();
                                        const formattedTime = date.toLocaleTimeString();
                                        return(
                                            <div key={message?.id}>
                                                {auth.user.id === message.sender_id ? (
                                                    <div  className={`flex flex-col items-start gap-2 pl-[300px] border-b border-gray-200`}>
                                                        <span className="flex items-start gap-2">Sent: { formattedDate } { formattedTime }</span>
                                                        <span className="gap-2">
                                                            Message: <RichTextDisplay content={ message.content } />
                                                        </span>
                                                    </div>
                                                ):(
                                                <div className={`flex flex-col items-start gap-2 border-b border-gray-200`}>
                                                    <span className="flex items-start gap-2">Recieved: { formattedDate } { formattedTime }</span>
                                                    <span className="gap-2">
                                                        Message: <RichTextDisplay content={ message.content } />
                                                    </span>
                                                </div>
                                                )}                                            
                                            </div>
                                    )})}
                                </div>
                                <div className="flex flex-col w-[60%]  mb-4">
                                    <InputRichText
                                    title="Reply*"
                                    field="content"
                                    value={data.content}
                                    mode={mode}
                                    handleChange={handleChange}
                                    errors={errors}
                                    />
                                    <button type="submit" 
                                    className="flex items-center justify-center w-[130px] h-[57px] gap-2 px-4 py-2 rounded-[10px] border border-primary-14 text-primary-14 cursor-pointer" 
                                    onClick={handleSubmit}><span className="text-btn-outline-gray">Reply</span>
                                    </button>

                                </div>


                            {/* <button type='button' onClick={() => router.get(route('admin.messenger.reply', [topic?.id]))} 
                            className="flex items-center justify-center w-[120px] h-[48px] gap-2 px-4 py-2 rounded-[10px] border border-primary-14 text-primary-14 cursor-pointer">
                                <span className="text-btn-outline-gray">reply</span>
                            </button> */}
                        </div>


                </div>
            </div>
          </div>



    </AuthenticatedLayout>
  );
}
