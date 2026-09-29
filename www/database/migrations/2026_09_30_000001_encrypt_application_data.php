<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The applicant's answers (ID numbers, finances) are now stored encrypted
// (Laravel's `encrypted:array` cast). An encrypted payload is not valid JSON,
// so the column can no longer be a JSON type. No real rows exist yet.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('franchisee_applications', function (Blueprint $table) {
            $table->longText('data')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('franchisee_applications', function (Blueprint $table) {
            $table->json('data')->nullable()->change();
        });
    }
};
