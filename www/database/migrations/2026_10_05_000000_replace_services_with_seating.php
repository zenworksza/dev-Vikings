<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A restaurant's "service" is the food, so bookings are limited by seating
// instead: how many tables a location has and how many guests it can seat.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('location_services');
        Schema::dropIfExists('services');

        Schema::table('locations', function (Blueprint $table) {
            $table->unsignedSmallInteger('table_count')->nullable()->after('contact_email');
            $table->unsignedSmallInteger('seat_capacity')->nullable()->after('table_count');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['table_count', 'seat_capacity']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('default_duration_minutes')->default(60);
            $table->unsignedInteger('default_price_cents')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('location_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price_cents')->nullable();
            $table->unsignedSmallInteger('duration_minutes');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['location_id', 'service_id']);
        });
    }
};
