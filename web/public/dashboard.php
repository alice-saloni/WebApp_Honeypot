<?php require_once '/var/www/includes/init.php'; ?>
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h1>
    <p><a href="new_ticket.php">Create New Ticket</a> | <a href="logout.php">Logout</a></p>
    <h2>Your Tickets</h2>
    <ul>
        <?php
    require_once '/var/www/includes/db.php';
        $userId = $user['id'];

        // VULN: IDOR - No proper ownership check
        $query = "SELECT * FROM tickets WHERE owner_id = $userId";
        $result = $db->query($query);

        while ($ticket = $result->fetch_assoc()) {
            echo "<li><a href='ticket.php?id={$ticket['id']}'>{$ticket['title']}</a></li>";
        }
        ?>
    </ul>
</body>
</html>
