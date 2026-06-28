<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\Reports\BookingsReportExport;
use App\Exports\Reports\BookingAdjustmentReportExport;
use App\Exports\Reports\CheckInManifestReportExport;
use App\Exports\Reports\FranchiseFeesReportExport;
use App\Exports\Reports\MarketingOptInReportExport;
use App\Exports\Reports\PaymentsReportExport;
use App\Jobs\SendReportReadyEmail;
use App\Models\Service;
use App\Models\TypePayment;
use App\Models\BookingQuestionnaire;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Minimum report requirements from my side  - all available in Excel/CSV format

        // Payments Report

        //     Selection Fields
        //     Payment Date (From To)
        //     Payment Type/ Method
        //     Activity/Service
        //     Report Fields
        //     Booking Number/Identifier
        //     Booking Status (e.g. Active or Cancelled)
        //     Booking Source (e.g. online or manual)
        //     Customer Name (who booked)
        //     Booking Date
        //     Activity Date
        //     Payment Method
        //     Payment Date
        //     Payment Value/ Amount

        // Bookings Report

        //     Selection Fields
        //     Activity Date (From To)
        //     Activity/Service
        //     Report Fields
        //     Booking Number/ Identifier
        //     Booking Status (e.g. Active or Cancelled)
        //     Booking Source (e.g. online or manual)
        //     Customer Name (who booked)
        //     Booking Date
        //     Activity Date
        //     Agent/User (who made the booking)
        //     Activity/Service
        //     Pax Adults
        //     Pax Children
        //     Pax Guides
        //     Total Value/ Amount
        //     Value/Amount Paid
        //     Fully Paid Indicator (Y/N)
        //     Booking Note

        // Check-In Report/ Manifest

        //     Selection Fields
        //     Activity Date (From To)
        //     Activity/Service
        //     You could allow the inclusion of various fields here from the Manifest by a checkbox (see Activitar Passenger List Report). For e.g. Group Tye, Nationality or Meal
        //     Report Fields
        //     Booking Number/ Identifier
        //     Activity/Service
        //     Activity Date
        //     Time slot/ Activity Time
        //     Customer Name (who booked)
        //     Customer Name (who checked in) – will be blank if no check in
        //     Checked Indicator (Y/N)
        //     Arrived Indicator (Y/N) – as discussed yesterday, this would be a field on the booking that the Front Desk would check to say the client actually arrived. If clients check in off site, the info here will exist but this does not mean they actually arrived.
        //     Additional fields as selected
        //     Booking Note

        // Bookings Adjustment Report

        //     Total Value – value before pricing adjustment
        //     Pricing Adjustment – the value of the pricing adjustment on that booking (discounts or promotions)
        //     Net Value – value after the pricing adjustment

        $services = Service::select('id', 'name')->orderBy('name')->get();
        $paymentTypes = TypePayment::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Reports/Index', [
            'title' => 'All',
            'services' => $services,
            'paymentTypes' => $paymentTypes,
        ]);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'report' => 'required|in:bookings,payments,checkin_manifest,booking_adjustment,marketing_opt_in,franchise_fees',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'service_id' => 'nullable|exists:services,id',
            'type_payment_id' => 'nullable|exists:type_payments,id',
            'include_group_type' => 'nullable|in:0,1,true,false',
            'include_nationality' => 'nullable|in:0,1,true,false',
            'include_meal' => 'nullable|in:0,1,true,false',
            'delivery' => 'nullable|in:download,email',
        ]);

        $teamId = auth()->user()->team_id;
        $serviceId = $validated['service_id'] ?? null;
        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;
        $includeGroupType = filter_var($validated['include_group_type'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $includeNationality = filter_var($validated['include_nationality'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $includeMeal = filter_var($validated['include_meal'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($validated['report'] === 'bookings') {
            $export = new BookingsReportExport($teamId, $startDate, $endDate, $serviceId ? (int) $serviceId : null);
            $label = 'Bookings';
        } elseif ($validated['report'] === 'payments') {
            $typePaymentId = $validated['type_payment_id'] ?? null;
            $export = new PaymentsReportExport(
                $teamId,
                $startDate,
                $endDate,
                $serviceId ? (int) $serviceId : null,
                $typePaymentId ? (int) $typePaymentId : null
            );
            $label = 'Payments';
        } elseif ($validated['report'] === 'checkin_manifest') {
            $export = new CheckInManifestReportExport(
                $teamId,
                $startDate,
                $endDate,
                $serviceId ? (int) $serviceId : null,
                $includeGroupType,
                $includeNationality,
                $includeMeal
            );
            $label = 'Check-in Manifest';
        } elseif ($validated['report'] === 'marketing_opt_in') {
            $export = new MarketingOptInReportExport(
                $teamId,
                $startDate,
                $endDate,
                $serviceId ? (int) $serviceId : null
            );
            $label = 'Marketing Opt-In';
        } elseif ($validated['report'] === 'franchise_fees') {
            $export = new FranchiseFeesReportExport(
                $teamId,
                $startDate,
                $endDate,
                $serviceId ? (int) $serviceId : null
            );
            $label = 'Franchise Fees';
        } else {
            $export = new BookingAdjustmentReportExport(
                $teamId,
                $startDate,
                $endDate,
                $serviceId ? (int) $serviceId : null
            );
            $label = 'Booking Adjustment';
        }

        $delivery = $validated['delivery'] ?? 'download';
        $filename = strtolower($label) . '-report-' . now()->format('Ymd-His') . '.xlsx';

        if ($delivery === 'email') {
            $path = 'reports/' . $filename;
            $disk = 'public';
            $user = $request->user();
            $downloadUrl = Storage::disk($disk)->url($path);

            Excel::queue($export, $path, $disk)->chain([
                new SendReportReadyEmail(
                    $user->email,
                    $user->name ?? $user->email,
                    $downloadUrl,
                    $label
                ),
            ]);

            return response()->json([
                'message' => 'Report queued. You will receive an email when it is ready.',
            ]);
        }

        return Excel::download($export, $filename);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(BookingQuestionnaire $bookingQuestionnaire)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BookingQuestionnaire $bookingQuestionnaire)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BookingQuestionnaire $bookingQuestionnaire)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BookingQuestionnaire $bookingQuestionnaire)
    {
        //
    }
}
