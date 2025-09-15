<?php
include('../includes/init.php');
include('../includes/db.php');

// IDOR Vulnerability: Access user profiles by changing the 'id' parameter
$userId = $_GET['id'];

$query = "SELECT id, username, email FROM users WHERE id = $userId";
$result = $db->query($query);
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>User Profile</h1>
        <p><strong>ID:</strong> <?php echo $user['id']; ?></p>
        <p><strong>Username:</strong> <?php echo $user['username']; ?></p>
        <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>
