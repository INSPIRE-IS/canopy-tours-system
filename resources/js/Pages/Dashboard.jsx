import Header from '@/Components/Header';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Line } from 'react-chartjs-2';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';
import BookingsTable from '@/Components/BookingsTable';

// Register Chart.js components
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

export default function Dashboard({ auth, chart1, latestBookings, statusBookings, notifications, customerManifest }) {
    console.log('chart1', chart1);
    const data = {
        labels: chart1.labels || [],

        datasets: chart1.datasets.map(ds => ({
            label: ds.label,
            data: ds.data,
            borderColor: 'rgba(75,192,192,1)',
            backgroundColor: 'rgba(75,192,192,0.2)',
            fill: true,
        })),
    };
    //console.log('data', data);
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>}
        >
            <Head title="Dashboard" />
            <header className="bg-white shadow-md px-6 py-4 flex justify-between items-center">
                <div className="flex items-center gap-4">

                    <span className="font-semibold text-lg text-gray-800">Welcome, {auth?.user?.name}</span>
                </div>
                {/* Add user dropdown, settings, notifications, etc. */}
            </header>
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="py-6 max-w-7xl mx-auto space-y-8">
                            {/* Chart 1 
                            <div className="bg-white p-4 shadow rounded-lg">
                                <h3 className="text-lg font-bold mb-4">{chart1.title}</h3>
                                <div className="flex w-full h-[300px] gap-2">
                                <Line data={data} options={chart1.options} />
                                </div>
                            </div>*/}
                            <div className="flex w-full h-[300px] gap-2">
                                {/* Latest Bookings Table */}
                                <BookingsTable
                                    chartTitle={latestBookings?.chart_title}
                                    fields={latestBookings?.fields}
                                    data={latestBookings?.data}
                                />
                                {/* status Bookings Table */}
                                <BookingsTable
                                    chartTitle={statusBookings?.chart_title}
                                    fields={statusBookings?.fields}
                                    data={statusBookings?.data}
                                />
                            </div>
                            <div className="flex w-full h-[300px] gap-2">
                                {/* notifications Table */}
                                <BookingsTable
                                    chartTitle={notifications?.chart_title}
                                    fields={notifications?.fields}
                                    data={notifications?.data}
                                />
                                {/* customerManifest */}
                                <BookingsTable
                                    chartTitle={customerManifest?.chart_title}
                                    fields={customerManifest?.fields}
                                    data={customerManifest?.data}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
