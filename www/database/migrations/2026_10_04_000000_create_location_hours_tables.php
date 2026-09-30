<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Weekly opening hours (one row per open weekday; no row = closed) plus
// special days that override them: closures (Christmas) and changed hours.
// Booking slots are built from these.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_business_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0 = Sunday … 6 = Saturday (Carbon)
            $table->time('opens_at');
            $table->time('closes_at');
            $table->timestamps();

            $table->unique(['location_id', 'day_of_week']);
        });

        Schema::create('location_special_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('label');
            // Both null = closed all day; otherwise the hours for that date.
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->timestamps();

            $table->unique(['location_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_special_days');
        Schema::dropIfExists('location_business_hours');
    }
};
