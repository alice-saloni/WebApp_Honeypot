<?php
// Enhanced navigation with threat intelligence features
?>
<nav class="navbar">
    <div class="nav-links">
        <a href="dashboard.php" class="<?php echo isActive('dashboard.php'); ?>">📊 Dashboard</a>
        <a href="new_ticket.php" class="<?php echo isActive('new_ticket.php'); ?>">🎫 New Ticket</a>
        <a href="profile.php" class="<?php echo isActive('profile.php'); ?>">👤 Profile</a>
        <a href="search.php" class="<?php echo isActive('search.php'); ?>">🔍 Search</a>
        <a href="monitor.php" class="<?php echo isActive('monitor.php'); ?>">📈 Monitor</a>
        
        <div class="nav-dropdown">
            <a href="#" class="dropdown-toggle">🛡️ Security ▼</a>
            <div class="dropdown-menu">
                <a href="sql.php">💉 SQL Console</a>
                <a href="api.php">🔌 API Test</a>
                <a href="upload.php">📁 File Upload</a>
                <a href="cmd.php">⚡ Command Shell</a>
                <a href="ping.php">🏓 Network Ping</a>
            </div>
        </div>
        
        <div class="nav-dropdown">
            <a href="#" class="dropdown-toggle">🕵️ Threat Intel ▼</a>
            <div class="dropdown-menu">
                <a href="threat-intel.php">📊 Dashboard</a>
                <a href="threat-api.php?action=ioc_export">📋 Export IOCs</a>
                <a href="threat-api.php?action=live_stats&hours=24" target="_blank">📡 Live Stats</a>
            </div>
        </div>
        
        <div class="nav-dropdown">
            <a href="#" class="dropdown-toggle">🧪 Testing ▼</a>
            <div class="dropdown-menu">
                <a href="login-api.php">🔐 Login API</a>
                <a href="register-api.php">📝 Register API</a>
                <a href="test_db.php">🗄️ DB Test</a>
                <a href="comment.php">💬 Comments</a>
            </div>
        </div>
    </div>
    
    <div class="user-info">
        <?php if (isset($_SESSION['username'])): ?>
            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
            <a href="logout.php" class="logout-btn">🚪 Logout</a>
        <?php else: ?>
            <a href="login.php" class="logout-btn">🔑 Login</a>
        <?php endif; ?>
    </div>
</nav>