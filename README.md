# Vulnerable Bug Tracker Honeypot

## Warning
**This application is intentionally vulnerable.**
- For lab use only.
- Do not expose to the public Internet.
- Do not attack systems you do not own.

## Setup Instructions

### Prerequisites
- Docker
- Docker Compose

### Steps
1. Clone this repository.
2. Navigate to the project directory.
3. Run the following command to start the application:
   ```bash
   docker-compose up -d
   ```
4. Access the application:
   - **Bug Tracker**: [http://localhost:8080](http://localhost:8080)
   - **Monitor**: [http://localhost:8080/monitor.php](http://localhost:8080/monitor.php) (Password: `monitor123`)
   - **phpMyAdmin**: [http://localhost:8081](http://localhost:8081)

### Default Credentials
- **Admin**: `admin/admin123`
- **User 1**: `alice/alice123`
- **User 2**: `bob/bob123`

## Features
- **Bug Tracker**:
  - Login, register, create tickets, comment, upload files, and search.
- **Monitor**:
  - View and filter logged requests.
  - Live charts for requests over time, attack tags, and top IPs.
  - Raw request data view.

## Vulnerabilities
- SQL Injection
- Reflected XSS
- Stored XSS
- Insecure File Upload
- IDOR (Insecure Direct Object References)
- Weak Authentication
- Information Disclosure

## Teardown
To stop and remove the containers, run:
```bash
docker-compose down
```
