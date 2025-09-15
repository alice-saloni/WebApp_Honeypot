<?php
include('../includes/init.php');
include('../includes/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = $_POST['comment'];
    // Stored XSS vulnerability
    $query = "INSERT INTO comments (comment) VALUES ('$comment')";
    $db->query($query);
}

$result = $db->query("SELECT comment, created_at FROM comments ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Comments Board</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Comments Board</h1>
        <p>This board is vulnerable to Stored XSS. Try submitting a comment like <code>&lt;script&gt;alert('XSS');&lt;/script&gt;</code>.</p>
        <form method="POST" action="comments.php">
            <textarea name="comment" placeholder="Your comment"></textarea>
            <button type="submit">Submit</button>
        </form>
        <hr>
        <h2>Comments</h2>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="comment">
                <p><?php echo $row['comment']; ?></p>
                <small><?php echo $row['created_at']; ?></small>
            </div>
        <?php endwhile; ?>
        <p><a href="index.php">Back to Home</a></p>
    </div>
</body>
</html>
