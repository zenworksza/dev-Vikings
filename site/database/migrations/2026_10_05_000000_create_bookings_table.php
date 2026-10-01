<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Locations live in the franchise portal's database, so a booking refers to its
// location by the portal's permanent slug and keeps a snapshot of the name and
// booking inbox as they were when the booking was made.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('token', 40)->unique();
            $table->string('location_slug');
            $table->string('location_name');
            $table->string('location_email');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 40);
            $table->unsignedSmallInteger('party_size');
            $table->text('notes')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status')->default('pending')->index();
            $table->dateTime('decided_at')->nullable();
            $table->string('decline_reason', 300)->nullable();
            $table->timestamps();

            $table->index(['location_slug', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
