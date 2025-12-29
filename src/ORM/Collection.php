<?php

namespace Redium\ORM;

class Collection implements \Iterator, \Countable, \ArrayAccess
{
    private array $items = [];
    private int $position = 0;

    public function __construct(array $items = [])
    {
        $this->items = array_values($items);
    }

    /**
     * Get all items
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get first item
     */
    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    /**
     * Get last item
     */
    public function last(): mixed
    {
        return end($this->items) ?: null;
    }

    /**
     * Map over items
     */
    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->items));
    }

    /**
     * Filter items
     */
    public function filter(callable $callback): self
    {
        return new self(array_filter($this->items, $callback));
    }

    /**
     * Find item by callback
     */
    public function find(callable $callback): mixed
    {
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Pluck values by key
     */
    public function pluck(string $key): array
    {
        return array_map(fn($item) => is_array($item) ? $item[$key] : $item->$key, $this->items);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return array_map(function($item) {
            return $item instanceof Entity ? $item->toArray() : $item;
        }, $this->items);
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    // Iterator implementation
    public function current(): mixed
    {
        return $this->items[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }

    // Countable implementation
    public function count(): int
    {
        return count($this->items);
    }

    // ArrayAccess implementation
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
}
