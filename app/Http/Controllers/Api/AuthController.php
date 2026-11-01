<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

/** Authentification de l'API par jeton (Laravel Sanctum), compatible avec la double authentification. */
class AuthController extends Controller
{
    public function login(Request $request, TwoFactorAuthenticationProvider $twoFactor): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'code' => 'nullable|string',
            'recovery_code' => 'nullable|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        // Si la 2FA est activée, un code TOTP (ou un code de récupération) est exigé, comme sur le site.
        if ($user->hasTwoFactorEnabled()) {
            $this->verifierDeuxiemeFacteur($user, $data, $twoFactor);
        }

        return response()->json([
            'token' => $user->createToken($data['device_name'] ?? 'api')->plainTextToken,
            'user' => $this->present($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->present($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        // Révoque uniquement le jeton utilisé pour cette requête.
        $request->user()->currentAccessToken()?->delete();

        return response()->json(null, 204);
    }

    private function verifierDeuxiemeFacteur(User $user, array $data, TwoFactorAuthenticationProvider $twoFactor): void
    {
        if (! empty($data['code'])) {
            if ($twoFactor->verify(decrypt($user->two_factor_secret), $data['code'])) {
                return;
            }
            throw ValidationException::withMessages(['code' => __('The provided two factor authentication code was invalid.')]);
        }

        if (! empty($data['recovery_code'])) {
            $code = collect($user->recoveryCodes())->first(fn ($c) => hash_equals($c, $data['recovery_code']));
            if ($code) {
                $user->replaceRecoveryCode($code); // un code de récupération ne sert qu'une fois

                return;
            }
            throw ValidationException::withMessages(['recovery_code' => __('The provided two factor recovery code was invalid.')]);
        }

        throw ValidationException::withMessages(['code' => 'La double authentification est activée : envoyez "code" ou "recovery_code".']);
    }

    private function present(User $user): array
    {
        return $user->only('id', 'name', 'email', 'communaute_id') + [
            'role' => $user->role,
            'two_factor' => $user->hasTwoFactorEnabled(),
        ];
    }
}
