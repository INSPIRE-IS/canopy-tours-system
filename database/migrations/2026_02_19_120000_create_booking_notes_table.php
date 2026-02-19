<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('booking_item_id');
            $table->string('type');
            $table->text('note');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['booking_id', 'booking_item_id', 'type']);
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('booking_item_id')->references('id')->on('booking_items')->onDelete('cascade');
            $table->foreign('created_by_id')->references('id')->on('users')->nullOnDelete();
        });

        $query = DB::table('booking_fees')
            ->join('booking_items', 'booking_items.id', '=', 'booking_fees.booking_item_id')
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->select(
                'booking_fees.id as fee_id',
                'booking_items.booking_id',
                'booking_fees.booking_item_id',
                'bookings.user_id',
                'booking_fees.invoice_note',
                'booking_fees.email_reminder',
                'booking_fees.booking_note',
                'booking_fees.created_at'
            );

        $query->orderBy('booking_fees.id')->chunkById(200, function ($rows) {
            $batch = [];
            foreach ($rows as $row) {
                $createdAt = $row->created_at ?? now();
                $createdBy = $row->user_id ?? null;

                if (!empty($row->invoice_note)) {
                    $batch[] = [
                        'booking_id' => $row->booking_id,
                        'booking_item_id' => $row->booking_item_id,
                        'type' => 'invoice_note',
                        'note' => $row->invoice_note,
                        'created_by_id' => $createdBy,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];
                }
                if (!empty($row->email_reminder)) {
                    $batch[] = [
                        'booking_id' => $row->booking_id,
                        'booking_item_id' => $row->booking_item_id,
                        'type' => 'email_reminder',
                        'note' => $row->email_reminder,
                        'created_by_id' => $createdBy,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];
                }
                if (!empty($row->booking_note)) {
                    $batch[] = [
                        'booking_id' => $row->booking_id,
                        'booking_item_id' => $row->booking_item_id,
                        'type' => 'booking_note',
                        'note' => $row->booking_note,
                        'created_by_id' => $createdBy,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];
                }
            }

            if (!empty($batch)) {
                DB::table('booking_notes')->insert($batch);
            }
        }, 'fee_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_notes');
    }
};
