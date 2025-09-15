<?php require_once '/var/www/includes/init.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Vulnerable Bug Tracker</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(to right, #0f2027, #203a43, #2c5364);
            color: #e0e0e0;
            text-align: center;
        }
        .welcome-container {
            background: rgba(0, 0, 0, 0.2);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            border: 1px solid #2c5364;
        }
        .banner {
            background-color: rgba(255, 77, 77, 0.2);
            color: #ff4d4d;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        h1 {
            color: #61dafb;
        }
        a {
            color: #61dafb;
            text-decoration: none;
            font-weight: 700;
            margin: 0 15px;
            font-size: 1.2em;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="banner">
            <strong>WARNING:</strong> This application is intentionally vulnerable. For lab use only.
        </div>
        <h1>Welcome to the Vulnerable Bug Tracker</h1>
        <p>
            <a href="login.php">Login</a> | <a href="register.php">Register</a>
        </p>
    </div>
</body>
</html>
