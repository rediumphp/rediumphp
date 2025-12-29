<?php

namespace App\Controllers;

use Redium\Core\Controller;
use Redium\Attributes\Route;
use Redium\Validation\Validator;
use Redium\Http\Response;
use Redium\Http\RateLimiter;
use Redium\Cache\Cache;
use Redium\Events\EventDispatcher;
use Redium\Storage\FileUpload;
use App\Entities\User;

#[Route("/api")]
class AdvancedExampleController extends Controller
{
    /**
     * Example: ORM with caching
     */
    #[Route("/users/cached", "GET")]
    public function getCachedUsers(): array
    {
        // Cache for 1 hour
        $users = Cache::remember('users.all', 3600, function() {
            return User::active()->get();
        });

        return Response::success(array_map(fn($u) => is_array($u) ? $u : $u->toArray(), $users));
    }

    /**
     * Example: ORM Create with Events
     */
    #[Route("/users/create-with-event", "POST")]
    public function createUserWithEvent(array $data): array
    {
        // Validate
        $validator = Validator::make($data, [
            'first_name' => 'required|min:2',
            'last_name' => 'required|min:2',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8'
        ]);

        if ($validator->fails()) {
            return Response::validationError($validator->errors());
        }

        // Create user using ORM
        $user = new User();
        $user->identifier = generateUniqueIdentifier();
        $user->firstName = $data['first_name'];
        $user->lastName = $data['last_name'];
        $user->email = $data['email'];
        $user->setPassword($data['password']);
        $user->role = $data['role'] ?? 'USER';
        $user->status = 'ACTIVE';
        $user->save();

        // Dispatch event
        EventDispatcher::dispatch('user.created', $user);

        // Clear cache
        Cache::forget('users.all');

        return Response::created($user->toArray(), 'User created successfully');
    }

    /**
     * Example: ORM Find with relationships
     */
    #[Route("/users/{id}/profile", "GET")]
    public function getUserProfile(int $id): array
    {
        $user = User::find($id);

        if (!$user) {
            return Response::notFound('User not found');
        }

        return Response::success($user->toArray());
    }

    /**
     * Example: File upload
     */
    #[Route("/upload/avatar", "POST")]
    public function uploadAvatar(): array
    {
        if (!isset($_FILES['avatar'])) {
            return Response::error('No file uploaded', 400);
        }

        $uploader = new FileUpload();
        $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif'])
                 ->setMaxFileSize(2 * 1024 * 1024); // 2MB

        try {
            $result = $uploader->upload($_FILES['avatar'], 'avatars');
            return Response::success($result, 'File uploaded successfully');
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Example: Base64 image upload
     */
    #[Route("/upload/base64", "POST")]
    public function uploadBase64(string $image): array
    {
        $uploader = new FileUpload();

        try {
            $result = $uploader->storeBase64($image, 'profiles', 'png');
            return Response::success($result, 'Image uploaded successfully');
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Example: Rate limited endpoint
     */
    #[Route("/rate-limited", "GET")]
    public function rateLimitedEndpoint(): array
    {
        // Allow 10 requests per minute
        RateLimiter::forIp('rate-limited', 10, 1);

        return Response::success(['message' => 'This endpoint is rate limited']);
    }

    /**
     * Example: ORM Update
     */
    #[Route("/users/{id}/update", "PUT")]
    public function updateUser(int $id, array $data): array
    {
        $user = User::find($id);

        if (!$user) {
            return Response::notFound('User not found');
        }

        // Update fields
        if (isset($data['first_name'])) $user->firstName = $data['first_name'];
        if (isset($data['last_name'])) $user->lastName = $data['last_name'];
        if (isset($data['email'])) $user->email = $data['email'];

        $user->save();

        // Dispatch event
        EventDispatcher::dispatch('user.updated', $user);

        // Clear cache
        Cache::forget('users.all');

        return Response::success($user->toArray(), 'User updated successfully');
    }

    /**
     * Example: ORM Delete
     */
    #[Route("/users/{id}/delete", "DELETE")]
    public function deleteUser(int $id): array
    {
        $user = User::find($id);

        if (!$user) {
            return Response::notFound('User not found');
        }

        $user->delete();

        // Dispatch event
        EventDispatcher::dispatch('user.deleted', ['id' => $id]);

        // Clear cache
        Cache::forget('users.all');

        return Response::success(null, 'User deleted successfully');
    }

    /**
     * Example: ORM Query scopes
     */
    #[Route("/users/active-admins", "GET")]
    public function getActiveAdmins(): array
    {
        $admins = User::active()
            ->where('role', 'ADMIN')
            ->orderBy('created_at', 'DESC')
            ->get();

        return Response::success(array_map(fn($u) => is_array($u) ? $u : $u->toArray(), $admins));
    }

    /**
     * Example: Cache management
     */
    #[Route("/cache/clear", "POST")]
    public function clearCache(): array
    {
        Cache::clear();
        return Response::success(null, 'Cache cleared successfully');
    }
}
