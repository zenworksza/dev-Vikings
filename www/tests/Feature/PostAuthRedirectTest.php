<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

function registrationPayload(): array
{
    return [
        'name' => 'Ingrid Olsen',
        'email' => 'ingrid@example.com',
        'password' => 'a-long-Passw0rd!',
        'password_confirmation' => 'a-long-Passw0rd!',
    ];
}

test('an investor who once tried /admin is not sent back to a 403 after registering', function () {
    $this->withSession(['url.intended' => url('/admin')])
        ->post('/register', registrationPayload())
        ->assertRedirect(route('dashboard'));
});

test('an investor is not sent to /admin after logging in either', function () {
    $user = User::factory()->create(['password' => 'a-long-Passw0rd!']);

    $this->withSession(['url.intended' => url('/admin/applications')])
        ->post('/login', ['email' => $user->email, 'password' => 'a-long-Passw0rd!'])
        ->assertRedirect(route('dashboard'));
});

test('an investor is sent back to /portal if that is where they were headed', function () {
    $user = User::factory()->create(['password' => 'a-long-Passw0rd!']);

    $this->withSession(['url.intended' => url('/portal')])
        ->post('/login', ['email' => $user->email, 'password' => 'a-long-Passw0rd!'])
        ->assertRedirect(url('/portal'));
});

test('an admin is sent back to the admin page they wanted', function () {
    $admin = User::factory()->create(['password' => 'a-long-Passw0rd!'])->assignRole('platform_admin');

    $this->withSession(['url.intended' => url('/admin/applications')])
        ->post('/login', ['email' => $admin->email, 'password' => 'a-long-Passw0rd!'])
        ->assertRedirect(url('/admin/applications'));
});

test('with nothing remembered, login lands on the dashboard which routes by role', function () {
    $user = User::factory()->create(['password' => 'a-long-Passw0rd!']);

    $this->post('/login', ['email' => $user->email, 'password' => 'a-long-Passw0rd!'])
        ->assertRedirect(route('dashboard'));
});
