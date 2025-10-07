<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Str;

class TestVoucherEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-voucher-email {email}';

    // php artisan app:test-voucher-email <email>
    // php artisan app:test-voucher-email jason@example.com

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        // Example voucher data
        $voucher = [
            'booking' => [
                'reference' => 'B2D7-C9FD',
                'date' => '2025-09-14',
            ],
            'provider' => [
                'name' => 'Magoebaskloof Adventures',
            ],
            'service' => [
                'name' => 'Kloofing',
                'location' => 'Magoebaskloof Adventures Directions From Polokwane: Take the
                                R71 north towards Tzaneen. Continue for approximately 55kms
                                towards Haenertsburg. Cross over the stop street in Haenertsburg,
                                continue for 1km and turn right at the sign that says Tzaneen via
                                George’s Valley R528. Continue on this road for 11kms and turn
                                right at the Magoebaskloof Canopy Tour sign (FARM GV11B) and
                                park at the reception. From Phalaborwa/Tzaneen: From
                                Phalaborwa, take the R71 to Tzaneen. From Tzaneen take the R528
                                towards Haenertsburg/ Polokwane. Continue on this road for
                                25kms and turn left at the Magoebaskloof Canopy Tour sign (FARM
                                GV11B) and park at the reception. From Hoedspruit: Take the R40
                                towards Mica. Just past Mica, turn left on the R526 towards
                                Tzaneen via Gravelotte. This becomes the R71. From Tzaneen take
                                the R528 towards Haenertsburg/ Polokwane. Continue on this road
                                for 25kms and turn left at the Magoebaskloof Canopy Tour sign
                                (FARM GV11B) and park at the reception. From Louis Trichardt
                                (Makhado): Take the N1 towards Polokwane. Just before the
                                Capricorn Toll Plaza, turn left onto the R36 towards
                                Modjajiskloof/Tzaneen. You will go through Soekmekaar, Mooketsi
                                and Modjajiskloof before you reach Tzaneen. From Tzaneen take
                                the R528 towards Haenertsburg/ Polokwane. Continue on this road
                                for 25kms and turn left at the Magoebaskloof Canopy Tour sign
                                (FARM GV11B) and park at the reception.',
                'start' => '13:00 on 14 Sep 2025',
                'start_location' => 'Magoebaskloof Adventures GV11B, R528, Georges Valley, Haenertsburg, Limpopo',
                'end_location' => 'Magoebaskloof Adventures GV11B, R528, Georges Valley, Haenertsburg, Limpopo',
                'directions' => "From Polokwane: Take the R71 north towards Tzaneen...\nFrom Louis Trichardt: Take the N1 towards Polokwane...",
                'what_to_wear' => "River shoes, trainers (no flip-flops), swimming costume, shorts, t-shirt, sunscreen. Bring a towel, dry clothes and warm jacket.",
            ],
            'lead' => [
                'name' => 'Jane Allison',
                'email' => 'JANE@THABAMETSI.COM',
                'party_size' => '4 adults',
            ],
            'supplier' => [
                'email' => 'adventures@thabametsi.com',
                'landline' => '0838661546',
                'mobile' => '0838661546',
            ],
        ];


        // Generate PDF
        $pdf = Pdf::loadView('emails.booking_detail', compact('voucher'));

        // Save to storage
        $bookingRef = 'REF-' . strtoupper(Str::random(6));
        //$fileName = 'voucher_' . $voucher['booking']['reference'] . '.pdf';
        $fileName = 'voucher_' . $bookingRef . '.pdf';
        $filePath = 'vouchers/' . $fileName;
        Storage::put($filePath, $pdf->output());
        // Send email
        if (false) {
            Mail::send('emails.voucher-test', ['voucher' => $voucher], function ($message) use ($email, $pdf, $fileName) {
                $message->to($email)
                    ->subject('Your Test Voucher')
                    ->attachData($pdf->output(), $fileName);
            });
        }




        $this->info("✅ Voucher sent to {$email} and saved as {$filePath}");
    }
}
