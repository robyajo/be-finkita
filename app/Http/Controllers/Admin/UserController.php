<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of user role users.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');

        $users = User::query()
            ->where('role', 'USER')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10);

        return $this->paginatedResponse(
            $users,
            'Users retrieved successfully'
        );
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $avatarUrl = null;
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            if ($path) {
                $avatarUrl = asset('storage/' . $path);
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'USER',
            'provider' => 'CREDENTIALS',
            'avatar' => $avatarUrl,
            'email_verified_at' => now(), // Auto-verify administrative creations
            'has_password' => true,
        ]);

        return $this->createdResponse(
            new UserResource($user),
            'User created successfully'
        );
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        if ($user->role !== 'USER') {
            return $this->notFoundResponse('User not found');
        }

        return $this->successResponse(
            new UserResource($user),
            'User retrieved successfully'
        );
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        if ($user->role !== 'USER') {
            return $this->notFoundResponse('User not found');
        }

        $data = $request->only(['name', 'email', 'is_active']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return $this->successResponse(
            new UserResource($user->fresh()),
            'User updated successfully'
        );
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->role !== 'USER') {
            return $this->notFoundResponse('User not found');
        }

        $user->delete();

        return $this->successResponse(
            message: 'User deleted successfully'
        );
    }

    /**
     * Toggle the active status of the specified user.
     */
    public function toggleActive(User $user): JsonResponse
    {
        if ($user->role !== 'USER') {
            return $this->notFoundResponse('User not found');
        }

        $user->update([
            'is_active' => !$user->is_active
        ]);

        return $this->successResponse(
            new UserResource($user->fresh()),
            'User active status updated successfully'
        );
    }

    /**
     * Update specified user's avatar.
     */
    public function updateAvatar(Request $request, User $user): JsonResponse
    {
        if ($user->role !== 'USER') {
            return $this->notFoundResponse('User not found');
        }

        $request->validate([
            'avatar' => ['required', 'image', 'max:2048']
        ]);

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
            $user->update([
                'avatar' => asset('storage/' . $path)
            ]);
        }

        return $this->successResponse(
            new UserResource($user->fresh()),
            'User avatar updated successfully'
        );
    }
}
