<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\TwoFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function __construct(protected TwoFactor $twoFactor, protected ActivityLogger $logger) {}

    /** Starts setup: new (unconfirmed) secret + QR code. Requires the current password. */
    public function enable(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();
        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['password' => 'The password is incorrect.']);
        }

        $secret = $this->twoFactor->generateSecret();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();

        return response()->json(['secret' => $secret, 'qr_svg' => $this->twoFactor->qrSvg($user, $secret)]);
    }

    /** Confirms setup with a code from the app; returns one-time recovery codes. */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|max:10']);
        $user = $request->user();

        if (! $user->two_factor_secret || $user->two_factor_confirmed_at || ! $this->twoFactor->verify($user, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'The code is invalid. Check your phone clock and try the newest code.']);
        }

        $codes = $this->twoFactor->generateRecoveryCodes();
        $user->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => $codes])->save();
        $this->logger->log('2fa_enabled', $user);

        return response()->json(['recovery_codes' => $codes]);
    }

    public function recoveryCodes(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();
        if (! $user->hasTwoFactor() || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['password' => 'The password is incorrect.']);
        }
        $codes = $this->twoFactor->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();
        $this->logger->log('2fa_recovery_regenerated', $user);

        return response()->json(['recovery_codes' => $codes]);
    }

    public function disable(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();
        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['password' => 'The password is incorrect.']);
        }
        $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();
        $this->logger->log('2fa_disabled', $user);

        return response()->json(['message' => 'Two-factor authentication disabled.']);
    }

    /** An admin with user-management rights can reset 2FA for someone who lost their phone. */
    public function reset(Request $request, int $id): JsonResponse
    {
        $target = User::findOrFail($id);
        Gate::authorize('update', $target);
        $target->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();
        $this->logger->log('2fa_reset', $target);

        return response()->json(['message' => 'Two-factor authentication was reset for '.$target->name.'.']);
    }
}
