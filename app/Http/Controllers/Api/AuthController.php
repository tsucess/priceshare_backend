<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|min:2|max:100',
            'contact'  => 'required|string',
            'state'    => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $contact = $request->contact;
        $isEmail = filter_var($contact, FILTER_VALIDATE_EMAIL);

        if ($isEmail) {
            if (User::where('email', $contact)->exists()) {
                return response()->json(['message' => 'Email already registered.'], 422);
            }
            $userData = ['email' => $contact];
        } else {
            $phone = preg_replace('/\s+|-/', '', $contact);
            if (User::where('phone', $phone)->exists()) {
                return response()->json(['message' => 'Phone number already registered.'], 422);
            }
            $userData = ['phone' => $phone];
        }

        $user = User::create(array_merge($userData, [
            'name'     => $request->name,
            'state'    => $request->state,
            'password' => Hash::make($request->password),
        ]));

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => $this->userResource($user),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'contact'  => 'required|string',
            'password' => 'required|string',
        ]);

        $contact = $request->contact;
        $isEmail = filter_var($contact, FILTER_VALIDATE_EMAIL);

        $user = $isEmail
            ? User::where('email', $contact)->first()
            : User::where('phone', preg_replace('/\s+|-/', '', $contact))->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'contact' => ['These credentials do not match our records.'],
            ]);
        }

        if ($user->is_banned) {
            $reason = $user->ban_reason ? " Reason: {$user->ban_reason}" : '';
            return response()->json(['message' => "Your account has been suspended.{$reason}"], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => $this->userResource($user),
            'token' => $token,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userResource($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);
        $user->tokens()->delete();

        return response()->json(['message' => 'Password changed. Please log in again.']);
    }

    private function userResource(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'username'   => $user->username,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'state'      => $user->state,
            'bio'        => $user->bio,
            'avatar_url' => $user->avatar_url,
            'visibility' => $user->visibility,
            'role'             => $user->role,
            'is_banned'        => $user->is_banned,
            'is_shadow_banned' => $user->is_shadow_banned,
        ];
    }
}
