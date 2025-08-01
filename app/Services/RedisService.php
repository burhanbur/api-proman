<?php 

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Facades\JWTAuth;

class RedisService
{
	private static $instance = null;

    public static function getInstance()
    {
        if(self::$instance == null)
        {
            self::$instance = new RedisService();
        }

        return self::$instance;
    }

    /**
     * Store a JWT token in Redis for a given user UUID
     *
     * The token is stored in three different ways:
     * 1. As a simple key-value pair with TTL (token details)
     * 2. As a mapping from token to user UUID (for fast lookup)
     * 3. As a sorted set with score = timestamp expired (for fast identification of valid tokens)
     *
     * @param string $uuid The user UUID
     * @param string $token The JWT token
     * @return void
     */
	public function storeTokenInRedis($uuid, $token)
    {
		$ttl = JWTAuth::factory()->getTTL() * 60;

		// 1. Simpan detail token dengan TTL individu
		Redis::setex("token_details:{$token}", $ttl, json_encode([
			'uuid' => $uuid,
			'created_at' => now()->timestamp
		]));
		
		// 2. Simpan mapping token ke user untuk pencarian cepat
		Redis::setex("token_to_user:{$token}", $ttl, $uuid);
		
		// 3. Tambahkan token ke sorted set dengan score = timestamp expired
		// Dengan sorted set, kita bisa mengidentifikasi token valid dengan membandingkan score dengan waktu sekarang
		$expiresAt = now()->addSeconds($ttl)->timestamp;
		Redis::zadd("user_tokens:{$uuid}", $expiresAt, $token);
    }

    /**
     * Remove a JWT token from Redis for a given user UUID.
     *
     * This method deletes the token details, the mapping from token to user,
     * and removes the token from the sorted set of user tokens.
     *
     * @param string $uuid The user UUID
     * @param string $token The JWT token
     * @return void
     */

	public function removeTokenFromRedis($uuid, $token)
	{
		// Hapus detail token
		Redis::del("token_details:{$token}");
		
		// Hapus mapping token ke user
		Redis::del("token_to_user:{$token}");
		
		// Hapus token dari sorted set user
		Redis::zrem("user_tokens:{$uuid}", $token);
	}

    /**
     * Clean expired tokens for a given user UUID.
     *
     * This method uses the user_tokens:{$uuid} sorted set to remove all tokens
     * with a score (expiration time) less than the current timestamp.
     *
     * @param string $uuid The user UUID
     * @return void
     */
	public function cleanExpiredTokens($uuid)
	{
		$now = now()->timestamp;
		// Hapus semua token dengan score < timestamp sekarang (sudah expired)
		Redis::zremrangebyscore("user_tokens:{$uuid}", '-inf', $now - 1);
	}

    /**
     * Validate a JWT token in Redis.
     *
     * This method checks if the token is valid and not expired by checking
     * the mapping from token to user UUID, and the sorted set of user tokens.
     * If the token is valid, the method returns the token details as an array.
     * If the token is invalid or expired, the method returns false.
     *
     * @param string $token The JWT token
     * @return array|false The token details, or false if invalid or expired
     */
    public function validateTokenInRedis($token)
    {
        $userUuid = Redis::get("token_to_user:{$token}");
        
        if (!$userUuid) {
            return false;
        }
        
        $now = now()->timestamp;
        $expiryTime = Redis::zscore("user_tokens:{$userUuid}", $token);
        
        if (!$expiryTime || $expiryTime < $now) {
            // Token expired or not found in sorted set
            return false;
        }
        
        $details = Redis::get("token_details:{$token}");
        return $details ? json_decode($details, true) : false;
    }

    public function getCookie($token)
    {
        return [
            'name' => config('cookie.name'),
            'value' => $token,
            'expire' => config('jwt.refresh_ttl'),
            'path' => config('cookie.path'),
            'domain' => config('cookie.domain'),
            'secure' => config('cookie.secure'),
            'httpOnly' => config('cookie.httpOnly'),
            'raw' => config('cookie.raw'),
            'sameSite' => config('cookie.sameSite'),
        ];
    }
}