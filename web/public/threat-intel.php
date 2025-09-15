<?php
require_once '/var/www/includes/init.php';
require_once '/var/www/includes/db.php';
require_once '/var/www/includes/mitre_mapper.php';

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
    
    // Parse TTPs and tactics
    if ($row['ttps']) {
        $ttps = json_decode($row['ttps'], true);
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
    
    // Parse attribution
    if ($row['attribution']) {
        $attribution = json_decode($row['attribution'], true);
        if (isset($attribution['tools'])) {
            foreach ($attribution['tools'] as $tool) {
                if (!isset($attribution_stats[$tool])) {
                    $attribution_stats[$tool] = 0;
                }
                $attribution_stats[$tool]++;
            }
        }
    }
}

// Sort data for display
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
    <title>🕵️ Threat Intelligence Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); 
            color: #fff; 
            min-height: 100vh;
        }
        
        .header {
            background: rgba(0,0,0,0.3);
            padding: 20px 0;
            text-align: center;
            border-bottom: 3px solid #00ff88;
        }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .widget {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .widget h3 {
            color: #00ff88;
            margin-bottom: 15px;
        }
        
        .severity-critical { color: #ff4757; font-weight: bold; }
        .severity-high { color: #ff6b35; font-weight: bold; }
        .severity-medium { color: #ffa726; }
        .severity-low { color: #66bb6a; }
        
        .metric-large {
            font-size: 2.5em;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        
        .ttp-item, .ip-item, .tool-item {
            background: rgba(0,0,0,0.2);
            margin: 8px 0;
            padding: 10px;
            border-radius: 8px;
            border-left: 4px solid #00ff88;
        }
        
        .ttp-id { 
            color: #00ff88; 
            font-family: monospace; 
            font-weight: bold;
        }
        
        .tactic-badge {
            background: #2196f3;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-left: 5px;
        }
        
        .filters {
            background: rgba(0,0,0,0.3);
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filters select, .filters button {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
        }
        
        .attacks-timeline {
            max-height: 400px;
            overflow-y: auto;
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
            padding: 15px;
        }
        
        .attack-entry {
            background: rgba(255,255,255,0.05);
            margin: 5px 0;
            padding: 10px;
            border-radius: 5px;
            border-left: 3px solid;
        }
        
        .mitre-heatmap {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }
        
        .tactic-cell {
            background: rgba(0,0,0,0.3);
            padding: 15px 10px;
            text-align: center;
            border-radius: 8px;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .tactic-cell:hover { transform: scale(1.05); }
        .tactic-count { font-size: 1.2em; font-weight: bold; margin-top: 5px; }
        
        .stat-bar {
            background: rgba(0,0,0,0.2);
            height: 25px;
            border-radius: 12px;
            margin: 5px 0;
            position: relative;
            overflow: hidden;
        }
        
        .stat-fill {
            background: linear-gradient(90deg, #00ff88, #0099cc);
            height: 100%;
            border-radius: 12px;
            transition: width 0.3s;
        }
        
        .stat-label {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.9em;
            font-weight: bold;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .pulse-red { animation: pulseRed 0.5s ease; }
        @keyframes pulseRed {
            from { background-color: rgba(255,75,87,0.2); }
            to { background-color: transparent; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Threat Intelligence Dashboard</h1>
        <p>Real-time MITRE ATT&CK mapping and attack attribution</p>
    </div>

    <div class="container">
        <div class="filters">
            <label>Time Range:</label>
            <select onchange="updateFilter('hours', this.value)">
                <option value="1" <?= $hours == 1 ? 'selected' : '' ?>>Last Hour</option>
                <option value="6" <?= $hours == 6 ? 'selected' : '' ?>>Last 6 Hours</option>
                <option value="24" <?= $hours == 24 ? 'selected' : '' ?>>Last 24 Hours</option>
                <option value="168" <?= $hours == 168 ? 'selected' : '' ?>>Last Week</option>
            </select>

            <label>Severity Filter:</label>
            <select onchange="updateFilter('severity', this.value)">
                <option value="" <?= !$severity_filter ? 'selected' : '' ?>>All Severities</option>
                <option value="Critical" <?= $severity_filter == 'Critical' ? 'selected' : '' ?>>Critical</option>
                <option value="High" <?= $severity_filter == 'High' ? 'selected' : '' ?>>High</option>
                <option value="Medium" <?= $severity_filter == 'Medium' ? 'selected' : '' ?>>Medium</option>
                <option value="Low" <?= $severity_filter == 'Low' ? 'selected' : '' ?>>Low</option>
            </select>

            <button onclick="window.location.reload()">Refresh</button>
        </div>

        <div class="dashboard-grid">
            <!-- Attack Summary -->
            <div class="widget">
                <h3>Attack Summary</h3>
                <div class="metric-large"><?= count($attacks) ?></div>
                <p style="text-align: center;">Total Attacks Detected</p>
                
                <div style="margin: 20px 0;">
                    <?php foreach ($severity_count as $sev => $count): ?>
                        <div class="stat-bar" data-severity="<?= $sev ?>">
                            <div class="stat-fill" style="width: <?= $count > 0 ? ($count / count($attacks) * 100) : 0 ?>%"></div>
                            <div class="stat-label severity-<?= strtolower($sev) ?>"><?= $sev ?>: <?= $count ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- MITRE ATT&CK Tactics Heatmap -->
            <div class="widget" style="grid-column: span 2;">
                <h3>MITRE ATT&CK Tactics Heatmap</h3>
                <div class="mitre-heatmap">
                    <?php 
                    $tactic_colors = [
                        'Initial Access' => '#ff4757',
                        'Execution' => '#ff6b35', 
                        'Persistence' => '#ffa726',
                        'Privilege Escalation' => '#ff7043',
                        'Defense Evasion' => '#ab47bc',
                        'Credential Access' => '#5c6bc0',
                        'Discovery' => '#42a5f5',
                        'Collection' => '#26c6da',
                        'Exfiltration' => '#66bb6a',
                        'Command and Control' => '#9ccc65'
                    ];
                    
                    foreach ($tactic_colors as $tactic => $color): 
                        $count = $tactics_count[$tactic] ?? 0;
                    ?>
                        <div class="tactic-cell" style="border-color: <?= $color ?>;">
                            <div style="color: <?= $color ?>; font-weight: bold; font-size: 0.9em;"><?= $tactic ?></div>
                            <div class="tactic-count" style="color: <?= $color ?>;"><?= $count ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Top TTPs -->
            <div class="widget">
                <h3>Top MITRE TTPs</h3>
                <?php $i = 0; foreach (array_slice($ttp_count, 0, 10, true) as $ttp_id => $data): ?>
                    <div class="ttp-item">
                        <div>
                            <span class="ttp-id"><?= $ttp_id ?></span>
                            <span class="tactic-badge"><?= $data['tactic'] ?></span>
                            <span style="float: right; font-weight: bold;"><?= $data['count'] ?></span>
                        </div>
                        <div style="font-size: 0.9em; margin-top: 5px; opacity: 0.9;">
                            <?= $data['name'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Threat Actors / Attribution -->
            <div class="widget">
                <h3>Attack Attribution</h3>
                <?php foreach (array_slice($attribution_stats, 0, 10, true) as $tool => $count): ?>
                    <div class="tool-item">
                        <span><?= htmlspecialchars($tool) ?></span>
                        <span style="float: right; font-weight: bold;"><?= $count ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Top Attacking IPs -->
            <div class="widget">
                <h3>Top Attacking IPs</h3>
                <?php foreach (array_slice($ip_stats, 0, 10, true) as $ip => $stats): ?>
                    <div class="ip-item">
                        <div>
                            <span style="font-family: monospace; font-weight: bold;"><?= $ip ?></span>
                            <span class="severity-<?= strtolower($stats['severity']) ?>" style="float: right;"><?= $stats['severity'] ?></span>
                        </div>
                        <div style="font-size: 0.8em; opacity: 0.8; margin-top: 5px;">
                            <?= $stats['count'] ?> attacks | First: <?= $stats['first_seen'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent Attacks Timeline -->
            <div class="widget" style="grid-column: span 2;">
                <h3>Recent Attacks Timeline</h3>
                <div class="attacks-timeline">
                    <?php foreach (array_slice($attacks, 0, 50) as $attack): 
                        $severity_class = 'severity-' . strtolower($attack['severity']);
                        $border_color = ['Low' => '#66bb6a', 'Medium' => '#ffa726', 'High' => '#ff6b35', 'Critical' => '#ff4757'][$attack['severity']];
                    ?>
                        <div class="attack-entry" style="border-left-color: <?= $border_color ?>;">
                            <div>
                                <span class="<?= $severity_class ?>"><?= $attack['severity'] ?></span>
                                <span style="font-family: monospace;"><?= $attack['ip'] ?></span>
                                <span style="float: right; font-size: 0.9em;"><?= $attack['ts'] ?></span>
                            </div>
                            <div style="margin: 5px 0; font-size: 0.9em;">
                                <strong><?= $attack['method'] ?></strong> <?= htmlspecialchars(substr($attack['path'], 0, 50)) ?>
                            </div>
                            <div style="font-size: 0.8em; opacity: 0.8;">
                                Tags: <?= $attack['tags'] ?: 'None' ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateFilter(param, value) {
            const url = new URL(window.location);
            url.searchParams.set(param, value);
            window.location = url;
        }
        
        // Real-time updates every 5 seconds
        async function realtimeUpdate() {
            try {
                const response = await fetch(window.location.href);
                if (response.ok) {
                    // Only update dynamic content sections
                    updateDashboardStats();
                }
            } catch (error) {
                console.log('Update check failed, will retry...');
            }
            setTimeout(realtimeUpdate, 5000);
        }
        
        async function updateDashboardStats() {
            try {
                const response = await fetch('threat-api.php?action=live_stats&hours=' + getTimeRange());
                const data = await response.json();
                
                // Update counters
                document.querySelector('.metric-large').textContent = data.total_attacks || 0;
                
                // Update severity bars
                const severities = ['Critical', 'High', 'Medium', 'Low'];
                severities.forEach(sev => {
                    const count = data.severity_count[sev] || 0;
                    const percentage = data.total_attacks > 0 ? (count / data.total_attacks * 100) : 0;
                    const bar = document.querySelector(`[data-severity="${sev}"]`);
                    if (bar) {
                        bar.querySelector('.stat-fill').style.width = percentage + '%';
                        bar.querySelector('.stat-label').textContent = `${sev}: ${count}`;
                    }
                });
                
                // Flash notification for new attacks
                if (data.new_attacks > 0) {
                    showNotification(`${data.new_attacks} new attacks detected!`);
                }
                
            } catch (error) {
                console.error('Failed to update stats:', error);
            }
        }
        
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed; top: 20px; right: 20px; z-index: 9999;
                background: rgba(255,75,87,0.9); color: white; padding: 15px 20px;
                border-radius: 8px; font-weight: bold; animation: slideIn 0.5s ease;
            `;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.5s ease';
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }
        
        function getTimeRange() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('hours') || '24';
        }
        
        // Start real-time updates
        setTimeout(realtimeUpdate, 5000);
    </script>
</body>
</html>