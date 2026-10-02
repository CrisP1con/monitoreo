<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTelemetrySamplesRequest;
use App\Models\AgentConnection;
use App\Models\TelemetrySample;
use Illuminate\Http\JsonResponse;

class TelemetryController extends Controller
{
    public function store(StoreTelemetrySamplesRequest $request): JsonResponse
    {
        /** @var AgentConnection $connection */
        $connection = $request->attributes->get('diagnostic_connection');
        $now = now();
        $samples = collect($request->validated('samples'))
            ->map(fn (array $sample): array => [
                'agent_connection_id' => $connection->id,
                'captured_at' => $sample['captured_at'],
                'cpu_temperature_c' => $sample['cpu_temperature_c'] ?? null,
                'gpu_temperature_c' => $sample['gpu_temperature_c'] ?? null,
                'motherboard_temperature_c' => $sample['motherboard_temperature_c'] ?? null,
                'disk_temperature_c' => $sample['disk_temperature_c'] ?? null,
                'memory_used_percent' => $sample['memory_used_percent'] ?? null,
                'cpu_package_c' => $sample['cpu_package_c'] ?? null,
                'cpu_core_max_c' => $sample['cpu_core_max_c'] ?? null,
                'cpu_cores' => isset($sample['cpu_cores']) ? json_encode($sample['cpu_cores'], JSON_THROW_ON_ERROR) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        TelemetrySample::upsert(
            $samples,
            ['agent_connection_id', 'captured_at'],
            ['cpu_temperature_c', 'gpu_temperature_c', 'motherboard_temperature_c', 'disk_temperature_c', 'memory_used_percent', 'cpu_package_c', 'cpu_core_max_c', 'cpu_cores', 'updated_at'],
        );

        return response()->json(['accepted' => count($samples)]);
    }
}
