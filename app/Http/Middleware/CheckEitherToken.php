<?php

namespace App\Http\Middleware;

use App\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Http;
use Tymon\JWTAuth\Exceptions\JWTException;

class CheckEitherToken
{
    use ApiResponse;

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
    
        if (!$token) {
            return $this->errorResponse(null, 'Token not found', Response::HTTP_UNAUTHORIZED);
        }
    
        // Coba validasi JWT Token
        try {
            if ($user = JWTAuth::setToken($token)->authenticate()) {
                $request->merge(['user' => $user]);
                return $next($request); // JWT token valid, lanjutkan
            }
        } catch (JWTException $e) {
            // Jangan return error di sini, lanjut cek SSO token
        }
    
        // Jika JWT gagal, coba validasi token sebagai SSO
        $ssoResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->get(config('services.sso.server_url') . '/api/auth/me');
    
        if ($ssoResponse->failed()) {
            return $this->errorResponse(null, 'Token is invalid (JWT/SSO)', Response::HTTP_UNAUTHORIZED);
        }
    
        $mentorData = $ssoResponse->json();
    
        if (!isset($mentorData['data']['user']['npp'])) {
            return $this->errorResponse(null, 'Invalid SSO response format', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    
        $mentorId = $mentorData['data']['user']['npp'];
        $request->merge(['user_sso_id' => $mentorId]);
    
        return $next($request); // SSO token valid, lanjutkan
    }
    
}
