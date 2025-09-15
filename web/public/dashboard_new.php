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
$statsQuery = "SELECT COUNT(*) as total FROM tickets";
$stats = $db->query($statsQuery)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bug Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .navbar { background: linear-gradient(135deg, #6f42c1 0%, #6610f2 100%); }
        .card { transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-bug me-2"></i>Bug Tracker</a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['user']['username']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Tickets</h5>
                        <h2><?php echo $stats['total']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Tickets</h5>
                <a href="new_ticket.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> New Ticket
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Reporter</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($ticket = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $ticket['id']; ?></td>
                                <td>
                                    <a href="ticket.php?id=<?php echo $ticket['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($ticket['title']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($ticket['username']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></td>
                                <td>
                                    <a href="ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
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
