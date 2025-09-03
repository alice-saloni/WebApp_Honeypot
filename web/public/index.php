<?php require_once '/var/www/includes/init.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vulnerable Bug Tracker</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        h1 { text-align: center; }
        .banner { background-color: #ffcccc; padding: 10px; text-align: center; border: 1px solid red; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="banner">
            <strong>WARNING:</strong> This application is intentionally vulnerable. For lab use only. Do not expose to the public Internet.
        </div>
        <h1>Welcome to the Vulnerable Bug Tracker</h1>
        <p><a href="login.php">Login</a> | <a href="register.php">Register</a></p>
    </div>
</body>
</html>
