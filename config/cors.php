<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'], // Define the routes where CORS is allowed, e.g., your API routes
    'allowed_methods' => ['*'], // Allow all HTTP methods (GET, POST, etc.)
    'allowed_origins' => ['*'], // Allow all origins, or specify your frontend origin (e.g., http://localhost:3000)
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'], // Allow all headers
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
