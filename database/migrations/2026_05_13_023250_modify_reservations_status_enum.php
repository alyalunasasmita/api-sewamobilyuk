<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; 
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE reservations
        MODIFY reservations_status
        ENUM('pending',  
        'on-going', 
        'completed', 
        'cancelled',
        'rejected', 
        'confirmed',
        'waiting_payment',
        'waiting_confirmation'
        )
        NOT NULL DEFAULT 'waiting_payment'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
