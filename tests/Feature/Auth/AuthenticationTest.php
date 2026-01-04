<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->withSession(['_token' => 'test'])
        ->post(route('login.store'), [
            '_token' => 'test',
            'email' => $user->email,
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->withSession(['_token' => 'test'])
        ->post(route('login.store'), [
            '_token' => 'test',
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

    $response->assertSessionHasErrors();

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['_token' => 'test'])
        ->post(route('logout'), ['_token' => 'test']);

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});