import { usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';


export default function Index({ topics, title, unreads }) {
const props = usePage();
  return (
    // <AuthenticatedLayout role={props.auth.role}>
    <AuthenticatedLayout

            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Messages</h2>}
    >
        <Head title="Messages" />
            <div className="bg-white p-6 ">
                {/* top */}
                <div className="flex justify-between items-center mb-6">
                    <div className="flex items-center gap-4">
                        <button type='button' onClick={() => router.get(route('admin.messenger.createTopic'))} 
                        className="flex items-center justify-center w-full h-[48px] gap-2 px-4 py-2 rounded-[10px] border border-primary-14 text-primary-14 cursor-pointer">
                            <span className="text-btn-outline-gray">New Message</span>
                        </button>

                    </div>
                    <div className="flex items-center gap-4">
                        <button type='button' onClick={() => router.get(route('admin.messenger.index'))} 
                        className="flex items-center justify-center w-[200px] h-[48px] gap-2 px-4 py-2 rounded-[10px] border border-primary-14 text-primary-14 cursor-pointer">
                            <span className="text-btn-outline-gray">All Messages</span>
                        </button>
                        <button type='button' onClick={() => router.get(route('admin.messenger.showInbox'))} 
                        className="flex items-center justify-center w-[120px] h-[48px] gap-2 px-4 py-2 rounded-[10px] border border-primary-14 text-primary-14 cursor-pointer">
                            {unreads['inbox'] > 0 ? (
                                <span className="text-btn-outline-gray text-red-400">
                                    Inbox {unreads['inbox']}
                                </span>
                            ):(
                                <span className="text-btn-outline-gray">Inbox</span>
                            )}
                        </button>

                        <button type='button' onClick={() => router.get(route('admin.messenger.showOutbox'))} 
                        className="flex items-center justify-center w-[120px] h-[48px] gap-2 px-4 py-2 rounded-[10px] border border-primary-14 text-primary-14 cursor-pointer">
                            {unreads['outbox'] > 0 ? (
                                <span className="text-btn-outline-gray text-red-400">
                                    Outbox {unreads['outbox']}
                                </span>
                            ):(
                                <span className="text-btn-outline-gray">Outbox</span>
                            )}
                        </button>
                    </div>
                </div>
                {/* bottom */}
                <div className="flex w-full rounded-[10px] bg-white border border-secondary-100">
                    <div className="flex flex-col w-full overflow-hidden shadow-sm sm:rounded-lg ">
                        <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                            <span className='flex items-center gap-2'>
                                <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                                <span className="font-semibold text-lg text-gray-800 block text-start">{title} Messages </span>
                            </span>
                        </div>
                        <div className="flex flex-col  w-full justify-between items-center px-4 pb-[9px] shadow-sm sm:rounded-lg ">
                            {/* table */}
                                <div className="flex w-full gap-2 border-b border-gray-200 mb-2 text-center">
                                    <span className="text-gray-800 text-sm basis-[25%]">From</span>
                                    <span className="text-gray-800 text-sm basis-[25%]">Subject</span>
                                    <span className="text-gray-800 text-sm basis-[25%]">Sent</span>
                                    <span className="text-gray-800 text-sm basis-[25%]">Action</span>
                                </div>                            
                            <div className="flex flex-col justify-between items-center w-full">

                            {topics?.length > 0 ? (
                                topics.map((topic) => {
                                const receiverOrCreator = topic.receiver_or_creator; // Adjust key based on your controller/API
                                const hasUnreads = topic.has_unreads; // Adjust key based on your controller/API

                                return (
                                    <div
                                    key={topic.id}
                                    className="flex flex-wrap w-full bg-gray-50 p-4 border border-gray-200 rounded-md shadow-sm hover:bg-gray-100"
                                    >
                                    <div className="basis-[25%] mb-2 md:mb-0 ">
                                        <button
                                        onClick={() => router.get(route('admin.messenger.showMessages', [topic.id]))}
                                        className={`block text-sm ${
                                            hasUnreads ? 'font-bold text-blue-800' : 'text-gray-800'
                                        } items-center justify-center w-full cursor-pointer`}
                                        >
                                        {receiverOrCreator?.email || ''}
                                        </button>
                                    </div>
                                    <div className="basis-[25%] mb-2 md:mb-0 text-center">
                                        <a
                                        href={route('admin.messenger.showMessages', [topic.id])}
                                        className={`text-sm ${
                                            hasUnreads ? 'font-bold text-blue-800' : 'text-gray-700'
                                        }`}
                                        >
                                        {topic.subject}
                                        </a>
                                    </div>
                                    <div className="basis-[25%] text-sm text-gray-500 text-center">
                                        {topic.created_at_human} {/* should be "2 hours ago" etc */}
                                    </div>
                                    <div className="basis-[25%] text-center">
                                        <button
                                        onClick={() => {
                                            if (confirm('Are you sure?')) {
                                            router.delete(route('admin.messenger.destroyTopic', [topic.id]));
                                            }
                                        }}
                                        className="text-red-600 hover:text-red-800 text-sm"
                                        >
                                        Delete
                                        </button>
                                    </div>
                                    </div>
                                );
                                })
                            ) : (
                                <div className="text-gray-500 text-sm">You have no messages.</div>
                            )}
                            </div>

                        </div>
                    </div>
                </div>
            </div>



    </AuthenticatedLayout>
  );
}
