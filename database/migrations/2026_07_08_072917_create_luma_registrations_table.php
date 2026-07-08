<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLumaRegistrationsTable extends Migration
{
    public function up()
    {
        Schema::create('luma_registrations', function (Blueprint $table) {
            $table->id();

            // Luma identifiers
            $table->string('luma_event_id')->nullable()->index();
            $table->string('luma_event_name')->nullable();
            $table->string('luma_guest_id')->nullable()->unique(); // primary idempotency key
            $table->string('webhook_event_type')->nullable(); // e.g. event.person_added, event.person_status_changed
            $table->boolean('signature_valid')->nullable(); // null = no signature check configured
            $table->string('source_ip')->nullable();

            // Basic guest details (parsed out for easy querying/reporting)
            $table->string('email')->nullable()->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company')->nullable();
            $table->string('job_title')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->nullable(); // registered, attended, cancelled, no_show
            $table->timestamp('registration_date')->nullable();

            // HubSpot sync state (used by the next phase)
            $table->string('hubspot_contact_id')->nullable();
            $table->timestamp('hubspot_marketing_event_synced_at')->nullable();
            $table->string('last_status_synced')->nullable();
            $table->string('sync_state')->default('pending'); // pending, synced, failed
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();

            // Full raw capture — every webhook response logged as-is
            $table->longText('raw_payload')->nullable(); // decoded JSON, for querying
            $table->longText('raw')->nullable();          // exact raw request body, for audit/replay

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('luma_registrations');
    }
}
