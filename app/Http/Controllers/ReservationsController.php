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
use App\Models\Branch;
use App\Services\MidtransService;
use App\Services\DistanceService;
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
    public function store(Request $request, MidtransService $midtransService, DistanceService $distanceService)
    {
        $request->validate([
            'data_car_id' => 'required|exists:data_cars,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'payment_method' => 'required|in:cash,QRIS,GOPAY,BSI_VA,BNI_VA,CIMB_VA', 
            'latitude' => 'required|numeric', 
            'longitude' => 'required|numeric'
        ]);

        $user = $request->attributes->get('user');

        try {

            DB::beginTransaction();

            //validasi jarak
            
            $branches = Branch::where('is_active', true)->get();
            $nearestBranch = null;
            $nearestDistance = PHP_FLOAT_MAX;
            foreach ($branches as $branch) {

                $distance = $distanceService->calculate(
                    $request->latitude,
                    $request->longitude,
                    $branch->latitude,
                    $branch->longitude
                );

                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestBranch = $branch;
                }
            }

            if (!$nearestBranch) {
                throw new \Exception('Cabang tidak ditemukan');
            }

            if ($nearestDistance > 20) {
                throw new \Exception(
                    'Maaf, layanan belum tersedia di lokasi Anda'
                );
            }
             

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

            $orderId = 'ORDER-' . Str::uuid();
            $paymentResponse = null;
            $paymentData = null;
            //qris
            if ($request->payment_method === 'QRIS' || $request->payment_method === 'GOPAY'
            ) {
                $paymentResponse = $midtransService
                    ->createQris(
                        $orderId,
                        $amount
                    );
                foreach ($paymentResponse->actions as $action) {
                    if ($action->name === 'generate-qr-code') {
                        $paymentData = [
                            'type' => 'QRIS',
                            'qr_url' => $action->url
                        ];
                        break;
                    }
                }
            }
            //VA Bank 
            if (in_array($request->payment_method, [
                'BSI_VA', 'BNI_VA','CIMB_VA'
                ])) {
                $bank = match ($request->payment_method) {
                    'BCA_VA' => 'bca',
                    'BNI_VA' => 'bni',
                    'BRI_VA' => 'bri',
                };
                $paymentResponse = $midtransService
                    ->createVa(
                        $orderId,
                        $amount,
                        $bank
                    );
                $paymentData = [
                    'type' => $request->payment_method,
                    'va_number' => $paymentResponse
                        ->va_numbers[0]
                        ->va_number
                ];
            }
            
            $payment = Payment::create([
                'user_id' => $user->id,
                'reservation_id' => $reservation->id,
                'order_id' => $orderId,
                'amount' => $amount,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'tax_amount' => $tax,
                'expired_at' => now()->addMinutes(10),
                'provider_ref' => $paymentResponse->transaction_id,
                'payload' => json_encode($paymentResponse)
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
                'payment_data' => $paymentData,
                'subtotal' => $total_price,
                'pajak' => $tax
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
