<?php

namespace App\Http\Controllers;

use App\Models\AgentConnection;
use App\Models\DiagnosticReport;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $since = now()->subDay();
        $connectionsQuery = $user->agentConnections();
        $connectionIds = (clone $connectionsQuery)->pluck('id');
        $reports = DiagnosticReport::query()
            ->whereIn('agent_connection_id', $connectionIds)
            ->where('collected_at', '>=', $since);

        $connections = $connectionsQuery
            ->withCount('reports')
            ->with(['latestReport', 'latestTelemetry'])
            ->latest()
            ->get()
            ->map(fn (AgentConnection $connection): array => [
                'id' => $connection->id,
                'name' => $connection->name,
                'device_id' => $connection->device_id,
                'last_seen_at' => $connection->last_seen_at?->toIso8601String(),
                'revoked_at' => $connection->revoked_at?->toIso8601String(),
                'reports_count' => $connection->reports_count,
                'status' => $this->connectionStatus($connection),
                'latest_report' => $connection->latestReport ? [
                    'collected_at' => $connection->latestReport->collected_at?->toIso8601String(),
                    'title' => $connection->latestReport->summary['title'] ?? 'Eventos importantes',
                    'event_count' => $connection->latestReport->event_count,
                    'has_unexpected_shutdown' => $connection->latestReport->has_unexpected_shutdown,
                ] : null,
                'telemetry' => $connection->latestTelemetry ? [
                    'captured_at' => $connection->latestTelemetry->captured_at?->toIso8601String(),
                    'cpu_temperature_c' => $connection->latestTelemetry->cpu_temperature_c,
                    'gpu_temperature_c' => $connection->latestTelemetry->gpu_temperature_c,
                    'memory_used_percent' => $connection->latestTelemetry->memory_used_percent,
                ] : null,
            ]);

        $recentAlerts = (clone $reports)
            ->with('connection:id,name')
            ->latest('collected_at')
            ->limit(8)
            ->get()
            ->map(fn (DiagnosticReport $report): array => [
                'id' => $report->id,
                'connection_id' => $report->agent_connection_id,
                'connection_name' => $report->connection->name,
                'collected_at' => $report->collected_at?->toIso8601String(),
                'title' => $report->summary['title'] ?? 'Eventos importantes',
                'event_count' => $report->event_count,
                'has_unexpected_shutdown' => $report->has_unexpected_shutdown,
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_connections' => $connections->count(),
                'active_connections' => $connections->where('revoked_at', null)->count(),
                'online_connections' => $connections->filter(fn (array $connection): bool => $connection['status'] === 'online')->count(),
                'reports_last_24h' => (clone $reports)->count(),
                'unexpected_shutdowns' => (clone $reports)->where('has_unexpected_shutdown', true)->count(),
                'events_last_24h' => (clone $reports)->sum('event_count'),
            ],
            'connections' => $connections,
            'recent_alerts' => $recentAlerts,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    private function connectionStatus(AgentConnection $connection): string
    {
        if ($connection->revoked_at) {
            return 'revoked';
        }

        return $connection->last_seen_at?->greaterThanOrEqualTo(Carbon::now()->subMinutes(10)) ? 'online' : 'offline';
    }
}
