<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiagnosticReportRequest;
use App\Models\AgentConnection;
use App\Models\DiagnosticReport;
use App\Services\DiagnosticReportAnalyzer;
use Illuminate\Http\JsonResponse;

class DiagnosticReportController extends Controller
{
    public function store(StoreDiagnosticReportRequest $request, DiagnosticReportAnalyzer $analyzer): JsonResponse
    {
        /** @var AgentConnection $connection */
        $connection = $request->attributes->get('diagnostic_connection');
        $data = $request->validated();
        $summary = $analyzer->analyze($data['events']);
        $report = DiagnosticReport::updateOrCreate(
            ['agent_connection_id' => $connection->id, 'report_key' => $data['report_key']],
            [
                'incident_type' => $data['incident_type'], 'collected_at' => $data['collected_at'],
                'started_at' => $data['started_at'], 'ended_at' => $data['ended_at'] ?? null,
                'event_count' => count($data['events']), 'has_unexpected_shutdown' => $summary['has_unexpected_shutdown'],
                'summary' => $summary, 'events' => $data['events'], 'system_state' => $data['system_state'] ?? null,
            ],
        );

        return response()->json(['id' => $report->id, 'summary' => $summary], $report->wasRecentlyCreated ? 201 : 200);
    }
}
