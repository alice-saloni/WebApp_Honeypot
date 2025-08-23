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

// Simple tagging function
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
    $tags = hp_simple_tag($query, $body, json_decode($headers, true));

    // Insert into database
    $sql = "INSERT INTO requests (ip, method, path, query, body, headers, cookies, referer, user_agent, session_id, response_status, tags, notes) VALUES (" .
        "'$ip', '$method', '$path', '$query', '$body', '$headers', '$cookies', '$referer', '$userAgent', '$sessionId', $status, '$tags', '$notes'");

    if (!$mysqli->query($sql)) {
        // Log to file if DB insert fails
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
            'notes' => $notes
        ]) . PHP_EOL;
        file_put_contents(__DIR__ . '/../logs/app.log', $logLine, FILE_APPEND);
    }
}

// Helper to set response status
function hp_set_response_status($code) {
    http_response_code($code);
}
