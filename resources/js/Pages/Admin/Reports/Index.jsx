import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';
import CustomSelect from '@/Components/CustomSelect';
import BlueButton from '@/Components/BlueButton';
import CustomCheckbox from '@/Components/CustomCheckbox';
import { useToast } from '@/Context/ToastContext';

const formatDate = (date) => date.toISOString().slice(0, 10);

export default function Index({ auth, services = [], paymentTypes = [] }) {
    const { showToast } = useToast();
    const today = new Date();
    const defaultEnd = formatDate(today);
    const defaultStart = formatDate(new Date(today.getFullYear(), today.getMonth(), today.getDate() - 30));

    const [bookingFilters, setBookingFilters] = useState({
        start_date: defaultStart,
        end_date: defaultEnd,
        service_id: '',
        delivery: 'download',
    });

    const [paymentFilters, setPaymentFilters] = useState({
        start_date: defaultStart,
        end_date: defaultEnd,
        service_id: '',
        type_payment_id: '',
        delivery: 'download',
    });

    const [manifestFilters, setManifestFilters] = useState({
        start_date: defaultStart,
        end_date: defaultEnd,
        service_id: '',
        include_group_type: false,
        include_nationality: false,
        include_meal: false,
        delivery: 'download',
    });

    const [adjustmentFilters, setAdjustmentFilters] = useState({
        start_date: defaultStart,
        end_date: defaultEnd,
        service_id: '',
        delivery: 'download',
    });

    const buildParams = (report, filters) => {
        const params = { report, ...filters };
        Object.keys(params).forEach((key) => {
            if (params[key] === '' || params[key] === null || params[key] === undefined || params[key] === false) {
                delete params[key];
            }
        });
        return params;
    };

    const exportReport = async (report, filters) => {
        const params = buildParams(report, filters);
        if (filters.delivery === 'email') {
            try {
                const response = await axios.post(route('admin.reports.export'), params);
                showToast(response?.data?.message || 'Report queued. You will receive an email shortly.', 'success');
            } catch (error) {
                showToast('Failed to queue report. Please try again.', 'error');
            }
            return;
        }

        try {
            const response = await axios.get(route('admin.reports.export'), {
                params,
                responseType: 'blob',
            });
            const contentDisposition = response.headers?.['content-disposition'] || '';
            const filenameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
            const fallbackName = `${report}-report.xlsx`;
            const filename = filenameMatch?.[1] || fallbackName;
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
        } catch (error) {
            showToast('Failed to download report. Please try again.', 'error');
        }
    };

    const inputClass = "mt-1 block w-full h-[48px] border-gray-300 shadow-sm input-text rounded-[10px] hover:border-black active:border-blue-600 focus:outline-none focus:ring-0 focus:border-black";

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Reports</h2>}
        >
            <Head title="Reports" />

            <div className="bg-white p-6">
                <div className="grid gap-6 md:grid-cols-2">
                    <div className="border border-secondary-100 rounded-lg shadow-sm p-5">
                        <div className="flex items-center gap-2 mb-4">
                            <img src="/assets/icons/task-square.svg" alt="reports" className="w-5 h-5" />
                            <span className="font-semibold text-lg text-gray-800">Bookings Report</span>
                        </div>
                        <div className="grid gap-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]">
                                        Activity Date From
                                    </label>
                                    <input
                                        type="date"
                                        className={inputClass}
                                        value={bookingFilters.start_date}
                                        onChange={(e) => setBookingFilters(prev => ({ ...prev, start_date: e.target.value }))}
                                    />
                                </div>
                                <div>
                                    <label className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]">
                                        Activity Date To
                                    </label>
                                    <input
                                        type="date"
                                        className={inputClass}
                                        value={bookingFilters.end_date}
                                        onChange={(e) => setBookingFilters(prev => ({ ...prev, end_date: e.target.value }))}
                                    />
                                </div>
                            </div>
                            <CustomSelect
                                label="Service"
                                name="booking_service_id"
                                value={bookingFilters.service_id}
                                onChange={(e) => setBookingFilters(prev => ({ ...prev, service_id: e.target.value }))}
                                options={services}
                                optionText="All services"
                            />
                            <CustomSelect
                                label="Delivery"
                                name="booking_delivery"
                                value={bookingFilters.delivery}
                                onChange={(e) => setBookingFilters(prev => ({ ...prev, delivery: e.target.value }))}
                                options={[
                                    { id: 'download', name: 'Download now' },
                                    { id: 'email', name: 'Email when ready' },
                                ]}
                                showOption={false}
                            />
                            <div className="flex justify-end">
                                <BlueButton
                                    title="Export Bookings"
                                    width="w-[180px]"
                                    onClick={() => exportReport('bookings', bookingFilters)}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="border border-secondary-100 rounded-lg shadow-sm p-5">
                        <div className="flex items-center gap-2 mb-4">
                            <img src="/assets/icons/task-square.svg" alt="reports" className="w-5 h-5" />
                            <span className="font-semibold text-lg text-gray-800">Payments Report</span>
                        </div>
                        <div className="grid gap-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]">
                                        Payment Date From
                                    </label>
                                    <input
                                        type="date"
                                        className={inputClass}
                                        value={paymentFilters.start_date}
                                        onChange={(e) => setPaymentFilters(prev => ({ ...prev, start_date: e.target.value }))}
                                    />
                                </div>
                                <div>
                                    <label className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]">
                                        Payment Date To
                                    </label>
                                    <input
                                        type="date"
                                        className={inputClass}
                                        value={paymentFilters.end_date}
                                        onChange={(e) => setPaymentFilters(prev => ({ ...prev, end_date: e.target.value }))}
                                    />
                                </div>
                            </div>
                            <CustomSelect
                                label="Service"
                                name="payment_service_id"
                                value={paymentFilters.service_id}
                                onChange={(e) => setPaymentFilters(prev => ({ ...prev, service_id: e.target.value }))}
                                options={services}
                                optionText="All services"
                            />
                            <CustomSelect
                                label="Payment Method"
                                name="type_payment_id"
                                value={paymentFilters.type_payment_id}
                                onChange={(e) => setPaymentFilters(prev => ({ ...prev, type_payment_id: e.target.value }))}
                                options={paymentTypes}
                                optionText="All methods"
                            />
                            <CustomSelect
                                label="Delivery"
                                name="payment_delivery"
                                value={paymentFilters.delivery}
                                onChange={(e) => setPaymentFilters(prev => ({ ...prev, delivery: e.target.value }))}
                                options={[
                                    { id: 'download', name: 'Download now' },
                                    { id: 'email', name: 'Email when ready' },
                                ]}
                                showOption={false}
                            />
                            <div className="flex justify-end">
                                <BlueButton
                                    title="Export Payments"
                                    width="w-[180px]"
                                    onClick={() => exportReport('payments', paymentFilters)}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="border border-secondary-100 rounded-lg shadow-sm p-5">
                        <div className="flex items-center gap-2 mb-4">
                            <img src="/assets/icons/task-square.svg" alt="reports" className="w-5 h-5" />
                            <span className="font-semibold text-lg text-gray-800">Check-in / Manifest</span>
                        </div>
                        <div className="grid gap-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]">
                                        Activity Date From
                                    </label>
                                    <input
                                        type="date"
                                        className={inputClass}
                                        value={manifestFilters.start_date}
                                        onChange={(e) => setManifestFilters(prev => ({ ...prev, start_date: e.target.value }))}
                                    />
                                </div>
                                <div>
                                    <label className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]">
                                        Activity Date To
                                    </label>
                                    <input
                                        type="date"
                                        className={inputClass}
                                        value={manifestFilters.end_date}
                                        onChange={(e) => setManifestFilters(prev => ({ ...prev, end_date: e.target.value }))}
                                    />
                                </div>
                            </div>
                            <CustomSelect
                                label="Service"
                                name="manifest_service_id"
                                value={manifestFilters.service_id}
                                onChange={(e) => setManifestFilters(prev => ({ ...prev, service_id: e.target.value }))}
                                options={services}
                                optionText="All services"
                            />
                            <div className="grid gap-2 md:grid-cols-3">
                                <CustomCheckbox
                                    label="Group Type"
                                    name="include_group_type"
                                    checked={manifestFilters.include_group_type}
                                    onChange={(name, checked) => setManifestFilters(prev => ({ ...prev, [name]: checked }))}
                                />
                                <CustomCheckbox
                                    label="Nationality"
                                    name="include_nationality"
                                    checked={manifestFilters.include_nationality}
                                    onChange={(name, checked) => setManifestFilters(prev => ({ ...prev, [name]: checked }))}
                                />
                                <CustomCheckbox
                                    label="Meal Option"
                                    name="include_meal"
                                    checked={manifestFilters.include_meal}
                                    onChange={(name, checked) => setManifestFilters(prev => ({ ...prev, [name]: checked }))}
                                />
                            </div>
                            <CustomSelect
                                label="Delivery"
                                name="manifest_delivery"
                                value={manifestFilters.delivery}
                                onChange={(e) => setManifestFilters(prev => ({ ...prev, delivery: e.target.value }))}
                                options={[
                                    { id: 'download', name: 'Download now' },
                                    { id: 'email', name: 'Email when ready' },
                                ]}
                                showOption={false}
                            />
                            <div className="flex justify-end">
                                <BlueButton
                                    title="Export Manifest"
                                    width="w-[180px]"
                                    onClick={() => exportReport('checkin_manifest', manifestFilters)}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="border border-secondary-100 rounded-lg shadow-sm p-5">
                        <div className="flex items-center gap-2 mb-4">
                            <img src="/assets/icons/task-square.svg" alt="reports" className="w-5 h-5" />
                            <span className="font-semibold text-lg text-gray-800">Booking Adjustment</span>
                        </div>
                        <div className="grid gap-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]">
                                        Activity Date From
                                    </label>
                                    <input
                                        type="date"
                                        className={inputClass}
                                        value={adjustmentFilters.start_date}
                                        onChange={(e) => setAdjustmentFilters(prev => ({ ...prev, start_date: e.target.value }))}
                                    />
                                </div>
                                <div>
                                    <label className="text-[#161618] font-poppins text-[12px] font-normal leading-[130%]">
                                        Activity Date To
                                    </label>
                                    <input
                                        type="date"
                                        className={inputClass}
                                        value={adjustmentFilters.end_date}
                                        onChange={(e) => setAdjustmentFilters(prev => ({ ...prev, end_date: e.target.value }))}
                                    />
                                </div>
                            </div>
                            <CustomSelect
                                label="Service"
                                name="adjustment_service_id"
                                value={adjustmentFilters.service_id}
                                onChange={(e) => setAdjustmentFilters(prev => ({ ...prev, service_id: e.target.value }))}
                                options={services}
                                optionText="All services"
                            />
                            <CustomSelect
                                label="Delivery"
                                name="adjustment_delivery"
                                value={adjustmentFilters.delivery}
                                onChange={(e) => setAdjustmentFilters(prev => ({ ...prev, delivery: e.target.value }))}
                                options={[
                                    { id: 'download', name: 'Download now' },
                                    { id: 'email', name: 'Email when ready' },
                                ]}
                                showOption={false}
                            />
                            <div className="flex justify-end">
                                <BlueButton
                                    title="Export Adjustments"
                                    width="w-[180px]"
                                    onClick={() => exportReport('booking_adjustment', adjustmentFilters)}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
