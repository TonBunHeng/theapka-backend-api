<?php

use App\Enums\RoleName;
use App\Models\AuditLog;
use App\Models\Backup;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('payment provider secrets are never present in any API response body', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();

    // Configure payment credentials including sensitive API key
    $secretApiKey = 'SUPER_SECRET_PAYWAY_PRIVATE_KEY_999';
    $this->actingAs($superAdmin)
        ->putJson('/api/super-admin/payment-config', [
            'aba_payway' => [
                'merchant_id' => 'ec4321',
                'api_key' => $secretApiKey,
                'api_url' => 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase',
                'sandbox' => true,
            ],
            'khqr' => [
                'bakong_account_id' => 'theapka_online@aclb',
                'merchant_name' => 'TheapKa Online',
                'merchant_city' => 'Phnom Penh',
                'sandbox' => true,
            ],
        ])->assertOk();

    // Fetch config
    $response = $this->actingAs($superAdmin)
        ->getJson('/api/super-admin/payment-config')
        ->assertOk();

    $responseContent = $response->getContent();

    // Verify the secret API key is never returned in plaintext
    expect($responseContent)->not->toContain($secretApiKey);

    $abaConfig = $response->json('data.aba_payway');
    expect($abaConfig['merchant_id'])->toBe('ec4321')
        ->and($abaConfig['api_key_configured'])->toBeTrue()
        ->and($abaConfig)->not->toHaveKey('api_key');
});

test('super admin can toggle maintenance mode and user routes receive 503', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();
    $couple = User::factory()->asUser()->create();

    // Enable maintenance mode
    $this->actingAs($superAdmin)
        ->putJson('/api/super-admin/maintenance', [
            'enabled' => true,
            'message' => 'Upgrading servers.',
        ])->assertOk()
          ->assertJsonPath('data.enabled', true);

    // Couple request now receives 503
    $this->actingAs($couple)
        ->getJson('/api/user/dashboard')
        ->assertStatus(503);

    // Super Admin remains exempt
    $this->actingAs($superAdmin)
        ->getJson('/api/super-admin/maintenance')
        ->assertOk();

    // Disable maintenance mode
    $this->actingAs($superAdmin)
        ->putJson('/api/super-admin/maintenance', [
            'enabled' => false,
        ])->assertOk();

    // Couple request succeeds again (or returns 404 for wedding if none created yet, not 503)
    $response = $this->actingAs($couple)->getJson('/api/user/dashboard');
    expect($response->status())->not->toBe(503);
});

test('super admin can query and export audit logs', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();

    AuditLog::create([
        'actor_id' => $superAdmin->id,
        'action' => 'security.update',
        'subject_type' => 'Setting',
        'subject_id' => 1,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest Test',
    ]);

    // List audit logs
    $listRes = $this->actingAs($superAdmin)
        ->getJson('/api/super-admin/audit-logs')
        ->assertOk();

    expect(count($listRes->json('data')))->toBeGreaterThanOrEqual(1);

    // Export audit logs
    $exportRes = $this->actingAs($superAdmin)
        ->getJson('/api/super-admin/audit-logs/export')
        ->assertOk();

    expect($exportRes->headers->get('content-type'))->toContain('text/csv');
});

test('no route exists to update or delete audit logs', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();

    $log = AuditLog::create([
        'actor_id' => $superAdmin->id,
        'action' => 'test.action',
        'subject_type' => 'Test',
        'subject_id' => 1,
        'ip_address' => '127.0.0.1',
    ]);

    $this->actingAs($superAdmin);

    // PUT route does not exist
    $this->putJson("/api/super-admin/audit-logs/{$log->id}", ['action' => 'hacked'])
        ->assertNotFound();

    // DELETE route does not exist
    $this->deleteJson("/api/super-admin/audit-logs/{$log->id}")
        ->assertNotFound();
});

test('super admin can trigger and list database backups', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();

    $createRes = $this->actingAs($superAdmin)
        ->postJson('/api/super-admin/backups')
        ->assertCreated();

    $createRes->assertJsonStructure([
        'data' => [
            'id',
            'file_name',
            'status',
        ],
    ]);

    $listRes = $this->actingAs($superAdmin)
        ->getJson('/api/super-admin/backups')
        ->assertOk();

    expect(count($listRes->json('data')))->toBeGreaterThanOrEqual(1);
});
