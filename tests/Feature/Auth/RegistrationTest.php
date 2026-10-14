<?php

use App\Enums\Role;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register as default user', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    expect(auth()->user()->role)->toBe(Role::User);
});

test('new creators can register with role', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test Kreator',
        'email' => 'kreator@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'kreator',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    expect(auth()->user()->role)->toBe(Role::Kreator);
});
