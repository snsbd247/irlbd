<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\TwoFactor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * Session-cookie authentication for the admin SPA (Laravel Sanctum "SPA authentication").
 * No tokens are issued to the browser; the session cookie is HttpOnly and CSRF-protected.
 * There is deliberately no public registration endpoint.
 */
class AuthController extends Controller
{
    public function __construct(protected ActivityLogger $logger) {}

    public function login(Request $request): JsonResponse
    {
        // Sessions only exist for requests from SANCTUM_STATEFUL_DOMAINS (the admin SPA).
        if (! $request->hasSession()) {
            return response()->json(['message' => 'Login is only available from the admin panel origin.'], 400);
        }

        $credentials = $request->validate([
            'email' => 'required|email|max:150',
            'password' => 'required|string|max:128',
            'remember' => 'boolean',
        ]);

        $remember = (bool) ($credentials['remember'] ?? false);
        $guard = Auth::guard('web');

        if (! $guard->validate(['email' => $credentials['email'], 'password' => $credentials['password'], 'is_active' => true])) {
            $this->logger->log('login_failed', null, null, null, $credentials['email']);
            throw ValidationException::withMessages(['email' => 'These credentials do not match an active account.']);
        }

        $user = User::where('email', $credentials['email'])->firstOrFail();

        // Second step required: remember who passed the password check for 5 minutes.
        if ($user->hasTwoFactor()) {
            $request->session()->put('2fa', ['id' => $user->id, 'remember' => $remember, 'expires' => now()->addMinutes(5)->timestamp]);

            return response()->json(['two_factor' => true]);
        }

        return $this->completeLogin($request, $user, $remember);
    }

    public function twoFactorChallenge(Request $request, TwoFactor $twoFactor): JsonResponse
    {
        if (! $request->hasSession()) {
            return response()->json(['message' => 'Login is only available from the admin panel origin.'], 400);
        }
        $data = $request->validate(['code' => 'nullable|string|max:10', 'recovery_code' => 'nullable|string|max:20']);
        $pending = $request->session()->get('2fa');

        if (! $pending || $pending['expires'] < now()->timestamp) {
            $request->session()->forget('2fa');

            return response()->json(['message' => 'Your login session expired. Please sign in again.'], 419);
        }

        $user = User::where('id', $pending['id'])->where('is_active', true)->first();
        $ok = $user && (
            (! empty($data['code']) && $twoFactor->verify($user, $data['code'])) ||
            (! empty($data['recovery_code']) && $twoFactor->useRecoveryCode($user, $data['recovery_code']))
        );

        if (! $ok) {
            $this->logger->log('2fa_failed', $user, null, $user);
            throw ValidationException::withMessages(['code' => 'The authentication code is invalid.']);
        }

        $request->session()->forget('2fa');

        return $this->completeLogin($request, $user, (bool) $pending['remember'], ! empty($data['recovery_code']));
    }

    protected function completeLogin(Request $request, User $user, bool $remember, bool $usedRecovery = false): JsonResponse
    {
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        $this->logger->log($usedRecovery ? 'login_recovery_code' : 'login', $user, null, $user);

        return response()->json(['user' => $this->present($user)]);
    }

    /** Always answers the same way, so it cannot be used to discover which emails have accounts. */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email|max:150']);
        $user = User::where('email', $request->input('email'))->where('is_active', true)->first();

        if ($user) {
            try {
                Password::broker()->sendResetLink(['email' => $user->email]);
            } catch (\Throwable $e) {
                Log::warning('Password reset email failed: '.$e->getMessage());
            }
            $this->logger->log('password_reset_requested', $user, null, $user);
        }

        return response()->json(['message' => 'If an active account exists for that email, a password reset link has been sent.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|max:150',
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers(), 'max:128'],
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                $this->logger->log('password_reset', $user, null, $user);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'This password reset link is invalid or has expired.']);
        }

        return response()->json(['message' => 'Your password has been reset. You can now sign in.']);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->user()) {
            $this->logger->log('logout', $request->user());
        }
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->present($request->user())]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,email,'.$user->id,
            'current_password' => 'required_with:password|nullable|string',
            'password' => ['nullable', 'confirmed', PasswordRule::min(10)->letters()->numbers(), 'max:128'],
        ]);

        if (! empty($data['password']) && ! Hash::check($data['current_password'] ?? '', $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();
        if (! empty($data['password'])) {
            $this->logger->log('password_changed', $user);
        }

        return response()->json(['user' => $this->present($user)]);
    }

    protected function present(User $user): array
    {
        $user->loadMissing('role.permissions');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->name,
            'is_super' => $user->isSuperAdmin(),
            'permissions' => $user->permissionSlugs(),
            'two_factor_enabled' => $user->hasTwoFactor(),
        ];
    }
}
