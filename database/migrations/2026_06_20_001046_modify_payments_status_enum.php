<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'waiting_upload',
                'pending_approval',
                'paid',
                'expired',
                'failed',
                'refunded',
                'rejected'
            ])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'paid',
                'expired',
                'failed',
                'refunded'
            ])->default('pending')->change();
        });
    }
};