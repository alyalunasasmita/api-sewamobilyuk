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
        Schema::create('data_cars', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();
            $table->string('name_car'); 
            $table->integer('passenger_capacity');
            $table->string('model'); 
            $table->integer('year_of_car'); 
            $table->integer('price');
            $table->string('plate_number')->unique();
            $table->string('description'); 
            $table->enum('transmisi', ['automatic', 'manual']);
            $table->enum('kategori', ['MPV', 'sedan', 'hatchback', 'SUV']);
            $table->enum('availability_status' , ['available','booked', 'on rent','maintenance'])->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_cars');
    }
};
