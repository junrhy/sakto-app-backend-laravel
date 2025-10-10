<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class ChatAuthController extends Controller
{
    /**
     * Register a new chat user
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'username' => 'required|string|unique:chat_users,username',
            'email' => 'required|email|unique:chat_users,email',
            'password' => 'required|string|min:6',
            'display_name' => 'required|string|max:255',
            'avatar_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $chatUser = ChatUser::create([
                'client_identifier' => $request->input('client_identifier'),
                'username' => $request->input('username'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'display_name' => $request->input('display_name'),
                'avatar_url' => $request->input('avatar_url'),
                'preferences' => [
                    'theme' => 'light',
                    'notifications' => true,
                    'sound_enabled' => true,
                ],
            ]);

            // Create token for immediate login
            $token = $chatUser->createToken('chat-token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Chat user registered successfully',
                'data' => [
                    'user' => $chatUser,
                    'token' => $token,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login chat user
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $chatUser = ChatUser::forClient($request->input('client_identifier'))
                ->where('username', $request->input('username'))
                ->where('is_active', true)
                ->first();

            if (!$chatUser || !Hash::check($request->input('password'), $chatUser->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Update online status
            $chatUser->setOnline(true);

            // Create token
            $token = $chatUser->createToken('chat-token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'user' => $chatUser,
                    'token' => $token,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Login failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout chat user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if ($user) {
                // Update online status
                $user->setOnline(false);
                
                // Revoke current token
                $request->user()->currentAccessToken()->delete();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Logout successful'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Logout failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current chat user profile
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No token provided'
                ], 401);
            }

            // Find the token in the database
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$personalAccessToken) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid token'
                ], 401);
            }

            $user = $personalAccessToken->tokenable;
            
            // Debug logging
            \Log::info('Chat profile request debug', [
                'user' => $user ? $user->toArray() : null,
                'token' => $token,
                'token_id' => $personalAccessToken->id
            ]);
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 401);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            \Log::error('Chat profile error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update chat user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'display_name' => 'sometimes|string|max:255',
            'avatar_url' => 'nullable|url',
            'preferences' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $user->update($request->only(['display_name', 'avatar_url', 'preferences']));

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profile update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();

            if (!Hash::check($request->input('current_password'), $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Current password is incorrect'
                ], 400);
            }

            $user->update([
                'password' => Hash::make($request->input('new_password'))
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Password changed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password change failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get online users for a client
     */
    public function getOnlineUsers(Request $request): JsonResponse
    {
        try {
            $clientIdentifier = $request->input('client_identifier');
            
            if (!$clientIdentifier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'client_identifier is required'
                ], 400);
            }

            $onlineUsers = ChatUser::forClient($clientIdentifier)
                ->online()
                ->active()
                ->select('id', 'username', 'display_name', 'avatar_url', 'last_seen_at')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $onlineUsers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get online users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update online status
     */
    public function updateOnlineStatus(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $isOnline = $request->input('is_online', true);
            
            $user->setOnline($isOnline);

            return response()->json([
                'status' => 'success',
                'message' => 'Online status updated',
                'data' => [
                    'is_online' => $user->is_online,
                    'last_seen_at' => $user->last_seen_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update online status: ' . $e->getMessage()
            ], 500);
        }
    }
}