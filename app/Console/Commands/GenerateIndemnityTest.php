<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{
    Booking,
    BookingItem,
    BookingQuestionnaire,
    Customer,
    User,
    Team,
    Service,
    File,
    Email
};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\CheckInMail;

class GenerateIndemnityTest extends Command
{
    protected $signature = 'test:indemnity {booking_questionnaire_id?}';
    // php artisan test:indemnity 1
    protected $description = 'Generate and email test indemnity PDF for a booking';

    public function handle()
    {
        $bookingQuestionnaireId = $this->argument('booking_questionnaire_id') ?? BookingQuestionnaire::latest()->value('id');


        if (!$bookingQuestionnaireId) {
            $this->error('No booking found.');
            return;
        }
        $bookingQuestionnaire = BookingQuestionnaire::find($bookingQuestionnaireId);

        $bookingQuestionnaire->load('booking_item', 'booking_item.booking', 'customer', 'gender', 'province', 'country', 'continent');

        $bookingItem = BookingItem::find($bookingQuestionnaire->booking_item_id);
        $booking = Booking::find($bookingItem->booking_id);
        if (!$booking) {
            $this->error("Booking ID {$bookingQuestionnaire->booking_id} not found.");
            return;
        }

        $this->info("Generating indemnity for booking #{$booking->reference}...");

        // Replace this with however you actually fetch these in your system
        $validated = [
            'lead_guide_id' => $bookingQuestionnaire->guide_id ?? null,
            'safety_guide_id' => $bookingQuestionnaire->safety_guide_id ?? null,
            'customer_id' => $booking->customer_id,
            'booking_item_id' => $bookingQuestionnaire->booking_item_id ?? null,
        ];

        $site = Team::select('full_name', 'reg_no', 'address', 'address_code', 'account_owner', 'account_number', 'account_type', 'bank_name', 'branch_code')
            ->find($booking->team_id);
        $guide = User::find($validated['lead_guide_id']);
        $safetyGuide = User::find($validated['safety_guide_id']);
        $customer = Customer::find($validated['customer_id']);
        $bookingItem = BookingItem::find($validated['booking_item_id']);
        $service = Service::with('team')->find($bookingItem?->service_id);
        $clients = collect([
            [
                'sign_date'      => $bookingQuestionnaire->created_at ?? '',
                'id_number'      => $bookingQuestionnaire->id_number ?? '',
                'full_name'      => $bookingQuestionnaire->customer?->full_name ?? '',
                'age'            => $bookingQuestionnaire->customer?->age ?? '',
                'phone'          => $bookingQuestionnaire->customer?->contact_number ?? '',
                'email'          => $bookingQuestionnaire->customer?->email_address ?? '',
                'gender'         => $bookingQuestionnaire->gender?->name ?? '',
                'country'        => $bookingQuestionnaire->country?->name ?? '',
                'province'       => $bookingQuestionnaire->province?->name ?? '',
                'is_medical'     => $bookingQuestionnaire->is_medical ? 'Yes' : 'No',
                'medical_detail' => $bookingQuestionnaire->medical_detail ?? '',
                'is_fit'         => $bookingQuestionnaire->is_fit ? 'Yes' : 'No',
                'is_minor'       => $bookingQuestionnaire->is_minor ? 'Yes' : 'No',
                'signature'      => $bookingQuestionnaire->signature
                    ? public_path("storage/signatures/{$bookingQuestionnaire->signature}")
                    : null,
            ],
        ]);
        // $clients = collect($bookingQuestionnaire)->map(function ($p) {

        //     return [
        //         'id_number' => $p?->id_number ?? '',
        //         'full_name' => $p->customer?->full_name ?? '',
        //         'age' => $p->customer?->age ?? '',
        //         'phone' => $p->customer?->contact_number ?? '',
        //         'email' => $p->customer?->email_address ?? '',
        //         'gender' => $p->gender?->name ?? '',
        //         'country' => $p->country?->name ?? '',
        //         'province' => $p->province?->name ?? '',
        //         'gender' => $p->gender->name ?? '',
        //         'country' => $p->country->name ?? '',
        //         'province' => $p->province->name ?? '',
        //         'is_medical' => 'No', // $p->is_medical ? 'Yes' : 'No',
        //         'medical_detail' => $p?->medical_detail ?? '',
        //         'is_fit' => 'No', //$p?->is_fit ? 'Yes' : 'No',
        //         'is_minor' => 'No', //$p?->is_minor ? 'Yes' : 'No',
        //     ];
        // });
        $this->info($clients->toJson(JSON_PRETTY_PRINT));
        //dd($service);
        $year = now()->format('Y');

        $pdf = Pdf::loadView(
            count($clients) > 1 ? 'pdfs.indemnity-multi' : 'pdfs.indemnity-single',
            compact('booking', 'clients', 'service', 'year')
        );

        $filename = 'indemnities/indemnity-' . $booking->reference . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        $this->info("PDF saved to storage/app/public/{$filename}");

        // Optional: attach email send test
        if ($this->confirm('Send test email to booking customer?')) {
            $recipient = $customer->email_address;
            $attachments = [
                [
                    'data' => $pdf->output(),
                    'name' => "indemnity-{$booking->reference}.pdf",
                    'mime' => 'application/pdf',
                ],
            ];

            Mail::to($recipient)->send(new CheckInMail([
                'subject' => "Indemnity Form - {$booking->reference}",
                'content' => "Hello {$customer->full_name},<br>Your indemnity form is attached.",
            ], $attachments));

            $this->info("Email sent to {$recipient}");
        }
        if (false) {
            $site = Team::select('full_name', 'reg_no', 'address', 'address_code', 'account_owner', 'account_number', 'account_type', 'bank_name', 'branch_code')->find($booking->team_id);
            $guide = User::find($validated['lead_guide_id']);
            $safetyGuide = User::find($validated['safety_guide_id']);
            $customer = Customer::find($validated['customer_id']);
            $bookingItem = BookingItem::find($validated['booking_item_id']);

            $service = Service::find($bookingItem->service_id);

            $clients = collect($bookingQuestionnaire)->map(function ($p) {
                return [
                    'id_number' => $p->id_number,
                    'full_name' => $p->full_name,
                    'age' => $p->age,
                    'phone' => $p->contact_number,
                    'email' => $p->email_address,
                    'gender' => $p->gender->name ?? '',
                    'country' => $p->country->name ?? '',
                    'province' => $p->province->name ?? '',
                    'is_medical' => $p->is_medical ? 'Yes' : 'No',
                    'medical_detail' => $p->medical_detail,
                    'is_fit' => $p->is_fit ? 'Yes' : 'No',
                    'is_minor' => $p->is_minor ? 'Yes' : 'No',
                ];
            });
            $data = [
                'logo' => '<img src="logo.png" alt="Logo">',
                'site_name' => $site->full_name,
                'site_reg_no' => $site->reg_no,
                'site_address' => $site->address,
                'site_address_code' => $site->address_code,
                'booking_ref' => $booking->reference,
                'date' => now()->format('d M Y'),
                'guide_name' => optional($guide)->full_name,
                'safety_guide_name' => optional($safetyGuide)->full_name,
                'customer_name' => $customer->full_name,
                'customer_contact_number' => $customer->contact_number,
                'customer_email' => $customer->email_address,
                'participants' => collect($booking->participants)->map(function ($p) {
                    return [
                        'name' => $p->full_name,
                        'age' => $p->age,
                        'gender' => $p->gender->name ?? '',
                        'country' => $p->country->name ?? '',
                        'province' => $p->province->name ?? '',
                        'is_medical' => $p->is_medical ? 'Yes' : 'No',
                        'medical_detail' => $p->medical_detail,
                        'is_fit' => $p->is_fit ? 'Yes' : 'No',
                        'is_minor' => $p->is_minor ? 'Yes' : 'No',
                    ];
                }),
                'children' => collect($booking->children)->map(fn($c) => [
                    'name' => $c->full_name,
                    'age' => $c->age,
                    'gender' => $c->gender->name ?? '',
                ]),
                'signature' => $customer->signature ?? null,
                'year' => date('Y'),
            ];

            $pdf = Pdf::loadView(
                count($clients) > 1 ? 'pdf.indemnity-multi' : 'pdf.indemnity-single',
                compact('booking', 'clients', 'service')
            );
            //return $pdf->download('indemnity.pdf');
            $indemnityFilename = 'invoices/' . $data['invoice']['number'] . '.pdf';
            Storage::disk('public')->put($indemnityFilename, $pdf->output());
            File::create([
                'team_id'      => $booking->team_id,
                'table_name'   => 'booking_questionnaires',
                'table_row_id' => $bookingQuestionnaire->id,
                'file_name'    => $data['invoice']['number'] . '.pdf',
                'file_type'    => "application/pdf",
                'type_file_id' => 5, // invoice
                'path'         => $indemnityFilename,
                'booking_id'   => $booking->id,
            ]);

            $subject = 'Indemnity ' . $data['invoice']['number'];
            $recipient = $customer->email_address;


            $content = "
                        <p>Hello {$customer->full_name},</p>
                        <p>Your booking has been paid.</p>
                        <p>Thank you for choosing Canopy Tours.</p>
                        <p>Your booking reference is {$booking->reference}.</p>
                        <p>Please Contact us if you have any questions.</p>";

            Email::create([
                'sender'         => config('mail.from.address'),
                'receiver'       => $recipient,
                'subject'        => 'Invoice ' . $data['invoice']['number'],
                'body'           => strip_tags($content), // or a summary
                'attachment_path' => $indemnityFilename,
                'sent'           => true,
                'sent_at'        => now(),
                'user_id'        => null,
                'auditable_type' => 'App\Models\BookingQuestionnaire',
                'auditable_id'   => $bookingQuestionnaire->id,
                'team_id'        => $bookingQuestionnaire->team_id,
            ]);

            $attachments = [
                [
                    'data' => $pdf->output(),
                    'name' => "invoice-{$data['invoice']['number']}.pdf",
                    'mime' => 'application/pdf',
                ],
            ];
            Mail::to($recipient)->send(new CheckInMail($data, $attachments));
        }
        $this->info("✅ Indemnity generation complete!");
    }
}
