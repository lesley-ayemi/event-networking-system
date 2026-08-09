<?php

// Small dedicated config file (rather than editing the stock config/app.php)
// so the frontend origin used to build links in emails — e.g. the password
// reset link — has a single source of truth, matching the CLIENT_URL env
// var config/cors.php already relies on for the same origin.
return [
    'url' => env('CLIENT_URL', 'http://localhost:5173'),
];
