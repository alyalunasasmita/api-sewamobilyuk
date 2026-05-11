<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('data_car_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('count_days');
            $table->integer('total_price');
            $table->enum('payment_status',['pending','paid','failed'])->default('pending');
            $table->enum('reservations_status',['pending','paid','on-going','completed','cancelled'])->default('pending');
            $table->enum('refund_status',['pending','refunded','failed','none'])->default('none');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
