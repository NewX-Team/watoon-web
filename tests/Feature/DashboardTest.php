<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated user sees user dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('dashboard/User'));
});

test('authenticated kreator sees kreator dashboard', function () {
    $user = User::factory()->kreator()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('dashboard/Kreator'));
});

test('authenticated admin sees admin dashboard', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('dashboard/Admin'));
});

test('authenticated user accessing favorites is redirected to dashboard favorite tab', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('favorites'))
        ->assertRedirect(route('dashboard', ['tab' => 'favorite']));
});

test('authenticated user accessing subscribe is redirected to dashboard subscribe tab', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('subscribe'))
        ->assertRedirect(route('dashboard', ['tab' => 'subscribe']));
});

test('authenticated user accessing favorite singular is redirected to dashboard favorite tab', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/favorite')
        ->assertRedirect(route('dashboard', ['tab' => 'favorite']));
});

test('authenticated user accessing home is redirected to dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/home')
        ->assertRedirect(route('dashboard'));
});
