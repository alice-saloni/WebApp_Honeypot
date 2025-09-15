<?php
// Polyfill for getallheaders if not available
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// Enhanced threat intelligence tagging with MITRE ATT&CK mapping
require_once __DIR__ . '/mitre_mapper.php';

function hp_enhanced_threat_analysis($query, $body, $headers, $ip, $path, $user_agent) {
    $request_data = [
        'query' => $query,
        'body' => $body, 
        'headers' => $headers,
        'ip' => $ip,
        'path' => $path,
        'user_agent' => $user_agent
    ];
    
    // MITRE ATT&CK TTP Analysis
    $detected_ttps = MitreTTPMapper::analyzeTTP($request_data);
    
    // Legacy simple tags for backward compatibility  
    $simple_tags = hp_simple_tag($query, $body, $headers);
    
    // Threat severity calculation
    $severity = hp_calculate_severity($detected_ttps, $request_data);
    
    // Attack attribution hints
    $attribution = hp_analyze_attribution($request_data);
    
    return [
        'ttps' => $detected_ttps,
        'simple_tags' => $simple_tags,
        'severity' => $severity,
        'attribution' => $attribution,
        'tactics' => MitreTTPMapper::getTacticSummary($detected_ttps)
    ];
}

// Legacy function for backward compatibility
function hp_simple_tag($query, $body, $headers) {
    $tags = [];
    $patterns = [
        'SQLI' => '/\bunion\s+select\b|\bor\s+1=1\b|\bsleep\s*\(|\bload_file\s*\(/i',
        'XSS' => '/<script\b|onerror\s*=|onload\s*=/i', 
        'TRAVERSAL' => '/\.\.\/|\.\.\\|\/etc\/passwd/i',
        'LFI' => '/php:\/\/|\/proc\/self\/environ/i',
        'CMD' => '/;id\b|;cat\b|;bash\b|;nc\b/i'
    ];

    foreach ($patterns as $tag => $regex) {
        if (preg_match($regex, $query) || preg_match($regex, $body) || preg_match($regex, json_encode($headers))) {
            $tags[] = $tag;
        }
    }

    return implode(',', $tags);
}

function hp_calculate_severity($ttps, $request_data) {
    if (empty($ttps)) return 'Low';
    
    $high_impact_tactics = ['Initial Access', 'Execution', 'Persistence', 'Privilege Escalation'];
    $score = 0;
    
    foreach ($ttps as $ttp) {
        if (in_array($ttp['tactic'], $high_impact_tactics)) {
            $score += $ttp['confidence'];
        } else {
            $score += $ttp['confidence'] * 0.5;
        }
    }
    
    if ($score >= 200) return 'Critical';
    if ($score >= 150) return 'High'; 
    if ($score >= 100) return 'Medium';
    return 'Low';
}

function hp_analyze_attribution($request_data) {
    $attribution = [];
    
    // User-Agent analysis
    $ua = strtolower($request_data['user_agent']);
    if (strpos($ua, 'curl') !== false) $attribution[] = 'Automated Tool';
    if (strpos($ua, 'python') !== false) $attribution[] = 'Python Script';
    if (strpos($ua, 'sqlmap') !== false) $attribution[] = 'SQLMap';
    if (strpos($ua, 'nikto') !== false) $attribution[] = 'Nikto Scanner';
    if (strpos($ua, 'burp') !== false) $attribution[] = 'Burp Suite';
    if (strpos($ua, 'nmap') !== false) $attribution[] = 'Nmap NSE';
    
    // Attack sophistication
    $sophistication = 'Basic';
    if (count(explode('union', strtolower($request_data['query'] . $request_data['body']))) > 2) {
        $sophistication = 'Intermediate';
    }
    if (strpos(strtolower($request_data['query'] . $request_data['body']), 'load_file') !== false) {
        $sophistication = 'Advanced';
    }
    
    return [
        'tools' => $attribution,
        'sophistication' => $sophistication,
        'fingerprint' => md5($request_data['user_agent'] . $request_data['ip'])
    ];
}

// Log request function
function hp_log_request(mysqli $mysqli, int $status = 200, string $notes = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
    $path = $_SERVER['REQUEST_URI'] ?? 'unknown';
    $query = $_SERVER['QUERY_STRING'] ?? '';
    $body = file_get_contents('php://input');
    $body = substr($body, 0, 65536); // Truncate to 64KB
    $headers = json_encode(getallheaders());
    $cookies = json_encode($_COOKIE);
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $sessionId = session_id();
    
    // Enhanced threat intelligence analysis
    $threat_intel = hp_enhanced_threat_analysis($query, $body, json_decode($headers, true), $ip, $path, $userAgent);
    
    $tags = $threat_intel['simple_tags']; // Legacy compatibility
    $ttps = json_encode($threat_intel['ttps']);
    $severity = $threat_intel['severity'];
    $attribution = json_encode($threat_intel['attribution']);
    $tactics = json_encode($threat_intel['tactics']);

    // Insert into database with enhanced fields using prepared statement
    $sql = "INSERT INTO requests (ip, method, path, query, body, headers, cookies, referer, user_agent, session_id, response_status, tags, notes, ttps, severity, attribution, tactics) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ssssssssssissssss', 
            $ip, $method, $path, $query, $body, $headers, $cookies, $referer, 
            $userAgent, $sessionId, $status, $tags, $notes, $ttps, $severity, $attribution, $tactics);
        
        if (!$stmt->execute()) {
            error_log("Database insert failed: " . $stmt->error);
        }
        $stmt->close();
    } else {
        // Log to file if DB prepare fails
        error_log("Failed to prepare SQL statement: " . $mysqli->error);
        
        $logLine = json_encode([
            'ip' => $ip,
            'method' => $method,
            'path' => $path,
            'query' => $query,
            'body' => $body,
            'headers' => $headers,
            'cookies' => $cookies,
            'referer' => $referer,
            'user_agent' => $userAgent,
            'session_id' => $sessionId,
            'response_status' => $status,
            'tags' => $tags,
            'notes' => $notes,
            'threat_intel' => [
                'ttps' => $threatIntel['ttps'] ?? [],
                'severity' => $threatIntel['severity'] ?? 'Low',
                'attribution' => $threatIntel['attribution'] ?? [],
                'tactics' => $threatIntel['tactics'] ?? []
            ]
        ]) . PHP_EOL;
        file_put_contents(__DIR__ . '/../logs/app.log', $logLine, FILE_APPEND);
    }
}
// Helper to set response status
function hp_set_response_status($code) {
    http_response_code($code);
}
