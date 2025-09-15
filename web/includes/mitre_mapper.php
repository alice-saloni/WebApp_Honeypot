<?php
// MITRE ATT&CK TTP Mapping for Honeypot Attacks
class MitreTTPMapper {
    
    public static $attack_patterns = [
        // Initial Access - T1190: Exploit Public-Facing Application
        'T1190' => [
            'name' => 'Exploit Public-Facing Application',
            'tactic' => 'Initial Access',
            'description' => 'SQL injection, command injection, file upload attacks',
            'patterns' => [
                '/union\s+select/i',
                '/\'\s*or\s+1\s*=\s*1/i',
                '/\'\s*--/i',
                '/\'\s*#/i',
                '/sleep\s*\(/i',
                '/\bload_file\s*\(/i',
                '/information_schema/i',
                '/@@version/i',
                '/show\s+tables/i',
                '/describe\s+\w+/i',
                '/<script/i',
                '/onerror\s*=/i',
                '/onload\s*=/i',
                '/;.*id\b/i',
                '/;.*whoami/i',
                '/;.*cat\s+/i',
                '/;.*ls\s+/i',
                '/\.\.\/.*etc\/passwd/i',
                '/php:\/\//i',
                '/proc\/self\/environ/i'
            ]
        ],
        
        // Execution - T1059: Command and Scripting Interpreter
        'T1059.004' => [
            'name' => 'Command and Scripting Interpreter: Unix Shell',
            'tactic' => 'Execution',
            'description' => 'Shell command execution via injection',
            'patterns' => [
                '/;\s*(ls|cat|whoami|id|pwd|uname)/i',
                '/&&\s*(ls|cat|whoami|id|pwd|uname)/i',
                '/\|\s*(ls|cat|whoami|id|pwd|uname)/i',
                '/`[^`]*`/',
                '/\$\([^)]*\)/',
                '/nc\s+-e/i',
                '/bash\s+-i/i',
                '/sh\s+-i/i'
            ]
        ],
        
        // T1059.003: Windows Command Shell  
        'T1059.003' => [
            'name' => 'Command and Scripting Interpreter: Windows Command Shell',
            'tactic' => 'Execution', 
            'description' => 'Windows command execution',
            'patterns' => [
                '/&\s*(dir|type|echo|net\s+user)/i',
                '/\|\s*(dir|type|echo|net\s+user)/i',
                '/cmd\.exe/i',
                '/powershell/i',
                '/\.bat\b/i',
                '/\.cmd\b/i'
            ]
        ],
        
        // Persistence - T1505.003: Web Shell
        'T1505.003' => [
            'name' => 'Server Software Component: Web Shell',
            'tactic' => 'Persistence',
            'description' => 'Web shell upload and execution',
            'patterns' => [
                '/\$_GET\[.*\]/',
                '/\$_POST\[.*\]/',
                '/\$_REQUEST\[.*\]/',
                '/system\s*\(/i',
                '/exec\s*\(/i',
                '/shell_exec\s*\(/i',
                '/passthru\s*\(/i',
                '/eval\s*\(/i',
                '/assert\s*\(/i',
                '/\.php\b.*\?cmd=/i'
            ]
        ],
        
        // Privilege Escalation - T1068: Exploitation for Privilege Escalation  
        'T1068' => [
            'name' => 'Exploitation for Privilege Escalation',
            'tactic' => 'Privilege Escalation',
            'description' => 'Attempting to gain admin/root privileges',
            'patterns' => [
                '/role\s*=\s*[\'"]admin[\'"]/i',
                '/admin.*insert/i',
                '/sudo\s+/i',
                '/su\s+-/i',
                '/chmod\s+\+s/i',
                '/\/etc\/passwd/i',
                '/\/etc\/shadow/i'
            ]
        ],
        
        // Defense Evasion - T1027: Obfuscated Files or Information
        'T1027' => [
            'name' => 'Obfuscated Files or Information', 
            'tactic' => 'Defense Evasion',
            'description' => 'Encoded or obfuscated payloads',
            'patterns' => [
                '/base64_decode/i',
                '/chr\s*\(/i',
                '/hex2bin/i',
                '/gzinflate/i',
                '/str_rot13/i',
                '/%[0-9a-f]{2}/i',
                '/\\\\x[0-9a-f]{2}/i',
                '/\\\\u[0-9a-f]{4}/i'
            ]
        ],
        
        // Credential Access - T1110: Brute Force
        'T1110.001' => [
            'name' => 'Brute Force: Password Guessing',
            'tactic' => 'Credential Access', 
            'description' => 'Login attempts with common passwords',
            'patterns' => [
                '/password.*admin/i',
                '/password.*123/i',
                '/password.*password/i',
                '/password.*root/i'
            ]
        ],
        
        // Discovery - T1082: System Information Discovery
        'T1082' => [
            'name' => 'System Information Discovery',
            'tactic' => 'Discovery',
            'description' => 'Gathering system/database information',
            'patterns' => [
                '/@@version/i',
                '/version\(\)/i',
                '/show\s+databases/i',
                '/information_schema/i',
                '/mysql\.user/i',
                '/pg_catalog/i',
                '/sys\./i',
                '/uname\s+-a/i',
                '/cat\s+\/proc\/version/i'
            ]
        ],
        
        // T1083: File and Directory Discovery
        'T1083' => [
            'name' => 'File and Directory Discovery',
            'tactic' => 'Discovery',
            'description' => 'Directory traversal and file enumeration',
            'patterns' => [
                '/\.\.\/.*\.\.\//i',
                '/\.\.\\\\.*\.\.\\\\/i',
                '/\/etc\//i',
                '/\/var\//i', 
                '/\/tmp\//i',
                '/C:\\\\Windows/i',
                '/C:\\\\Users/i',
                '/dir\s+/i',
                '/ls\s+-la/i'
            ]
        ],
        
        // Collection - T1005: Data from Local System
        'T1005' => [
            'name' => 'Data from Local System',
            'tactic' => 'Collection',
            'description' => 'Extracting sensitive data from database/files',
            'patterns' => [
                '/select.*password/i',
                '/select.*user/i',
                '/select.*admin/i',
                '/load_file\s*\(/i',
                '/into\s+outfile/i',
                '/into\s+dumpfile/i'
            ]
        ],
        
        // Exfiltration - T1041: Exfiltration Over C2 Channel
        'T1041' => [
            'name' => 'Exfiltration Over C2 Channel',
            'tactic' => 'Exfiltration',
            'description' => 'Attempts to send data to external servers',
            'patterns' => [
                '/fetch\s*\(/i',
                '/XMLHttpRequest/i',
                '/document\.location/i',
                '/window\.location/i',
                '/curl\s+/i',
                '/wget\s+/i'
            ]
        ]
    ];
    
    public static function analyzeTTP($request_data) {
        $detected_ttps = [];
        $query = strtolower($request_data['query'] ?? '');
        $body = strtolower($request_data['body'] ?? ''); 
        $headers = strtolower(json_encode($request_data['headers'] ?? []));
        
        $full_text = "$query $body $headers";
        
        foreach (self::$attack_patterns as $ttp_id => $ttp_data) {
            foreach ($ttp_data['patterns'] as $pattern) {
                if (preg_match($pattern, $full_text)) {
                    $detected_ttps[] = [
                        'ttp_id' => $ttp_id,
                        'name' => $ttp_data['name'],
                        'tactic' => $ttp_data['tactic'],
                        'description' => $ttp_data['description'],
                        'confidence' => self::calculateConfidence($full_text, $ttp_data['patterns'])
                    ];
                    break; // Don't double-count same TTP
                }
            }
        }
        
        return $detected_ttps;
    }
    
    private static function calculateConfidence($text, $patterns) {
        $matches = 0;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $matches++;
            }
        }
        return min(100, ($matches / count($patterns)) * 100 + 30);
    }
    
    public static function getTacticSummary($ttps) {
        $tactics = [];
        foreach ($ttps as $ttp) {
            $tactic = $ttp['tactic'];
            if (!isset($tactics[$tactic])) {
                $tactics[$tactic] = ['count' => 0, 'ttps' => []];
            }
            $tactics[$tactic]['count']++;
            $tactics[$tactic]['ttps'][] = $ttp;
        }
        return $tactics;
    }
}
?>