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
        Schema::create('vehicle_trackers', function (Blueprint $table) {
            $table->id();
            $table->string('car_id')->nullable();        // ID mobil dari API eksternal
            $table->string('car_label')->nullable();     // Nama/plat mobil (untuk display)
            $table->string('device_token')->unique();    // Token unik HP tersembunyi
            $table->decimal('lat', 10, 7)->nullable();   // Latitude
            $table->decimal('lng', 10, 7)->nullable();   // Longitude
            $table->float('speed')->nullable();          // Kecepatan (km/h)
            $table->float('accuracy')->nullable();       // Akurasi GPS (meter)
            $table->integer('battery')->nullable();      // Level baterai (%)
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_trackers');
    }
};
