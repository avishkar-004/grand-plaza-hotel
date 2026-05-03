# Deployment Guide

## 1. Server Requirements

| Requirement | Minimum Version | Notes |
|-------------|----------------|-------|
| PHP | 8.0+ | With extensions: `pdo`, `pdo_mysql`, `mbstring`, `json`, `mysqli` |
| MySQL | 8.0+ | InnoDB engine, utf8mb4 charset |
| Web Server | Apache 2.4+ with `mod_rewrite` OR Nginx 1.18+ | |
| SSL | TLS 1.2+ | Required for HTTPS |
| Composer | Latest | For dependency installation |

Verify PHP extensions on the server:

```bash
php -m | grep -E 'pdo|pdo_mysql|mbstring|json|mysqli'
```

## 2. Deployment Steps

### 2.1 Upload Application

```bash
# On the server
cd /var/www
git clone <repo-url> hotel_management_system
cd hotel_management_system

# Install production dependencies (no dev packages)
composer install --no-dev --optimize-autoloader

# Create environment file
cp .env.example .env
# Edit .env with production values (see Section 4)
```

### 2.2 Set File Permissions

```bash
# Set ownership (adjust user/group for your web server)
sudo chown -R www-data:www-data /var/www/hotel_management_system

# Directories: 755, Files: 644
find /var/www/hotel_management_system -type d -exec chmod 755 {} \;
find /var/www/hotel_management_system -type f -exec chmod 644 {} \;

# Writable directories for the web server
chmod -R 775 /var/www/hotel_management_system/storage/logs
chmod -R 775 /var/www/hotel_management_system/storage/cache
chmod -R 775 /var/www/hotel_management_system/storage/uploads

# Protect sensitive files
chmod 640 /var/www/hotel_management_system/.env
```

### 2.3 Database Setup

```bash
# Log into MySQL as root
mysql -u root -p

# Create the database
CREATE DATABASE hotel_management_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create a dedicated application user with least-privilege access
CREATE USER 'hotel_app'@'localhost' IDENTIFIED BY '<strong-random-password>';
GRANT SELECT, INSERT, UPDATE, DELETE ON hotel_management_db.* TO 'hotel_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import the schema
mysql -u root -p hotel_management_db < database/schema_mysql.sql

# Import seed data if needed
mysql -u root -p hotel_management_db < database/seeds/*.sql

# Hash any plaintext seed passwords
php src/Utils/PasswordMigration.php
```

### 2.4 Configure the Web Server

Choose Apache OR Nginx below.

### 2.5 Verify Deployment

```bash
# Test PHP can connect to the database
php -r "
require 'vendor/autoload.php';
\$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
\$dotenv->load();
\$dsn = 'mysql:host=' . \$_ENV['DB_HOST'] . ';dbname=' . \$_ENV['DB_DATABASE'];
\$pdo = new PDO(\$dsn, \$_ENV['DB_USERNAME'], \$_ENV['DB_PASSWORD']);
echo 'Database connection OK' . PHP_EOL;
"

# Test that the site responds
curl -I https://grandplaza.in
```

## 3. Apache Configuration

### Virtual Host

Create `/etc/apache2/sites-available/hotel.conf`:

```apache
# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName grandplaza.in
    ServerAlias www.grandplaza.in
    Redirect permanent / https://grandplaza.in/
</VirtualHost>

# HTTPS virtual host
<VirtualHost *:443>
    ServerName grandplaza.in
    ServerAlias www.grandplaza.in
    DocumentRoot /var/www/hotel_management_system/public

    <Directory /var/www/hotel_management_system/public>
        AllowOverride All
        Require all granted
        Options -Indexes -FollowSymLinks
    </Directory>

    # Block access to dotfiles and sensitive directories
    <DirectoryMatch "^\.|\/\.">
        Require all denied
    </DirectoryMatch>

    # Block access to storage directory
    <Directory /var/www/hotel_management_system/storage>
        Require all denied
    </Directory>

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/grandplaza.in/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/grandplaza.in/privkey.pem

    # Security headers
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/hotel_error.log
    CustomLog ${APACHE_LOG_DIR}/hotel_access.log combined
</VirtualHost>
```

### .htaccess

Create `public/.htaccess`:

```apache
RewriteEngine On

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Route all requests through index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Block access to dotfiles
<FilesMatch "^\.">
    Require all denied
</FilesMatch>

# Disable directory listing
Options -Indexes

# Set PHP options
<IfModule mod_php.c>
    php_flag display_errors Off
    php_flag log_errors On
    php_value error_log /var/www/hotel_management_system/storage/logs/php_errors.log
</IfModule>
```

Enable the site:

```bash
sudo a2enmod rewrite ssl headers
sudo a2ensite hotel.conf
sudo a2dissite 000-default.conf
sudo apachectl configtest
sudo systemctl restart apache2
```

## 4. Nginx Configuration

Create `/etc/nginx/sites-available/hotel`:

```nginx
# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name grandplaza.in www.grandplaza.in;
    return 301 https://grandplaza.in$request_uri;
}

# HTTPS server
server {
    listen 443 ssl http2;
    server_name grandplaza.in www.grandplaza.in;
    root /var/www/hotel_management_system/public;
    index index.php;

    # SSL certificates
    ssl_certificate /etc/letsencrypt/live/grandplaza.in/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/grandplaza.in/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers on;

    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Route all requests through index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Block access to dotfiles (.env, .git, .htaccess, etc.)
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Block access to sensitive directories
    location ~ ^/(storage|config|database|src|tests|vendor) {
        deny all;
    }

    # Static file caching
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Logging
    access_log /var/log/nginx/hotel_access.log;
    error_log /var/log/nginx/hotel_error.log;
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/hotel /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
```

## 5. Production .env Settings

```env
# Application
APP_NAME="Hotel Management System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://grandplaza.in

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hotel_management_db
DB_USERNAME=hotel_app
DB_PASSWORD=<strong-random-password>

# Security -- ALL protections enabled
SECURITY_MODE=secure
CSRF_ENABLED=true
FORCE_HTTPS=true

# Session
SESSION_LIFETIME=120
SESSION_DRIVER=file
SESSION_SECURE=true

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX_REQUESTS=100
RATE_LIMIT_WINDOW=60

# Password Hashing
PASSWORD_ALGO=BCRYPT
PASSWORD_COST=12

# CORS
CORS_ENABLED=false
CORS_ALLOWED_ORIGINS=https://grandplaza.in

# Logging
LOG_CHANNEL=single
LOG_LEVEL=warning
LOG_PATH=storage/logs/app.log

# File Upload
MAX_UPLOAD_SIZE=5242880
ALLOWED_EXTENSIONS=jpg,jpeg,png,pdf
```

**Critical differences from development:**

| Setting | Development | Production |
|---------|------------|------------|
| `APP_ENV` | `development` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `SECURITY_MODE` | `vulnerable` or `secure` | `secure` |
| `CSRF_ENABLED` | `false` or `true` | `true` |
| `FORCE_HTTPS` | `false` | `true` |
| `SESSION_SECURE` | `false` | `true` |
| `RATE_LIMIT_ENABLED` | `false` | `true` |
| `LOG_LEVEL` | `debug` | `warning` |

## 6. SSL Certificate Setup

Using Let's Encrypt (free):

```bash
# Install Certbot
sudo apt install certbot

# For Apache
sudo apt install python3-certbot-apache
sudo certbot --apache -d grandplaza.in -d www.grandplaza.in

# For Nginx
sudo apt install python3-certbot-nginx
sudo certbot --nginx -d grandplaza.in -d www.grandplaza.in

# Verify auto-renewal
sudo certbot renew --dry-run

# Renewal runs automatically via systemd timer or cron
```

## 7. Security Checklist

Run through this checklist before going live:

### Application Settings

- [ ] `APP_DEBUG=false` -- never expose stack traces in production
- [ ] `APP_ENV=production`
- [ ] `SECURITY_MODE=secure` -- enables all security protections
- [ ] `CSRF_ENABLED=true` -- protects against cross-site request forgery
- [ ] `FORCE_HTTPS=true` -- redirects all HTTP to HTTPS
- [ ] `SESSION_SECURE=true` -- session cookie only sent over HTTPS
- [ ] `RATE_LIMIT_ENABLED=true` -- prevents brute-force attacks

### Server Configuration

- [ ] HTTPS enabled with valid SSL certificate
- [ ] HTTP redirects to HTTPS (301)
- [ ] HSTS header set (`Strict-Transport-Security`)
- [ ] `.env` file not accessible via web (test: `curl https://grandplaza.in/.env` should return 403)
- [ ] `storage/` directory not accessible via web
- [ ] `config/`, `src/`, `database/`, `tests/`, `vendor/` not accessible via web
- [ ] Directory listing disabled (`Options -Indexes`)
- [ ] `X-Content-Type-Options: nosniff` header present
- [ ] `X-Frame-Options: DENY` header present
- [ ] PHP `display_errors` set to `Off`
- [ ] PHP `expose_php` set to `Off` in php.ini

### Database

- [ ] Dedicated database user (`hotel_app`) with least-privilege permissions (SELECT, INSERT, UPDATE, DELETE only)
- [ ] Strong, randomly generated database password
- [ ] MySQL root account not used by the application
- [ ] MySQL not listening on external interfaces (bind-address = 127.0.0.1)
- [ ] MySQL slow query log enabled

### File Permissions

- [ ] Directories: `755`
- [ ] Files: `644`
- [ ] `.env`: `640` (readable only by owner and web server group)
- [ ] `storage/logs/`, `storage/cache/`, `storage/uploads/`: `775`
- [ ] No world-writable files

### Passwords and Seeds

- [ ] All seed/default passwords have been hashed (`php src/Utils/PasswordMigration.php`)
- [ ] Default admin password changed after first login
- [ ] `PASSWORD_COST=12` or higher for bcrypt

## 8. PHP Production Configuration

Recommended `php.ini` settings for production:

```ini
; Disable error display
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/error.log

; Hide PHP version
expose_php = Off

; Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = Strict

; File upload limits
upload_max_filesize = 5M
post_max_size = 6M

; Execution limits
max_execution_time = 30
memory_limit = 128M

; Disable dangerous functions
disable_functions = exec,passthru,shell_exec,system,proc_open,popen
```

## 9. Monitoring

### Application Logs

```bash
# Watch application logs in real time
tail -f /var/www/hotel_management_system/storage/logs/app.log

# Watch PHP error logs
tail -f /var/www/hotel_management_system/storage/logs/php_errors.log

# Search for errors in the last 24 hours
find /var/www/hotel_management_system/storage/logs/ -name "*.log" -mtime -1 -exec grep -l "ERROR\|CRITICAL" {} \;
```

### Database Monitoring

```bash
# Check for slow queries (enable slow query log in MySQL first)
# In /etc/mysql/mysql.conf.d/mysqld.cnf:
#   slow_query_log = 1
#   slow_query_log_file = /var/log/mysql/slow.log
#   long_query_time = 2

# Monitor active connections
mysql -u root -p -e "SHOW PROCESSLIST;"

# Check database size
mysql -u root -p -e "SELECT table_name, ROUND(data_length/1024/1024, 2) AS 'Size (MB)' FROM information_schema.tables WHERE table_schema = 'hotel_management_db' ORDER BY data_length DESC;"
```

### Security Event Monitoring

```bash
# Query the activity_logs table for suspicious events
mysql -u root -p hotel_management_db -e "
SELECT action, username, ip_address, created_at
FROM activity_logs
WHERE action IN ('login_failed', 'unauthorized_access', 'csrf_violation')
ORDER BY created_at DESC
LIMIT 50;
"
```

### Web Server Logs

```bash
# Apache
tail -f /var/log/apache2/hotel_error.log
tail -f /var/log/apache2/hotel_access.log

# Nginx
tail -f /var/log/nginx/hotel_error.log
tail -f /var/log/nginx/hotel_access.log
```

## 10. Backup Strategy

### Automated Database Backup

Create `/opt/scripts/hotel_backup.sh`:

```bash
#!/bin/bash

# Configuration
BACKUP_DIR="/opt/backups/hotel"
DB_NAME="hotel_management_db"
DB_USER="root"
DB_PASS="<root-password>"
RETENTION_DAYS=30

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Timestamp
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Database backup
mysqldump -u "$DB_USER" -p"$DB_PASS" \
    --single-transaction \
    --routines \
    --triggers \
    "$DB_NAME" | gzip > "$BACKUP_DIR/db_${TIMESTAMP}.sql.gz"

# Full application backup (excluding vendor, logs, and cache)
tar -czf "$BACKUP_DIR/app_${TIMESTAMP}.tar.gz" \
    --exclude='vendor' \
    --exclude='storage/logs/*' \
    --exclude='storage/cache/*' \
    --exclude='.git' \
    -C /var/www hotel_management_system

# Backup the .env file separately (encrypted)
cp /var/www/hotel_management_system/.env "$BACKUP_DIR/env_${TIMESTAMP}.bak"
chmod 600 "$BACKUP_DIR/env_${TIMESTAMP}.bak"

# Remove backups older than retention period
find "$BACKUP_DIR" -type f -mtime +${RETENTION_DAYS} -delete

# Log result
echo "[$(date)] Backup completed: db_${TIMESTAMP}.sql.gz, app_${TIMESTAMP}.tar.gz" >> "$BACKUP_DIR/backup.log"
```

Schedule with cron:

```bash
# Make the script executable
chmod +x /opt/scripts/hotel_backup.sh

# Add to crontab (daily at 2:00 AM)
crontab -e
# Add this line:
0 2 * * * /opt/scripts/hotel_backup.sh
```

### Manual Backup Commands

```bash
# Quick database dump
mysqldump -u root -p hotel_management_db > backup_$(date +%Y%m%d).sql

# Compressed database dump
mysqldump -u root -p --single-transaction hotel_management_db | gzip > backup_$(date +%Y%m%d).sql.gz

# Full application archive
tar -czf hotel_backup_$(date +%Y%m%d).tar.gz \
    --exclude=vendor \
    --exclude='storage/logs/*' \
    --exclude='storage/cache/*' \
    /var/www/hotel_management_system/
```

### Restore from Backup

```bash
# Restore database
gunzip < /opt/backups/hotel/db_20260425_020000.sql.gz | mysql -u root -p hotel_management_db

# Or from an uncompressed dump
mysql -u root -p hotel_management_db < backup_20260425.sql

# Restore application files
cd /var/www
tar -xzf /opt/backups/hotel/app_20260425_020000.tar.gz

# Reinstall dependencies after restore
cd /var/www/hotel_management_system
composer install --no-dev --optimize-autoloader

# Restore .env
cp /opt/backups/hotel/env_20260425_020000.bak /var/www/hotel_management_system/.env
chmod 640 /var/www/hotel_management_system/.env

# Fix permissions
sudo chown -R www-data:www-data /var/www/hotel_management_system
```

## 11. Updating the Application

```bash
cd /var/www/hotel_management_system

# Pull latest code
git pull origin main

# Install any new dependencies
composer install --no-dev --optimize-autoloader

# Run any new database migrations
mysql -u root -p hotel_management_db < database/migrations/<new_migration>.sql

# Clear application cache
rm -rf storage/cache/*

# Restart PHP-FPM to clear opcache
sudo systemctl restart php8.2-fpm

# Verify the site is working
curl -I https://grandplaza.in
```

## 12. Rollback Procedure

If a deployment causes issues:

```bash
# 1. Restore the previous application version
cd /var/www
mv hotel_management_system hotel_management_system_broken
tar -xzf /opt/backups/hotel/app_<previous_date>.tar.gz

# 2. Restore the .env file
cp /opt/backups/hotel/env_<previous_date>.bak /var/www/hotel_management_system/.env

# 3. Restore the database if schema changed
gunzip < /opt/backups/hotel/db_<previous_date>.sql.gz | mysql -u root -p hotel_management_db

# 4. Reinstall dependencies and fix permissions
cd /var/www/hotel_management_system
composer install --no-dev --optimize-autoloader
sudo chown -R www-data:www-data /var/www/hotel_management_system

# 5. Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx   # or apache2

# 6. Verify
curl -I https://grandplaza.in
```
