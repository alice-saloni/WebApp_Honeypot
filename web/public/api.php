<?php
// VULN: Ultimate GET-based SQL injection endpoint
require_once '/var/www/includes/init.php';
require_once '/var/www/includes/db.php';

// CORS headers for maximum accessibility
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$response = ['status' => 'error', 'data' => null, 'query' => '', 'error' => ''];

if (isset($_GET['query']) || isset($_GET['q']) || isset($_GET['sql'])) {
    $query = $_GET['query'] ?? $_GET['q'] ?? $_GET['sql'] ?? '';
    
    if (!empty($query)) {
        $response['query'] = $query;
        
        // VULN: Execute ANY SQL query directly!
        $result = $db->query($query);
        
        if ($result === TRUE) {
            $response['status'] = 'success';
            $response['data'] = ['message' => 'Query executed successfully', 'affected_rows' => $db->affected_rows];
        } elseif ($result === FALSE) {
            $response['status'] = 'error';
            $response['error'] = $db->error;
        } else {
            // SELECT query
            $rows = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
            }
            $response['status'] = 'success';
            $response['data'] = $rows;
        }
    } else {
        $response['error'] = 'No query provided';
    }
} else {
    $response['error'] = 'Use ?query=YOUR_SQL_HERE or ?q=YOUR_SQL_HERE or ?sql=YOUR_SQL_HERE';
    $response['examples'] = [
        'Get users: ?q=SELECT * FROM users',
        'Show tables: ?query=SHOW TABLES',
        'Get credentials: ?sql=SELECT username,password FROM users'
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>