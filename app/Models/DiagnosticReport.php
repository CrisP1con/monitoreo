<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticReport extends Model
{
    protected $fillable = ['agent_connection_id', 'report_key', 'incident_type', 'collected_at', 'started_at', 'ended_at', 'event_count', 'has_unexpected_shutdown', 'summary', 'events', 'system_state'];

    protected function casts(): array
    {
        return ['collected_at' => 'datetime', 'started_at' => 'datetime', 'ended_at' => 'datetime', 'event_count' => 'integer', 'has_unexpected_shutdown' => 'boolean', 'summary' => 'array', 'events' => 'array', 'system_state' => 'array'];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(AgentConnection::class, 'agent_connection_id');
    }
}
