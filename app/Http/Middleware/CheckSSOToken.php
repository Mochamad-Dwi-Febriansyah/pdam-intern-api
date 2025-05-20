<?php

namespace App\Http\Middleware;

use App\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;

class CheckSSOToken
{
    use ApiResponse;

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return $this->errorResponse(null, 'Token not found', Response::HTTP_UNAUTHORIZED);
        }

        // Kirim request ke SSO untuk validasi token
        $ssoResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->get(config('services.sso.server_url') . '/api/auth/me');

        if ($ssoResponse->failed()) {
            return $this->errorResponse(null, 'Invalid SSO token', Response::HTTP_UNAUTHORIZED);
        }

        $data = $ssoResponse->json();

        if (!isset($data['data']['user']['npp'])) {
            return $this->errorResponse(null, 'Invalid SSO response format', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Ambil ID mentor dari respons
        $userData = $data['data']['user']['npp'];

        // Simpan mentor_id ke dalam request agar bisa digunakan di controller
        $request->merge(['user_sso_id' => $userData]); 

        return $next($request);
    }
}
