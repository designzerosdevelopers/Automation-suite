<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            
            $table->string('call_id')->nullable()->index();
            
            $table->string('google_event_id')->nullable()->index();
            $table->string('google_event_link')->nullable();
            $table->string('google_meet_link')->nullable();
            $table->string('google_sync_status')->nullable()->default('pending');
            $table->text('google_sync_error')->nullable();
            
            $table->dateTime('appointment_time');
            $table->integer('duration_minutes')->default(60);
            $table->string('service')->nullable();
            $table->string('status')->default('confirmed');
            $table->string('source_call_id')->nullable();
            $table->boolean('reminder_2h_sent')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            // New fields for multiple booking handling
            $table->timestamp('last_booking_at')->nullable();
            $table->integer('booking_count_today')->default(0);
            $table->timestamp('last_cancellation_at')->nullable();
            $table->integer('cancellation_count_today')->default(0);
            $table->boolean('is_flagged')->default(false);
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['appointment_time', 'status']);
            $table->index('phone');
            $table->index('google_sync_status');
            $table->index(['phone', 'status', 'appointment_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};