<?php require_once '/var/www/includes/init.php'; ?>
<?php
if ($_SERVER['PHP_AUTH_USER'] !== 'monitor' || $_SERVER['PHP_AUTH_PW'] !== 'monitor123') {
    header('WWW-Authenticate: Basic realm="Monitor Access"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Unauthorized';
    exit;
}

require_once '/var/www/includes/db.php';

$filter = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $filter = $_GET['filter'] ?? '';
}

// Add pagination and filtering logic
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$since = isset($_GET['since']) ? (int)$_GET['since'] : 60; // Default to last 60 minutes

$query = "SELECT * FROM requests WHERE ts >= NOW() - INTERVAL $since MINUTE";
if ($filter) {
    $query .= " AND path LIKE '%$filter%'"; // VULN: SQL Injection
}
$query .= " LIMIT $limit OFFSET $offset";

$result = $db->query($query);

// Prepare data for charts
$chartData = [
    'requestsOverTime' => [],
    'countsByTag' => [],
    'topIPs' => []
];

// Fetch data for charts
$chartQuery = "SELECT COUNT(*) as count, DATE_FORMAT(ts, '%Y-%m-%d %H:%i') as minute FROM requests WHERE ts >= NOW() - INTERVAL $since MINUTE GROUP BY minute ORDER BY minute";
$chartResult = $db->query($chartQuery);
while ($row = $chartResult->fetch_assoc()) {
    $chartData['requestsOverTime'][] = $row;
}

$tagQuery = "SELECT tags, COUNT(*) as count FROM requests WHERE ts >= NOW() - INTERVAL $since MINUTE GROUP BY tags ORDER BY count DESC";
$tagResult = $db->query($tagQuery);
while ($row = $tagResult->fetch_assoc()) {
    $chartData['countsByTag'][] = $row;
}

$ipQuery = "SELECT ip, COUNT(*) as count FROM requests WHERE ts >= NOW() - INTERVAL $since MINUTE GROUP BY ip ORDER BY count DESC LIMIT 10";
$ipResult = $db->query($ipQuery);
while ($row = $ipResult->fetch_assoc()) {
    $chartData['topIPs'][] = $row;
}

// Add auto-refresh and chart rendering
?>
<script>
setInterval(() => {
    location.reload();
}, 5000);

const chartData = <?php echo json_encode($chartData); ?>;
// Chart data processed

// Render charts using vanilla JavaScript
function renderCharts(data) {
    // Requests Over Time
    const requestsOverTimeCtx = document.getElementById('requestsOverTime').getContext('2d');
    const requestsOverTimeLabels = data.requestsOverTime.map(item => item.minute);
    const requestsOverTimeCounts = data.requestsOverTime.map(item => item.count);

    new Chart(requestsOverTimeCtx, {
        type: 'line',
        data: {
            labels: requestsOverTimeLabels,
            datasets: [{
                label: 'Requests Over Time',
                data: requestsOverTimeCounts,
                borderColor: 'blue',
                fill: false
            }]
        }
    });

    // Counts By Tag
    const countsByTagCtx = document.getElementById('countsByTag').getContext('2d');
    const countsByTagLabels = data.countsByTag.map(item => item.tags);
    const countsByTagCounts = data.countsByTag.map(item => item.count);

    new Chart(countsByTagCtx, {
        type: 'bar',
        data: {
            labels: countsByTagLabels,
            datasets: [{
                label: 'Counts By Tag',
                data: countsByTagCounts,
                backgroundColor: 'orange'
            }]
        }
    });

    // Top IPs
    const topIPsCtx = document.getElementById('topIPs').getContext('2d');
    const topIPsLabels = data.topIPs.map(item => item.ip);
    const topIPsCounts = data.topIPs.map(item => item.count);

    new Chart(topIPsCtx, {
        type: 'pie',
        data: {
            labels: topIPsLabels,
            datasets: [{
                label: 'Top IPs',
                data: topIPsCounts,
                backgroundColor: ['red', 'green', 'blue', 'yellow', 'purple']
            }]
        }
    });
}

// Call renderCharts with chartData
renderCharts(chartData);

// Add event listeners for row clicks to open modal
function setupRowClickListeners() {
    document.querySelectorAll('.request-row').forEach(row => {
        row.addEventListener('click', () => {
            const requestId = row.dataset.id;
            fetch(`monitor.php?id=${requestId}&raw=1`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modal-content').textContent = data;
                    document.getElementById('modal').style.display = 'block';
                });
        });
    });
}

// Close modal
function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

// Call setupRowClickListeners after rendering rows
setupRowClickListeners();
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor</title>
</head>
<body>
    <h1>Monitor</h1>
    <form method="GET">
        <label for="filter">Filter:</label>
        <input type="text" id="filter" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
        <button type="submit">Apply Filter</button>
    </form>

    <h2>Requests</h2>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Timestamp</th>
                <th>IP</th>
                <th>Method</th>
                <th>Path</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $request): ?>
                <tr class="request-row" data-id="<?php echo $request['id']; ?>">
                    <td><?php echo $request['id']; ?></td>
                    <td><?php echo $request['ts']; ?></td>
                    <td><?php echo $request['ip']; ?></td>
                    <td><?php echo $request['method']; ?></td>
                    <td><?php echo htmlspecialchars($request['path']); ?></td>
                    <td><?php echo $request['response_status']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Charts</h2>
    <canvas id="requestsOverTime" width="400" height="200"></canvas>
    <canvas id="countsByTag" width="400" height="200"></canvas>
    <canvas id="topIPs" width="400" height="200"></canvas>

    <div id="modal" style="display:none; position:fixed; top:10%; left:10%; width:80%; height:80%; background:white; border:1px solid black; overflow:auto;">
        <div style="padding:10px;">
            <button onclick="closeModal()">Close</button>
            <pre id="modal-content"></pre>
        </div>
    </div>
</body>
</html>

<?php
// Handle raw view endpoint
if (isset($_GET['id']) && isset($_GET['raw'])) {
    $requestId = (int)$_GET['id'];
    $rawQuery = "SELECT * FROM requests WHERE id = $requestId"; // VULN: SQL Injection
    $rawResult = $db->query($rawQuery);

    if ($rawResult && $rawData = $rawResult->fetch_assoc()) {
        header('Content-Type: text/plain');
        echo json_encode($rawData, JSON_PRETTY_PRINT);
    } else {
        header('HTTP/1.0 404 Not Found');
        echo 'Request not found.';
    }
    exit;
}
