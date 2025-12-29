<?php

namespace App\Services;

use App\Entities\User;
use App\Repositories\UserRepository;
use Redium\Auth\AuthService;
use Exception;

class UserService
{
    private AuthService $authService;
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userRepository = new UserRepository();
    }

    /**
     * User login
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            throw new Exception("Invalid email or password");
        }

        if (!password_verify($password, $user->password)) {
            throw new Exception("Invalid email or password");
        }

        if ($user->status !== 'ACTIVE') {
            throw new Exception("Account is not active");
        }

        // Generate JWT token
        $tokenData = [
            'identifier' => $user->identifier,
            'email' => $user->email,
            'role' => $user->role,
            'permissions' => $this->getUserPermissions($user->role)
        ];

        $token = $this->authService->generateAuthToken($tokenData);

        return [
            'user' => $user->toArray(),
            'token' => $token
        ];
    }

    /**
     * Get all users
     */
    public function getAllUsers(int $page = 0, int $size = 10): array
    {
        $users = $this->userRepository->getActiveUsers($page, $size);
        
        return [
            'data' => array_map(fn($user) => $user->toArray(), $users),
            'page' => $page,
            'size' => $size
        ];
    }

    /**
     * Get user by identifier
     */
    public function getUserByIdentifier(string $identifier): array
    {
        $user = $this->userRepository->findByIdentifier($identifier);

        if (!$user) {
            throw new Exception("User not found");
        }

        return $user->toArray();
    }

    /**
     * Create new user
     */
    public function createUser(array $data): array
    {
        // Check if email already exists
        if ($this->userRepository->findByEmail($data['email'] ?? '')) {
            throw new Exception("Email already in use");
        }

        $user = new User($data);
        $user->identifier = generateUniqueIdentifier();
        $user->setPassword($data['password']);
        $user->role = $data['role'] ?? 'USER';
        $user->status = 'ACTIVE';
        
        $this->userRepository->save($user);

        return ['identifier' => $user->identifier];
    }

    /**
     * Update user
     */
    public function updateUser(string $identifier, array $data): void
    {
        $user = $this->userRepository->findByIdentifier($identifier);

        if (!$user) {
            throw new Exception("User not found");
        }

        $user->fill($data);
        $this->userRepository->save($user);
    }

    /**
     * Delete user (soft delete by changing status)
     */
    public function deleteUser(string $identifier): void
    {
        $user = $this->userRepository->findByIdentifier($identifier);

        if (!$user) {
            throw new Exception("User not found");
        }

        $user->status = 'DELETED';
        $this->userRepository->save($user);
    }

    /**
     * Get user permissions based on role
     */
    private function getUserPermissions(string $role): array
    {
        return match($role) {
            'ADMIN' => ['all'],
            'USER' => ['user.read', 'user.update'],
            default => []
        };
    }
}
