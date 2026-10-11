<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\Reports\BookingsReportExport;
use App\Exports\Reports\BookingAdjustmentReportExport;
use App\Exports\Reports\CheckInManifestReportExport;
use App\Exports\Reports\FranchiseFeesReportExport;
use App\Exports\Reports\MarketingOptInReportExport;
use App\Exports\Reports\PaymentsReportExport;
use App\Exports\Reports\PromoCodeUsageReportExport;
use App\Exports\Reports\WholesalerUsageReportExport;
use App\Jobs\SendReportReadyEmail;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\TypePayment;
use App\Models\BookingQuestionnaire;
use App\Models\Wholesaler;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel as ExcelWriter;
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
        $wholesalers = Wholesaler::select('id', 'name')->orderBy('name')->get();
        $promotions = Promotion::select('id', 'name', 'promo_code')->orderBy('name')->get();

        return Inertia::render('Admin/Reports/Index', [
            'title' => 'All',
            'services' => $services,
            'paymentTypes' => $paymentTypes,
            'wholesalers' => $wholesalers,
            'promotions' => $promotions,
        ]);
    }

    public function export(Request $request)
    {
        $validated = $this->validateReportRequest($request);
        [$export, $label] = $this->buildReport($validated, (int) auth()->user()->team_id);

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

    public function preview(Request $request)
    {
        $validated = $this->validateReportRequest($request, true);
        [$export, $label] = $this->buildReport($validated, (int) auth()->user()->team_id);

        $html = Excel::raw($export, ExcelWriter::HTML);
        $title = htmlspecialchars($label . ' Report', ENT_QUOTES, 'UTF-8');
        $period = htmlspecialchars(
            $validated['start_date'] . ' to ' . $validated['end_date'],
            ENT_QUOTES,
            'UTF-8'
        );

        $styles = <<<'HTML'
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    body { margin: 0; background: #f5f6f3; color: #161618; font-family: Arial, sans-serif; }
    .report-preview-toolbar { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 14px 20px; background: #fff; border-bottom: 1px solid #d9ddd5; }
    .report-preview-title { font-size: 18px; font-weight: 700; }
    .report-preview-period { margin-top: 3px; color: #667085; font-size: 13px; }
    .report-preview-print { padding: 9px 16px; border: 0; border-radius: 8px; background: #167c18; color: #fff; cursor: pointer; font-weight: 700; }
    .report-preview-table { margin: 20px; padding: 16px; overflow-x: auto; background: #fff; border: 1px solid #d9ddd5; border-radius: 10px; }
    .report-preview-table table { min-width: max-content; }
    @media print {
        body { background: #fff; }
        .report-preview-toolbar { position: static; }
        .report-preview-print { display: none; }
        .report-preview-table { margin: 0; padding: 0; border: 0; }
    }
</style>
HTML;
        $toolbar = <<<HTML
<div class="report-preview-toolbar">
    <div>
        <div class="report-preview-title">{$title}</div>
        <div class="report-preview-period">{$period}</div>
    </div>
    <button type="button" class="report-preview-print" onclick="window.print()">Print</button>
</div>
<div class="report-preview-table">
HTML;

        $html = str_replace('</head>', $styles . '</head>', $html);
        $html = str_replace('<body>', '<body>' . $toolbar, $html);
        $html = str_replace('</body>', '</div></body>', $html);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'inline');
    }

    private function validateReportRequest(Request $request, bool $preview = false): array
    {
        $dateRule = $preview ? ['required', 'date'] : ['nullable', 'date'];

        $validated = $request->validate([
            'report' => 'required|in:bookings,payments,checkin_manifest,booking_adjustment,marketing_opt_in,franchise_fees,wholesaler_usage,promo_code_usage',
            'start_date' => $dateRule,
            'end_date' => $preview ? ['required', 'date', 'after_or_equal:start_date'] : $dateRule,
            'service_id' => 'nullable|exists:services,id',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|exists:services,id',
            'wholesaler_id' => 'nullable|exists:wholesalers,id',
            'promotion_id' => 'nullable|exists:promotions,id',
            'type_payment_id' => 'nullable|exists:type_payments,id',
            'include_group_type' => 'nullable|in:0,1,true,false',
            'include_nationality' => 'nullable|in:0,1,true,false',
            'include_guardian' => 'nullable|in:0,1,true,false',
            'include_referral' => 'nullable|in:0,1,true,false',
            'include_gender' => 'nullable|in:0,1,true,false',
            'include_medical' => 'nullable|in:0,1,true,false',
            'include_medical_detail' => 'nullable|in:0,1,true,false',
            'include_fitness' => 'nullable|in:0,1,true,false',
            'include_pregnancy' => 'nullable|in:0,1,true,false',
            'include_duplicate_checkins' => 'nullable|in:0,1,true,false',
            'delivery' => 'nullable|in:download,email',
        ]);

        if ($preview) {
            $startDate = CarbonImmutable::parse($validated['start_date'])->startOfDay();
            $endDate = CarbonImmutable::parse($validated['end_date'])->startOfDay();

            if ($endDate->gt($startDate->addMonthsNoOverflow(2))) {
                throw ValidationException::withMessages([
                    'end_date' => 'Online report viewing is limited to a maximum period of two months.',
                ]);
            }
        }

        return $validated;
    }

    private function buildReport(array $validated, int $teamId): array
    {

        $serviceId = $validated['service_id'] ?? null;
        $serviceIds = collect($validated['service_ids'] ?? [])
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;
        $includeGroupType = filter_var($validated['include_group_type'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $includeNationality = filter_var($validated['include_nationality'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $includeGuardian = filter_var($validated['include_guardian'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $includeReferral = filter_var($validated['include_referral'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $includeGender = filter_var($validated['include_gender'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $includeMedical = filter_var($validated['include_medical'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $includeMedicalDetail = filter_var($validated['include_medical_detail'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $includeFitness = filter_var($validated['include_fitness'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $includePregnancy = filter_var($validated['include_pregnancy'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $includeDuplicateCheckins = filter_var($validated['include_duplicate_checkins'] ?? false, FILTER_VALIDATE_BOOLEAN);

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
                $includeGuardian,
                $includeReferral,
                $includeGender,
                $includeMedical,
                $includeMedicalDetail,
                $includeFitness,
                $includePregnancy,
                $includeDuplicateCheckins
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
            $franchiseServiceIds = !empty($serviceIds)
                ? $serviceIds
                : ($serviceId ? [(int) $serviceId] : []);

            $export = new FranchiseFeesReportExport(
                $teamId,
                $startDate,
                $endDate,
                $franchiseServiceIds
            );
            $label = 'Franchise Fees';
        } elseif ($validated['report'] === 'wholesaler_usage') {
            $wholesalerId = $validated['wholesaler_id'] ?? null;
            $export = new WholesalerUsageReportExport(
                $teamId,
                $startDate,
                $endDate,
                $serviceId ? (int) $serviceId : null,
                $wholesalerId ? (int) $wholesalerId : null
            );
            $label = 'Wholesaler Usage';
        } elseif ($validated['report'] === 'promo_code_usage') {
            $promotionId = $validated['promotion_id'] ?? null;
            $export = new PromoCodeUsageReportExport(
                $teamId,
                $startDate,
                $endDate,
                $serviceId ? (int) $serviceId : null,
                $promotionId ? (int) $promotionId : null
            );
            $label = 'Promo Code Usage';
        } else {
            $export = new BookingAdjustmentReportExport(
                $teamId,
                $startDate,
                $endDate,
                $serviceId ? (int) $serviceId : null
            );
            $label = 'Booking Adjustment';
        }

        return [$export, $label];
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
