<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Who owned a location and when: opened, sold to a franchisee, taken back by
// the franchisor, transferred. Owner names are snapshotted so the record still
// reads correctly if a franchisee's account is later deleted.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_ownership_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_owner_name')->nullable();
            $table->string('to_owner_name')->nullable();
            $table->boolean('is_creation')->default(false);
            $table->string('note')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_ownership_history');
    }
};
