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
        if (! Schema::hasColumn('request_histories', 'method')) {
            Schema::table('request_histories', function (Blueprint $table) {
                $table->string('method', 10)->after('request_id')->default('GET');
                $table->text('url')->after('method')->default('');
            });
        }
    }

    public function down(): void
    {
        Schema::table('request_histories', function (Blueprint $table) {
            $table->dropColumn(['method', 'url']);
        });
    }
};
