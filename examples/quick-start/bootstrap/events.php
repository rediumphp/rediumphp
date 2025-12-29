<?php

// Example: Setup event listeners in your application bootstrap

use Redium\Events\EventDispatcher;
use Redium\Cache\Cache;

// Listen for user creation
EventDispatcher::listen('user.created', function($user) {
    // Send welcome email
    error_log("Welcome email sent to: " . $user->email);
    
    // Log user creation
    error_log("New user created: " . $user->identifier);
});

// Listen for user updates
EventDispatcher::listen('user.updated', function($user) {
    // Log update
    error_log("User updated: " . $user->identifier);
    
    // Invalidate user cache
    Cache::forget("user.{$user->id}");
});

// Listen for user deletion
EventDispatcher::listen('user.deleted', function($data) {
    // Log deletion
    error_log("User deleted: " . $data['id']);
    
    // Cleanup related data
    // ...
});

// High priority listener (executes first)
EventDispatcher::listen('user.created', function($user) {
    // This runs before other listeners
    error_log("High priority: User validation");
}, priority: 100);
