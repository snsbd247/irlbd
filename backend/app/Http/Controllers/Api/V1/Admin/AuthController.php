<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Session-cookie authentication for the admin SPA (Laravel Sanctum "SPA authentication").
 * No tokens are issued to the browser; the session cookie is HttpOnly and CSRF-protected.
 * There is deliberately no public registration endpoint.
 */
class AuthController extends Controller
{
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
        unset($credentials['remember']);

        if (! Auth::guard('web')->attempt([...$credentials, 'is_active' => true], $remember)) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match an active account.']);
        }

        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json(['user' => $this->present($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
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
            'password' => ['nullable', 'confirmed', Password::min(10)->letters()->numbers(), 'max:128'],
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
        ];
    }
}
