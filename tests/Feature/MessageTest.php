<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page when visiting messages', function () {
    $response = $this->get(route('messages.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view messages page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('messages.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('messages/Index'));
});

test('authenticated user can view messages page with creator query param', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('messages.index', ['creator' => 'creator-1']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('messages/Index')
            ->where('selectedCreatorId', 'creator-1')
        );
});

test('accessing singular message route redirects to messages.index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/message')
        ->assertRedirect(route('messages.index'));
});
