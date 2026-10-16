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
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->integer('points');
            $table->integer('balance_after');
            $table->string('description')->nullable();
            $table->nullableMorphs('reference');
            $table->unsignedInteger('points_remaining')->default(0);
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('created_at')->nullable();

            $table->index(['account_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
