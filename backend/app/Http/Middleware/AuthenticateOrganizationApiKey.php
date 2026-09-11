<?php

namespace App\Http\Middleware;

use App\Models\OrganizationApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateOrganizationApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextKey = $request->bearerToken();

        if (!$plainTextKey) {
            return response()->json([
                'message' => 'API key is required.',
            ], 401);
        }

        $apiKey = OrganizationApiKey::where(
            'key_hash',
            hash('sha256', $plainTextKey)
        )
            ->whereNull('revoked_at')
            ->first();

        if (!$apiKey) {
            return response()->json([
                'message' => 'Invalid API key.',
            ], 401);
        }

        $apiKey->update([
            'last_used_at' => now(),
        ]);

        $request->attributes->set(
            'authenticated_organization',
            $apiKey->organization
        );

        return $next($request);
    }
}
