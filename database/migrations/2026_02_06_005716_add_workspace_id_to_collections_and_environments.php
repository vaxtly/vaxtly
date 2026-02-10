<?php

use App\Models\Collection;
use App\Models\Environment;
use App\Models\Workspace;
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
        if (! Schema::hasColumn('collections', 'workspace_id')) {
            Schema::table('collections', function (Blueprint $table) {
                $table->uuid('workspace_id')->nullable()->after('id');
                $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
            });
        }

        if (! Schema::hasColumn('environments', 'workspace_id')) {
            Schema::table('environments', function (Blueprint $table) {
                $table->uuid('workspace_id')->nullable()->after('id');
                $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
            });
        }

        // Create default workspace with migrated settings if none exists
        if (Workspace::count() === 0) {
            $workspace = Workspace::create([
                'name' => 'Default',
                'order' => 0,
                'settings' => [
                    'remote' => [
                        'provider' => get_setting('remote.provider', ''),
                        'repository' => get_setting('remote.repository', ''),
                        'token' => get_setting('remote.token', ''),
                        'branch' => get_setting('remote.branch', 'main'),
                        'auto_sync' => get_setting('remote.auto_sync', false),
                    ],
                    'vault' => [
                        'provider' => get_setting('vault.provider', ''),
                        'url' => get_setting('vault.url', ''),
                        'auth_method' => get_setting('vault.auth_method', 'token'),
                        'token' => get_setting('vault.token', ''),
                        'role_id' => get_setting('vault.role_id', ''),
                        'secret_id' => get_setting('vault.secret_id', ''),
                        'namespace' => get_setting('vault.namespace', ''),
                        'mount' => get_setting('vault.mount', 'secret'),
                    ],
                ],
            ]);

            Collection::query()->update(['workspace_id' => $workspace->id]);
            Environment::query()->update(['workspace_id' => $workspace->id]);

            set_setting('active.workspace', $workspace->id);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropColumn('workspace_id');
        });

        Schema::table('environments', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropColumn('workspace_id');
        });
    }
};
