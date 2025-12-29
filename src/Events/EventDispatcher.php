<?php

namespace Redium\Events;

class EventDispatcher
{
    private static array $listeners = [];

    /**
     * Register an event listener
     */
    public static function listen(string $event, callable $listener, int $priority = 0): void
    {
        if (!isset(self::$listeners[$event])) {
            self::$listeners[$event] = [];
        }

        self::$listeners[$event][] = [
            'callback' => $listener,
            'priority' => $priority
        ];

        // Sort by priority (higher priority first)
        usort(self::$listeners[$event], fn($a, $b) => $b['priority'] <=> $a['priority']);
    }

    /**
     * Dispatch an event
     */
    public static function dispatch(string $event, mixed $data = null): void
    {
        if (!isset(self::$listeners[$event])) {
            return;
        }

        foreach (self::$listeners[$event] as $listener) {
            call_user_func($listener['callback'], $data);
        }
    }

    /**
     * Remove all listeners for an event
     */
    public static function forget(string $event): void
    {
        unset(self::$listeners[$event]);
    }

    /**
     * Remove all listeners
     */
    public static function clear(): void
    {
        self::$listeners = [];
    }

    /**
     * Check if event has listeners
     */
    public static function hasListeners(string $event): bool
    {
        return isset(self::$listeners[$event]) && !empty(self::$listeners[$event]);
    }
}
