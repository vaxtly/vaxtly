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
        if (! Schema::hasColumn('collections', 'environment_ids')) {
            Schema::table('collections', function (Blueprint $table) {
                $table->text('environment_ids')->nullable();
                $table->string('default_environment_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn(['environment_ids', 'default_environment_id']);
        });
    }
};
