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
        // Shadow-ban a user: their posts vanish from all public feeds silently
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_shadow_banned')->default(false)->after('ban_reason');
        });

        // Hide an individual post: removed from public feeds but not deleted
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('is_flagged');
            $table->text('hide_reason')->nullable()->after('is_hidden');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_shadow_banned');
        });
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['is_hidden', 'hide_reason']);
        });
    }
};
