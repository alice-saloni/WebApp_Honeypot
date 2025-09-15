<?php require_once '/var/www/includes/init.php'; ?>
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once '/var/www/includes/db.php';

$searchResults = [];
$executed_query = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['q'])) {
    $query = $_GET['q'];

    // VULN: MAXIMUM SQL Injection - accepts ANY SQL query!
    $sql = "SELECT * FROM tickets WHERE title LIKE '%$query%'";
    $executed_query = $sql;
    
    // If query looks like a direct SQL statement, just execute it
    if (preg_match('/^\s*(select|show|describe|explain)/i', trim($query))) {
        $sql = trim($query);
        $executed_query = $sql;
    }
    
    $result = $db->query($sql);

    if ($result && is_object($result)) {
        while ($row = $result->fetch_assoc()) {
            $searchResults[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Tickets</title>
</head>
<body>
    <h1>Search Tickets</h1>
    <form method="GET">
        <label for="q">Search:</label>
        <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
        <!-- VULN: Reflected XSS - User input is echoed back without proper encoding. -->
        <button type="submit">Search</button>
    </form>

    <?php if (isset($_GET['q'])): ?>
        <h2>Results for "<?php echo htmlspecialchars($_GET['q']); ?>":</h2>
        <!-- VULN: Reflected XSS - User input is echoed back without proper encoding. -->
        <?php if (empty($searchResults)): ?>
            <p>No results found.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($searchResults as $ticket): ?>
                    <li><a href="ticket.php?id=<?php echo $ticket['id']; ?>"><?php echo htmlspecialchars($ticket['title']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
