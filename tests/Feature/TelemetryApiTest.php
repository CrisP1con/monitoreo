<?php

namespace Tests\Feature;

use App\Models\AgentConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TelemetryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_upload_telemetry_samples(): void
    {
        $token = 'dc_test1234_secret-token';
        $connection = AgentConnection::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Servidor de prueba',
            'device_id' => 'server-test',
            'token_id' => 'test1234',
            'token_hash' => Hash::make($token),
        ]);

        $response = $this->withHeader('X-Diagnostics-Token', $token)->postJson('/api/diagnostics/telemetry', [
            'samples' => [[
                'captured_at' => '2026-10-01T18:00:01.6891945+00:00',
                'cpu_temperature_c' => 45,
                'cpu_package_c' => 47,
                'cpu_core_max_c' => 49,
                'cpu_cores' => ['1' => 45, '2' => 46, '3' => 48, '4' => 49],
                'gpu_temperature_c' => 52,
                'memory_used_percent' => 42,
            ]],
        ]);

        $response->assertOk()->assertJson(['accepted' => 1]);
        $this->assertDatabaseHas('telemetry_samples', [
            'agent_connection_id' => $connection->id,
            'cpu_temperature_c' => 45,
            'gpu_temperature_c' => 52,
            'cpu_package_c' => 47,
            'cpu_core_max_c' => 49,
        ]);

        $this->assertSame(['1' => 45, '2' => 46, '3' => 48, '4' => 49], $connection->telemetrySamples()->firstOrFail()->cpu_cores);
    }

    public function test_invalid_agents_cannot_upload_telemetry(): void
    {
        $response = $this->withHeader('X-Diagnostics-Token', 'dc_invalid_token')->postJson('/api/diagnostics/telemetry', [
            'samples' => [['captured_at' => '2026-10-01T18:00:01Z', 'cpu_temperature_c' => 45]],
        ]);

        $response->assertUnauthorized();
    }
}
