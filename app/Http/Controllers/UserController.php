<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\User;

class UserController extends Controller
{
    public function profile()
    {
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:15',
            'birth_date' => 'nullable|date'
        ]);

        $user->update([
            'name' => $request->name,
            'bio' => $request->bio,
            'phone' => $request->phone,
            'birth_date' => $request->birth_date
        ]);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Profile updated successfully'
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không đúng'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully'
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:1024'
        ]);

        try {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            
            // Update user avatar path
            $user->update(['avatar' => $avatarPath]);
            
            // Generate full URL
            $avatarUrl = Storage::disk('public')->url($avatarPath);

            return response()->json([
                'success' => true,
                'data' => [
                    'avatar_url' => $avatarUrl,
                    'avatar_path' => $avatarPath
                ],
                'message' => 'Avatar uploaded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload avatar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function readingHistory()
    {
        $user = Auth::user();
        
        // Mock data for now - replace with actual reading history logic
        $history = [
            [
                'id' => 1,
                'book' => [
                    'id' => 1,
                    'title' => 'Nhà Giả Kim',
                    'cover_image' => 'https://via.placeholder.com/80x120',
                    'authors' => [
                        ['name' => 'Paulo Coelho']
                    ]
                ],
                'progress' => 75,
                'updated_at' => now()->subDays(1)
            ],
            [
                'id' => 2,
                'book' => [
                    'id' => 2,
                    'title' => 'Đắc Nhân Tâm',
                    'cover_image' => 'https://via.placeholder.com/80x120',
                    'authors' => [
                        ['name' => 'Dale Carnegie']
                    ]
                ],
                'progress' => 50,
                'updated_at' => now()->subDays(3)
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    public function favorites()
    {
        $user = Auth::user();
        
        // Mock data for now - replace with actual favorites logic
        $favorites = [
            [
                'id' => 1,
                'book' => [
                    'id' => 1,
                    'title' => 'Nhà Giả Kim',
                    'cover_image' => 'https://via.placeholder.com/200x300',
                    'authors' => [
                        ['name' => 'Paulo Coelho']
                    ]
                ]
            ],
            [
                'id' => 2,
                'book' => [
                    'id' => 2,
                    'title' => 'Đắc Nhân Tâm',
                    'cover_image' => 'https://via.placeholder.com/200x300',
                    'authors' => [
                        ['name' => 'Dale Carnegie']
                    ]
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }

    public function removeFavorite($bookId)
    {
        // Mock implementation - replace with actual logic
        return response()->json([
            'success' => true,
            'message' => 'Favorite removed successfully'
        ]);
    }
}
