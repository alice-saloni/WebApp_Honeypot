<?php require_once __DIR__ . '/../includes/init.php';
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $ownerId = $_SESSION['user']['id'];

    // VULN: No input sanitization
    $query = "INSERT INTO tickets (owner_id, title, description, status) VALUES ($ownerId, '$title', '$description', 'open')";

    if ($db->query($query)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Failed to create ticket: ' . $db->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Ticket</title>
</head>
<body>
    <h1>Create New Ticket</h1>
    <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
    <form method="POST">
        <label for="title">Title:</label>
        <input type="text" id="title" name="title" required><br>
        <label for="description">Description:</label>
        <textarea id="description" name="description" required></textarea><br>
        <button type="submit">Create</button>
    </form>
</body>
</html>
