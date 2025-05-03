<?php

return [
    // Manual version number - update this when deploying new asset versions
    'number' => env('APP_VERSION', '1.0.0'),
    
    // Automatic timestamp - changes on each deployment
    'timestamp' => time(),
    
    // For development - use file timestamps
    'dev_mode' => env('APP_ENV') !== 'production',
    
    // Get version string based on environment
    'get' => function() {
        if (config('version.dev_mode')) {
            return time(); // Always fresh in development
        } else {
            return config('version.number'); // Use version number in production
        }
    }
];