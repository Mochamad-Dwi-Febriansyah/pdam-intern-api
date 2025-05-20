<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Helpers\LogHelper;
use App\Mail\MailSendResetPassword;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 'error validation', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            LogHelper::log('auth_login_failed', 'Failed login attempt', null, ['email' => $request->email, 'ip' => $request->ip(),], 'error');
            return $this->errorResponse(null, 'Incorrect credentials', Response::HTTP_UNAUTHORIZED);
        }

        $user = auth()->user();

        $berkasApproved = Document::firstWhere([
            'user_id' => $user->id,
            'document_status' => 'accepted'
        ]);

        if (!$berkasApproved) {
            return $this->errorResponse(null, 'Berkas belum disetujui', Response::HTTP_FORBIDDEN);
        }

        $customClaims = [
            'name' => $user->name,
            'nisn_npm_nim' => $user->nisn_npm_nim,
            'email' => $user->email,
            'role' => $user->getRoleNames(),
        ];

        $token = JWTAuth::claims($customClaims)->attempt($request->only('email', 'password'));

        $refreshToken = JWTAuth::fromUser($user);

        $customData = [
            'access_token' => $token,
            'refresh_token' => $refreshToken,
        ];

        LogHelper::log('auth_login', 'User logged in', $user, ['ip' => $request->ip(), 'user_agent' => $request->header('User-Agent'),]);

        return $this->successResponse($customData, 'Login successful', Response::HTTP_OK);
    }

    public function register(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 'error validation', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            DB::commit();

            LogHelper::log('auth_store', 'User registered', $user, [
                'user' => $user->id,
            ]);

            return $this->successResponse(null, 'User registered successfully', Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('auth_store', 'An error occurred while saving data', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while saving data', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        LogHelper::log('auth_logout', 'User logged out');
        return $this->successResponse(null, 'Successfully logged out', Response::HTTP_OK);
    }

    public function me()
    {
        LogHelper::log('auth_me', 'User get me');

        $user = JWTAuth::parseToken()->authenticate()->load('document','document.schoolUni');

        $user['role'] = $user->getRoleNames()->first();

        return $this->successResponse($user, 'OK', Response::HTTP_OK);
    }

    public function validateToken(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return $this->errorResponse(null, 'Token is required', Response::HTTP_UNAUTHORIZED);
        }
        try {
            if (JWTAuth::setToken($token)->check()) {
                $user = JWTAuth::toUser($token);
                $session_id = $user->id;
                $is_expired = JWTAuth::getPayload($token)->get('exp') < time();

                $responseData = [
                    'user_session' => [
                        'session_id' => $session_id,
                        'is_expired' => $is_expired,
                    ]
                ];
                LogHelper::log('auth_validate_token', 'User token is valid');
                return $this->successResponse($responseData, 'Token is valid', Response::HTTP_OK);
            } else {
                LogHelper::log('auth_validate_token', 'User invalid token', null, [], 'error');
                return $this->errorResponse(null, 'Invalid token', Response::HTTP_UNAUTHORIZED);
            }
        } catch (JWTException $e) {
            LogHelper::log('auth_validate_token', 'An error occurred while validated token', null, [], 'error');
            return $this->errorResponse(null, 'Invalid token', Response::HTTP_UNAUTHORIZED);
        }
    }

    public function refreshToken(Request $request)
    {
        $refreshToken = $request->bearerToken();

        if (!$refreshToken) {
            return $this->errorResponse(null, 'Refresh token is required', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();
            $newToken = JWTAuth::refresh($refreshToken);
            LogHelper::log('auth_refresh_token', 'User token refreshed is valid');
            return $this->successResponse(['access_token' => $newToken], 'Token refreshed successfully', Response::HTTP_OK);
        } catch (JWTException $e) {
            LogHelper::log('auth_refresh_token', 'An error occurred while refreshed token', null, [], 'error');
            return $this->errorResponse(null, 'Invalid refresh token', Response::HTTP_UNAUTHORIZED);
        }
    }

    public function currentSession(Request $request)
    {
        $deviceInfo = $request->input('device_info');

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        $data = [
            'user_device' => [
                'device_info' => $deviceInfo,
            ],
            'latitude' => $latitude,
            'longitude' => $longitude,

        ];
        LogHelper::log('auth_current_session', 'User current session');
        return $this->successResponse($data, 'OK', Response::HTTP_OK);
    }

    public function getRoleName(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $result = [
            'role' => $user->getRoleNames()
        ];
        return $this->successResponse($result, 'Successfully retrieved user roles.', Response::HTTP_OK);
    }

    public function getPermissions(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $result = [
            'permissions' => $user->getAllPermissions()->pluck('name')
        ];
        return $this->successResponse($result, 'Successfully retrieved user permissions.', Response::HTTP_OK);
    }

    public function hasRole($role)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $result = [
            'has_role' => $user->hasRole($role) ?? false
        ];
        $message = $result['has_role']
            ? "User has the role '$role'."
            : "User does not have the role '$role'.";
        return $this->successResponse($result, $message, Response::HTTP_OK);
    }

    public function hasPermission($permission)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $result = [
            'has_permission' => $user->hasPermissionTo($permission) ?? false
        ];
        $message = $result['has_permission']
            ? "User has the permission '$permission'."
            : "User does not have the permission '$permission'.";
        return $this->successResponse($result, $message, Response::HTTP_OK);
    }

    public function forgotPassword(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 'Validation error', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::where('email', $request->email)->first();

        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => now()]
        );

        // Kirim email ke user
        Mail::to($user->email)->send(new MailSendResetPassword($token));

        return $this->successResponse(null, 'A password reset link has been sent to your email address.', Response::HTTP_OK); 
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'password' => 'required|min:6|confirmed'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 'Validation error', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $reset = DB::table('password_reset_tokens')->where('token', $request->token)->first();

        if (!$reset) {
            return $this->errorResponse(null, 'The reset token is invalid or has expired.', Response::HTTP_UNPROCESSABLE_ENTITY); 
        } 

        $user = User::where('email', $reset->email)->first();
        if (!$user) {
            return $this->errorResponse(null, 'User not found.', Response::HTTP_NOT_FOUND);  
        }

        // Update password
        $user->update(['password' => Hash::make($request->password)]);

        // Hapus token
        DB::table('password_reset_tokens')->where('email', $reset->email)->delete();

        return $this->successResponse(null, 'Your password has been successfully updated. Please log in again.', Response::HTTP_OK);  
    }
}
