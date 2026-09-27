<?php

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('user can register and receive wedding workspace and sanctum token', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Sok & Dara',
        'email' => 'sokdara@example.com',
        'phone' => '+85512345678',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'wedding_title' => 'Sok and Dara Wedding Celebration',
        'wedding_date' => '2026-11-20',
    ])->assertCreated();

    $response->assertJsonStructure([
        'data' => [
            'token',
            'user' => ['id', 'name', 'email', 'phone'],
            'wedding' => ['id', 'title', 'slug', 'status'],
            'role',
            'permissions',
        ],
    ]);

    expect($response->json('data.role'))->toBe(RoleName::USER->value)
        ->and($response->json('data.user.email'))->toBe('sokdara@example.com')
        ->and($response->json('data.wedding.title'))->toBe('Sok and Dara Wedding Celebration');

    $this->assertDatabaseHas('users', ['email' => 'sokdara@example.com']);
    $this->assertDatabaseHas('weddings', ['title' => 'Sok and Dara Wedding Celebration']);
});

test('user cannot register with duplicate email or phone', function () {
    User::factory()->create([
        'email' => 'existing@example.com',
        'phone' => '+85598765432',
    ]);

    $this->postJson('/api/auth/register', [
        'name' => 'Another User',
        'email' => 'existing@example.com',
        'phone' => '+85598765432',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['email', 'phone']);
});

test('user can login with valid email or phone and receive token', function () {
    $user = User::factory()->asUser()->create([
        'email' => 'couple@example.com',
        'phone' => '+85511223344',
        'password' => bcrypt('SecretPass123'),
        'is_active' => true,
    ]);

    // Login with email
    $res1 = $this->postJson('/api/auth/login', [
        'email' => 'couple@example.com',
        'password' => 'SecretPass123',
    ])->assertOk();

    expect($res1->json('data.token'))->not->toBeEmpty()
        ->and($res1->json('data.user.email'))->toBe('couple@example.com');

    // Login with phone in the email field
    $res2 = $this->postJson('/api/auth/login', [
        'email' => '+85511223344',
        'password' => 'SecretPass123',
    ])->assertOk();

    expect($res2->json('data.token'))->not->toBeEmpty();
});

test('deactivated user cannot login', function () {
    User::factory()->asUser()->create([
        'email' => 'banned@example.com',
        'password' => bcrypt('SecretPass123'),
        'is_active' => false,
    ]);

    $this->postJson('/api/auth/login', [
        'email' => 'banned@example.com',
        'password' => 'SecretPass123',
    ])->assertStatus(403);
});

test('me endpoint returns user, role, and permissions matching API contract', function () {
    $admin = User::factory()->asAdmin()->create();

    $response = $this->actingAs($admin)
        ->getJson('/api/auth/me')
        ->assertOk();

    $response->assertJsonStructure([
        'data' => [
            'user' => ['id', 'name', 'email'],
            'role',
            'permissions',
        ],
    ]);

    expect($response->json('data.role'))->toBe(RoleName::ADMIN->value)
        ->and($response->json('data.permissions'))->toBeArray()
        ->and($response->json('data.permissions'))->toContain('weddings.view');
});

test('user can logout and token is revoked', function () {
    $user = User::factory()->asUser()->create();
    $token = $user->createToken('test_token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/auth/logout')
        ->assertOk();

    expect($user->tokens()->count())->toBe(0);
});

test('root endpoint renders welcome page for browser and json for api requests', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('TheapKa Online');

    $this->getJson('/')
        ->assertOk()
        ->assertJson([
            'status' => 'operational',
        ]);
});

test('user can connect with google account and register or login', function () {
    // 1. New user registration via Google
    $res = $this->postJson('/api/auth/google', [
        'email' => 'googlecouple@example.com',
        'name' => 'Google Couple',
        'avatar_url' => 'https://lh3.googleusercontent.com/a/photo.jpg',
    ])->assertOk();

    expect($res->json('data.token'))->not->toBeEmpty()
        ->and($res->json('data.user.email'))->toBe('googlecouple@example.com')
        ->and($res->json('data.role'))->toBe(RoleName::USER->value);

    $this->assertDatabaseHas('users', ['email' => 'googlecouple@example.com']);

    // 2. Existing user login via Google
    $res2 = $this->postJson('/api/auth/google', [
        'email' => 'googlecouple@example.com',
    ])->assertOk();

    expect($res2->json('data.user.email'))->toBe('googlecouple@example.com');
});
