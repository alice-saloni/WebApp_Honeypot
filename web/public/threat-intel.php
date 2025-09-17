<?php
require_once '/var/www/includes/init.php';
require_once '/var/www/includes/db.php';
require_once '/var/www/includes/mitre_mapper.php';

// Set title for header
$page_title = "Threat Intelligence Dashboard";
require_once '/var/www/includes/header.php';

echo '<h1 class="dashboard-title">Threat Intelligence Dashboard</h1>';
echo '<h2 class="dashboard-subtitle">Real-time Attack Monitoring & Analysis</h2>';

// Authentication for threat intelligence dashboard
if (!isset($_SERVER['PHP_AUTH_USER']) || 
    $_SERVER['PHP_AUTH_USER'] !== 'threat' || 
    $_SERVER['PHP_AUTH_PW'] !== 'intel123') {
    header('WWW-Authenticate: Basic realm="Threat Intelligence Dashboard"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Unauthorized Access - Use credentials: threat/intel123';
    exit;
}

// Get time range filter
$hours = isset($_GET['hours']) ? (int)$_GET['hours'] : 24;
$severity_filter = $_GET['severity'] ?? '';

// Main dashboard query with threat intelligence
$query = "SELECT * FROM requests WHERE ts >= NOW() - INTERVAL $hours HOUR";
if ($severity_filter) {
    $query .= " AND severity = '$severity_filter'";
}
$query .= " ORDER BY ts DESC";
$result = $db->query($query);

echo '<h3 class="section-heading">Attack Statistics Overview</h3>';

// Collect threat intelligence data
$attacks = [];
$tactics_count = [];
$severity_count = ['Low' => 0, 'Medium' => 0, 'High' => 0, 'Critical' => 0];
$ttp_count = [];
$ip_stats = [];
$attribution_stats = [];

while ($row = $result->fetch_assoc()) {
    $attacks[] = $row;
    
    // Count by severity
    $severity_count[$row['severity']]++;
    
    // Count by IP
    $ip = $row['ip'];
    if (!isset($ip_stats[$ip])) {
        $ip_stats[$ip] = ['count' => 0, 'severity' => 'Low', 'first_seen' => $row['ts'], 'last_seen' => $row['ts']];
    }
    $ip_stats[$ip]['count']++;
    $ip_stats[$ip]['last_seen'] = $row['ts'];
    if (array_search($row['severity'], ['Low', 'Medium', 'High', 'Critical']) > 
        array_search($ip_stats[$ip]['severity'], ['Low', 'Medium', 'High', 'Critical'])) {
        $ip_stats[$ip]['severity'] = $row['severity'];
    }
    
    // Track attack types
    if (!empty($row['attack_type'])) {
        if (!isset($ttp_count[$row['attack_type']])) {
            $ttp_count[$row['attack_type']] = 0;
        }
        $ttp_count[$row['attack_type']]++;
    }
    
    // Track tools/attribution
    if (!empty($row['tool'])) {
        if (!isset($attribution_stats[$row['tool']])) {
            $attribution_stats[$row['tool']] = 0;
        }
        $attribution_stats[$row['tool']]++;
    }
    
    // Parse TTPs and tactics
    if (!empty($row['ttps'])) {
        $ttps = json_decode($row['ttps'], true);
        if ($ttps) {
            foreach ($ttps as $ttp) {
                $ttp_id = $ttp['ttp_id'];
                $tactic = $ttp['tactic'];
                
                if (!isset($ttp_count[$ttp_id])) {
                    $ttp_count[$ttp_id] = ['count' => 0, 'name' => $ttp['name'], 'tactic' => $tactic];
                }
                $ttp_count[$ttp_id]['count']++;
                
                if (!isset($tactics_count[$tactic])) {
                    $tactics_count[$tactic] = 0;
                }
                $tactics_count[$tactic]++;
            }
        }
    }
    
    // Parse attribution details
    if (!empty($row['attribution'])) {
        $attr = json_decode($row['attribution'], true);
        if ($attr && isset($attr['tools'])) {
            foreach ($attr['tools'] as $tool) {
                if (!isset($attribution_stats[$tool])) {
                    $attribution_stats[$tool] = 0;
                }
                $attribution_stats[$tool]++;
            }
        }
    }
}

// Sort stats for display
arsort($ttp_count);
arsort($tactics_count);
arsort($ip_stats);
arsort($attribution_stats);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Threat Intelligence Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="threat-intel.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <div class="filters">
            <form method="get" action="">
                <select name="hours">
                    <option value="1" <?php echo $hours == 1 ? 'selected' : ''; ?>>Last Hour</option>
                    <option value="24" <?php echo $hours == 24 ? 'selected' : ''; ?>>Last 24 Hours</option>
                    <option value="168" <?php echo $hours == 168 ? 'selected' : ''; ?>>Last Week</option>
                </select>
                <select name="severity">
                    <option value="">All Severities</option>
                    <?php foreach (['Low', 'Medium', 'High', 'Critical'] as $sev): ?>
                        <option value="<?php echo $sev; ?>" <?php echo $severity_filter === $sev ? 'selected' : ''; ?>>
                            <?php echo $sev; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="refresh-button">Refresh</button>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Attacks</div>
                <div class="stat-value"><?php echo count($attacks); ?></div>
            </div>
            <?php foreach ($severity_count as $severity => $count): ?>
                <div class="stat-card">
                    <div class="stat-title"><?php echo $severity; ?> Severity</div>
                    <div class="stat-value severity-<?php echo strtolower($severity); ?>"><?php echo $count; ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="chart-container">
            <canvas id="attacksChart"></canvas>
        </div>

        <h2>Recent Attacks</h2>
        <table class="attacks-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>IP Address</th>
                    <th>Attack Type</th>
                    <th>Severity</th>
                    <th>Tool</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attacks as $attack): ?>
                <tr class="attack-row">
                    <td><?php echo date('Y-m-d H:i:s', strtotime($attack['ts'])); ?></td>
                    <td><?php echo htmlspecialchars($attack['ip']); ?></td>
                    <td><?php echo htmlspecialchars($attack['attack_type']); ?></td>
                    <td class="severity-<?php echo strtolower($attack['severity']); ?>">
                        <?php echo htmlspecialchars($attack['severity']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($attack['tool'] ?? 'Unknown'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        const ctx = document.getElementById('attacksChart').getContext('2d');
        
        // Prepare the data for the chart
        const labels = [];
        const data = [];
        
        <?php
        foreach ($ttp_count as $ttp_id => $info) {
            if (is_array($info)) {
                echo "labels.push(" . json_encode($ttp_id) . ");\n";
                echo "data.push(" . json_encode($info['count']) . ");\n";
            } else {
                echo "labels.push(" . json_encode($ttp_id) . ");\n";
                echo "data.push(" . json_encode($info) . ");\n";
            }
        }
        ?>
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Attack Types',
                    data: data,
                    backgroundColor: '#61dafb',
                    borderColor: '#2c5364',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#e0e0e0',
                            font: {
                                size: 12
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#e0e0e0',
                            font: {
                                size: 12
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#e0e0e0',
                            font: {
                                size: 14
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
                    backgroundColor: '#61dafb',
                    borderColor: '#2c5364',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#e0e0e0'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#e0e0e0'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#e0e0e0'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>