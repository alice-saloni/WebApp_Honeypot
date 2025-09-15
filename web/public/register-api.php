<?php
// VULN: Registration API with maximum vulnerability
require_once '/var/www/includes/init.php';
require_once '/var/www/includes/db.php';

// CORS for maximum accessibility
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$response = ['success' => false, 'message' => '', 'user' => null, 'query' => ''];

// Accept both GET and POST parameters
$username = $_POST['username'] ?? $_GET['username'] ?? $_GET['u'] ?? '';
$password = $_POST['password'] ?? $_GET['password'] ?? $_GET['p'] ?? '';
$email = $_POST['email'] ?? $_GET['email'] ?? $_GET['e'] ?? '';
$role = $_POST['role'] ?? $_GET['role'] ?? $_GET['r'] ?? 'user';

if (!empty($username) && !empty($email)) {
    
    // VULN: Direct SQL injection in registration
    $query = "INSERT INTO users (username, password, role, email) VALUES ('$username', '$password', '$role', '$email')";
    $response['query'] = $query;
    
    // VULN: Execute SELECT queries directly if detected
    if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $username) || 
        preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $email)) {
        
        $sql_query = preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $username) ? $username : $email;
        $result = $db->query($sql_query);
        
        if ($result && is_object($result)) {
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $response['success'] = true;
            $response['message'] = 'Query executed successfully';
            $response['data'] = $rows;
            $response['query'] = $sql_query;
        } else {
            $response['success'] = false;
            $response['message'] = 'Query failed';
            $response['error'] = $db->error;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    // Normal registration with SQL injection vulnerability
    $result = $db->query($query);
    
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'User registered successfully';
        $response['user'] = [
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'id' => $db->insert_id
        ];
        
        if ($role === 'admin') {
            $response['warning'] = '⚠️ Admin account created with elevated privileges!';
        }
    } else {
        $response['success'] = false;
        $response['message'] = 'Registration failed';
        $response['error'] = $db->error;
        $response['sql_error'] = true;
    }
    
} else {
    $response['message'] = 'Username and email required';
    $response['usage'] = [
        'POST' => 'Send username, password, email, role in POST body',
        'GET' => 'Use ?username=test&password=pass&email=test@example.com&role=admin',
        'Short' => 'Use ?u=test&p=pass&e=test@example.com&r=admin',
        'SQL Injection Examples' => [
            "?u=admin'), ('hacker', 'pass', 'admin', 'hack@evil.com&e=test@example.com",
            "?u=SELECT * FROM users--&e=test@example.com",
            "?username=admin&email=test'), ('backdoor', 'secret', 'admin', 'backdoor@evil.com"
        ]
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>