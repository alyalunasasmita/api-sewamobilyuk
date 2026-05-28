<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Models\Payment;

class ReservationExpired extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reservation-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancell expired reservations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $payments = Payment::with('reservation.car')->where('status', 'pending')->where('expired_at', '<=', now())->get();
        foreach($payments as $payment){
            $payment->update([
                'status' => 'expired'
            ]); 
            if($payment->reservation){
                $payment->reservation->update([
                    'reservations_status' => 'cancelled', 
                    'cancelled' => now()
                ]);

                $payment->reservation->car->update([
                    'availability_status' => 'available'
                ]);
            }
        }
        $this->info('expired reservations cancelled');
    }
}
