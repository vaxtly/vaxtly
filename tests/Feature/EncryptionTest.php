<?php

use App\Casts\EncryptedArray;
use App\Models\Collection;
use App\Models\Environment;
use App\Models\Request;
use App\Models\Workspace;
use App\Services\DataExportImportService;
use App\Services\EncryptionService;
use App\Services\YamlCollectionSerializer;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->encryption = app(EncryptionService::class);
});

// --- EncryptionService ---

it('encrypts and decrypts a string via Crypt fallback', function () {
    $original = 'my-secret-token';

    $encrypted = $this->encryption->encrypt($original);

    expect($encrypted)->not->toBe($original)
        ->and($this->encryption->decrypt($encrypted))->toBe($original);
});

it('detects plain JSON correctly', function () {
    expect($this->encryption->isPlainJson('{"key":"value"}'))->toBeTrue()
        ->and($this->encryption->isPlainJson('[]'))->toBeTrue()
        ->and($this->encryption->isPlainJson('null'))->toBeTrue()
        ->and($this->encryption->isPlainJson('not-json-at-all'))->toBeFalse()
        ->and($this->encryption->isPlainJson($this->encryption->encrypt('test')))->toBeFalse();
});

// --- EncryptedArray cast ---

it('encrypts on write and decrypts on read', function () {
    $workspace = Workspace::factory()->create([
        'settings' => ['remote' => ['token' => 'ghp_abc123']],
    ]);

    // Raw DB value should not be plain JSON
    $raw = DB::table('workspaces')->where('id', $workspace->id)->value('settings');
    expect($this->encryption->isPlainJson($raw))->toBeFalse();

    // Eloquent read should return decrypted array
    $fresh = $workspace->fresh();
    expect($fresh->settings)->toBe(['remote' => ['token' => 'ghp_abc123']]);
});

it('handles null values gracefully', function () {
    $collection = Collection::factory()->create(['variables' => null]);

    expect($collection->fresh()->variables)->toBeNull();

    $raw = DB::table('collections')->where('id', $collection->id)->value('variables');
    expect($raw)->toBeNull();
});

it('handles empty arrays', function () {
    $workspace = Workspace::factory()->create(['settings' => []]);

    expect($workspace->fresh()->settings)->toBe([]);
});

it('reads legacy plain-text JSON from database', function () {
    $workspace = Workspace::factory()->create(['settings' => []]);

    // Simulate legacy data by writing plain JSON directly to the DB
    $legacyJson = json_encode(['remote' => ['provider' => 'github', 'token' => 'old-token']]);
    DB::table('workspaces')->where('id', $workspace->id)->update(['settings' => $legacyJson]);

    // Reading through Eloquent should still work
    $fresh = $workspace->fresh();
    expect($fresh->settings)->toBe(['remote' => ['provider' => 'github', 'token' => 'old-token']]);
});

it('auto-upgrades legacy data on save', function () {
    $workspace = Workspace::factory()->create(['settings' => []]);

    // Write plain JSON directly
    $legacyJson = json_encode(['key' => 'value']);
    DB::table('workspaces')->where('id', $workspace->id)->update(['settings' => $legacyJson]);

    // Read then save — triggers encryption
    $fresh = $workspace->fresh();
    $fresh->update(['settings' => $fresh->settings]);

    // Now raw value should be encrypted
    $raw = DB::table('workspaces')->where('id', $workspace->id)->value('settings');
    expect($this->encryption->isPlainJson($raw))->toBeFalse();

    // And still readable
    expect($workspace->fresh()->settings)->toBe(['key' => 'value']);
});

it('returns null on decryption failure', function () {
    $workspace = Workspace::factory()->create(['settings' => ['foo' => 'bar']]);

    // Corrupt the encrypted data
    DB::table('workspaces')->where('id', $workspace->id)->update(['settings' => 'corrupted-not-json-not-encrypted']);

    $fresh = $workspace->fresh();
    expect($fresh->settings)->toBeNull();
});

// --- Workspace getSetting/setSetting ---

it('workspace getSetting and setSetting work through encryption', function () {
    $workspace = Workspace::factory()->create(['settings' => []]);

    $workspace->setSetting('remote.token', 'ghp_secret');

    expect($workspace->fresh()->getSetting('remote.token'))->toBe('ghp_secret');

    // Verify raw DB value is encrypted
    $raw = DB::table('workspaces')->where('id', $workspace->id)->value('settings');
    expect($raw)->not->toContain('ghp_secret');
});

// --- Model-specific tests ---

it('encrypts environment variables', function () {
    $vars = [
        ['key' => 'API_KEY', 'value' => 'secret-123', 'enabled' => true],
    ];
    $environment = Environment::factory()->withVariables($vars)->create();

    $raw = DB::table('environments')->where('id', $environment->id)->value('variables');
    expect($raw)->not->toContain('secret-123');

    expect($environment->fresh()->variables)->toBe($vars);
});

it('encrypts collection variables', function () {
    $vars = [
        ['key' => 'BASE_URL', 'value' => 'https://api.example.com', 'enabled' => true],
    ];
    $collection = Collection::factory()->create(['variables' => $vars]);

    $raw = DB::table('collections')->where('id', $collection->id)->value('variables');
    expect($raw)->not->toContain('https://api.example.com');

    expect($collection->fresh()->variables)->toBe($vars);
});

it('encrypts request auth', function () {
    $auth = ['type' => 'bearer', 'token' => 'my-bearer-token'];
    $request = Request::factory()->create(['auth' => $auth]);

    $raw = DB::table('requests')->where('id', $request->id)->value('auth');
    expect($raw)->not->toContain('my-bearer-token');

    expect($request->fresh()->auth)->toBe($auth);
});

// --- Vault-synced environment safeguard ---

it('vault-synced environment toArray returns empty variables', function () {
    $vars = [
        ['key' => 'SECRET', 'value' => 'vault-secret', 'enabled' => true],
    ];
    $environment = Environment::factory()->vaultSynced()->withVariables($vars)->create();

    $array = $environment->fresh()->toArray();

    expect($array['variables'])->toBe([])
        ->and($array['vault_synced'])->toBeTrue();
});

it('non-vault environment toArray includes variables', function () {
    $vars = [
        ['key' => 'API_KEY', 'value' => 'regular-value', 'enabled' => true],
    ];
    $environment = Environment::factory()->withVariables($vars)->create();

    $array = $environment->fresh()->toArray();

    expect($array['variables'])->toBe($vars);
});

// --- Export safety ---

it('DataExportImportService exports decrypted variables', function () {
    $workspace = Workspace::factory()->create(['settings' => []]);
    $vars = [
        ['key' => 'DB_PASS', 'value' => 'encrypted-secret', 'enabled' => true],
    ];
    Environment::factory()->withVariables($vars)->create(['workspace_id' => $workspace->id]);

    $service = new DataExportImportService;
    $export = $service->exportAll($workspace->id);

    expect($export['data']['environments'][0]['variables'])->toBe($vars);
});

it('DataExportImportService exports empty variables for vault-synced environments', function () {
    $workspace = Workspace::factory()->create(['settings' => []]);
    Environment::factory()->vaultSynced()->withVariables([
        ['key' => 'VAULT_SECRET', 'value' => 'should-not-export', 'enabled' => true],
    ])->create(['workspace_id' => $workspace->id]);

    $service = new DataExportImportService;
    $export = $service->exportAll($workspace->id);

    expect($export['data']['environments'][0]['variables'])->toBe([]);
});

it('YamlCollectionSerializer produces decrypted YAML', function () {
    $vars = [
        ['key' => 'TOKEN', 'value' => 'secret-token', 'enabled' => true],
    ];
    $collection = Collection::factory()->create(['variables' => $vars]);

    $serializer = new YamlCollectionSerializer;
    $files = $serializer->serializeToDirectory($collection);

    $collectionFile = $files[$collection->id.'/_collection.yaml'];

    expect($collectionFile)->toContain('secret-token');
});
