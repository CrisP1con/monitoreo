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
        Schema::table('telemetry_samples', function (Blueprint $table) {
            $table->decimal('cpu_package_c', 5, 2)->nullable()->after('memory_used_percent');
            $table->decimal('cpu_core_max_c', 5, 2)->nullable()->after('cpu_package_c');
            $table->json('cpu_cores')->nullable()->after('cpu_core_max_c');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telemetry_samples', function (Blueprint $table) {
            $table->dropColumn(['cpu_package_c', 'cpu_core_max_c', 'cpu_cores']);
        });
    }
};
