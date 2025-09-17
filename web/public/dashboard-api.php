<?php
require_once '/var/www/includes/init.php';
require_once '/var/www/includes/db.php';

header('Content-Type: application/json');

if ($_GET['action'] === 'dashboard_stats') {
    $stats = [];
    $charts = [];
    
    // Get active attacks (last 5 minutes)
    $activeQuery = "SELECT COUNT(*) as count FROM requests 
                   WHERE ts >= NOW() - INTERVAL 5 MINUTE";
    $result = $db->query($activeQuery);
    $stats['activeAttacks'] = $result->fetch_assoc()['count'];

    // Get total attacks today
    $todayQuery = "SELECT COUNT(*) as count FROM requests 
                  WHERE DATE(ts) = CURDATE()";
    $result = $db->query($todayQuery);
    $stats['totalAttacks'] = $result->fetch_assoc()['count'];

    // Get unique attackers
    $uniqueQuery = "SELECT COUNT(DISTINCT ip) as count FROM requests 
                   WHERE DATE(ts) = CURDATE()";
    $result = $db->query($uniqueQuery);
    $stats['uniqueAttackers'] = $result->fetch_assoc()['count'];

    // Get critical severity attacks
    $criticalQuery = "SELECT COUNT(*) as count FROM requests 
                     WHERE DATE(ts) = CURDATE() AND severity = 'Critical'";
    $result = $db->query($criticalQuery);
    $stats['criticalAttacks'] = $result->fetch_assoc()['count'];

    // Get attack types distribution
    $typesQuery = "SELECT 
        CASE 
            WHEN ttps LIKE '%T1190%' THEN 'SQL Injection'
            WHEN ttps LIKE '%T1059%' THEN 'Command Injection'
            WHEN ttps LIKE '%T1505%' THEN 'Upload Attack'
            WHEN ttps LIKE '%T1213%' THEN 'Data Discovery'
            ELSE 'Other'
        END as attack_type,
        COUNT(*) as count
        FROM requests 
        WHERE DATE(ts) = CURDATE()
        GROUP BY attack_type";
    $result = $db->query($typesQuery);
    $charts['attackTypes'] = [];
    while ($row = $result->fetch_assoc()) {
        $charts['attackTypes'][] = $row;
    }

    // Get severity distribution
    $severityQuery = "SELECT severity, COUNT(*) as count 
                     FROM requests 
                     WHERE DATE(ts) = CURDATE()
                     GROUP BY severity";
    $result = $db->query($severityQuery);
    $charts['severity'] = [];
    while ($row = $result->fetch_assoc()) {
        $charts['severity'][] = $row;
    }

    // Get timeline data (last hour)
    $timelineQuery = "SELECT 
        DATE_FORMAT(ts, '%H:%i') as time,
        COUNT(*) as count
        FROM requests 
        WHERE ts >= NOW() - INTERVAL 1 HOUR
        GROUP BY time
        ORDER BY time";
    $result = $db->query($timelineQuery);
    $charts['timeline'] = [];
    while ($row = $result->fetch_assoc()) {
        $charts['timeline'][] = $row;
    }

    echo json_encode([
        'stats' => $stats,
        'charts' => $charts
    ]);
    exit;
}

// Get detailed attack information
if ($_GET['action'] === 'attack_details') {
    $id = (int)$_GET['id'];
    
    $query = "SELECT r.*, 
              GROUP_CONCAT(DISTINCT m.description SEPARATOR '\n') as mitre_descriptions
              FROM requests r
              LEFT JOIN mitre_techniques m ON 
                  JSON_CONTAINS(r.ttps, CONCAT('\"', m.technique_id, '\"'))
              WHERE r.id = ?
              GROUP BY r.id";
              
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Add explanation in simple terms
        $row['simple_explanation'] = generateSimpleExplanation($row);
        // Add attack steps
        $row['attack_steps'] = generateAttackSteps($row);
        echo json_encode($row);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Attack not found']);
    }
    exit;
}

function generateSimpleExplanation($attack) {
    $explanation = "An attacker from IP {$attack['ip']} tried to ";
    
    if (strpos($attack['ttps'], 'T1190') !== false) {
        if (strpos($attack['query'], 'union') !== false) {
            $explanation .= "steal data from the database using SQL injection. They tried to trick the database into showing information it shouldn't.";
        } elseif (strpos($attack['query'], '../') !== false) {
            $explanation .= "access files on the server that they shouldn't be able to see.";
        } else {
            $explanation .= "exploit the application using common web vulnerabilities.";
        }
    } elseif (strpos($attack['ttps'], 'T1059') !== false) {
        $explanation .= "run system commands on the server. This could give them control over the server if successful.";
    }
    
    return $explanation;
}

function generateAttackSteps($attack) {
    $steps = [];
    
    // SQL Injection steps
    if (strpos($attack['query'], 'union') !== false) {
        $steps[] = "Attacker attempted to find injectable parameters";
        $steps[] = "Used UNION SELECT to try combining queries";
        $steps[] = "Attempted to extract database information";
    }
    
    // Command Injection steps
    elseif (preg_match('/;\s*(ls|cat|whoami)/', $attack['query'])) {
        $steps[] = "Injected command separator (;)";
        $steps[] = "Attempted to run system commands";
        $steps[] = "Tried to gather system information";
    }
    
    // File inclusion/path traversal
    elseif (strpos($attack['query'], '../') !== false) {
        $steps[] = "Used directory traversal sequence (../)";
        $steps[] = "Attempted to access sensitive files";
        $steps[] = "Tried to read system configuration";
    }
    
    return $steps;
}
