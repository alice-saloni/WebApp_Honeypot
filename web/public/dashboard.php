<?php
require_once '/var/www/includes/init.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once '/var/www/includes/db.php';

// Get recent tickets
$query = "SELECT t.*, u.username FROM tickets t 
          JOIN users u ON t.owner_id = u.id 
          ORDER BY t.created_at DESC LIMIT 10";
$result = $db->query($query);

// Get ticket statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
    FROM tickets";
$stats = $db->query($statsQuery)->fetch_assoc();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-bug me-2"></i>Bug Tracker</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user']['username']); ?></h1>
            <a href="new_ticket.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>New Ticket
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Your Tickets</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($ticket = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $ticket['id']; ?></td>
                                <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $ticket['status'] === 'open' ? 'danger' : 'success'; ?>">
                                        <?php echo ucfirst($ticket['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($ticket['created_at'])); ?></td>
                                <td>
                                    <a href="ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
