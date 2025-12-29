<?php

namespace App\Entities;

use Redium\ORM\Entity;
use Redium\Attributes\Table;
use Redium\Attributes\Column;

#[Table('users')]
class User extends Entity
{
    #[Column(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Column]
    public string $identifier;

    #[Column(name: 'first_name')]
    public string $firstName;

    #[Column(name: 'last_name')]
    public string $lastName;

    #[Column]
    public string $email;

    #[Column(hidden: true)]
    public string $password;

    #[Column]
    public string $role;

    #[Column]
    public string $status;

    #[Column(name: 'created_at', type: 'datetime')]
    public ?\DateTime $createdAt = null;

    #[Column(name: 'updated_at', type: 'datetime')]
    public ?\DateTime $updatedAt = null;

    /**
     * Hash password before saving
     */
    public function setPassword(string $password): void
    {
        $this->password = password_encrypt($password);
    }

    /**
     * Get full name
     */
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Scope: Get only active users
     */
    public static function active(): \Redium\Database\QueryBuilder
    {
        return static::where('status', 'ACTIVE');
    }

    /**
     * Scope: Get only admins
     */
    public static function admins(): \Redium\Database\QueryBuilder
    {
        return static::where('role', 'ADMIN');
    }
}
