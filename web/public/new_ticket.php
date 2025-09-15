<?php
require_once '/var/www/includes/init.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once '/var/www/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $ownerId = $_SESSION['user']['id'];

    // VULN: No input sanitization
    $query = "INSERT INTO tickets (owner_id, title, description, status) VALUES ($ownerId, '$title', '$description', 'open')";

    if ($db->query($query)) {
        $newTicketId = $db->insert_id;

        // Handle file upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = basename($_FILES['attachment']['name']);
            $filePath = $uploadDir . $fileName;

            // VULN: No proper file validation, allows dangerous file types
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filePath)) {
                $mime_type = $_FILES['attachment']['type'];
                $upload_query = "INSERT INTO uploads (ticket_id, user_id, filename, path, mime) VALUES ($newTicketId, $ownerId, '$fileName', '$filePath', '$mime_type')";
                $db->query($upload_query);
            }
        }

        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Failed to create ticket: ' . $db->error;
    }
}
?>

<?php include '/var/www/includes/header.php'; ?>

<h2>Create New Ticket</h2>

<?php if (isset($error)): ?>
    <p class="error"><?php echo $error; ?></p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" required>
    </div>
    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="5" required></textarea>
    </div>
    <div class="form-group">
        <label for="attachment">Attachment</label>
        <input type="file" id="attachment" name="attachment">
    </div>
    <button type="submit">Create Ticket</button>
</form>

<?php include '/var/www/includes/footer.php'; ?>
