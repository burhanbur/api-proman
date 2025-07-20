<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class UserAccess
{
    use ApiResponse;
    
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        

        return $this->errorResponse('Akses tidak diizinkan.', 403);
    }
}
