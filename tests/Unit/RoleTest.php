<?php

use App\Enums\Role;

it('exposes the expected backing values', function () {
    expect(Role::Admin->value)->toBe('admin')
        ->and(Role::Staff->value)->toBe('staff')
        ->and(Role::Customer->value)->toBe('customer');
});

it('returns a human readable label for each role', function () {
    expect(Role::Admin->label())->toBe('Administrator')
        ->and(Role::Staff->label())->toBe('Staff member')
        ->and(Role::Customer->label())->toBe('Customer');
});

it('can be resolved from its string value', function () {
    expect(Role::from('staff'))->toBe(Role::Staff);
});
