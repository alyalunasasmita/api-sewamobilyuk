<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Models\User; 
use App\Models\DataCar;
use Carbon\Carbon;

class ReservationsController extends Controller
{
    
    /**
     * Display a listing of the resource.
     */

    protected $car; 

    public function __construct(){
        
        
    }
    public function index(Request $request)
    {
        $user = $request->attributes->get('user');
        $query = Reservation::where('user_id', $user->id)->get(); 
        return response()->json([
            'status' => 'success', 
            'data' => $query
        ]);
       

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'data_car_id' => 'required|exists:data_cars,id',
            'start_date'=> 'required|date', 
            'end_date' =>'required|date|after_or_equal:start_end', 
        ]); 

        $user = $request->attributes->get('user');
        $car = DataCar::where('id' , $request->data_car_id)->where('availability_status', 'available')->first();
        if(!$car){
            return response()->json([
                'status' => 'error', 
                'message' => 'pilih mobil yang masih tersedia'
            ]);
        }
        $isBooked = Reservation::where('data_car_id', $request->data_car_id)
        ->where(function($query) use ($request){
        $query->whereBetween('start_date', [
            $request->start_date,
            $request->end_date
        ])
        ->orWhereBetween('end_date', [
            $request->start_date,
            $request->end_date
        ])
        ->orWhere(function($q) use ($request){
            $q->where('start_date', '<=', $request->start_date)
              ->where('end_date', '>=', $request->end_date);
        });
    })

    ->exists();
        if($isBooked){
            return response()->json([
                'status' => 'error', 
                'message' => 'mobil sudah dibooking pada rentang tanggal tersebut'
            ], 409);
        }                

        $start = Carbon::parse($request->start_date); 
        $end = Carbon::parse($request->end_date);
       
        $count = max(1, $start->diffInDays($end));
        $total_price = $car->price * $count;

        $reserv = Reservation::create([
            'user_id' => $user->id, 
            'data_car_id' => $request->data_car_id, 
            'start_date' => $start, 
            'end_date' => $end, 
            'count_days' => $count,
            'total_price' =>$total_price,
        ]);

        $reserv->update([
            'no_reservasi' => 'RSV-' . date('Ymd') . '-' . $reserv->id,
        ]);

        return response()->json([
            'status'=>'success', 
            'data' => $reserv
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Reservations $reservations)
    {
        return response()->json([
            'status' => 'success', 
            'data' => $reservations
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reservations $reservations)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reservations $reservations)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reservations $reservations)
    {
        //
    }

    public function cancel($id){
        $reservation = Reservation::find($id);
        if(!$reservation){
            return response()->json([
                'status' => 'error', 
                'message' => 'data tidak ditemukan'
            ]);            
        }
        $reservation->update([
            'reservations_status' => 'cancelled', 
            'refund_status' => 'pending',
            'cancelled_at' => now()
        ]); 
        return response()->json([
            'message' => 'Reservasi berhasil dibatalkan'
        ]);
    }
}
