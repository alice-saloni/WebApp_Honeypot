<?php require_once __DIR__ . '/../includes/init.php'; ?>
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = $_POST['ticket_id'];
    $userId = $_SESSION['user']['id'];
    $uploadDir = 'uploads/';
    $fileName = basename($_FILES['file']['name']);
    $filePath = $uploadDir . $fileName;

    // VULN: No proper file validation, allows dangerous file types
    if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
        require_once '../includes/db.php';
        $query = "INSERT INTO uploads (ticket_id, user_id, filename, path, mime) VALUES ($ticketId, $userId, '$fileName', '$filePath', '{$_FILES['file']['type']}')";

        if ($db->query($query)) {
            header("Location: ticket.php?id=$ticketId");
            exit;
        } else {
            $error = 'Failed to save file info to database: ' . $db->error;
        }
    } else {
        $error = 'Failed to upload file.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload File</title>
</head>
<body>
    <h1>Upload File</h1>
    <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($_GET['ticket_id']); ?>">
        <label for="file">File:</label>
        <input type="file" id="file" name="file" required><br>
        <button type="submit">Upload</button>
    </form>
</body>
</html>
