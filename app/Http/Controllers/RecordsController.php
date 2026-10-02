<?php

namespace App\Http\Controllers;

use App\Models\AgentConnection;
use Inertia\Inertia;
use Inertia\Response;

class RecordsController extends Controller
{
    public function index(): Response
    {
        $connections = auth()->user()->agentConnections()
            ->withCount('reports')
            ->with('latestReport')
            ->latest()
            ->get()
            ->map(fn (AgentConnection $connection): array => $this->connectionCard($connection));

        return Inertia::render('Records', ['connections' => $connections]);
    }

    public function show(AgentConnection $agentConnection): Response
    {
        abort_unless($agentConnection->user_id === auth()->id(), 404);
        $agentConnection->loadCount('reports');
        $telemetry = $agentConnection->telemetrySamples()
            ->latest('captured_at')
            ->limit(1000)
            ->get()
            ->sortBy('captured_at')
            ->values()
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

        $reports = $agentConnection->reports()
            ->latest('started_at')
            ->limit(30)
            ->get()
            ->map(fn ($report): array => [
                'id' => $report->id,
                'incident_type' => $report->incident_type,
                'collected_at' => $report->collected_at?->toIso8601String(),
                'started_at' => $report->started_at?->toIso8601String(),
                'event_count' => $report->event_count,
                'has_unexpected_shutdown' => $report->has_unexpected_shutdown,
                'summary' => $report->summary,
                'events' => $report->events,
                'system_state' => $report->system_state,
            ]);

        return Inertia::render('RecordDetail', [
            'connection' => [
                'id' => $agentConnection->id,
                'name' => $agentConnection->name,
                'device_id' => $agentConnection->device_id,
                'last_seen_at' => $agentConnection->last_seen_at?->toIso8601String(),
                'revoked_at' => $agentConnection->revoked_at?->toIso8601String(),
                'reports_count' => $agentConnection->reports_count,
            ],
            'reports' => $reports,
            'telemetry' => $telemetry,
        ]);
    }

    private function connectionCard(AgentConnection $connection): array
    {
        $report = $connection->latestReport;

        return [
            'id' => $connection->id,
            'name' => $connection->name,
            'device_id' => $connection->device_id,
            'last_seen_at' => $connection->last_seen_at?->toIso8601String(),
            'revoked_at' => $connection->revoked_at?->toIso8601String(),
            'reports_count' => $connection->reports_count,
            'latest_report_at' => $report?->collected_at?->toIso8601String(),
            'latest_title' => $report?->summary['title'] ?? null,
            'latest_event_count' => $report?->event_count ?? 0,
            'has_unexpected_shutdown' => $report?->has_unexpected_shutdown ?? false,
        ];
    }
}
