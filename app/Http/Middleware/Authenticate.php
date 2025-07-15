<?php 
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use App\Services\RedisService;

class Authenticate
{
    public function handle(Request $request, Closure $next)
    {
        $token = null;
        $isCookie = false;

        // Cek Authorization header (Bearer)
        if ($request->bearerToken()) {
            $token = $request->bearerToken();
        }

        // Kalau tidak ada, cek cookie
        if (!$token && $request->hasCookie(config('cookie.name'))) {
            $token = $request->cookie(config('cookie.name'));
            $isCookie = true;
        }

        if (!$token) {
            throw new UnauthorizedHttpException('jwt-auth', 'Token tidak ditemukan.');
        }

        try {
            JWTAuth::setToken($token);
            JWTAuth::checkOrFail();

            $user = JWTAuth::authenticate();

            if (!$user) {
                throw new UnauthorizedHttpException('jwt-auth', 'Data pengguna tidak ditemukan.');
            }

            auth()->setUser($user);
        } catch (TokenExpiredException $e) {
            try {
                if (!$isCookie) {
                    throw new JWTException("Token telah kadaluwarsa", 1);
                }

                $newToken = JWTAuth::refresh($token);

                JWTAuth::setToken($newToken);
                $user = JWTAuth::authenticate();

                auth()->setUser($user);

                RedisService::getInstance()->removeTokenFromRedis($user->uuid, $token);
                RedisService::getInstance()->storeTokenInRedis($user->uuid, $newToken);

                $response = $next($request);

                return $response->withCookie(
                    cookie(
                        config('cookie.name'),
                        $newToken,
                        config('jwt.refresh_ttl'),
                        config('cookie.path'),
                        config('cookie.domain'),
                        config('cookie.secure'),
                        config('cookie.httpOnly'),
                        config('cookie.raw'),
                        config('cookie.sameSite'),
                    )
                );
            } catch (JWTException $refreshException) {
                // throw new UnauthorizedHttpException('jwt-auth', $refreshException->getMessage());
                throw new UnauthorizedHttpException('jwt-auth', 'Token telah kedaluwarsa dan tidak dapat diperbarui.');
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            throw new UnauthorizedHttpException('jwt-auth', 'Token tidak valid.');
        }

        return $next($request);
    }
}