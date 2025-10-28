<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Models\File;
use App\Mail\BookingMail;

class DocumentService
{
    /**
     * Generate and store a PDF.
     */
    public function generateAndStorePdf(string $viewOrHtml, array $data, string $path, array $fileMeta, bool $isView = true)
    {
        $pdf = $isView
            ? Pdf::loadView($viewOrHtml, $data)
            : Pdf::loadHTML($viewOrHtml);

        Storage::disk('public')->put($path, $pdf->output());

        File::create([
            'team_id'      => $fileMeta['team_id'],
            'table_name'   => $fileMeta['table_name'],
            'table_row_id' => $fileMeta['table_row_id'],
            'file_name'    => $fileMeta['file_name'],
            'file_type'    => 'application/pdf',
            'type_file_id' => $fileMeta['type_file_id'] ?? null,
            'path'         => $path,
            'booking_id'   => $fileMeta['booking_id'] ?? null,
        ]);

        return $pdf;
    }

    /**
     * Send an email with optional attachments.
     */
    public function sendEmail(string $recipient, string $subject, array $data, array $attachments = [])
    {
        try {
            Mail::to($recipient)->send(new BookingMail($data, $attachments));
        } catch (\Exception $e) {
            \Log::error("Email send failed: " . $e->getMessage());
        }
    }

    /**
     * Helper to build attachment array for PDF(s)
     */
    public function prepareAttachments(...$pdfs)
    {
        $attachments = [];

        foreach ($pdfs as $pdf) {
            $attachments[] = [
                'data' => $pdf['pdf']->output(),
                'name' => $pdf['name'],
                'mime' => 'application/pdf',
            ];
        }

        return $attachments;
    }
}
