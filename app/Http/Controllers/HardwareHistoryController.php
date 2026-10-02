<?php

namespace App\Http\Controllers;

use App\Models\AgentConnection;
use Inertia\Inertia;
use Inertia\Response;

class HardwareHistoryController extends Controller
{
    public function show(AgentConnection $agentConnection): Response
    {
        abort_unless($agentConnection->user_id === auth()->id(), 404);

        $telemetry = $agentConnection->telemetrySamples()
            ->latest('captured_at')
            ->limit(5000)
            ->get()
            ->map(fn ($sample): array => [
                'captured_at' => $sample->captured_at?->toIso8601String(),
                'cpu_temperature_c' => $sample->cpu_temperature_c,
                'cpu_package_c' => $sample->cpu_package_c,
                'cpu_core_max_c' => $sample->cpu_core_max_c,
                'cpu_cores' => $sample->cpu_cores ?? [],
                'gpu_temperature_c' => $sample->gpu_temperature_c,
                'motherboard_temperature_c' => $sample->motherboard_temperature_c,
                'disk_temperature_c' => $sample->disk_temperature_c,
                'memory_used_percent' => $sample->memory_used_percent,
            ]);

        return Inertia::render('HardwareHistory', [
            'connection' => [
                'id' => $agentConnection->id,
                'name' => $agentConnection->name,
                'device_id' => $agentConnection->device_id,
            ],
            'telemetry' => $telemetry,
        ]);
    }
}
