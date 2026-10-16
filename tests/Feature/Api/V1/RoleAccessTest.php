<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])
        ->get('/api/v1/_test/admin-only', fn () => response()->json(['ok' => true]));

    Route::middleware(['auth:sanctum', 'role:staff,admin'])
        ->get('/api/v1/_test/staff-or-admin', fn () => response()->json(['ok' => true]));
});

it('allows admins on admin-only routes', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->getJson('/api/v1/_test/admin-only')->assertOk();
});

it('forbids customers from admin-only routes', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/_test/admin-only')->assertForbidden();
});

it('allows staff on routes that accept staff or admin', function () {
    Sanctum::actingAs(User::factory()->staff()->create());

    $this->getJson('/api/v1/_test/staff-or-admin')->assertOk();
});

it('forbids customers from staff-only routes', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/_test/staff-or-admin')->assertForbidden();
});

it('rejects guests from role-protected routes', function () {
    $this->getJson('/api/v1/_test/admin-only')->assertUnauthorized();
});
