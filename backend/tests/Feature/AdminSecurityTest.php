<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    protected function login(User $user, string $password = 'Password123')
    {
        return $this->spa()->postJson('/api/v1/admin/login', ['email' => $user->email, 'password' => $password]);
    }

    // ------------------------------------------------------------ activity log

    public function test_admin_changes_are_logged_without_secrets(): void
    {
        $admin = $this->admin();
        $id = $this->actingAs($admin)->spa()->postJson('/api/v1/admin/products', ['name' => 'Bottle', 'status' => 'draft'])->json('id');
        $this->actingAs($admin)->spa()->putJson("/api/v1/admin/products/{$id}", ['name' => 'Bottle XL', 'status' => 'published', 'description' => '<p>Long text</p>']);
        $this->actingAs($admin)->spa()->deleteJson("/api/v1/admin/products/{$id}");

        $logs = ActivityLog::where('subject_type', 'Product')->orderBy('id')->get();
        $this->assertSame(['created', 'updated', 'deleted'], $logs->pluck('action')->all());
        $this->assertSame(['from' => 'Bottle', 'to' => 'Bottle XL'], $logs[1]->changes['name']);
        $this->assertSame(['changed' => true], $logs[1]->changes['description']);
        $this->assertSame($admin->id, $logs[0]->user_id);

        // A password change is logged, but the hash never is.
        $this->actingAs($admin)->spa()->putJson('/api/v1/admin/users/'.$admin->id, [
            'name' => $admin->name, 'email' => $admin->email, 'role_id' => $admin->role_id, 'is_active' => true, 'password' => 'NewPassword999',
        ])->assertOk();
        $userLog = ActivityLog::where('subject_type', 'User')->where('action', 'updated')->latest('id')->first();
        $this->assertSame(['changed' => true], $userLog->changes['password']);
        $this->assertStringNotContainsString('$2y$', json_encode(ActivityLog::all()));
    }

    public function test_public_submissions_and_failed_logins(): void
    {
        $this->postJson('/api/v1/contact', ['name' => 'Visitor', 'email' => 'v@example.com', 'subject' => 'Other', 'message' => 'Hello from a visitor.'])->assertCreated();
        $this->assertSame(0, ActivityLog::count());

        $admin = $this->admin();
        $this->login($admin, 'wrong-password')->assertStatus(422);
        $this->assertSame('login_failed', ActivityLog::sole()->action);
        $this->assertSame($admin->email, ActivityLog::sole()->subject_label);

        $this->login($admin)->assertOk();
        $this->assertSame('login', ActivityLog::latest('id')->first()->action);
    }

    public function test_activity_log_is_restricted_to_user_managers(): void
    {
        $this->actingAs($this->editor())->spa()->getJson('/api/v1/admin/activity-log')->assertForbidden();
        $this->actingAs($this->admin())->spa()->postJson('/api/v1/admin/services', ['title' => 'Molding'])->assertCreated();

        $this->actingAs($this->admin())->spa()->getJson('/api/v1/admin/activity-log?type=Service')->assertOk()
            ->assertJsonPath('data.0.action', 'created')
            ->assertJsonPath('data.0.subject_label', 'Molding');
    }

    // --------------------------------------------------------------------- 2FA

    public function test_two_factor_setup_and_login_flow(): void
    {
        $admin = $this->admin();
        $google = new Google2FA;

        // Setup requires the password.
        $this->actingAs($admin)->spa()->postJson('/api/v1/admin/two-factor', ['password' => 'wrong'])->assertStatus(422);
        $setup = $this->actingAs($admin)->spa()->postJson('/api/v1/admin/two-factor', ['password' => 'Password123'])->assertOk();
        $this->assertStringContainsString('<svg', $setup->json('qr_svg'));
        $secret = $setup->json('secret');

        // Secret is stored encrypted, not in plain text.
        $raw = \DB::table('users')->where('id', $admin->id)->value('two_factor_secret');
        $this->assertNotSame($secret, $raw);

        $this->actingAs($admin)->spa()->postJson('/api/v1/admin/two-factor/confirm', ['code' => '000000'])->assertStatus(422);
        $codes = $this->actingAs($admin)->spa()->postJson('/api/v1/admin/two-factor/confirm', ['code' => $google->getCurrentOtp($secret)])
            ->assertOk()->json('recovery_codes');
        $this->assertCount(8, $codes);
        $this->assertTrue($admin->fresh()->hasTwoFactor());

        // Fresh login now needs a second step.
        $this->app['auth']->forgetGuards();
        $this->login($admin)->assertOk()->assertJsonPath('two_factor', true)->assertJsonMissingPath('user');
        $this->assertGuest('web');

        $this->spa()->postJson('/api/v1/admin/two-factor-challenge', ['code' => '123456'])->assertStatus(422);
        $this->assertGuest('web');

        // A recovery code works once.
        $this->spa()->postJson('/api/v1/admin/two-factor-challenge', ['recovery_code' => $codes[0]])->assertOk()->assertJsonPath('user.two_factor_enabled', true);
        $this->assertAuthenticatedAs($admin->fresh(), 'web');
        $this->assertNotContains($codes[0], $admin->fresh()->two_factor_recovery_codes);

        $this->spa()->postJson('/api/v1/admin/logout')->assertOk();
        $this->login($admin)->assertJsonPath('two_factor', true);
        $this->spa()->postJson('/api/v1/admin/two-factor-challenge', ['recovery_code' => $codes[0]])->assertStatus(422);
        // (The current TOTP was already used to confirm setup, so reuse protection would reject it here.)
        $this->spa()->postJson('/api/v1/admin/two-factor-challenge', ['recovery_code' => $codes[1]])->assertOk();
    }

    public function test_totp_code_cannot_be_reused(): void
    {
        $admin = $this->admin();
        $secret = (new Google2FA)->generateSecretKey(32);
        $admin->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => []])->save();
        $code = (new Google2FA)->getCurrentOtp($secret);

        $this->login($admin)->assertJsonPath('two_factor', true);
        $this->spa()->postJson('/api/v1/admin/two-factor-challenge', ['code' => $code])->assertOk();
        $this->spa()->postJson('/api/v1/admin/logout');

        $this->login($admin)->assertJsonPath('two_factor', true);
        $this->spa()->postJson('/api/v1/admin/two-factor-challenge', ['code' => $code])->assertStatus(422);
    }

    public function test_challenge_without_password_step_is_rejected(): void
    {
        $this->spa()->postJson('/api/v1/admin/two-factor-challenge', ['code' => '123456'])->assertStatus(419);
        $this->assertGuest('web');
    }

    public function test_super_admin_can_reset_someone_elses_two_factor(): void
    {
        $editor = $this->editor();
        $editor->forceFill(['two_factor_secret' => 'ABC', 'two_factor_confirmed_at' => now()])->save();

        $this->actingAs($this->support())->spa()->deleteJson("/api/v1/admin/users/{$editor->id}/two-factor")->assertForbidden();
        $this->actingAs($this->admin())->spa()->deleteJson("/api/v1/admin/users/{$editor->id}/two-factor")->assertOk();
        $this->assertFalse($editor->fresh()->hasTwoFactor());
    }

    // ---------------------------------------------------------- password reset

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $admin = $this->admin();

        // Unknown emails get the same answer (no account enumeration) and no email.
        $same = $this->spa()->postJson('/api/v1/admin/forgot-password', ['email' => 'nobody@example.com'])->assertOk()->json('message');
        $this->assertSame($same, $this->spa()->postJson('/api/v1/admin/forgot-password', ['email' => $admin->email])->assertOk()->json('message'));

        $token = null;
        Notification::assertSentTo($admin, ResetPassword::class, function ($n) use (&$token, $admin) {
            $token = $n->token;
            $url = $n->toMail($admin)->actionUrl;

            return str_starts_with($url, 'http://localhost:5173/admin/reset-password?token=');
        });
        Notification::assertCount(1);

        $this->spa()->postJson('/api/v1/admin/reset-password', [
            'token' => 'invalid', 'email' => $admin->email, 'password' => 'BrandNew12345', 'password_confirmation' => 'BrandNew12345',
        ])->assertStatus(422);

        $this->spa()->postJson('/api/v1/admin/reset-password', [
            'token' => $token, 'email' => $admin->email, 'password' => 'short', 'password_confirmation' => 'short',
        ])->assertStatus(422)->assertJsonValidationErrors('password');

        $this->spa()->postJson('/api/v1/admin/reset-password', [
            'token' => $token, 'email' => $admin->email, 'password' => 'BrandNew12345', 'password_confirmation' => 'BrandNew12345',
        ])->assertOk();

        $this->login($admin, 'BrandNew12345')->assertOk();
        // Tokens are single use.
        $this->spa()->postJson('/api/v1/admin/reset-password', [
            'token' => $token, 'email' => $admin->email, 'password' => 'Another12345', 'password_confirmation' => 'Another12345',
        ])->assertStatus(422);
        $this->assertTrue(ActivityLog::where('action', 'password_reset')->exists());
    }

    public function test_inactive_users_get_no_reset_email(): void
    {
        Notification::fake();
        $user = $this->makeUser('content-editor', ['is_active' => false]);

        $this->spa()->postJson('/api/v1/admin/forgot-password', ['email' => $user->email])->assertOk();

        Notification::assertNothingSent();
        $this->assertNotNull(Product::query()); // keeps imports used
    }
}
