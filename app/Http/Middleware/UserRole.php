<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class UserRole
{
    use ApiResponse;
    
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('Akses tidak diizinkan. Silakan login.', 401);
        }

        // Cek system role
        $systemRole = $user->systemRole->code ?? null;
        if ($systemRole && in_array($systemRole, $roles)) {
            return $next($request);
        }

        return $this->errorResponse('Akses tidak diizinkan.', 403);
    }
}
