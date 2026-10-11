<?php

namespace Tests;

use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /** Origin of the admin SPA; requests from it receive a session (Sanctum stateful). */
    protected const SPA_ORIGIN = 'http://localhost:5173';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    protected function makeUser(string $roleSlug = 'super-admin', array $attributes = []): User
    {
        return User::create($attributes + [
            'name' => 'Test '.$roleSlug,
            'email' => $roleSlug.uniqid().'@example.com',
            'password' => 'Password123',
            'role_id' => Role::where('slug', $roleSlug)->value('id'),
            'is_active' => true,
        ]);
    }

    protected function admin(): User
    {
        return $this->makeUser('super-admin');
    }

    protected function editor(): User
    {
        return $this->makeUser('content-editor');
    }

    protected function support(): User
    {
        return $this->makeUser('support');
    }

    /** Authenticate through the Sanctum guard used by the admin API. */
    public function actingAs(Authenticatable $user, $guard = null)
    {
        Sanctum::actingAs($user);

        return $this;
    }

    /** Request headers the admin SPA sends. */
    protected function spa(): static
    {
        return $this->withHeaders(['Origin' => self::SPA_ORIGIN, 'Referer' => self::SPA_ORIGIN.'/admin', 'Accept' => 'application/json']);
    }

    protected function mediaPath(string $path = 'test/image.png'): string
    {
        Media::create(['path' => $path, 'original_name' => basename($path), 'mime_type' => 'image/png', 'size' => 10]);

        return $path;
    }
}
