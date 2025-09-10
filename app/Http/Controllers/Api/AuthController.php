<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Services\RedisService;

use Tymon\JWTAuth\Facades\JWTAuth;

use Exception;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for authentication"
 * )
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Authenticate user and get token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","password"},
     *             @OA\Property(property="username", type="string", example="john.doe"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLC..."),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600),
     *             @OA\Property(property="formatted_expires_in", type="integer", example="2023-06-01 11:00:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid credentials"),
     *             @OA\Property(property="url", type="string", example="http://localhost:8000/api/v1/auth/login"),
     *             @OA\Property(property="method", type="string", example="POST"),
     *             @OA\Property(property="timestamp", type="string", example="2023-06-01 10:00:00")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        $user = User::where('username', $credentials['username'])->first();

        if (!$user) {
            return $this->errorResponse('Login gagal. Periksa kembali data Anda.', 401);
        }

        if (!password_verify($credentials['password'], $user->password)) {
            return $this->errorResponse('Kombinasi username dan password tidak valid.', 401);
        }

        $token = JWTAuth::fromUser($user);
        $expiredIn = JWTAuth::factory()->getTTL() * config('jwt.ttl');

        RedisService::getInstance()->storeTokenInRedis($user->uuid, $token);

        $response = response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $expiredIn,
            'formatted_expires_in' => Carbon::now()->addMinutes()->format('Y-m-d H:i:s'),
        ]);

        $response->cookie(
            config('cookie.name'),
            $token, // nilai token
            config('jwt.refresh_ttl'), // durasi dalam menit
            config('cookie.path'),
            config('cookie.domain'),
            config('cookie.secure'),
            config('cookie.httpOnly'),
            config('cookie.raw'),
            config('cookie.sameSite'),
        );

        return $response;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Logout user and invalidate token",
     *     tags={"Authentication"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logged out successfully"),
     *             @OA\Property(property="url", type="string", example="http://localhost:8000/api/v1/auth/logout"),
     *             @OA\Property(property="method", type="string", example="POST"),
     *             @OA\Property(property="timestamp", type="string", example="2023-06-01 10:00:00"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="success", type="boolean", example=true)
     *             )
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        try {
            $user = auth()->user();
            $cookieAccessToken = $request->cookie(config('cookie.name'));
            $token = JWTAuth::getToken() ? JWTAuth::getToken()->get() : $cookieAccessToken;

            RedisService::getInstance()->removeTokenFromRedis($user->uuid, $token);

            JWTAuth::invalidate($token);

            $response = $this->successResponse(
                [
                    'success' => true
                ],
                'Sesi Anda telah berakhir.'
            );

            if ($cookieAccessToken) {
                $response->cookie(
                    config('cookie.name'), // nama cookie
                    '', // nilai kosong untuk menghapus cookie
                    -1, // durasi negatif untuk menghapus cookie
                    config('cookie.path'),
                    config('cookie.domain'),
                    config('cookie.secure'),
                    config('cookie.httpOnly'),
                    config('cookie.raw'),
                    config('cookie.sameSite'),
                );
            }

            return $response;
        } catch (Exception $ex) {
            return $this->errorResponse($ex->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     summary="Refresh access token",
     *     tags={"Authentication"},
     *     @OA\Response(
     *         response=200,
     *         description="Access token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="access_token", type="string", example="token"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600),
     *             @OA\Property(property="formatted_expires_in", type="string", example="2022-01-01 00:00:00")
     *         )
     *     )
     * )
     * */
    public function refreshToken(Request $request)
    {
        try {
            $cookieAccessToken = $request->cookie(config('cookie.name'));

            $oldToken = JWTAuth::getToken();
            $oldTokenString = $oldToken->get();
            $user = JWTAuth::parseToken()->authenticate();
            $newToken = JWTAuth::refresh($oldToken);
            $expiredIn = JWTAuth::factory()->getTTL() * 60;

            RedisService::getInstance()->removeTokenFromRedis($user->uuid, $oldTokenString);
            RedisService::getInstance()->storeTokenInRedis($user->uuid, $newToken);
            
            $response = response()->json([
                'success' => true,
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => $expiredIn,
                'formatted_expires_in' => Carbon::now()->addMinutes(JWTAuth::factory()->getTTL())->format('Y-m-d H:i:s'),
            ]);

            if ($cookieAccessToken) {
                $response->cookie(
                    config('cookie.name'),
                    $newToken, // nilai token
                    config('jwt.refresh_ttl'), // durasi dalam menit
                    config('cookie.path'),
                    config('cookie.domain'),
                    config('cookie.secure'),
                    config('cookie.httpOnly'),
                    config('cookie.raw'),
                    config('cookie.sameSite'),
                );
            }

            return $response;
        } catch (Exception $ex) {
            // return $this->errorResponse('Sesi Anda telah berakhir. Silakan login kembali.', 401);
            return $this->errorResponse($ex->getMessage(), 401);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     summary="Get user data",
     *     tags={"Authentication"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="User data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User data retrieved successfully"),
     *             @OA\Property(property="url", type="string", example="http://localhost:8000/api/v1/auth/me"),
     *             @OA\Property(property="method", type="string", example="POST"),
     *             @OA\Property(property="timestamp", type="string", example="2023-06-01 10:00:00"),
     *             @OA\Property(property="data", ref="#/components/schemas/UserResource")
     *         )
     *     )
     * )
     */
    public function me(Request $request)
    {
        $user = auth()->user()->load([
            'systemRole', 
            'projectUsers.project', 
            'projectUsers.projectRole', 
            'workspaceUsers.workspace', 
            'workspaceUsers.workspaceRole', 
        ]);

        $payload = JWTAuth::getPayload();
        if ($payload->get('impersonated_by')) {
            $user->impersonated_by = $payload->get('impersonated_by');
            $user->is_impersonated = true;
        }

        return $this->successResponse(
            new UserResource($user),
            'User data retrieved successfully'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/impersonate/start/{uuid}",
     *     summary="Start impersonation",
     *     tags={"Authentication"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="Impersonation started successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Impersonation started successfully"),
     *             @OA\Property(property="url", type="string", example="http://localhost:8000/api/v1/auth/impersonate/start/550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="method", type="string", example="POST"),
     *             @OA\Property(property="timestamp", type="string", example="2023-06-01 10:00:00"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="success", type="boolean", example=true),
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLC..."),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600),
     *                 @OA\Property(property="formatted_expires_in", type="integer", example="2023-06-01 11:00:00"),
     *                 @OA\Property(property="impersonated_user", ref="#/components/schemas/UserResource")
     *             )
     *         )
     *     )
     * )
     */
    public function startImpersonate(Request $request, $uuid)
    {
        $admin = auth()->user();
        $cookieAccessToken = $request->cookie(config('cookie.name'));
        $target = User::where('uuid', $uuid)->first();

        if (!$target) {
            return $this->errorResponse('Data pengguna tidak ditemukan.', 404);
        }

        if ($admin->uuid === $target->uuid) {
            return $this->errorResponse('Tidak dapat impersonasi diri sendiri.', 403);
        }

        $target->setCustomClaims(['impersonated_by' => $admin->uuid]);
        $token = JWTAuth::fromUser($target);
        $ttl = JWTAuth::factory()->getTTL() * 60;

        // Simpan detail token dengan informasi impersonasi
        Redis::setex("token_details:{$token}", $ttl, json_encode([
            'uuid' => $target->uuid,
            'created_at' => now()->timestamp,
            'impersonated_by' => $admin->uuid,
            'is_impersonation' => true
        ]));
        
        // Simpan mapping token ke user
        Redis::setex("token_to_user:{$token}", $ttl, $target->uuid);
        
        // Tambahkan token ke sorted set dengan score = timestamp expired
        $expiresAt = now()->addSeconds($ttl)->timestamp;
        Redis::zadd("user_tokens:{$target->uuid}", $expiresAt, $token);

        $response = $this->successResponse(
            [
                'success' => true,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $ttl,
                'formatted_expires_in' => Carbon::now()->addMinutes(JWTAuth::factory()->getTTL())->format('Y-m-d H:i:s'),
                'impersonated_user' => new UserResource($target),
            ],
            'Berhasil impersonasi sebagai ' . $target->full_name . '.'
        );

        if ($cookieAccessToken) {
            $response->cookie(
                config('cookie.name'),
                $token, // nilai token
                config('jwt.refresh_ttl'), // durasi dalam menit
                config('cookie.path'),
                config('cookie.domain'),
                config('cookie.secure'),
                config('cookie.httpOnly'),
                config('cookie.raw'),
                config('cookie.sameSite'),
            );
        }

        return $response;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/impersonate/leave",
     *     summary="Leave impersonation",
     *     tags={"Authentication"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="Impersonation left successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="url", type="string", example="http://localhost:8000/api/v1/auth/impersonate/leave"),
     *             @OA\Property(property="method", type="string", example="POST"),
     *             @OA\Property(property="timestamp", type="string", example="2023-06-01 10:00:00"),
     *             @OA\Property(property="message", type="string", example="Impersonation left successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="success", type="boolean", example=true),
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLC..."),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600),
     *                 @OA\Property(property="formatted_expires_in", type="string", example="2023-06-01 11:00:00"),
     *                 @OA\Property(property="impersonated_user", ref="#/components/schemas/UserResource"),
     *                 @OA\Property(property="original_user", ref="#/components/schemas/UserResource")
     *             )
     *         )
     *     )
     * )
     */
    public function leaveImpersonate(Request $request)
    {
        $current = auth()->user();
        $originalAdminUuid = JWTAuth::getPayload()->get('impersonated_by');
        $token = JWTAuth::getToken()->get();

        if (!$originalAdminUuid) {
            return $this->errorResponse('Anda tidak sedang impersonasi pengguna lain.', 403);
        }

        $admin = User::where('uuid', $originalAdminUuid)->first();
        
        if (!$admin) {
            return $this->errorResponse('Data pengguna tidak ditemukan.', 404);
        }

        RedisService::getInstance()->removeTokenFromRedis($current->uuid, $token);
        JWTAuth::invalidate(JWTAuth::getToken());

        /** 
         * 
         * kenapa ketika assign token baru, ada custom claims yang ikut terbawa dari token sebelumnya?
        */

        // assign new token
        $adminToken = JWTAuth::claims(['impersonated_by' => null, 'is_impersonation' => false])->fromUser($admin);
        RedisService::getInstance()->storeTokenInRedis($admin->uuid, $adminToken);
        $expiresIn = JWTAuth::factory()->getTTL() * 60;

        $response = $this->successResponse(
            [
                'success' => true,
                'access_token' => $adminToken,
                'token_type' => 'bearer',
                'expires_in' => $expiresIn,
                'formatted_expires_in' => Carbon::now()->addMinutes(JWTAuth::factory()->getTTL())->format('Y-m-d H:i:s'),
                'impersonated_user' => new UserResource($current),
                'original_user' => new UserResource($admin),
            ],
            'Berhasil mengakhiri impersonasi sebagai ' . $current->full_name . '.'
        );

        $cookieAccessToken = $request->cookie(config('cookie.name'));
        if ($cookieAccessToken) {
            $response->cookie(
                config('cookie.name'),
                $adminToken, // nilai token
                config('jwt.refresh_ttl'), // durasi dalam menit
                config('cookie.path'),
                config('cookie.domain'),
                config('cookie.secure'),
                config('cookie.httpOnly'),
                config('cookie.raw'),
                config('cookie.sameSite'),     
            );
        }

        return $response;
    }
}