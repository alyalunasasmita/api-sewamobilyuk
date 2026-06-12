<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Models\User; 
use App\Models\Notification; 
use App\Models\DataCar;
use App\Models\Payment;
use App\Services\MidtransService;
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
            'end_date' => 'required|date|after_or_equal:start_date',
            'payment_method' => 'required|in:cash,midtrans'
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

            $reservationStatus = $request->payment_method === 'cash'
            ? 'pending_cash'
            : 'waiting_payment';

            $reservation = Reservation::create([
                'user_id' => $user->id,
                'data_car_id' => $request->data_car_id,
                'start_date' => $start,
                'end_date' => $end,
                'count_days' => $count_days,
                'total_price' => $total_price,
                'reservations_status' => $reservationStatus,
            ]);

            $reservation->update([
                'no_reservasi' => 'RSV-' .
                    now()->format('Ymd') .
                    '-' .
                    str_pad($reservation->id, 5, '0', STR_PAD_LEFT)
            ]);

            $tax = $total_price * 0.10;
            $amount = round($total_price + $tax);

            if ($request->payment_method === 'cash') {

                $payment = Payment::create([
                    'user_id' => $user->id,
                    'reservation_id' => $reservation->id,
                    'amount' => $amount,
                    'payment_method' => 'cash',
                    'status' => 'pending',
                    'tax_amount' => $tax
                ]);

                $notif = Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Reservasi Berhasil',
                    'message' => 'Reservasi berhasil dibuat dan silahkan bayar cash pada saat pengambilan mobil rental.'
                ]);

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Reservasi berhasil dibuat',
                    'reservation' => $reservation,
                    'payment' => $payment, 
                    'subtotal' => $total_price, 
                    'pajak' => $tax
                ], 201);
            }

            /**
             * MIDTRANS
             */
            MidtransService::init();

            $orderId = 'ORDER-' . Str::uuid();

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
                    'duration' => 10
                ]
            ];

            $snapResponse = Snap::createTransaction($params);

            $payment = Payment::create([
                'user_id' => $user->id,
                'reservation_id' => $reservation->id,
                'order_id' => $orderId,
                'snap_token' => $snapResponse->token,
                'amount' => $amount,
                'payment_method' => 'midtrans',
                'status' => 'pending',
                'tax_amount' => $tax, 
                'expired_at' => now()->addMinutes(10)
            ]);

            $notif = Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Reservasi Berhasil',
                    'message' => 'Reservasi berhasil dibuat dan menunggu pembayaran.'
                ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Reservasi berhasil dibuat',
                'reservation' => $reservation,
                'payment' => $payment,
                'snap_token' => $snapResponse->token,
                'redirect_url' => $snapResponse->redirect_url,
                'pajak' => $tax, 
                'subtotal' => $total_price
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
        $reservation->load([
            'car',
            'payment'
        ]);
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

    public function cancel($id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'status' => 'error',
                'message' => 'data tidak ditemukan'
            ], 404);
        }

        if ($reservation->reservations_status === 'cancelled') {
            return response()->json([
                'status' => 'error',
                'message' => 'reservasi sudah dibatalkan'
            ], 400);
        }

        $startDate = Carbon::parse($reservation->start_date)->startOfDay();
        $today = now()->startOfDay();

        if ($today->gte($startDate->copy()->subDay())) {
            return response()->json([
                'status' => 'error',
                'message' => 'reservasi tidak dapat dibatalkan mulai H-1'
            ], 400);
        }

        $reservation->update([
            'reservations_status' => 'cancelled',
            'refund_status' => 'pending',
            'cancelled_at' => now()
        ]);

        if ($reservation->car) {
            $reservation->car->update([
                'availability_status' => 'available'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'reservasi berhasil dibatalkan'
        ]);
    }
}
