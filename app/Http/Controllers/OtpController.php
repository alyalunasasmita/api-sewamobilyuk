<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Otps;

class OtpController extends Controller
{
    public function verify_otp_forget_password(Request $request){
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required'
        ]); 
        $otp = Otps::where('OTP_code', $request->otp)
                ->where('type', 'forget_password')
                ->whereNull('used_at')
                ->latest()
                ->first();

        if(!$otp) {
            return response()->json([
                'status'=> 'error', 
                'message' => 'otp tidak valid, harap memasukan kode otp yang valid'
            ]); 
        }
        if($otp->exp < now()) {
            return response()->json([
                'status' => 'error', 
                'message' => 'token telah kadaluarsa, silahkan meminta kode otp yang baru'
            ]);
        }
        if (!$otp->user) {
            return response()->json([
                'status' => 'error',
                'message' => 'pengguna tidak ditemukan'
            ]);
        }

        $plainToken = Str::random(64); 
        //delete token sementara
        DB::table('password_reset_tokens')->where('email', $otp->user->email)->delete(); 
        //create token sementara
        DB::table('password_reset_tokens')->insert([
            'email' => $otp->user->email, 
            'token' => Hash::make($plainToken), 
            'created_at' => now()
        ]);

        $otp->used_at = now();
        $otp->save();
        
        return response()->json([
            'status' => 'succes', 
            'message' => 'verifikasi OTP berhasil',
            'reset_token' => $plainToken
        ]);        
    }

    public function verify_otp_account(Request $request){
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required'
        ]); 
        $otp = Otps::where('OTP_code', $request->otp)
                ->where('type', 'verify_account')
                ->whereNull('used_at')
                ->latest()
                ->first();

        if(!$otp) {
            return response()->json([
                'status'=> 'error', 
                'message' => 'otp tidak valid, harap memasukan kode otp yang valid'
            ]); 
        }
        if($otp->exp < now()) {
            return response()->json([
                'status' => 'error', 
                'message' => 'token telah kadaluarsa, silahkan meminta kode otp yang baru'
            ]);
        }
        if (!$otp->user) {
            return response()->json([
                'status' => 'error',
                'message' => 'pengguna tidak ditemukan'
            ]);
        }
        $otp->user->email_verified_at = now();
        $otp->user->save();
        $otp->used_at = now();
        $otp->save();

        return response()->json([
            'status' => 'succes', 
            'message' => 'verifikasi Email berhasil'
        ]);        
    }



}
