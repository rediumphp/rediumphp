<?php

namespace Redium\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
class Route
{
    /**
     * @param string $path Route path (e.g., "/users", "/users/{id}")
     * @param string $method HTTP method: GET, POST, PUT, PATCH, DELETE
     * @param string $permission Permission required to access this route (default: "none" for public routes)
     */
    public function __construct(
        private readonly string $path,
        private readonly string $method = "GET",
        private readonly string $permission = "none"
    ) {}

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return strtolower($this->method);
    }

    public function getPermission(): string
    {
        return $this->permission;
    }

    public function isPublic(): bool
    {
        return $this->permission === "none";
    }
}
