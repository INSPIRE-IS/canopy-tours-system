<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_indemnity_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('document_type')->default('checkin_indemnity');
            $table->string('document_version')->default('generic-v1');
            $table->longText('indemnity_text');
            $table->char('indemnity_text_hash', 64);
            $table->json('document_snapshot')->nullable();
            $table->char('document_snapshot_hash', 64)->nullable();
            $table->json('answers_snapshot')->nullable();
            $table->string('signer_name')->nullable();
            $table->string('signer_email')->nullable();
            $table->string('signer_phone')->nullable();
            $table->string('guardian_name')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->string('viewed_ip', 45)->nullable();
            $table->text('viewed_user_agent')->nullable();
            $table->timestamp('signed_at');
            $table->string('signed_ip', 45)->nullable();
            $table->text('signed_user_agent')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('signed_pdf_path');
            $table->char('signed_pdf_hash', 64);
            $table->boolean('email_requested')->default(false);
            $table->string('emailed_to')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('booking_item_id')->nullable()->constrained('booking_items')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_manifest_id')->nullable()->constrained('customer_manifests')->nullOnDelete();

            $table->index(['team_id', 'booking_id']);
            $table->index(['booking_item_id', 'customer_id']);
            $table->index('customer_manifest_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_indemnity_documents');
    }
};
