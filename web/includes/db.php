<?php
// Database connection
$host = 'db';
$user = 'bugtracker_user';
$password = 'bugtracker_password';
$database = 'bugtracker';

$db = new mysqli($host, $user, $password, $database);

if ($db->connect_error) {
    die('Connection failed: ' . $db->connect_error);
}
?>
