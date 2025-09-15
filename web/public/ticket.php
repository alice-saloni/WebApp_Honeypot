<?php
require_once '/var/www/includes/init.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once '/var/www/includes/db.php';

$ticketId = $_GET['id'];

// VULN: SQL Injection
$query = "SELECT t.*, u.username as owner_name FROM tickets t JOIN users u ON t.owner_id = u.id WHERE t.id = $ticketId";
$result = $db->query($query);

if ($result && $ticket = $result->fetch_assoc()) {
    $title = $ticket['title'];
    $description = $ticket['description'];
    $status = $ticket['status'];
    $owner_name = $ticket['owner_name'];
} else {
    die('Ticket not found.');
}
?>

<?php include '/var/www/includes/header.php'; ?>

<div class="ticket-details">
    <h1><?php echo htmlspecialchars($title); ?></h1>
    <p><strong>Status:</strong> <?php echo htmlspecialchars($status); ?></p>
    <p><strong>Owner:</strong> <?php echo htmlspecialchars($owner_name); ?></p>
    <p><strong>Description:</strong></p>
    <p><?php echo nl2br(htmlspecialchars($description)); ?></p>
</div>

<div class="comments">
    <h2>Comments</h2>
    <?php
    // VULN: Stored XSS is rendered here
    $c_query = "SELECT c.body, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.ticket_id = $ticketId ORDER BY c.created_at ASC";
    $c_result = $db->query($c_query);
    if ($c_result && $c_result->num_rows > 0) {
        while ($comment = $c_result->fetch_assoc()) {
            echo "<div class='comment'><p><strong>" . htmlspecialchars($comment['username']) . ":</strong> " . $comment['body'] . "</p></div>";
        }
    } else {
        echo "<p>No comments yet.</p>";
    }
    ?>
    <form action="comment.php" method="POST" class="form-group">
        <input type="hidden" name="ticket_id" value="<?php echo $ticketId; ?>">
        <textarea name="body" placeholder="Add a comment..." required></textarea><br>
        <button type="submit">Add Comment</button>
    </form>
</div>

<div class="attachments">
    <h2>Attachments</h2>
    <?php
    $a_query = "SELECT filename, path FROM uploads WHERE ticket_id = $ticketId";
    $a_result = $db->query($a_query);
    if ($a_result && $a_result->num_rows > 0) {
        echo "<ul>";
        while ($attachment = $a_result->fetch_assoc()) {
            // VULN: Path traversal from stored path in DB
            echo "<li><a href='uploads/" . htmlspecialchars($attachment['filename']) . "'>" . htmlspecialchars($attachment['filename']) . "</a></li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No attachments.</p>";
    }
    ?>
    <p><a href="upload.php?ticket_id=<?php echo $ticketId; ?>">Upload a file</a></p>
</div>

<a href="dashboard.php">Back to Dashboard</a>

<?php include '/var/www/includes/footer.php'; ?>
