<?php

namespace App\Controllers;

use Redium\Core\Controller;
use Redium\Attributes\Route;
use Redium\Validation\Validator;
use Redium\Http\Response;
use Redium\Database\QueryBuilder;
use App\Services\UserService;

#[Route("/users")]
class ImprovedUserController extends Controller
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    /**
     * Create user with validation
     */
    #[Route("/create", "POST", permission: "all")]
    public function create(array $data): array
    {
        // Validate input with comprehensive rules
        $validator = Validator::make($data, [
            'first_name' => 'required|min:2|max:50|alpha',
            'last_name' => 'required|min:2|max:50|alpha',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'in:USER,ADMIN'
        ]);

        if ($validator->fails()) {
            return Response::validationError($validator->errors());
        }

        // Create user
        $user = $this->userService->createUser($data);

        // Return standardized created response
        return Response::created($user, 'User created successfully');
    }

    /**
     * Search users with Query Builder
     */
    #[Route("/search", "GET", permission: "user.read")]
    public function search(string $query = '', int $page = 0, int $size = 10): array
    {
        // Build complex search query
        $users = QueryBuilder::table('users')
            ->select('id', 'identifier', 'first_name', 'last_name', 'email', 'role', 'status')
            ->whereLike('first_name', "%{$query}%")
            ->where('status', 'ACTIVE')
            ->orderBy('first_name', 'ASC')
            ->limit($size)
            ->offset($page * $size)
            ->get();

        // Get total count for pagination
        $total = QueryBuilder::table('users')
            ->whereLike('first_name', "%{$query}%")
            ->where('status', 'ACTIVE')
            ->count();

        // Return paginated response
        return Response::paginated($users, $total, $page, $size);
    }

    /**
     * Get user with standardized responses
     */
    #[Route("/{identifier}", "GET", permission: "user.read")]
    public function getUser(string $identifier): array
    {
        $user = QueryBuilder::table('users')
            ->where('identifier', $identifier)
            ->where('status', '!=', 'DELETED')
            ->first();

        if (!$user) {
            return Response::notFound('User not found');
        }

        // Remove sensitive data
        unset($user['password']);

        return Response::success($user);
    }

    /**
     * Update user with validation
     */
    #[Route("/{identifier}", "PUT", permission: "user.update")]
    public function update(string $identifier, array $data): array
    {
        // Validate update data
        $validator = Validator::make($data, [
            'first_name' => 'min:2|max:50|alpha',
            'last_name' => 'min:2|max:50|alpha',
            'email' => 'email'
        ]);

        if ($validator->fails()) {
            return Response::validationError($validator->errors());
        }

        // Check if user exists
        $user = QueryBuilder::table('users')
            ->where('identifier', $identifier)
            ->first();

        if (!$user) {
            return Response::notFound('User not found');
        }

        // Update user
        QueryBuilder::table('users')
            ->where('identifier', $identifier)
            ->update($data);

        return Response::success(null, 'User updated successfully');
    }

    /**
     * Get active users count
     */
    #[Route("/stats/active", "GET", permission: "user.read")]
    public function getActiveCount(): array
    {
        $count = QueryBuilder::table('users')
            ->where('status', 'ACTIVE')
            ->count();

        return Response::success(['active_users' => $count]);
    }

    /**
     * Bulk update user status
     */
    #[Route("/bulk/status", "PUT", permission: "all")]
    public function bulkUpdateStatus(array $identifiers, string $status): array
    {
        // Validate status
        $validator = Validator::make(['status' => $status], [
            'status' => 'required|in:ACTIVE,BLOCKED,DELETED'
        ]);

        if ($validator->fails()) {
            return Response::validationError($validator->errors());
        }

        // Update multiple users
        QueryBuilder::table('users')
            ->whereIn('identifier', $identifiers)
            ->update(['status' => $status]);

        return Response::success(null, 'Users updated successfully');
    }
}
