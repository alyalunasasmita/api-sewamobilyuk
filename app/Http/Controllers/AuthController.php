<?php

namespace App\Http\Controllers;

use App\Mail\SendEmail;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Otps;
use Carbon\Carbon;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
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
        $otp = rand(100000, 999999); 
        Otps::create([
            'user_id' => $user->id, 
            'OTP_code' => $otp, 
            'type' => 'verify_account', 
            'exp' => now()->addMinutes(5)
        ]);
        $data = [
            'name' => $user->name, 
            'otp' => $otp
        ];
        Mail::to($user->email)->send(new SendEmail($data, 'verifikasi Email')); 
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
                'message' => 'email tidak ditemukan'
            ], 404);
        }
        Otps::where('user_id', $user->id)->where('type', 'forget_password')->whereNull('used_at')->delete();
        $otp = rand(100000, 999999);  
        Otps::create([
            'user_id' => $user->id, 
            'OTP_code' => $otp, 
            'type' => 'forget_password',
            'exp' => now()->addMinutes(5)
        ]);
        $data = [
            'otp' => $otp
        ];
        Mail::to($request->email)->send(new SendEmail($data, 'Kode OTP'));
        return response()->json([
            'status' => 'success', 
            'message' => 'kode OTP sudah ke email, expired 5 menit dari dikirim'
        ]);
    }

    public function resetPassword(Request $request){
        $request->validate([
            'email' => 'required|email', 
            'otp' => 'required', 
            'password' => 'required|min:6'
        ]); 
        $resetData = DB::table('password_reset_tokens')->where('email', $request->email)->first(); 
        if(!$resetData){
            return response()->json([
                'status' => 'error', 
                'message' => 'reset token tidak ditemukan'
            ], 400);
        }

        if(Hash::check($request->reset_token, $resetData->token)){
            return response()->json([
                'status' => 'error', 
                'message' => 'token tidak valid'
            ]);
        }

        if(Carbon::parse($resetData->created_at)->addMinute(15)->isPast()){
            return response()->json([
                'status' => 'error', 
                'message' => 'token telah kadaluarsa'
            ], 400);
        }

        $user = User::where('email', $request->email)->first(); 
        $user->password = bcrypt($request->password); 
        $user->save(); 
        
        //delete token reset
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        return response()->json([
            'status' => 'success', 
            'message' => 'password berhasil diubah'
        ]);
    }
}
