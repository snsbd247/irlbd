<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_admin_can_log_in_with_valid_credentials(): void
    {
        $user = $this->admin();

        $this->spa()->postJson('/api/v1/admin/login', ['email' => $user->email, 'password' => 'Password123'])
            ->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.is_super', true)
            ->assertJsonMissingPath('user.password');

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_login_never_returns_a_token(): void
    {
        $user = $this->admin();

        $response = $this->spa()->postJson('/api/v1/admin/login', ['email' => $user->email, 'password' => 'Password123']);

        $this->assertStringNotContainsStringIgnoringCase('token', $response->getContent());
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->admin();

        $this->spa()->postJson('/api/v1/admin/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertGuest('web');
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = $this->makeUser('super-admin', ['is_active' => false]);

        $this->spa()->postJson('/api/v1/admin/login', ['email' => $user->email, 'password' => 'Password123'])
            ->assertStatus(422);
    }

    public function test_deactivated_user_is_rejected_on_next_request(): void
    {
        $user = $this->admin();
        $user->update(['is_active' => false]);

        $this->actingAs($user)->spa()->getJson('/api/v1/admin/me')->assertForbidden();
    }

    public function test_admin_endpoints_require_authentication(): void
    {
        $this->spa()->getJson('/api/v1/admin/me')->assertUnauthorized();
        $this->getJson('/api/v1/admin/products')->assertUnauthorized();
        $this->postJson('/api/v1/admin/products', ['name' => 'X'])->assertUnauthorized();
        $this->getJson('/api/v1/admin/enquiries')->assertUnauthorized();
    }

    public function test_login_is_rate_limited(): void
    {
        RateLimiter::clear('login');
        $user = $this->admin();

        for ($i = 0; $i < 5; $i++) {
            $this->spa()->postJson('/api/v1/admin/login', ['email' => $user->email, 'password' => 'bad-password-'.$i]);
        }

        $this->spa()->postJson('/api/v1/admin/login', ['email' => $user->email, 'password' => 'Password123'])
            ->assertStatus(429);
    }

    public function test_there_is_no_public_registration_endpoint(): void
    {
        $this->spa()->postJson('/api/v1/admin/register', ['email' => 'x@example.com', 'password' => 'Password123'])->assertNotFound();
        $this->spa()->postJson('/api/v1/register', ['email' => 'x@example.com'])->assertNotFound();
    }

    public function test_user_can_log_out(): void
    {
        $user = $this->admin();
        $this->spa()->postJson('/api/v1/admin/login', ['email' => $user->email, 'password' => 'Password123'])->assertOk();

        $this->spa()->postJson('/api/v1/admin/logout')->assertOk();

        $this->assertGuest('web');
    }

    public function test_profile_password_change_requires_current_password(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->spa()->putJson('/api/v1/admin/profile', [
            'name' => 'New Name', 'email' => $user->email,
            'current_password' => 'not-it', 'password' => 'NewPassword456', 'password_confirmation' => 'NewPassword456',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->actingAs($user)->spa()->putJson('/api/v1/admin/profile', [
            'name' => 'New Name', 'email' => $user->email,
            'current_password' => 'Password123', 'password' => 'NewPassword456', 'password_confirmation' => 'NewPassword456',
        ])->assertOk()->assertJsonPath('user.name', 'New Name');
    }
}
