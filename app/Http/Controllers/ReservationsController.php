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
    public function store(Request $request, DistanceService $distanceService)
    {
        $request->validate([
            'data_car_id' => 'required|exists:data_cars,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'payment_method' => 'required|in:cash,transfer', 
            'latitude' => 'required|numeric', 
            'longitude' => 'required|numeric'
        ]);

        $user = $request->attributes->get('user');

        
        //validasi rental aktif
        $hasActiveRental = Reservation::where('user_id', $user->id)->whereIn('reservations_status', ['confirmed', 'on-going'])->exists(); 
        if ($hasActiveRental && !$request->has('confirm_override')){
            return response()->json([
                'status' => 'warning', 
                'message' => 'kamu masih punya rental yang aktif, yakin akan membuat rental baru?'
            ], 200);
        }

        //validasi data berkas yang dibutuhkan 
        if(blank($user->id_card) || blank($user->drive_licence)) {
            return response()->json([
                "status" => "error", 
                'message' => 'harap unggah KTP dan SIM terlebih dahulu'
                ], 422);
                }

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

        try {

            DB::beginTransaction();

            //mencegah double booking

            $car = Datacar::where('id', $request->data_car_id)
                ->where('availability_status', 'available')
                ->lockForUpdate()
                ->first();

            if (!$car) {
                throw new \Exception('Mobil sudah di booking, silahan pilih mobil yang masih tersedia');
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
                'branch_id' => $nearestBranch->id,
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

            $paymentStatus = $request->payment_method === 'cash' ? 'pending' : 'waiting_upload';

            $payment = Payment::create([
                'user_id' => $user->id,
                'reservation_id' => $reservation->id,
                'amount' => $amount,
                'payment_method' => $request->payment_method,
                'status' => $paymentStatus,
                'tax_amount' => $tax
            ]);

            $reservation->car->update([
                'availability_status' => 'booked'
            ]);

            $message = $request->payment_method === 'cash'
            ? 'Reservasi berhasil dibuat dan silahkan bayar cash pada saat pengambilan mobil rental.'
            : 'Reservasi berhasil dibuat. Silakan upload bukti transfer untuk proses verifikasi admin.';

            Notification::create([
                'user_id' => $user->id,
                'title' => 'Reservasi Berhasil',
                'message' => $message
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Reservasi berhasil dibuat',
                'reservation' => $reservation,
                'payment' => $payment,
                'subtotal' => $total_price,
                'pajak' => $tax,
                'amount' => $amount
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

        $allowedStatuses = ['waiting_confirmation', 'waiting_upload', 'pending_cash', 'confirmed'];

        if (!in_array($reservation->reservations_status, $allowedStatuses)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reservasi tidak dapat dibatalkan karena sedang berjalan atau sudah diproses.'
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

        try {
            \DB::beginTransaction();

            $refundStatus = 'none';
            if ($reservation->reservations_status === 'waiting_confirmation') {
                $refundStatus = 'pending'; 
            }

            $reservation->update([
                'reservations_status' => 'cancelled',
                'refund_status' => $refundStatus,
                'cancelled_at' => now()
            ]);

            $reservation->car->update([
                'availability_status' => 'available'
            ]);

            \DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'reservasi berhasil dibatalkan'
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
