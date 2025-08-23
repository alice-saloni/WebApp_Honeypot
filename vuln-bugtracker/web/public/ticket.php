<?php require_once __DIR__ . '/../includes/init.php'; ?>
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/db.php';

$ticketId = $_GET['id'];

// VULN: SQL Injection
$query = "SELECT * FROM tickets WHERE id = $ticketId";
$result = $db->query($query);

if ($result && $ticket = $result->fetch_assoc()) {
    $title = $ticket['title'];
    $description = $ticket['description'];
    $status = $ticket['status'];
} else {
    die('Ticket not found.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details</title>
</head>
<body>
    <h1><?php echo htmlspecialchars($title); ?></h1>
    <p><strong>Description:</strong> <?php echo htmlspecialchars($description); ?></p>
    <p><strong>Status:</strong> <?php echo htmlspecialchars($status); ?></p>
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>
