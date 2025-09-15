<?php
include('../includes/init.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ping Tool</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Ping a device</h1>
        <p>Enter an IP address to ping. This tool is vulnerable to command injection.</p>
        <form method="GET" action="ping.php">
            <input type="text" name="ip" placeholder="e.g. 8.8.8.8">
            <button type="submit">Ping</button>
        </form>
        <pre>
<?php
if (isset($_GET['ip'])) {
    $ip = $_GET['ip'];
    // Vulnerable to command injection
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $cmd = "ping " . $ip;
    } else {
        $cmd = "ping -c 4 " . $ip;
    }
    echo shell_exec($cmd);
}
?>
        </pre>
        <p><a href="index.php">Back to Home</a></p>
    </div>
</body>
</html>
