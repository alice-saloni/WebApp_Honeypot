<?php
// Only start session if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    // Start session (default insecure settings)
    session_start();
}

// Buffer all output
ob_start();

// Include database and logger
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';

// Log the request
hp_log_request($db);
