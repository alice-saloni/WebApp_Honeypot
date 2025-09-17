<?php
require_once '/var/www/includes/init.php';
require_once '/var/www/includes/db.php';

// Basic authentication
if ($_SERVER['PHP_AUTH_USER'] !== 'monitor' || $_SERVER['PHP_AUTH_PW'] !== 'monitor123') {
    header('WWW-Authenticate: Basic realm="Monitor Access"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Unauthorized';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Honeypot Visual Dashboard</title>
    <!-- Chart.js for visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Socket.IO for real-time updates -->
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .attack-card {
            transition: all 0.3s ease;
        }
        .attack-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold mb-8 text-gray-800">Honeypot Attack Monitor</h1>
        
        <!-- Real-time Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-2">Active Attacks</h3>
                <p class="text-3xl font-bold text-red-600" id="activeAttacks">0</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-2">Total Attacks Today</h3>
                <p class="text-3xl font-bold text-blue-600" id="totalAttacks">0</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-2">Unique Attackers</h3>
                <p class="text-3xl font-bold text-green-600" id="uniqueAttackers">0</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-2">Critical Severity</h3>
                <p class="text-3xl font-bold text-purple-600" id="criticalAttacks">0</p>
            </div>
        </div>

        <!-- Attack Timeline -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-2xl font-bold mb-4">Attack Timeline</h2>
            <div class="chart-container">
                <canvas id="timelineChart"></canvas>
            </div>
        </div>

        <!-- Attack Distribution -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-2xl font-bold mb-4">Attack Types</h2>
                <div class="chart-container">
                    <canvas id="attackTypesChart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-2xl font-bold mb-4">Severity Distribution</h2>
                <div class="chart-container">
                    <canvas id="severityChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Live Attack Feed -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold mb-4">Live Attack Feed</h2>
            <div id="attackFeed" class="space-y-4">
                <!-- Attack cards will be inserted here -->
            </div>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
</body>
</html>
