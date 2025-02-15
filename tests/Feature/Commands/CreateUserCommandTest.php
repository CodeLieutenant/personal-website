<?php

declare(strict_types=1);

use App\Helpers\Roles;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\Concerns\InteractsWithConsole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\seed;

uses(LazilyRefreshDatabase::class, InteractsWithConsole::class);

beforeEach(function (): void {
    seed(RolesAndPermissionsSeeder::class);
});

it('creates user with command line arguments', function (): void {
    artisan('app:create-user', ['name' => 'Dusan Malusev', 'email' => 'dusan@dusanmalusev.dev', 'role' => Roles::OWNER])
        ->expectsQuestion('What will be user\'s password?', 'really-Complicated-password-$678')
        ->assertExitCode(0)
        ->expectsOutput('User create with ID 1');

    assertDatabaseHas('users', ['id' => 1]);
    expect(\App\Models\User::query()->findOrFail(1)->roles()->first()->name)->toBe(Roles::OWNER);
});

it('creates user with generated password', function (): void {
    artisan('app:create-user',
        ['name' => 'Dusan Malusev', 'email' => 'dusan@dusanmalusev.dev', 'role' => Roles::OWNER, '--generate-password' => true])
        ->assertExitCode(0)
        ->expectsOutputToContain('User create with ID 2')
        ->expectsOutputToContain('Generated password: ');

    assertDatabaseHas('users', ['id' => 2]);
    expect(\App\Models\User::query()->findOrFail(2)->roles()->first()->name)->toBe(Roles::OWNER);
});

it('creates user with prompts', function (): void {
    artisan('app:create-user')
        ->expectsQuestion('User\'s name?', 'Dusan Malusev')
        ->expectsQuestion('User\'s email?', 'test@testexample.com')
        ->expectsQuestion('User\'s role?', Roles::OWNER)
        ->expectsQuestion('What will be user\'s password?', 'really-Complicated-password-$678')
        ->assertSuccessful()
        ->expectsOutputToContain('User create with ID 3');

    assertDatabaseHas('users', ['id' => 3]);
    expect(\App\Models\User::query()->findOrFail(3)->roles()->first()->name)->toBe(Roles::OWNER);
});

it('failed with email already exists', function (): void {
    $user = User::factory()->create();

    artisan('app:create-user', ['name' => 'Dusan Malusev', 'email' => $user->email, 'role' => Roles::OWNER])
        ->expectsOutput('Check you input: The email has already been taken.')
        ->expectsQuestion('What will be user\'s password?', 'really-Complicated-password-$678')
        ->assertFailed();
});
