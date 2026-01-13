<?php

require __DIR__ . '/../../vendor/autoload.php';

use Redium\Core\Application;

// Create and configure application
$app = new Application(__DIR__);

// Register all controllers from the directory
$app->registerControllersFromDirectory(__DIR__ . '/src/Controllers');

// Run the application
$app->run();
