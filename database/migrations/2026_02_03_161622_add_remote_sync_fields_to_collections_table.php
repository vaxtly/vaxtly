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
        if (! Schema::hasColumn('collections', 'remote_sha')) {
            Schema::table('collections', function (Blueprint $table) {
                $table->string('remote_sha')->nullable();
                $table->datetime('remote_synced_at')->nullable();
                $table->boolean('is_dirty')->default(false);
                $table->boolean('sync_enabled')->default(false);
            });
        }
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn(['remote_sha', 'remote_synced_at', 'is_dirty', 'sync_enabled']);
        });
    }
};
