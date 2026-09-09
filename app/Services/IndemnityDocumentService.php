<?php

namespace App\Services;

use App\Mail\IndemnityDocumentMail;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerIndemnityDocument;
use App\Models\CustomerManifest;
use App\Models\Email;
use App\Models\File;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\Questionnaire;
use App\Models\State;
use App\Models\TypeConfirmation;
use App\Models\TypeFile;
use App\Models\TypeGroup;
use App\Models\TypeReferral;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class IndemnityDocumentService
{
    public const VERSION = 'generic-v1';

    public static function document(): array
    {
        return [
            'title' => 'Digital Indemnity / Disclaimer / Waiver',
            'definitions' => [
                'In this document "Canopy Tours" means and includes the operating company, site operator, land/property owners, holding and subsidiary companies, affiliates, employees, directors, members, agents, shareholders, contractors, and representatives connected to the activity.',
                'The "activity" means and includes Canopy Tours and all related activities, whether directly or indirectly, including but not limited to use of the cableway, climbing up and down platforms, walking along pathways, and being conveyed by any motor vehicle.',
            ],
            'terms' => [
                'I acknowledge that I am aware of and appreciate the real dangers and risks associated with the activity, including that the activity may take place at considerable height and speed above the ground, where falling is an inherent danger. I also acknowledge risks including reptiles, insects, bodily harm, injury, and death. I accept and acknowledge that I participate in the activity entirely at my own risk.',
                'I understand that I need to be in good health to participate in the activity. I declare that I am in good health, that I am not pregnant, and that I am prepared to act responsibly at all times.',
                'I waive any and all claims of whatsoever cause or nature, howsoever arising against Canopy Tours, which I might have arising out of any harm, injury, death, or loss suffered while participating in, or related directly or indirectly to, the activity, whether arising from an act, omission, or negligence on the part of Canopy Tours.',
                'I undertake to indemnify and hold harmless Canopy Tours from any and all claims of whatsoever cause or nature which may be made by my spouse, children, dependants, or any other party who may suffer damages, including but not limited to personal injury, death, loss of support, or any other loss, arising from any cause whatsoever, including any act, omission, or negligence on the part of Canopy Tours.',
                'Without limiting the above, I acknowledge that Canopy Tours shall not be liable to me or any of my dependants for any injury, illness, death, harm, loss, loss of support, consequential loss, damage, or damages of whatsoever nature from any cause whatsoever that may be sustained as a result of my participation in the activity, including negligence.',
                'Any claims, whether in contract, delict, or otherwise, instituted against Canopy Tours shall be governed by the laws of South Africa and may only be instituted in a South African court, to the exclusion of all other jurisdictions.',
                'Without limiting the above clauses, I, together with my heirs, successors in title, executors, and administrators, release Canopy Tours from any and all liability and/or claims that could accrue to me or them due to illness, injury, death, and/or loss of or damage to property, irrespective of whether such claim or claims arose as a result of any act or omission, whether by negligence or gross negligence, or any other cause whatsoever.',
                'In the event of injury, Canopy Tours may at its discretion and without prejudice or admission of liability arrange and pay for emergency medical treatment for and on behalf of myself, my spouse, common-law partner, my children whether minor or adult, relatives, and/or any person accompanying me. I acknowledge that I will be directly responsible to reimburse Canopy Tours for these expenses.',
                'I allow Canopy Tours to use any pictures and/or video taken of me for any purposes they wish, including marketing, promotions, and social media.',
                'I agree not to smoke or light any flame whatsoever for the duration of the tour, from the time I leave reception for the tour until I return to reception after the tour, including the drive up to the start of the zip-line.',
            ],
        ];
    }

    public static function text(): string
    {
        $document = self::document();

        return trim(implode("\n\n", [
            $document['title'],
            'Definitions',
            implode("\n\n", $document['definitions']),
            'Terms and Conditions',
            collect($document['terms'])->map(fn($term, $index) => ((int) $index + 1) . '. ' . $term)->implode("\n\n"),
        ]));
    }

    public function generateForManifest(
        CustomerManifest $manifest,
        array $viewMeta = [],
        bool $emailRequested = false,
        ?string $signingPlace = null
    ): CustomerIndemnityDocument {
        $manifest->loadMissing([
            'customer',
            'booking_item.booking',
            'booking_item.service.team',
            'booking_item.booking_inventory.inventory_time_slot',
        ]);

        $bookingItem = $manifest->booking_item;
        $booking = $bookingItem?->booking;
        $customer = $manifest->customer;
        $service = $bookingItem?->service;
        $team = $service?->team;
        $document = self::document();
        $text = self::text();
        $answers = $this->answersSnapshot($manifest);
        $signaturePath = $this->answerValue($answers, 'signature');
        $signatureDataUri = $this->signatureDataUri($signaturePath);
        $signedAt = now();
        $uuid = (string) Str::uuid();
        $activityDate = $bookingItem?->booking_inventory?->getRawOriginal('date') ?? $bookingItem?->booking_inventory?->date;
        $activityTime = $bookingItem?->booking_inventory?->inventory_time_slot?->start_time;
        $signingPlace = trim((string) $signingPlace) ?: ($team?->name ?: 'Canopy Tours');

        $snapshot = [
            'document' => $document,
            'document_version' => self::VERSION,
            'booking' => [
                'id' => $booking?->id,
                'reference' => $booking?->reference,
                'team_id' => $booking?->team_id ?? $team?->id,
                'service' => $service?->name,
                'activity_date' => $activityDate,
                'activity_time' => $activityTime,
            ],
            'signer' => [
                'customer_id' => $customer?->id,
                'name' => $customer?->full_name,
                'email' => $customer?->email_address,
                'phone' => $customer?->contact_number,
                'guardian_name' => $this->guardianName($manifest, $answers),
                'signing_place' => $signingPlace,
                'signature_label' => $this->isMinorParticipant($manifest, $answers) ? 'Parent / Guardian Signature' : 'Signature',
            ],
            'answers' => $answers,
            'audit' => [
                'viewed_at' => $viewMeta['viewed_at'] ?? null,
                'viewed_ip' => $viewMeta['viewed_ip'] ?? null,
                'viewed_user_agent' => $viewMeta['viewed_user_agent'] ?? null,
                'signed_at' => $signedAt->toDateTimeString(),
                'signed_ip' => request()?->ip(),
                'signed_user_agent' => request()?->userAgent(),
            ],
        ];

        $pdf = Pdf::loadView('pdfs.signed-indemnity', [
            'document' => $document,
            'snapshot' => $snapshot,
            'answers' => $answers,
            'signatureDataUri' => $signatureDataUri,
            'generatedAt' => $signedAt,
        ])->setPaper('A4', 'portrait');

        $pdfBytes = $pdf->output();
        $safeReference = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($booking?->reference ?? $uuid));
        $pdfPath = sprintf('indemnities/%s/%s/%s.pdf', $booking?->team_id ?? 'global', $safeReference, $uuid);
        Storage::disk('public')->put($pdfPath, $pdfBytes);

        $record = CustomerIndemnityDocument::create([
            'uuid' => $uuid,
            'document_type' => 'checkin_indemnity',
            'document_version' => self::VERSION,
            'indemnity_text' => $text,
            'indemnity_text_hash' => hash('sha256', $text),
            'document_snapshot' => $snapshot,
            'document_snapshot_hash' => hash('sha256', json_encode($snapshot, JSON_UNESCAPED_SLASHES)),
            'answers_snapshot' => $answers,
            'signer_name' => $customer?->full_name,
            'signer_email' => $customer?->email_address,
            'signer_phone' => $customer?->contact_number,
            'guardian_name' => $snapshot['signer']['guardian_name'],
            'signing_place' => $signingPlace,
            'viewed_at' => $viewMeta['viewed_at'] ?? null,
            'viewed_ip' => $viewMeta['viewed_ip'] ?? null,
            'viewed_user_agent' => $viewMeta['viewed_user_agent'] ?? null,
            'signed_at' => $signedAt,
            'signed_ip' => request()?->ip(),
            'signed_user_agent' => request()?->userAgent(),
            'signature_path' => $signaturePath,
            'signed_pdf_path' => $pdfPath,
            'signed_pdf_hash' => hash('sha256', $pdfBytes),
            'email_requested' => $emailRequested,
            'team_id' => $booking?->team_id ?? $team?->id,
            'booking_id' => $booking?->id,
            'booking_item_id' => $bookingItem?->id,
            'customer_id' => $customer?->id,
            'customer_manifest_id' => $manifest->id,
        ]);

        $this->createFileRecord($record, $pdfPath, $booking?->reference);

        if ($emailRequested && filled($customer?->email_address)) {
            $this->sendEmail($record, $customer, $booking?->reference, $pdfBytes);
        }

        return $record;
    }

    private function answersSnapshot(CustomerManifest $manifest): array
    {
        $bookingItem = $manifest->booking_item;
        if (!$bookingItem) {
            return [];
        }

        $questions = Question::where('is_visible', true)
            ->where('type', 'indemnity')
            ->whereHas('question_sites', function ($query) use ($bookingItem) {
                $query->where('service_id', $bookingItem->service_id)
                    ->where('season_id', $bookingItem->season_id);
            })
            ->with(['question_sites' => function ($query) use ($bookingItem) {
                $query->where('service_id', $bookingItem->service_id)
                    ->where('season_id', $bookingItem->season_id);
            }])
            ->get()
            ->filter(function (Question $question) {
                $site = $question->question_sites->first();
                return $site && ($question->is_disabled ? true : (bool) $site->enabled);
            })
            ->sortBy(fn(Question $question) => $question->question_sites->first()?->sort_order ?? 0)
            ->values();

        $answers = QuestionAnswer::where('booking_item_id', $bookingItem->id)
            ->where('customer_id', $manifest->customer_id)
            ->get()
            ->keyBy('question_id');

        return $questions->map(function (Question $question) use ($answers) {
            $raw = $answers->get($question->id)?->answer;

            return [
                'question_id' => $question->id,
                'slug' => $question->slug,
                'label' => $question->name,
                'raw_answer' => $raw,
                'answer' => $this->resolveAnswer($question->slug, $raw),
            ];
        })->all();
    }

    private function resolveAnswer(string $slug, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($slug) {
            'type_group_id' => $this->lookupName(TypeGroup::class, $value),
            'referral_id' => $this->lookupName(TypeReferral::class, $value),
            'continent_id' => $this->lookupName(Continent::class, $value),
            'country_id' => $this->lookupName(Country::class, $value),
            'province_id' => $this->lookupName(State::class, $value),
            'medical_id', 'is_fit', 'is_agree', 'pregnant', 'marketing_opt_in' => $this->lookupName(TypeConfirmation::class, $value),
            'age_id' => collect(Questionnaire::AGES)->firstWhere('id', (int) $value)['name'] ?? $value,
            'gender' => collect(Questionnaire::GENDERS)->firstWhere('id', (int) $value)['name'] ?? $value,
            'parent_id' => $value,
            default => $value,
        };
    }

    private function lookupName(string $modelClass, mixed $value): mixed
    {
        if (!is_numeric($value)) {
            return $value;
        }

        return $modelClass::find($value)?->name ?? $value;
    }

    private function answerValue(array $answers, string $slug): mixed
    {
        return collect($answers)->firstWhere('slug', $slug)['raw_answer'] ?? null;
    }

    private function guardianName(CustomerManifest $manifest, array $answers): ?string
    {
        if (filled($manifest->guardian_name)) {
            return (string) $manifest->guardian_name;
        }

        $guardian = collect($answers)->firstWhere('slug', 'parent_id');
        $rawGuardian = $guardian['raw_answer'] ?? null;
        if (filled($rawGuardian) && !is_numeric($rawGuardian)) {
            return (string) $rawGuardian;
        }

        return null;
    }

    private function isMinorParticipant(CustomerManifest $manifest, array $answers): bool
    {
        if ($manifest->type === 'child') {
            return true;
        }

        $age = collect($answers)->firstWhere('slug', 'age_id');

        return (string) ($age['raw_answer'] ?? '') === '1';
    }

    private function signatureDataUri(?string $signaturePath): ?string
    {
        if (!$signaturePath || !Storage::disk('public')->exists('signatures/' . basename($signaturePath))) {
            return null;
        }

        $path = 'signatures/' . basename($signaturePath);
        $contents = Storage::disk('public')->get($path);

        return 'data:image/png;base64,' . base64_encode($contents);
    }

    private function createFileRecord(CustomerIndemnityDocument $record, string $pdfPath, ?string $reference): void
    {
        File::create([
            'team_id' => $record->team_id,
            'table_name' => 'customer_indemnity_documents',
            'table_row_id' => $record->id,
            'file_name' => 'indemnity-' . ($reference ?: $record->uuid) . '.pdf',
            'file_type' => 'application/pdf',
            'type_file_id' => TypeFile::where('name', 'checkin')->value('id'),
            'path' => $pdfPath,
            'booking_id' => $record->booking_id,
        ]);
    }

    private function sendEmail(CustomerIndemnityDocument $record, Customer $customer, ?string $reference, string $pdfBytes): void
    {
        if (!app(EmailDispatchService::class)->canSendAutomatic($record->team_id, EmailDispatchService::SETTING_AUTO_QUESTIONNAIRE)) {
            return;
        }

        $subject = 'Signed Indemnity - ' . ($reference ?: $record->uuid);
        $body = "Hello {$customer->full_name},\n\nYour signed indemnity form is attached.\n\nBooking reference: " . ($reference ?: '-') . "\n\nThank you for choosing Canopy Tours.";

        $email = Email::create([
            'sender' => config('mail.from.address'),
            'receiver' => $customer->email_address,
            'subject' => $subject,
            'body' => $body,
            'attachment_path' => $record->signed_pdf_path,
            'sent' => false,
            'sent_at' => null,
            'user_id' => auth()->id(),
            'auditable_type' => CustomerIndemnityDocument::class,
            'auditable_id' => $record->id,
            'team_id' => $record->team_id,
        ]);

        try {
            Mail::to($customer->email_address)->send(new IndemnityDocumentMail([
                'name' => $customer->full_name,
                'reference' => $reference,
            ], $pdfBytes, 'indemnity-' . ($reference ?: $record->uuid) . '.pdf'));

            $email->update([
                'sent' => true,
                'sent_at' => now(),
            ]);
            $record->update([
                'emailed_to' => $customer->email_address,
                'emailed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
