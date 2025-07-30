import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';



export default function Index({ auth }) {


  return (
    // <AuthenticatedLayout role={props.auth.role}>
    <AuthenticatedLayout
        user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Reports</h2>}
    >
        <Head title="Reports" />

            <div className="bg-white p-6 ">
                {/* top */}
                <div className="flex justify-between items-center mb-6">

                </div>
                {/* bottom */}
                <div className="bg-white border border-secondary-100">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="flex justify-start items-center px-4 pt-4 pb-[9px] ">
                        <span className='flex items-center gap-2'>
                            <img src="/assets/icons/task-square.svg" alt="bookings" className="w-5 h-5" />
                            <span className="font-semibold text-lg text-gray-800 block text-start">Reports</span>
                        </span>
                    </div>
                    <div className="flex justify-between items-center px-4 pb-[9px] ">
                        <span className='flex items-center gap-2'>No Reports Available</span>
                    </div>
                    </div>
                </div>
            </div>
    </AuthenticatedLayout>
  );
}
