<?php
require_once '/var/www/includes/init.php';

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>
