<?php

namespace App\Controllers;

use Redium\Core\Controller;
use Redium\Attributes\Route;
use App\Services\UserService;

#[Route("/users")]
class UserController extends Controller
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    /**
     * User login (public endpoint)
     */
    #[Route("/login", "POST")]
    public function login(string $email, string $password): array
    {
        return $this->userService->login($email, $password);
    }

    /**
     * Get all users (requires user.read permission)
     */
    #[Route("/list", "GET", permission: "user.read")]
    public function list(int $page = 0, int $size = 10): array
    {
        return $this->userService->getAllUsers($page, $size);
    }

    /**
     * Get single user (requires user.read permission)
     */
    #[Route("/{identifier}", "GET", permission: "user.read")]
    public function getUser(string $identifier): array
    {
        return $this->userService->getUserByIdentifier($identifier);
    }

    /**
     * Create new user (requires all permission - admin only)
     */
    #[Route("/create", "POST", permission: "all")]
    public function create(array $data = []): array
    {
        $result = $this->userService->createUser($data);
        return $this->success("User created successfully", $result);
    }

    /**
     * Update user (requires user.update permission)
     */
    #[Route("/{identifier}", "PUT", permission: "user.update")]
    public function update(string $identifier, array $data = []): array
    {
        $this->userService->updateUser($identifier, $data);
        return $this->success("User updated successfully");
    }

    /**
     * Delete user (requires all permission - admin only)
     */
    #[Route("/{identifier}", "DELETE", permission: "all")]
    public function delete(string $identifier): array
    {
        $this->userService->deleteUser($identifier);
        return $this->success("User deleted successfully");
    }
}
