<?php require_once __DIR__ . '/../includes/init.php'; ?>
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = $_POST['ticket_id'];
    $userId = $_SESSION['user']['id'];
    $body = $_POST['body'];

    // VULN: No sanitization, allows XSS
    $query = "INSERT INTO comments (ticket_id, user_id, body) VALUES ($ticketId, $userId, '$body')";

    if ($db->query($query)) {
        header("Location: ticket.php?id=$ticketId");
        exit;
    } else {
        $error = 'Failed to add comment: ' . $db->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Comment</title>
</head>
<body>
    <h1>Add Comment</h1>
    <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
    <form method="POST">
        <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($_GET['ticket_id']); ?>">
        <label for="body">Comment:</label>
        <textarea id="body" name="body" required></textarea><br>
        <button type="submit">Add Comment</button>
    </form>
</body>
</html>
