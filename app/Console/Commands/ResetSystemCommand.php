<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use App\Models\File as FileModel;

class ResetSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:reset';
    // php artisan system:reset
    // protected $signature = 'db:truncate {tables* : List of tables to truncate}';
    protected $description = 'Force truncate given tables ignoring foreign key constraints';

    public function handle()
    {
        if (! $this->confirm('Are you sure you want to reset the system? This will delete data and files.')) {
            return;
        }

        //  'files'
        $tables = [
            'invoices',
            'invoice_items',
            'gift_vouchers',
            'gift_voucher_items',
            'emails',
            'customers',
            'customer_manifests',
            'customer_discounts',
            'booking_details',
            'booking_discounts',
            'booking_discount_items',
            'booking_fees',
            'booking_infos',
            'booking_inventories',
            'booking_items',
            'booking_items_customer',
            'booking_payments',
            'booking_staff',
            'booking_questionnaires',
            'bookings',
        ];
        $folders = [
            storage_path('app/public/indemnities'),
            storage_path('app/public/invoices'),
            storage_path('app/public/payment_proofs'),
            storage_path('app/public/signatures'),
            storage_path('app/public/vouchers'),
            storage_path('logs'),
            //public_path('images/temp'),
        ];

        $this->info('Disabling foreign key checks...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($tables as $table) {
            $this->warn("Truncating table: {$table}");
            DB::table($table)->truncate();
        }

        $files = FileModel::withTrashed()->whereIn('type_file_id', [4, 5, 6, 7])->get(); // invoice, checkin, booking_voucher, signature
        $this->info('files count', count($files));
        foreach ($files as $file) {
            $this->warn("Deleting file: {$file->path}");
            File::delete(storage_path('app/public/' . $file->path));
            // $file->delete();
            $file->forceDelete();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->info('Foreign key checks re-enabled.');
        foreach ($folders as $folder) {
            if (File::exists($folder)) {
                $this->warn("Clearing folder: {$folder}");
                File::cleanDirectory($folder);
                // If you want to delete the folders entirely
                // File::deleteDirectory($folder);
                // File::makeDirectory($folder, 0755, true);
            } else {
                $this->warn("Skipping missing folder: {$folder}");
            }
        }


        $this->info('✅ Truncation complete!');
    }
}
