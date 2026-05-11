<?php

namespace App\Http\Controllers;

use App\Models\DataCar;
use Illuminate\Http\Request;
use App\Services\ImageServices;

class DataCarController extends Controller
{
    protected $imageServices;

    public function __construct(ImageServices $imageServices)
    {
        $this->imageServices = $imageServices;
    }

    public function index(Request $request)
    {
        $query = DataCar::query();
        
        return response()->json([
            'status' => 'success',
            'data' => $query->paginate(10)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:5048',
            'name_car' => 'required|string',
            'passenger_capacity' => 'required|integer',
            'model' => 'required|string',
            'year_of_car' => 'required|integer',
            'price' => 'required|integer',
            'description' => 'required|string',
            'plate_number' => 'required|string',
            'transmisi' => 'required|in:automatic,manual',
            'kategori' => 'required|in:MPV,sedan,hatchback,SUV'
        ]);

        try {

            $path = $this->imageServices->uploadAndResize(
                $request->file('image')
            );

            $car = DataCar::create([
                'image' => $path,
                'name_car' => $request->name_car,
                'passenger_capacity' => $request->passenger_capacity,
                'model' => $request->model,
                'price' => $request->price,
                'year_of_car' => $request->year_of_car,
                'description' => $request->description,
                'plate_number' => $request->plate_number,
                'category' => $request->kategori,
                'transmisi' => $request->transmisi
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'data mobil berhasil ditambahkan',
                'data' => $car
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        $data = DataCar::find($id);

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'data tidak ditemukan'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function update(Request $request, DataCar $data_car)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:5048',
            'name_car' => 'required|string',
            'passenger_capacity' => 'required|integer',
            'model' => 'required|string',
            'year_of_car' => 'required|numeric',
            'price' => 'required|numeric',
            'description' => 'required|string',
            'plate_number' => 'required|string',
            'transmisi' => 'required|in:automatic,manual',
            'kategori' => 'required|in:MPV,sedan,hatchback,SUV'
        ]);

        try {

            if ($request->hasFile('image')) {

                $this->imageServices->deleteImage($data_car->image);

                $path = $this->imageServices->uploadAndResize(
                    $request->file('image')
                );

                $data_car->image = $path;
            }

            $data_car->update([
                'name_car' => $request->name_car,
                'passenger_capacity' => $request->passenger_capacity,
                'model' => $request->model,
                'price' => $request->price,
                'year_of_car' => $request->year_of_car,
                'description' => $request->description,
                'plate_number' => $request->plate_number,
                'category' => $request->kategori,
                'transmisi' => $request->transmisi,
                'image' => $data_car->image
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'data mobil berhasil diubah',
                'data' => $data_car
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        $data_car = DataCar::find($id);
        if(!$data_car){
            return response()->json([
                'status' => 'error', 
                'message' => 'data tidak ditemukan'
            ]);
        }

        $this->imageServices->deleteImage($data_car->image);

        $data_car->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'data mobil berhasil dihapus'
        ]);
    }
}