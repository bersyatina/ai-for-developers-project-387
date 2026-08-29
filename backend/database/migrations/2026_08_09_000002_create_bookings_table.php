<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_type_id')->constrained('event_types')->cascadeOnDelete();
            $table->dateTime('start');
            $table->dateTime('end');
            $table->string('guest_name');
            $table->string('guest_email');
            $table->timestamps();

            $table->unique('start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
