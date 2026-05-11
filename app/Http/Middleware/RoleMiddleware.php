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
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        $token = $request->bearerToken(); 
        if(!$token){
            return response()->json([
                'status' => 'error',
                'message' => 'token tidak ditemukan'
            ], 401);
        }
        $decode = JWT::decode($token, new key(env('JWT_SECRET_KEY'),'HS256'));
        
        $user = User::find($decode->sub);
        if (!$user){
            return response()->json([
                'status' => 'error', 
                'message' => 'pengguna tidak ditemukan'
            ],404);
        }
        $request->attributes->set('user', $user); 

        if($decode->role!=$role){
            return response()->json([
                'status' =>'error', 
                'message' => 'anda tidak memiliki akses'
            ], 401);
        }
        return $next($request);
    }
}
