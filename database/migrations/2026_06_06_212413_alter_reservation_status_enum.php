<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE reservations
            MODIFY reservations_status
            ENUM(
                'waiting_payment',
                'pending_cash',
                'waiting_confirmation',
                'confirmed',
                'on-going',
                'completed',
                'cancelled',
                'rejected',
                'failed'
            )
            NOT NULL DEFAULT 'waiting_payment'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE reservations
            MODIFY reservations_status
            ENUM(
                'waiting_payment',
                'pending_cash',
                'waiting_confirmation',
                'confirmed',
                'on-going',
                'completed',
                'cancelled',
                'rejected',
                'failed'
            )
            NOT NULL DEFAULT 'waiting_payment'
        ");
    }
};