<?php
require_once '/var/www/includes/init.php';
require_once '/var/www/includes/db.php';

header('Content-Type: application/json');

// Simple IP geolocation service (using ip-api.com for demo)
function getIPGeolocation($ip) {
    // Mock data for common IPs or use real API
    $geoData = [
        '127.0.0.1' => ['country' => 'Local', 'city' => 'Localhost', 'lat' => 0, 'lon' => 0, 'isp' => 'Local Network'],
        '10.0.0.1' => ['country' => 'Internal', 'city' => 'LAN', 'lat' => 0, 'lon' => 0, 'isp' => 'Private Network'],
        '192.168.1.1' => ['country' => 'Internal', 'city' => 'LAN', 'lat' => 0, 'lon' => 0, 'isp' => 'Private Network']
    ];
    
    if (isset($geoData[$ip])) {
        return $geoData[$ip];
    }
    
    // For real IPs, you would call ip-api.com or similar
    // $data = file_get_contents("http://ip-api.com/json/$ip");
    // return json_decode($data, true);
    
    // Mock foreign IP data
    $mockCountries = ['United States', 'China', 'Russia', 'Germany', 'Brazil', 'India', 'France', 'United Kingdom'];
    $mockCities = ['New York', 'Beijing', 'Moscow', 'Berlin', 'São Paulo', 'Mumbai', 'Paris', 'London'];
    $mockISPs = ['Suspicious ISP', 'VPN Provider', 'Tor Network', 'Cloud Hosting', 'Botnet Node'];
    
    return [
        'country' => $mockCountries[array_rand($mockCountries)],
        'city' => $mockCities[array_rand($mockCities)],
        'lat' => rand(-90, 90),
        'lon' => rand(-180, 180),
        'isp' => $mockISPs[array_rand($mockISPs)]
    ];
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'geolocate':
        $ip = $_GET['ip'] ?? '';
        if ($ip) {
            echo json_encode(getIPGeolocation($ip));
        } else {
            echo json_encode(['error' => 'No IP provided']);
        }
        break;
        
    case 'threat_map':
        // Get attack data for map visualization
        $hours = (int)($_GET['hours'] ?? 24);
        $query = "SELECT ip, COUNT(*) as attack_count, MAX(severity) as max_severity, 
                  MIN(ts) as first_attack, MAX(ts) as last_attack 
                  FROM requests 
                  WHERE ts >= NOW() - INTERVAL $hours HOUR 
                  GROUP BY ip 
                  ORDER BY attack_count DESC";
        
        $result = $db->query($query);
        $threats = [];
        
        while ($row = $result->fetch_assoc()) {
            $geo = getIPGeolocation($row['ip']);
            $threats[] = [
                'ip' => $row['ip'],
                'attacks' => $row['attack_count'],
                'severity' => $row['max_severity'],
                'first_attack' => $row['first_attack'],
                'last_attack' => $row['last_attack'],
                'location' => $geo
            ];
        }
        
        echo json_encode($threats);
        break;
        
    case 'attack_timeline':
        // Get attack timeline data
        $hours = (int)($_GET['hours'] ?? 24);
        $query = "SELECT DATE_FORMAT(ts, '%Y-%m-%d %H:00:00') as hour_bucket, 
                  COUNT(*) as attack_count,
                  COUNT(CASE WHEN severity = 'Critical' THEN 1 END) as critical_count,
                  COUNT(CASE WHEN severity = 'High' THEN 1 END) as high_count,
                  COUNT(CASE WHEN severity = 'Medium' THEN 1 END) as medium_count,
                  COUNT(CASE WHEN severity = 'Low' THEN 1 END) as low_count
                  FROM requests 
                  WHERE ts >= NOW() - INTERVAL $hours HOUR 
                  GROUP BY hour_bucket 
                  ORDER BY hour_bucket";
        
        $result = $db->query($query);
        $timeline = [];
        
        while ($row = $result->fetch_assoc()) {
            $timeline[] = $row;
        }
        
        echo json_encode($timeline);
        break;
        
    case 'live_stats':
        // Real-time statistics for dashboard updates
        $hours = (int)($_GET['hours'] ?? 24);
        $lastCheck = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-1 minute'));
        
        // Get current stats
        $query = "SELECT COUNT(*) as total_attacks,
                  COUNT(CASE WHEN severity = 'Critical' THEN 1 END) as critical_count,
                  COUNT(CASE WHEN severity = 'High' THEN 1 END) as high_count,
                  COUNT(CASE WHEN severity = 'Medium' THEN 1 END) as medium_count,
                  COUNT(CASE WHEN severity = 'Low' THEN 1 END) as low_count,
                  COUNT(CASE WHEN ts > '$lastCheck' THEN 1 END) as new_attacks
                  FROM requests 
                  WHERE ts >= NOW() - INTERVAL $hours HOUR";
        
        $result = $db->query($query);
        $stats = $result->fetch_assoc();
        
        $response = [
            'total_attacks' => (int)$stats['total_attacks'],
            'new_attacks' => (int)$stats['new_attacks'],
            'severity_count' => [
                'Critical' => (int)$stats['critical_count'],
                'High' => (int)$stats['high_count'],
                'Medium' => (int)$stats['medium_count'],
                'Low' => (int)$stats['low_count']
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response);
        break;
        
    case 'ioc_export':
        // Export Indicators of Compromise (IOCs)
        $hours = (int)($_GET['hours'] ?? 24);
        $format = $_GET['format'] ?? 'json';
        
        // Get malicious IPs
        $query = "SELECT DISTINCT ip, COUNT(*) as attack_count, 
                  GROUP_CONCAT(DISTINCT tags) as attack_types,
                  MAX(severity) as max_severity
                  FROM requests 
                  WHERE ts >= NOW() - INTERVAL $hours HOUR 
                  AND severity IN ('High', 'Critical')
                  GROUP BY ip";
        
        $result = $db->query($query);
        $iocs = ['ips' => [], 'generated' => date('Y-m-d H:i:s')];
        
        while ($row = $result->fetch_assoc()) {
            $iocs['ips'][] = [
                'ip' => $row['ip'],
                'threat_level' => $row['max_severity'],
                'attack_count' => $row['attack_count'],
                'attack_types' => explode(',', $row['attack_types']),
                'confidence' => 'high'
            ];
        }
        
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="iocs_' . date('Y-m-d_H-i-s') . '.csv"');
            
            echo "IP,Threat_Level,Attack_Count,Confidence\n";
            foreach ($iocs['ips'] as $ioc) {
                echo "{$ioc['ip']},{$ioc['threat_level']},{$ioc['attack_count']},{$ioc['confidence']}\n";
            }
        } else {
            echo json_encode($iocs);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>