<?php

namespace App\Http\Controllers;

use App\Mail\SendEmail;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    
    public function register(Request $request){
        $request->validate([
            'name'=> 'required|max:255', 
            'username'=> 'required|unique:users,username', 
            'email' => 'required|email|unique:users,email', 
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email, 
            'password' => Hash::make($request->password)
        ]); 

        return response()->json([
            'status' => 'success',
            'message' => 'register berhasil', 
            'data' => $user, 
        ], 201);
    }

    public function login(Request $request){
        $request->validate([
            'login' => 'required',  
            'password' => 'required',
        ]);

        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username' ;
        
        $credentials = [
            $loginType => $request->login, 
            'password' => $request->password
        ];

        if(Auth::attempt($credentials)){
            $user = Auth::User();

            $payload = [
                'sub'=>$user->id, 
                'username'=>$user->username,
                'role'=>$user->role,
                'iat' => now()->timestamp, 
                'exp' => now()->addHours(2)->timestamp,
            ]; 
            $token = JWT::encode($payload, env('JWT_SECRET_KEY'), 'HS256');

            return response()->json([
                'status' => 'success', 
                'message' => 'Login Berhasil', 
                'token' => $token
            ]);
        }

        return response()->json([
            'status' => 'error', 
            'message' => 'username/password salah'
        ], 401);
    }

    public function logout(Request $request) {

        return response()->json([
            "status" => "success", 
            "message" => "berhasil logout"
        ]);
    }

    public function forgetPassword(Request $request) {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first(); 
        if(!$user) {
            return response()->json([
                'status' => 'error', 
                'message' => 'email dengan '. $request->email . ' tidak ditemukan'
            ]);
        }
        $otp = rand(100000, 999999);  

        $data = [
            'otp' => $otp
        ];

        Mail::to($request->email)->send(new SendEmail($data, 'Kode OTP'));
        return response()->json([
            'status' => 'success', 
            'message' => 'kode OTP sudah ke email, kalo gada di inbox cek di spam'
        ]);
    }

    
}
