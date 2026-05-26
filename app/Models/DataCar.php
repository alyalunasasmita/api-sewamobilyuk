<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DataCar extends Model
{
    use HasFactory;
    protected $appends = ['image_url'];
    protected $fillable =[
        'image', 
        'name_car', 
        'passenger_capacity', 
        'model',
        'plate_number',
        'year_of_car', 
        'price', 
        'description',
        'transmisi', 
        'kategori'
    ];

    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image);
    }

    public function tracker()
    {
        return $this->hasOne(VehicleTracker::class);
    }
}
