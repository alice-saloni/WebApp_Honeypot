<?php
// Start session (default insecure settings)
session_start();

// Include database and logger
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';

// Log the request
hp_log_request($db);
