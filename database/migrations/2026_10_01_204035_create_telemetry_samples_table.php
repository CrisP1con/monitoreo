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
        Schema::create('telemetry_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_connection_id')->constrained()->cascadeOnDelete();
            $table->timestamp('captured_at');
            $table->decimal('cpu_temperature_c', 5, 2)->nullable();
            $table->decimal('gpu_temperature_c', 5, 2)->nullable();
            $table->decimal('motherboard_temperature_c', 5, 2)->nullable();
            $table->decimal('disk_temperature_c', 5, 2)->nullable();
            $table->decimal('memory_used_percent', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['agent_connection_id', 'captured_at']);
            $table->index(['agent_connection_id', 'captured_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetry_samples');
    }
};
