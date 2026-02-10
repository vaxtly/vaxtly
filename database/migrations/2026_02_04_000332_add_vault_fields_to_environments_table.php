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
        if (! Schema::hasColumn('environments', 'vault_synced')) {
            Schema::table('environments', function (Blueprint $table) {
                $table->boolean('vault_synced')->default(false)->after('order');
                $table->string('vault_path')->nullable()->after('vault_synced');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->dropColumn(['vault_synced', 'vault_path']);
        });
    }
};
