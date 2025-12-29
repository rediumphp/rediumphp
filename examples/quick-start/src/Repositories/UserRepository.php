<?php

namespace App\Repositories;

use Redium\ORM\Repository;
use App\Entities\User;

class UserRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        $result = $this->query()->where('email', $email)->first();
        return $result ? User::fromDatabase($result) : null;
    }

    /**
     * Get all active users
     */
    public function getActiveUsers(int $page = 0, int $size = 10): array
    {
        $results = $this->query()
            ->where('status', 'ACTIVE')
            ->limit($size)
            ->offset($page * $size)
            ->get();

        return array_map(fn($row) => User::fromDatabase($row), $results);
    }

    /**
     * Find user by identifier
     */
    public function findByIdentifier(string $identifier): ?User
    {
        $result = $this->query()->where('identifier', $identifier)->first();
        return $result ? User::fromDatabase($result) : null;
    }
}
