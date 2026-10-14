<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page when visiting film details', function () {
    $response = $this->get(route('films.show', 101));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view film details and episodes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('films.show', 101))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('films/Show')
            ->has('film')
            ->where('film.title', 'The Quiet Archipelago')
            ->has('episodes', 8)
        );
});

test('guests are redirected when visiting episode watch page', function () {
    $response = $this->get(route('films.episodes.watch', ['film' => 101, 'episode' => '08']));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view episode watch page with player and comments', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('films.episodes.watch', ['film' => 101, 'episode' => '08']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('films/Watch')
            ->has('film')
            ->where('film.title', 'The Quiet Archipelago')
            ->has('currentEpisode')
            ->where('currentEpisode.number', '08')
            ->where('currentEpisode.title', 'Echoes of the Fjord')
            ->has('episodes', 8)
            ->has('comments', 5)
        );
});
