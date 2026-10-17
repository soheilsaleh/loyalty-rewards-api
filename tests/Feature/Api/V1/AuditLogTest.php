<?php

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('records a manual adjustment in the audit trail', function () {
    $admin = User::factory()->admin()->create();
    $account = Account::factory()->create();

    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/accounts/{$account->getKey()}/adjust", [
        'points' => 100,
        'reason' => 'Goodwill',
    ])->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'account.adjust',
        'user_id' => $admin->getKey(),
        'auditable_type' => Account::class,
        'auditable_id' => $account->getKey(),
    ]);
});

it('records reward creation and deletion', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/v1/rewards', ['name' => 'Mug', 'cost_points' => 300])->assertCreated();
    $id = $created->json('data.id');

    $this->deleteJson("/api/v1/rewards/{$id}")->assertNoContent();

    $this->assertDatabaseHas('audit_logs', ['action' => 'reward.create', 'user_id' => $admin->getKey()]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'reward.delete', 'auditable_id' => $id]);
});

it('records campaign changes', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/campaigns', ['name' => 'Flash Sale'])->assertCreated();

    $this->assertDatabaseHas('audit_logs', ['action' => 'campaign.create', 'user_id' => $admin->getKey()]);
});

it('lists the audit trail for admins', function () {
    $admin = User::factory()->admin()->create();
    AuditLog::factory()->count(3)->create();

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/audit-logs')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'action', 'action_label', 'changes', 'actor', 'created_at']]]);
});

it('forbids non-admins from the audit trail', function () {
    Sanctum::actingAs(User::factory()->staff()->create());

    $this->getJson('/api/v1/audit-logs')->assertForbidden();
});

it('rejects guests from the audit trail', function () {
    $this->getJson('/api/v1/audit-logs')->assertUnauthorized();
});
