<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Passport\Token;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;

final class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'provider' => 'CREDENTIALS',
            'has_password' => true,
        ]);



        $user->sendEmailVerificationNotification();

        $tokens = $this->generateTokenPair($user);

        return $this->createdResponse([
            'user' => new UserResource($user),

            'tokens' => $tokens,
        ], 'User registered successfully. Please check your email to verify your account.');
    }
    /**
     * Login user and get token.
     * 
     * @unauthenticated
     * @param Request $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', $request->email)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->unauthorizedResponse('Invalid credentials');
        }

        // Track that user logged in via credentials
        if ($user->provider !== 'CREDENTIALS') {
            $user->update(['provider' => 'CREDENTIALS']);
        }

        $tokens = $this->generateTokenPair($user);

        return $this->successResponse([
            'user' => new UserResource($user),
            'tokens' => $tokens,
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->tokens()->delete();

        return $this->successResponse(message: 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(new UserResource($request->user()));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = [];

        if ($request->filled('name')) {
            $data['name'] = $request->name;
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
            $data['has_password'] = true;
        }

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                $oldPath = str_replace(asset('storage/'), '', $user->avatar);
                $fullPath = storage_path('app/public/' . $oldPath);
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            if ($path) {
                $data['avatar'] = asset('storage/' . $path);
            }
        }

        $user->update($data);

        return $this->successResponse(
            new UserResource($user->fresh()),
            'Profile updated successfully'
        );
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Current password is incorrect', 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return $this->successResponse(message: 'Password changed successfully');
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['refreshToken' => ['required', 'string']]);

        $tokenParts = explode('.', $request->refreshToken);
        if (count($tokenParts) !== 3) {
            return $this->unauthorizedResponse('Invalid refresh token format');
        }

        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1])), true);
        $tokenId = $payload['jti'] ?? null;

        if (!$tokenId) {
            return $this->unauthorizedResponse('Invalid refresh token claims');
        }

        /** @var Token|null $tokenModel */
        $tokenModel = Token::find($tokenId);

        if (! $tokenModel || $tokenModel->revoked || ($tokenModel->expires_at && $tokenModel->expires_at->isPast())) {
            return $this->unauthorizedResponse('Invalid refresh token');
        }

        /** @var User|null $user */
        $user = User::find($tokenModel->user_id);

        if (! $user) {
            return $this->unauthorizedResponse('User not found');
        }

        $tokenModel->update(['revoked' => true]);

        $tokens = $this->generateTokenPair($user);

        return $this->successResponse($tokens);
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(message: 'Email already verified');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->successResponse(message: 'Email verified successfully');
    }

    public function resendVerificationEmail(ResendVerificationRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->email)->first();

        if (! $user) {
            return $this->notFoundResponse('User not found');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->errorResponse('Email already verified', 400);
        }

        $user->sendEmailVerificationNotification();

        return $this->successResponse(message: 'Verification email sent successfully');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return $this->successResponse(message: 'Password reset link sent to your email');
        }

        return $this->errorResponse('Unable to send reset link', 500);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->successResponse(message: 'Password reset successfully');
        }

        return $this->errorResponse(
            match ($status) {
                Password::INVALID_TOKEN => 'Invalid or expired reset token',
                Password::INVALID_USER => 'User not found',
                default => 'Unable to reset password',
            },
            400
        );
    }

    /**
     * Redirect user to Google OAuth.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        /** @var GoogleProvider $driver */
        $driver = Socialite::driver('google');

        return $driver->stateless()->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function handleGoogleCallback(Request $request): JsonResponse|RedirectResponse
    {
        try {
            /** @var GoogleProvider $driver */
            $driver = Socialite::driver('google');
            $googleUser = $driver->stateless()->user();
        } catch (\Throwable $th) {
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

            return redirect("{$frontendUrl}/oauth/callback?error=" . urlencode('Google authentication failed.'));
        }

        // Find or create user
        $user = User::where('email', $googleUser->getEmail())->first();

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'User',
                'email' => $googleUser->getEmail(),
                'password' => Hash::make(Str::random(32)),
                'avatar' => $this->storeGoogleAvatarLocally($googleUser->getAvatar()),
                'role' => 'USER',
                'provider' => 'GOOGLE',
                'email_verified_at' => now(),
                'has_password' => false,
            ]);
        } else {
            // Update provider and avatar for existing user logging in via Google
            $user->update([
                'provider' => 'GOOGLE',
                'avatar' => $this->storeGoogleAvatarLocally($googleUser->getAvatar()),
            ]);
        }

        $tokens = $this->generateTokenPair($user);
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        return redirect("{$frontendUrl}/oauth/callback?accessToken={$tokens['accessToken']}&refreshToken={$tokens['refreshToken']}");
    }

    /**
     * Generate a pair of access and refresh tokens for the given user.
     *
     * @return array{accessToken: string, refreshToken: string}
     */
    private function generateTokenPair(User $user): array
    {
        return [
            'accessToken' => $user->createToken('access-token')->accessToken,
            'refreshToken' => $user->createToken('refresh-token')->accessToken,
        ];
    }

    /**
     * Download and store Google avatar locally, return local URL.
     */
    private function storeGoogleAvatarLocally(?string $googleAvatarUrl): ?string
    {
        if (! $googleAvatarUrl) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get($googleAvatarUrl);

            if (! $response->successful()) {
                return $googleAvatarUrl; // fallback to original URL
            }

            $imageContent = $response->body();
            $extension = 'jpg';
            $filename = 'google-avatar-' . Str::random(20) . '.' . $extension;
            $path = 'avatars/' . $filename;

            Storage::disk('public')->put($path, $imageContent);

            return asset('storage/' . $path);
        } catch (\Throwable) {
            return $googleAvatarUrl; // fallback to original URL on failure
        }
    }
}
