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
        Schema::table('point_transactions', function (Blueprint $table) {
            $table->index('type');
            $table->index(['points_remaining', 'expires_at']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('point_transactions', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['points_remaining', 'expires_at']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['occurred_at']);
        });
    }
};
