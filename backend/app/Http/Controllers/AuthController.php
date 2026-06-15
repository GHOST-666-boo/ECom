<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoogleAuthRequest;
use App\Http\Requests\PasswordResetEmailRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\GoogleAuthService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'], // Laravel will auto-hash via casts with bcrypt cost 12
            'role' => 'customer',
        ]);

        try {
            // Trigger the Registered event to send email verification notification
            event(new Registered($user));
        } catch (\Exception $e) {
            // Log the error so registration doesn't fail if SMTP/mail settings are incorrect on live
            \Illuminate\Support\Facades\Log::error('Registration email failed to send: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please check your email to verify your account.',
            'data' => null,
        ], 201);
    }

    /**
     * Login user and create token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
                'errors' => [
                    'email' => ['The provided credentials are incorrect.'],
                ],
            ], 401);
        }

        // Create token with 7-day expiry
        $token = $user->createToken('auth_token', ['*'], now()->addDays(7))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Logout user (revoke current token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Verify email address.
     */
    public function verify(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification link.',
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email already verified.',
            ]);
        }

        $user->markEmailAsVerified();

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.',
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($request->user()),
            ],
        ]);
    }

    /**
     * Get user profile.
     */
    public function getProfile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($request->user()),
            ],
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = $request->user();
        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => new UserResource($user->fresh()),
            ],
        ]);
    }

    /**
     * Authenticate user with Google OAuth.
     */
    public function googleAuth(GoogleAuthRequest $request, GoogleAuthService $googleAuthService)
    {
        $validated = $request->validated();
        $idToken = $validated['id_token'];

        try {
            // Verify the ID token using Google API Client
            $payload = $googleAuthService->verifyIdToken($idToken);
            
            if (!$payload) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google ID token.',
                    'errors' => [
                        'id_token' => ['Invalid Google ID token.'],
                    ],
                ], 401);
            }

            // Extract user information from the payload
            $googleId = $payload['sub'];
            $email = $payload['email'];
            $name = $payload['name'] ?? $email;

            // Find or create user by google_id
            $user = User::where('google_id', $googleId)->first();

            if (!$user) {
                // Check if user exists with this email
                $user = User::where('email', $email)->first();
                
                if ($user) {
                    // Link existing user to Google account
                    $user->update([
                        'google_id' => $googleId,
                        'email_verified_at' => now(),
                    ]);
                } else {
                    // Create new user
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'google_id' => $googleId,
                        'email_verified_at' => now(),
                        'role' => 'customer',
                    ]);
                }
            } else {
                // Update email_verified_at for existing Google users
                if (!$user->email_verified_at) {
                    $user->update(['email_verified_at' => now()]);
                }
            }

            // Create Sanctum token with 7-day expiry
            $token = $user->createToken('auth_token', ['*'], now()->addDays(7))->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Google authentication successful',
                'data' => [
                    'user' => new UserResource($user),
                    'token' => $token,
                ],
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google authentication failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Google authentication failed.',
                'errors' => [
                    'id_token' => ['Failed to verify Google ID token. Please try again.'],
                ],
            ], 401);
        }
    }

    /**
     * Send password reset link to user's email.
     */
    public function sendPasswordResetLink(PasswordResetEmailRequest $request)
    {
        $validated = $request->validated();

        // Send password reset link using Laravel's Password facade
        $status = Password::sendResetLink(
            ['email' => $validated['email']]
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent to your email.',
                'data' => null,
            ]);
        }

        // Handle throttling
        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting another password reset.',
                'errors' => [
                    'email' => ['Too many password reset attempts. Please try again later.'],
                ],
            ], 429);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to send password reset link.',
            'errors' => [
                'email' => ['We could not send the password reset link.'],
            ],
        ], 500);
    }

    /**
     * Reset user password with valid token.
     */
    public function resetPassword(PasswordResetRequest $request)
    {
        $validated = $request->validated();

        // Reset password using Laravel's Password facade
        $status = Password::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'token' => $validated['token'],
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => $password, // Will be hashed by User model cast
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully.',
                'data' => null,
            ]);
        }

        // Handle invalid or expired token
        if ($status === Password::INVALID_TOKEN) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired password reset token.',
                'errors' => [
                    'token' => ['The password reset token is invalid or has expired.'],
                ],
            ], 422);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to reset password.',
            'errors' => [
                'email' => ['We could not reset your password.'],
            ],
        ], 500);
    }
}
