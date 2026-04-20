<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->string('old_status');
            $table->string('new_status');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_logs');
    }
};
