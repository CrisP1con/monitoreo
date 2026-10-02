<?php

namespace App\Http\Middleware;

use App\Models\AgentConnection;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDiagnosticAgent
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->header('X-Diagnostics-Token');
        $parts = explode('_', $token, 3);
        $connection = count($parts) === 3 && $parts[0] === 'dc'
            ? AgentConnection::where('token_id', $parts[1])->whereNull('revoked_at')->first()
            : null;

        if ($connection === null || ! Hash::check($token, $connection->token_hash)) {
            return response()->json(['message' => 'Token inválido o revocado.'], 401);
        }

        $connection->forceFill(['last_seen_at' => now()])->save();
        $request->attributes->set('diagnostic_connection', $connection);

        return $next($request);
    }
}
