<?php
require_once '/var/www/includes/init.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once '/var/www/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $ownerId = $_SESSION['user']['id'];

    // VULN: Super vulnerable - executes SELECT queries directly!
    $title_trimmed = trim($title);
    $description_trimmed = trim($description);
    
    // Debug: Show what we're checking
    echo "<!-- DEBUG: Title='$title_trimmed', Desc='$description_trimmed' -->";
    
    if (preg_match('/^\s*select/i', $title_trimmed) || preg_match('/^\s*select/i', $description_trimmed)) {
        $sql_to_execute = '';
        if (preg_match('/^\s*select/i', $title_trimmed)) {
            $sql_to_execute = $title_trimmed;
        } else {
            $sql_to_execute = $description_trimmed;
        }
        
        echo "<!DOCTYPE html><html><head><title>Query Results - Bug Tracker</title>";
        echo "<style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
            .container { background: rgba(255,255,255,0.95); padding: 30px; border-radius: 15px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); max-width: 1200px; margin: 0 auto; }
            h2 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; }
            .query-box { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 8px; font-family: 'Courier New', monospace; margin: 20px 0; border-left: 5px solid #e74c3c; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
            th { background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 12px; text-align: left; font-weight: bold; }
            td { padding: 12px; border-bottom: 1px solid #ecf0f1; }
            tr:nth-child(even) { background: #f8f9fa; }
            tr:hover { background: #e3f2fd; transform: scale(1.01); transition: all 0.2s; }
            .nav-links { margin: 30px 0; text-align: center; }
            .nav-links a { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; margin: 0 10px; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s; }
            .nav-links a:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.3); }
            .success { color: #27ae60; font-weight: bold; }
            .error { color: #e74c3c; font-weight: bold; }
        </style></head><body>";
        echo "<div class='container'>";
        echo "<h2>🔍 SQL Query Execution Results</h2>";
        echo "<div class='query-box'><strong>Executed Query:</strong> " . htmlspecialchars($sql_to_execute) . "</div>";
        
        $extract_result = $db->query($sql_to_execute);
        if ($extract_result && $extract_result->num_rows > 0) {
            echo "<div class='success'>✅ Query executed successfully! Found " . $extract_result->num_rows . " results.</div>";
            echo "<table>";
            $first_row = true;
            while ($row = $extract_result->fetch_assoc()) {
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
            echo "<div class='error'>❌ Query executed but returned no results or failed.</div>";
            if ($db->error) {
                echo "<div class='query-box'><strong>MySQL Error:</strong> " . $db->error . "</div>";
            }
        }
        echo "<div class='nav-links'>";
        echo "<a href='new_ticket.php'>🔄 Execute Another Query</a>";
        echo "<a href='dashboard.php'>📊 Back to Dashboard</a>";
        echo "<a href='test_db.php'>🔍 Database Test</a>";
        echo "</div></div></body></html>";
        exit;
    }

    // VULN: No input sanitization - accepts any SQL injection for INSERT
    $query = "INSERT INTO tickets (owner_id, title, description, status) VALUES ($ownerId, '$title', '$description', 'open')";

    $result = $db->query($query);
    if ($result) {
        $newTicketId = $db->insert_id;

        // Handle file upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = basename($_FILES['attachment']['name']);
            $filePath = $uploadDir . $fileName;

            // VULN: No proper file validation, allows dangerous file types
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filePath)) {
                $mime_type = $_FILES['attachment']['type'];
                $upload_query = "INSERT INTO uploads (ticket_id, user_id, filename, path, mime) VALUES ($newTicketId, $ownerId, '$fileName', '$filePath', '$mime_type')";
                $db->query($upload_query);
            }
        }

        header('Location: dashboard.php');
        exit;
    } else {
        // Show SQL errors for easier exploitation (bad practice = good honeypot)
        $error = 'Database Error: ' . $db->error;
        
        // VULN: Also try to execute as SELECT if INSERT fails (super vulnerable)
        if (strpos(strtolower($title), 'select') !== false || strpos(strtolower($description), 'select') !== false) {
            $select_query = str_replace(['INSERT INTO tickets (owner_id, title, description, status) VALUES (', "$ownerId, '", "', '", "', 'open')"], '', $query);
            
            // Try to extract data if it looks like a SELECT
            if (preg_match('/select.*from/i', $title . ' ' . $description)) {
                $extract_query = trim(preg_replace('/.*?select/i', 'SELECT', $title . ' ' . $description));
                $extract_query = preg_replace('/[\'";]+.*$/', '', $extract_query); // Clean up
                
                if (!empty($extract_query)) {
                    $extract_result = $db->query($extract_query);
                    if ($extract_result && $extract_result->num_rows > 0) {
                        echo "<h3>Query Results:</h3>";
                        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                        $first_row = true;
                        while ($row = $extract_result->fetch_assoc()) {
                            if ($first_row) {
                                echo "<tr style='background: #f0f0f0;'>";
                                foreach (array_keys($row) as $column) {
                                    echo "<th style='padding: 8px; border: 1px solid #ddd;'>$column</th>";
                                }
                                echo "</tr>";
                                $first_row = false;
                            }
                            echo "<tr>";
                            foreach ($row as $value) {
                                echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($value ?? '') . "</td>";
                            }
                            echo "</tr>";
                        }
                        echo "</table>";
                        echo "<p><a href='new_ticket.php'>Create Another Ticket</a> | <a href='dashboard.php'>Back to Dashboard</a></p>";
                        exit;
                    }
                }
            }
        }
    }
}
?>

<?php include '/var/www/includes/header.php'; ?>

<h2>Create New Ticket</h2>

<?php if (isset($error)): ?>
    <p class="error"><?php echo $error; ?></p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" required>
    </div>
    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="5" required></textarea>
    </div>
    <div class="form-group">
        <label for="attachment">Attachment</label>
        <input type="file" id="attachment" name="attachment">
    </div>
    <button type="submit">Create Ticket</button>
</form>

<?php include '/var/www/includes/footer.php'; ?>
