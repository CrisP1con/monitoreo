<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgentConnectionRequest;
use App\Models\AgentConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ConnectivityController extends Controller
{
    public function index(): Response
    {
        $connections = auth()->user()->agentConnections()->withCount('reports')->latest()->get()->map(fn (AgentConnection $connection): array => [
            'id' => $connection->id,
            'name' => $connection->name,
            'device_id' => $connection->device_id,
            'last_seen_at' => $connection->last_seen_at?->toIso8601String(),
            'revoked_at' => $connection->revoked_at?->toIso8601String(),
            'reports_count' => $connection->reports_count,
        ]);

        return Inertia::render('Connectivity', [
            'endpoint' => $this->endpoint(),
            'connections' => $connections,
            'newConnection' => session('newConnection'),
        ]);
    }

    public function store(StoreAgentConnectionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $token = $this->newToken();
        $connection = auth()->user()->agentConnections()->create([
            'name' => $data['name'],
            'device_id' => $data['device_id'],
            'token_id' => $token['id'],
            'token_hash' => Hash::make($token['value']),
        ]);

        return redirect()->route('connectivity.index')->with('newConnection', [
            'name' => $connection->name,
            'device_id' => $connection->device_id,
            'token' => $token['value'],
            'endpoint' => $this->endpoint(),
        ]);
    }

    public function rotate(AgentConnection $agentConnection): RedirectResponse
    {
        $connection = $this->ownedConnection($agentConnection);
        $token = $this->newToken();
        $connection->update(['token_id' => $token['id'], 'token_hash' => Hash::make($token['value']), 'revoked_at' => null]);

        return back()->with('newConnection', ['name' => $connection->name, 'device_id' => $connection->device_id, 'token' => $token['value'], 'endpoint' => $this->endpoint()]);
    }

    public function revoke(AgentConnection $agentConnection): RedirectResponse
    {
        $this->ownedConnection($agentConnection)->update(['revoked_at' => now()]);

        return back();
    }

    private function newToken(): array
    {
        $id = Str::lower(Str::random(16));

        return ['id' => $id, 'value' => 'dc_'.$id.'_'.Str::random(48)];
    }

    private function endpoint(): string
    {
        return rtrim(config('app.url'), '/').'/api/diagnostics/reports';
    }

    private function ownedConnection(AgentConnection $connection): AgentConnection
    {
        abort_unless($connection->user_id === auth()->id(), 404);

        return $connection;
    }
}
