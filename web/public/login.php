<?php
require_once '/var/www/includes/init.php';
require_once '/var/www/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // VULN: Simple and effective SQL Injection vulnerability
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    
    // Suppress errors for injection attempts and try to execute
    $result = @$db->query($query);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user'] = $user;
        
        // VULN: Show successful login details (information disclosure)
        if (isset($_GET['debug'])) {
            echo "<pre>Successful login:\n";
            echo "Query: " . htmlspecialchars($query) . "\n";
            echo "User data: " . print_r($user, true) . "</pre>";
            exit;
        }
        
        header('Location: dashboard.php');
        exit;
    } else {
        // VULN: Show helpful error information for attackers
        if ($db->error) {
            // SQL injection succeeded but didn't return results
            $error = 'Login failed - but query executed successfully!';
            $error .= '<br><strong>SQL Query:</strong> ' . htmlspecialchars($query);
            $error .= '<br><strong>DB Response:</strong> ' . htmlspecialchars($db->error);
        } else {
            $error = 'Invalid username or password';
            // Show query for debugging injection attempts
            if (strpos($username, "'") !== false || strpos($username, "--") !== false) {
                $error .= '<br><small>🔍 Query Debug: ' . htmlspecialchars($query) . '</small>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bug Tracker</title>
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

        .login-container {
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
        input[type="password"] {
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
        input[type="password"]::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
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

        .register-link {
            margin-top: 20px;
        }

        .register-link a {
            color: #fff;
            text-decoration: none;
            font-weight: 700;
        }

        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        <?php if (isset($error)) {
            // VULN: Reflected XSS - username is echoed raw
            echo "<div class='error-message'>$error for username: " . ($_POST['username'] ?? '') . "</div>";
        } ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password">
            </div>
            <button type="submit">Login</button>
        </form>
        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</body>
</html>
