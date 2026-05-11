<?php 
namespace App\Services;
use Illuminate\Support\Str; 
use Illuminate\Support\Facades\Storage; 
use Intervention\Image\ImageManager; 
use Intervention\Image\Drivers\Gd\Driver;

class ImageServices{

    public function uploadAndResize($imageFile, $folder = 'Datacar') {
        $allowedFolders = ['Datacar', 'profile'];
        if(!in_array($folder, $allowedFolders)){
            throw new \Exception('Folder tidak diizinkan');
        }
        $manager = new ImageManager(new Driver()); 
        $filename = Str::random(20) . '.jpg';
        $img = $manager->read($imageFile)->resize(width: 800)->toJpeg(70);
        Storage::disk('public')->put($folder . '/' . $filename, (string) $img);
        return $folder . '/' . $filename;
    }

    public function deleteImage($path) {
        if (!$path) return; 
        try {
            if ( Storage::disk('public')->exists($path)){
                Storage::disk('public')->delete($path);
            }
        } catch (\exception $e) {
            \Log::error('gagal hapus gambar: ' . $e->getMessage());
        }
        
    }
}