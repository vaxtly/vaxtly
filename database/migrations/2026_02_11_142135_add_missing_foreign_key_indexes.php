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
        $indexes = [
            'collections' => ['workspace_id'],
            'environments' => ['workspace_id'],
            'folders' => ['collection_id', 'parent_id'],
            'requests' => ['collection_id', 'folder_id'],
            'request_histories' => ['request_id'],
        ];

        foreach ($indexes as $table => $columns) {
            $existing = Schema::getIndexListing($table);

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns, $existing) {
                foreach ($columns as $column) {
                    $indexName = "{$table}_{$column}_index";
                    if (! in_array($indexName, $existing)) {
                        $blueprint->index($column);
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collections', fn (Blueprint $t) => $t->dropIndex(['workspace_id']));
        Schema::table('environments', fn (Blueprint $t) => $t->dropIndex(['workspace_id']));
        Schema::table('folders', function (Blueprint $t) {
            $t->dropIndex(['collection_id']);
            $t->dropIndex(['parent_id']);
        });
        Schema::table('requests', function (Blueprint $t) {
            $t->dropIndex(['collection_id']);
            $t->dropIndex(['folder_id']);
        });
        Schema::table('request_histories', fn (Blueprint $t) => $t->dropIndex(['request_id']));
    }
};
