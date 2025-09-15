<?php
// VULN: Login API with maximum vulnerability
require_once '/var/www/includes/init.php';
require_once '/var/www/includes/db.php';

// CORS for maximum accessibility
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$response = ['success' => false, 'message' => '', 'user' => null, 'query' => ''];

// Accept both GET and POST
$username = $_POST['username'] ?? $_GET['username'] ?? $_GET['u'] ?? '';
$password = $_POST['password'] ?? $_GET['password'] ?? $_GET['p'] ?? '';

if (!empty($username)) {
    // VULN: Direct SQL injection in API
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $response['query'] = $query; // Show query for debugging
    
    $result = $db->query($query);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $response['success'] = true;
        $response['message'] = 'Login successful';
        $response['user'] = $user;
        
        // Start session for web interface compatibility
        session_start();
        $_SESSION['user'] = $user;
    } else {
        $response['success'] = false;
        $response['message'] = 'Login failed';
        
        // VULN: Show SQL errors
        if ($db->error) {
            $response['error'] = $db->error;
            $response['sql_error'] = true;
        }
    }
} else {
    $response['message'] = 'Username required';
    $response['usage'] = [
        'POST' => 'Send username and password in POST body',
        'GET' => 'Use ?username=admin&password=admin123',
        'Short' => 'Use ?u=admin&p=admin123',
        'SQL Injection Examples' => [
            "?u=admin'--&p=anything",
            "?u=admin' OR 1=1--&p=test",
            "?username=admin' UNION SELECT 1,2,3,4,5--&password=x"
        ]
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>