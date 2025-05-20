<?php

namespace App\Http\Middleware;

use App\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckJWTToken
{
    use ApiResponse; 

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
         
        if (!$token) {
            return $this->errorResponse(null, 'Token not found', Response::HTTP_UNAUTHORIZED);
        }

        try { 
            if (!$user = JWTAuth::parseToken()->authenticate()) { 
                return $this->errorResponse(null, 'User not found', Response::HTTP_UNAUTHORIZED);
            }
 
            $request->merge(['user' => $user]); 

        } catch (JWTException $e) { 
            return $this->errorResponse(null, 'Token is invalid or expired', Response::HTTP_UNAUTHORIZED);
        }
 
        return $next($request);
    
    }
}
