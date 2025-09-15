<?php
require_once '/var/www/includes/init.php';
require_once '/var/www/includes/db.php';

echo "<h1>Database Connection Test</h1>";

// Test basic connection
if ($db->connect_error) {
    echo "<p style='color: red;'>❌ Connection failed: " . $db->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Database connected successfully!</p>";
    echo "<p>MySQL Version: " . $db->server_info . "</p>";
}

// Test users table
echo "<h2>Users Table Test</h2>";
$users_result = $db->query("SELECT COUNT(*) as count FROM users");
if ($users_result) {
    $count = $users_result->fetch_assoc();
    echo "<p>✅ Users table exists with {$count['count']} records</p>";
    
    // Show all users
    $all_users = $db->query("SELECT * FROM users");
    if ($all_users && $all_users->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Password</th><th>Role</th><th>Email</th></tr>";
        while ($user = $all_users->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['password']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'>❌ Error accessing users table: " . $db->error . "</p>";
}

// Test tickets table
echo "<h2>Tickets Table Test</h2>";
$tickets_result = $db->query("SELECT COUNT(*) as count FROM tickets");
if ($tickets_result) {
    $count = $tickets_result->fetch_assoc();
    echo "<p>✅ Tickets table exists with {$count['count']} records</p>";
    
    if ($count['count'] > 0) {
        $all_tickets = $db->query("SELECT * FROM tickets LIMIT 5");
        if ($all_tickets && $all_tickets->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Owner ID</th><th>Title</th><th>Description</th><th>Status</th></tr>";
            while ($ticket = $all_tickets->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$ticket['id']}</td>";
                echo "<td>{$ticket['owner_id']}</td>";
                echo "<td>" . htmlspecialchars($ticket['title']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($ticket['description'], 0, 50)) . "...</td>";
                echo "<td>{$ticket['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ Error accessing tickets table: " . $db->error . "</p>";
}

// Show all tables
echo "<h2>Database Structure</h2>";
$tables = $db->query("SHOW TABLES");
if ($tables) {
    echo "<p>Available tables:</p><ul>";
    while ($table = $tables->fetch_array()) {
        echo "<li>{$table[0]}</li>";
    }
    echo "</ul>";
}

echo "<p><a href='dashboard.php'>Back to Dashboard</a></p>";
?>