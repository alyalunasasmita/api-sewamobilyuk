<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\ImageServices;
use Illuminate\Support\Facades\Hash;

class ProfileUser extends Controller
{
    protected $imageServices; 

    public function __construct(ImageServices $imageServices)
    {
        $this->imageServices = $imageServices;
    }

    function update (Request $request){
        $user = $request->attributes->get('user');
        $request->validate([
            'username'=>'required|string', 
            'name'=>'required|string', 
            'email'=>'required|string',
            'id_card' => 'nullable|image|mimes:jpg,jpeg,png|max:5048',
            'drive_licence' => 'nullable|image|mimes:jpg,jpeg,png|max:5048',
            'number_phone' => 'nullable|string|min:11', 
            'address'=>'nullable|string', 
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:5048',
        ]);

        try {
            $id_card = $user->id_card; 
            $avatar = $user->avatar; 
            $drive_licence = $user->drive_licence;

            if($request->hasFile('id_card')){
                $this->imageServices->deleteImage($user->id_card);
                $id_card = $this->imageServices->uploadAndResize($request->file('id_card'), 'profile');
            }
            if($request->hasFile('avatar')){
                $this->imageServices->deleteImage($user->avatar);
                $avatar = $this->imageServices->uploadAndResize($request->file('avatar'), 'profile');
            }
            if($request->hasFile('drive_licence')){
                $this->imageServices->deleteImage($user->drive_licence);
                $drive_licence = $this->imageServices->uploadAndResize($request->file('drive_licence'), 'profile');
            }

            $user->update([
                'username' => $request->username, 
                'name' => $request->name, 
                'email' => $request->email,
                'id_card' => $id_card,
                'drive_licence' => $drive_licence,
                'number_phone' => $request->number_phone, 
                'address' => $request->address, 
                'avatar' => $avatar,
            ]);

            return response()->json([
                'status' => 'success', 
                'message' => 'profile berhasil diubah',
                'data' => $user
            ]);

        }catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function destroy (Request $request){
        $user = $request->attributes->get('user');
        $request->validate([
            'current_password' => 'required'
        ]); 
        
        if(!Hash::check($request->current_password, $user->password)){
            return response()->json([
                'status' => 'error', 
                'message' => 'password anda salah'
            ]);
        }

        $this->imageServices->deleteImage($user->id_card);
        $this->imageServices->deleteImage($user->drive_licence);
        $this->imageServices->deleteImage($user->avatar);

        $user->delete();
        return response()->json([
            'status' => 'success', 
            'message' => 'akun berhasil dihapus'
        ]);
    }

    public function show (Request $request){
        $user = $request->attributes->get('user');

        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }
}
