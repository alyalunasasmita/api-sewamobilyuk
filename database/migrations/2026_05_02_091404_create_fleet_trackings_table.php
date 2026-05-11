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
        Schema::create('fleet_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_car_id')->constrained()->onDelete('cascade');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longtitude', 10,7); 
            $table->enum('movement',['moving','stopped',])->default('stopped');
            $table->integer('speed');
            $table->decimal('odometer', 10,2)->default(0);
            $table->enum('engine', ['on','off'])->default('off'); 
            $table->timestamp('tracked_at'); //waktu dari gps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_trackings');
    }
};
