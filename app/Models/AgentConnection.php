<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AgentConnection extends Model
{
    protected $fillable = ['user_id', 'name', 'device_id', 'token_id', 'token_hash', 'last_seen_at', 'revoked_at'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(DiagnosticReport::class);
    }

    public function telemetrySamples(): HasMany
    {
        return $this->hasMany(TelemetrySample::class);
    }

    public function latestTelemetry(): HasOne
    {
        return $this->hasOne(TelemetrySample::class)->latestOfMany('captured_at');
    }

    public function latestReport(): HasOne
    {
        return $this->hasOne(DiagnosticReport::class)->latestOfMany('collected_at');
    }
}
