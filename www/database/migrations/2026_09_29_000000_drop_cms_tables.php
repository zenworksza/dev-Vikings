<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// The CMS (pages, page_sections, medialibrary) was removed — the public
// site is now a static PHP site. Phase 2's create migrations were deleted,
// so fresh installs never create these; this drops them where they already
// exist (prod).
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('media');
    }

    public function down(): void
    {
        // Intentionally irreversible — the CMS is gone.
    }
};
