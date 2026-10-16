<?php

it('exposes a health endpoint', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJson(['status' => 'ok']);
});

it('serves an api index at the root', function () {
    $this->getJson('/')
        ->assertOk()
        ->assertJson(['status' => 'ok']);
});
