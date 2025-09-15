-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL
);

-- Insert default users
INSERT INTO users (username, password, role, email) VALUES
('admin', 'admin123', 'admin', 'admin@example.com'),
('alice', 'alice123', 'user', 'alice@example.com'),
('bob', 'bob123', 'user', 'bob@example.com');

-- Create tickets table
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create comments table for Stored XSS
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create ticket_comments table
CREATE TABLE ticket_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create uploads table
CREATE TABLE uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    path VARCHAR(255) NOT NULL,
    mime VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create enhanced requests table with threat intelligence
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip VARCHAR(64),
    method VARCHAR(10),
    path TEXT,
    query TEXT,
    body MEDIUMTEXT,
    headers MEDIUMTEXT,
    cookies MEDIUMTEXT,
    referer TEXT,
    user_agent TEXT,
    session_id VARCHAR(128),
    response_status INT DEFAULT 200,
    tags VARCHAR(255),
    notes VARCHAR(255),
    ttps JSON,
    severity ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Low',
    attribution JSON,
    tactics JSON
);

-- Insert synthetic attack rows
INSERT INTO requests (ip, method, path, query, user_agent, tags, notes)
VALUES
('192.0.2.10', 'GET', '/login.php', 'username=admin&password=%27%20OR%201%3D1--', 'sqlmap/1.7', 'SQLI', 'seed'),
('198.51.100.3', 'GET', '/search.php', 'q=%3Cscript%3Ealert(1)%3C/script%3E', 'curl/8.0', 'XSS', 'seed'),
('203.0.113.9', 'GET', '/download.php', 'file=../../etc/passwd', 'curl/7.88', 'TRAVERSAL', 'seed');
