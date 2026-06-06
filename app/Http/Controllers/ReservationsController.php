<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Models\User; 
use App\Models\DataCar;
use App\Models\Payment;
use App\Services\MidtransServices;
use Carbon\Carbon;
use Midtrans\Config;
use Midtrans\Snap;

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
        $query = Reservation::with('payment')->where('user_id', $user->id)->get(); 
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
'start_date' => 'required|date',
'end_date' => 'required|date|after_or_equal:start_date'
]);

$user = $request->attributes->get('user');

try {

    DB::beginTransaction();

    $car = Datacar::where('id', $request->data_car_id)
        ->where('availability_status', 'available')
        ->lockForUpdate()
        ->first();

    if (!$car) {
        throw new \Exception('Mobil tidak tersedia');
    }

    $isBooked = Reservation::where('data_car_id', $request->data_car_id)
        ->whereIn('reservations_status', [
            'waiting_payment',
            'pending_approval',
            'confirmed',
            'on-rent'
        ])
        ->where('start_date', '<=', $request->end_date)
        ->where('end_date', '>=', $request->start_date)
        ->exists();

    if ($isBooked) {
        throw new \Exception('Mobil sudah direservasi pada tanggal tersebut');
    }

    $start = Carbon::parse($request->start_date);
    $end = Carbon::parse($request->end_date);

    $count_days = max(1, $start->diffInDays($end));
    $total_price = $car->price * $count_days;

    $reservation = Reservation::create([
        'user_id' => $user->id,
        'data_car_id' => $request->data_car_id,
        'start_date' => $start,
        'end_date' => $end,
        'count_days' => $count_days,
        'total_price' => $total_price,
        'reservations_status' => 'waiting_payment',
    ]);

    $reservation->update([
        'no_reservasi' => 'RSV-' .
            now()->format('Ymd') .
            '-' .
            str_pad($reservation->id, 5, '0', STR_PAD_LEFT)
    ]);

    // Midtrans Init
    MidtransServices::init();

    $orderId = 'ORDER-' . Str::uuid();

    $amount = round($total_price * 0.10);

    $params = [
        'transaction_details' => [
            'order_id' => $orderId,
            'gross_amount' => $amount,
        ],
        'customer_details' => [
            'first_name' => $user->name,
            'email' => $user->email,
        ],
        'expiry' => [
            'unit' => 'minutes',
            'duration' => 1
        ]
    ];

    \Log::info('MIDTRANS CONFIG', [
        'server_key' => substr(Config::$serverKey, 0, 15),
        'is_production' => Config::$isProduction,
    ]);

    \Log::info('ORDER CREATED', [
        'order_id' => $orderId,
        'gross_amount' => $amount
    ]);

    \Log::info('PARAMS MIDTRANS', $params);


    $snapResponse = Snap::createTransaction($params);

    \Log::info('CREATE TRANSACTION RESPONSE', [
        'response' => json_encode($snapResponse)
    ]);


    dd($snapResponse);

    $snapToken = $snapResponse->token;

    $payment = Payment::create([
        'user_id' => $user->id,
        'reservation_id' => $reservation->id,
        'order_id' => $orderId,
        'snap_token' => $snapToken,
        'amount' => $amount,
        'status' => 'pending',
        'expired_at' => now()->addMinutes(1)
    ]);

    \Log::info('PAYMENT CREATED', [
        'id' => $payment->id,
        'order_id' => $payment->order_id,
    ]);

    DB::commit();

    return response()->json([
        'status' => 'success',
        'message' => 'Reservasi berhasil dibuat',
        'reservation' => $reservation,
        'payment' => $payment,
        'snap_token' => $snapToken,
        'redirect_url' => $snapResponse->redirect_url
    ], 201);

} catch (\Exception $e) {

    DB::rollBack();

    \Log::error('RESERVATION ERROR', [
        'message' => $e->getMessage()
    ]);

    return response()->json([
        'status' => 'error',
        'message' => $e->getMessage()
    ], 409);
}


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
