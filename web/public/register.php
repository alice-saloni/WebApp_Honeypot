<?php
require_once '/var/www/includes/init.php';
require_once '/var/www/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    
    // VULN: Multiple SQL injection points + privilege escalation
    
    // Check for admin registration attempts
    if (strpos(strtolower($username), 'admin') !== false || 
        strpos(strtolower($email), 'admin') !== false ||
        isset($_POST['role'])) {
        $role = $_POST['role'] ?? 'admin'; // VULN: Allow role specification
        $query = "INSERT INTO users (username, password, role, email) VALUES ('$username', '$password', '$role', '$email')";
    } else {
        $role = 'user';
        $query = "INSERT INTO users (username, password, role, email) VALUES ('$username', '$password', '$role', '$email')";
    }
    
    // VULN: Execute any SQL if it looks like a SELECT/SHOW query
    if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $username) || 
        preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $email)) {
        
        $sql_query = preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $username) ? $username : $email;
        
        echo "<h2>🔍 Query Execution Results</h2>";
        echo "<p><strong>Executed:</strong> " . htmlspecialchars($sql_query) . "</p>";
        
        $result = $db->query($sql_query);
        if ($result && is_object($result) && $result->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            $first_row = true;
            while ($row = $result->fetch_assoc()) {
                if ($first_row) {
                    echo "<tr style='background: #f0f0f0;'>";
                    foreach (array_keys($row) as $column) {
                        echo "<th style='padding: 8px;'>$column</th>";
                    }
                    echo "</tr>";
                    $first_row = false;
                }
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td style='padding: 8px;'>" . htmlspecialchars($value ?? '') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
            echo "<p><a href='register.php'>Register Another User</a> | <a href='login.php'>Login</a></p>";
            exit;
        }
    }
    
    $result = $db->query($query);
    if ($result) {
        $success = "Registration successful! ";
        if ($role === 'admin') {
            $success .= "⚠️ Admin account created! ";
        }
        $success .= "You can now <a href='login.php'>login</a>";
        
        // VULN: Show created user details
        if (isset($_POST['debug']) || isset($_GET['debug'])) {
            $success .= "<br><strong>Debug Info:</strong><br>";
            $success .= "Query: " . htmlspecialchars($query) . "<br>";
            $success .= "Username: $username, Role: $role, Email: $email";
        }
    } else {
        // VULN: Show SQL errors and query for debugging
        $error = 'Registration failed: ' . $db->error;
        $error .= '<br><strong>Query:</strong> ' . htmlspecialchars($query);
        
        // VULN: Try alternative injection methods
        if (strpos($username, "'") !== false || strpos($email, "'") !== false) {
            $error .= '<br><small>💡 Tip: Try escaping quotes or using different injection techniques</small>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Bug Tracker</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to right, #00c6ff, #0072ff);
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .register-container {
            background: rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 100%;
            max-width: 400px;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            margin-bottom: 20px;
            font-weight: 700;
        }

        .error-message {
            color: #ff4d4d;
            background: rgba(255, 77, 77, 0.2);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 400;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            box-sizing: border-box;
            transition: background 0.3s;
        }

        input[type="text"]::placeholder,
        input[type="password"]::placeholder,
        input[type="email"]::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus {
            background: rgba(255, 255, 255, 0.3);
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background: #fff;
            color: #0072ff;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.3s, color 0.3s;
        }

        button:hover {
            background: #f0f0f0;
            color: #00c6ff;
        }

        .login-link {
            margin-top: 20px;
        }

        .login-link a {
            color: #fff;
            text-decoration: none;
            font-weight: 700;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h1>Create Account</h1>
        <?php 
        if (isset($error)) {
            echo "<div class='error-message'>$error</div>";
        }
        if (isset($success)) {
            echo "<div style='color: #ffffff; background: #2196F3; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; text-shadow: 1px 1px 1px rgba(0,0,0,0.2); box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>$success</div>";
        }
        ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Choose a username (try 'admin' for special access)" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email (admin emails get special privileges)" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Choose a password" required>
            </div>
            
            <!-- 🍯 Hidden privilege escalation field -->
            <div class="form-group" style="display: none;" id="roleField">
                <label for="role">Account Role</label>
                <select id="role" name="role" style="width: 100%; padding: 12px; border: none; border-radius: 5px; background: rgba(255, 255, 255, 0.2); color: #fff;">
                    <option value="user">Regular User</option>
                    <option value="admin">Administrator</option>
                    <option value="moderator">Moderator</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="debug" name="debug" style="margin-right: 5px;">
                    Enable debug mode (show technical details)
                </label>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="showRole" onclick="toggleRoleField()" style="margin-right: 5px;">
                    Advanced user (show role options)
                </label>
            </div>
            
            <button type="submit">Create Account</button>
        </form>
        
        <script>
        function toggleRoleField() {
            var roleField = document.getElementById('roleField');
            var checkbox = document.getElementById('showRole');
            roleField.style.display = checkbox.checked ? 'block' : 'none';
        }
        </script>
        <div class="login-link">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html>
