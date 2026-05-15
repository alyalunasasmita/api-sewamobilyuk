<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Models\User;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role): Response
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'token tidak ditemukan'
            ], 401);
        }
        try {
            $decode = JWT::decode(
                $token,
                new Key(env('JWT_SECRET_KEY'), 'HS256')
            );
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'token tidak valid',
                'error' => $e->getMessage()
            ], 401);
        }
        $user = User::find($decode->sub);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'pengguna tidak ditemukan'
            ], 404);
        }
        $request->attributes->set('user', $user);
        if ($decode->role != $role) {
            return response()->json([
                'status' => 'error',
                'message' => 'anda tidak memiliki akses'
            ], 403);
        }
        return $next($request);
    }
}