<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// `services` is the franchisor's master catalogue; `location_services` is what
// each location actually offers, with its own price and duration.
return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('location_services');
        Schema::dropIfExists('services');
    }
};
