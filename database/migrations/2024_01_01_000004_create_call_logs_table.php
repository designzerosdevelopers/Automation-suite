<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('call_id')->nullable()->index();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->string('direction')->default('inbound'); // inbound, outbound
            $table->string('status')->default('initiated'); // initiated, in-progress, completed, failed
            $table->json('transcript')->nullable();
            $table->text('summary')->nullable();
            $table->string('intent')->nullable(); // booking, faq, escalation, other
            $table->integer('duration')->default(0);
            $table->decimal('cost', 10, 4)->default(0);
            $table->string('recording_url')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};