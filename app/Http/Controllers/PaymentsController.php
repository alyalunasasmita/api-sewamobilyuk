<?php

namespace App\Http\Controllers;

use App\Models\Payments;
use App\Models\Reservation;
use Illuminate\Http\Request;

use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;

class PaymentsController extends Controller
{
    public function store(Request $request)
    {
        Configuration::setXenditKey(env('XENDIT_SECRET_KEY'));

        $user = $request->attributes->get('user');

        $reservation = Reservation::where('user_id', $user->id)
            ->latest()
            ->first();

        if (!$reservation) {
            return response()->json([
                'status' => 'error',
                'message' => 'reservasi tidak ditemukan'
            ], 404);
        }

        $amount = (int) ($reservation->total * 0.11);

        $externalId = 'reservation-' . $reservation->id;
        $noPayment = 'INV-' . time() . $reservation->id;

        $apiInstance = new InvoiceApi();

        $params = new CreateInvoiceRequest([
            'external_id' => $externalId,
            'amount' => $amount,
            'description' => 'Pembayaran reservasi mobil berhasil',
            'payer_email' => $user->email,

            'metadata' => [
                'user_id' => $user->id,
                'reservation_id' => $reservation->id,
                'no_payment' => $noPayment
            ]
        ]);

        $invoice = $apiInstance->createInvoice($params);

        Payments::create([
            'external_id' => $externalId,
            'amount' => $amount,
            'user_id' => $user->id,
            'reservation_id' => $reservation->id,
            'no_payment' => $noPayment
        ]);

        return response()->json([
            'checkout_url' => $invoice['invoice_url']
        ]);
    }

    public function webhook(Request $request)
    {
        $data = $request->all();

        if ($data['status'] === 'PAID') {

            $payment = Payments::where(
                'external_id',
                $data['external_id']
            )->first();

            if (!$payment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'data pembayaran tidak ditemukan'
                ]);
            }

            $payment->update([
                'status' => 'paid'
            ]);

            $payment->reservation->update([
                'reservation_status' => 'paid'
            ]);

            $payment->reservation->dataCar->update([
                'availability_status' => 'booked'
            ]);
        }

        return response()->json([
            'status' => 'success'
        ]);
    }
}