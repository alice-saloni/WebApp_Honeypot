<?php
// VULN: Direct SQL execution endpoint - MAXIMUM VULNERABILITY!
require_once '/var/www/includes/init.php';
require_once '/var/www/includes/db.php';

// Allow from anywhere - disable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

?>
<!DOCTYPE html>
<html>
<head>
    <title>SQL Playground - Bug Tracker</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { background: rgba(255,255,255,0.95); margin: 20px; padding: 30px; border-radius: 15px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
        .header { text-align: center; color: #2c3e50; border-bottom: 3px solid #e74c3c; padding-bottom: 20px; margin-bottom: 30px; }
        .warning { background: linear-gradient(135deg, #ff6b35, #ff8e53); color: white; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center; font-weight: bold; }
        .sql-input { width: 100%; height: 120px; font-family: 'Courier New', monospace; font-size: 14px; padding: 15px; border: 2px solid #3498db; border-radius: 8px; background: #2c3e50; color: #ecf0f1; resize: vertical; }
        .execute-btn { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; padding: 15px 30px; border: none; border-radius: 25px; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .execute-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.3); }
        .quick-queries { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 30px 0; }
        .quick-query { background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 15px; border-radius: 10px; cursor: pointer; transition: all 0.3s; text-align: center; font-weight: bold; }
        .quick-query:hover { transform: scale(1.05); box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        th { background: linear-gradient(135deg, #27ae60, #229954); color: white; padding: 12px; text-align: left; font-weight: bold; }
        td { padding: 12px; border-bottom: 1px solid #ecf0f1; }
        tr:nth-child(even) { background: #f8f9fa; }
        tr:hover { background: #e3f2fd; }
        .result-box { background: #2c3e50; color: #ecf0f1; padding: 20px; border-radius: 10px; margin: 20px 0; font-family: monospace; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .nav-links { text-align: center; margin: 30px 0; }
        .nav-links a { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; margin: 0 10px; font-weight: bold; transition: all 0.3s; }
        .nav-links a:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 SQL Playground 🚨</h1>
            <p>Direct database access - Execute any SQL query!</p>
        </div>

        <div class="warning">
            ⚠️ WARNING: This page executes raw SQL queries directly against the database!
        </div>

        <form method="POST" id="sqlForm">
            <label for="sql"><strong>Enter SQL Query:</strong></label>
            <textarea name="sql" id="sql" class="sql-input" placeholder="SELECT * FROM users;&#10;SHOW TABLES;&#10;DESCRIBE users;&#10;-- Enter your SQL here..."></textarea>
            <br><br>
            <button type="submit" class="execute-btn">🚀 Execute Query</button>
        </form>

        <div class="quick-queries">
            <div class="quick-query" onclick="setQuery('SELECT * FROM users')">👥 Get All Users</div>
            <div class="quick-query" onclick="setQuery('SELECT username,password FROM users')">🔑 Get Credentials</div>
            <div class="quick-query" onclick="setQuery('SHOW TABLES')">📋 Show All Tables</div>
            <div class="quick-query" onclick="setQuery('SELECT * FROM tickets')">🎫 Get All Tickets</div>
            <div class="quick-query" onclick="setQuery('DESCRIBE users')">📊 Users Table Structure</div>
            <div class="quick-query" onclick="setQuery('SELECT COUNT(*) as total_users FROM users')">📈 Count Users</div>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['q'])) {
            $query = $_POST['sql'] ?? $_GET['q'] ?? '';
            
            if (!empty($query)) {
                echo "<div class='result-box'>";
                echo "<strong>Executed Query:</strong> " . htmlspecialchars($query);
                echo "</div>";
                
                $result = $db->query($query);
                
                if ($result === TRUE) {
                    echo "<div class='success'>✅ Query executed successfully!</div>";
                    if ($db->affected_rows > 0) {
                        echo "<p>Affected rows: " . $db->affected_rows . "</p>";
                    }
                } elseif ($result === FALSE) {
                    echo "<div class='error'>❌ Query failed!</div>";
                    echo "<div class='result-box'><strong>Error:</strong> " . $db->error . "</div>";
                } else {
                    // SELECT query with results
                    if ($result->num_rows > 0) {
                        echo "<div class='success'>✅ Found " . $result->num_rows . " results:</div>";
                        echo "<table>";
                        $first_row = true;
                        while ($row = $result->fetch_assoc()) {
                            if ($first_row) {
                                echo "<tr>";
                                foreach (array_keys($row) as $column) {
                                    echo "<th>$column</th>";
                                }
                                echo "</tr>";
                                $first_row = false;
                            }
                            echo "<tr>";
                            foreach ($row as $value) {
                                echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
                            }
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<div class='success'>✅ Query executed successfully but returned no results.</div>";
                    }
                }
            }
        }
        ?>

        <div class="nav-links">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="new_ticket.php">🎫 New Ticket</a>
            <a href="test_db.php">🔍 DB Test</a>
            <a href="monitor.php">📈 Monitor</a>
        </div>
    </div>

    <script>
        function setQuery(query) {
            document.getElementById('sql').value = query;
        }
        
        // Allow GET requests with query parameter
        if (window.location.search.includes('q=')) {
            document.getElementById('sqlForm').style.display = 'none';
        }
    </script>
</body>
</html>