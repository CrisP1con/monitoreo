<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('diagnostic_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_connection_id')->constrained()->cascadeOnDelete();
            $table->string('report_key', 64);
            $table->string('incident_type', 50);
            $table->timestamp('collected_at');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('event_count')->default(0);
            $table->boolean('has_unexpected_shutdown')->default(false);
            $table->json('summary');
            $table->json('events');
            $table->json('system_state')->nullable();
            $table->timestamps();
            $table->unique(['agent_connection_id', 'report_key']);
            $table->index(['agent_connection_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_reports');
    }
};
