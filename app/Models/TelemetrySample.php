<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelemetrySample extends Model
{
    protected $fillable = [
        'agent_connection_id',
        'captured_at',
        'cpu_temperature_c',
        'gpu_temperature_c',
        'motherboard_temperature_c',
        'disk_temperature_c',
        'memory_used_percent',
        'cpu_package_c',
        'cpu_core_max_c',
        'cpu_cores',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'cpu_temperature_c' => 'float',
            'gpu_temperature_c' => 'float',
            'motherboard_temperature_c' => 'float',
            'disk_temperature_c' => 'float',
            'memory_used_percent' => 'float',
            'cpu_package_c' => 'float',
            'cpu_core_max_c' => 'float',
            'cpu_cores' => 'array',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(AgentConnection::class, 'agent_connection_id');
    }
}
