# VPS Setup Guide for Artisan Kala API

This guide provides step-by-step instructions for setting up a VPS server to host the Artisan Kala Laravel API backend.

## Prerequisites

- Ubuntu 24.04 LTS VPS with at least 2GB RAM
- Root or sudo access
- Domain name pointing to your VPS IP (e.g., api.artisankala.com)
- SSH access to the server

## Table of Contents

1. [Initial Server Setup](#1-initial-server-setup)
2. [Install Required Software](#2-install-required-software)
3. [Configure MySQL](#3-configure-mysql)
4. [Configure Redis](#4-configure-redis)
5. [Configure PHP-FPM](#5-configure-php-fpm)
6. [Configure Nginx](#6-configure-nginx)
7. [SSL Certificate with Let's Encrypt](#7-ssl-certificate-with-lets-encrypt)
8. [Deploy Laravel Application](#8-deploy-laravel-application)
9. [Configure Supervisor for Queue Workers](#9-configure-supervisor-for-queue-workers)
10. [Configure Laravel Scheduler](#10-configure-laravel-scheduler)
11. [Security Hardening](#11-security-hardening)
12. [Monitoring and Maintenance](#12-monitoring-and-maintenance)

---

## 1. Initial Server Setup

### Update System Packages

```bash
sudo apt update && sudo apt upgrade -y
```

### Create Deploy User

```bash
# Create a dedicated user for deployment
sudo adduser deploy
sudo usermod -aG sudo deploy

# Switch to deploy user
su - deploy
```

### Configure SSH Key Authentication

```bash
# On your local machine, copy your SSH key to the server
ssh-copy-id deploy@your-server-ip

# On the server, disable password authentication (optional but recommended)
sudo nano /etc/ssh/sshd_config
# Set: PasswordAuthentication no
sudo systemctl restart sshd
```

### Configure Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

---

## 2. Install Required Software

### Install Nginx

```bash
sudo apt install nginx -y
sudo systemctl enable nginx
sudo systemctl start nginx
```

### Install PHP 8.3 and Extensions

```bash
# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.3 and required extensions
sudo apt install php8.3-fpm php8.3-cli php8.3-mysql php8.3-redis \
    php8.3-mbstring php8.3-xml php8.3-bcmath php8.3-curl \
    php8.3-zip php8.3-gd php8.3-intl php8.3-imagick -y

# Verify PHP installation
php -v
```

### Install MySQL 8.0

```bash
sudo apt install mysql-server -y
sudo systemctl enable mysql
sudo systemctl start mysql

# Secure MySQL installation
sudo mysql_secure_installation
```

### Install Redis 7.0

```bash
sudo apt install redis-server -y
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Verify Redis is running
redis-cli ping
# Should return: PONG
```

### Install Composer

```bash
cd ~
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

# Verify Composer installation
composer --version
```

### Install Supervisor

```bash
sudo apt install supervisor -y
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

### Install Certbot for Let's Encrypt

```bash
sudo apt install certbot python3-certbot-nginx -y
```

---

## 3. Configure MySQL

### Create Database and User

```bash
sudo mysql

# In MySQL prompt:
CREATE DATABASE artisan_kala CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'artisan_kala_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT ALL PRIVILEGES ON artisan_kala.* TO 'artisan_kala_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Optimize MySQL Configuration

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Add or modify these settings:

```ini
[mysqld]
# Performance tuning
max_connections = 200
innodb_buffer_pool_size = 512M
innodb_log_file_size = 128M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Character set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
```

Restart MySQL:

```bash
sudo systemctl restart mysql
```

---

## 4. Configure Redis

### Configure Redis for Production

```bash
sudo nano /etc/redis/redis.conf
```

Modify these settings:

```conf
# Bind to localhost only
bind 127.0.0.1

# Set a password
requirepass your_redis_password_here

# Enable persistence
save 900 1
save 300 10
save 60 10000

# Set max memory
maxmemory 256mb
maxmemory-policy allkeys-lru

# Disable dangerous commands
rename-command FLUSHDB ""
rename-command FLUSHALL ""
rename-command CONFIG ""
```

Restart Redis:

```bash
sudo systemctl restart redis-server
```

---

## 5. Configure PHP-FPM

### Optimize PHP-FPM Settings

```bash
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
```

Modify these settings:

```ini
[www]
user = deploy
group = deploy
listen = /run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

# Process management
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500

# Performance tuning
request_terminate_timeout = 60s
rlimit_files = 4096
```

### Configure PHP Settings

```bash
sudo nano /etc/php/8.3/fpm/php.ini
```

Modify these settings:

```ini
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 60
max_input_time = 60
date.timezone = Asia/Kolkata

# Security
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log

# OPcache
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
```

Create PHP error log directory:

```bash
sudo mkdir -p /var/log/php
sudo chown deploy:deploy /var/log/php
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.3-fpm
```

---

## 6. Configure Nginx

### Copy Nginx Configuration

Copy the provided Nginx configuration file to the sites-available directory:

```bash
sudo cp /path/to/deployment/nginx/api.artisankala.com.conf /etc/nginx/sites-available/api.artisankala.com
```

### Update Configuration

Edit the configuration file and replace placeholders:

```bash
sudo nano /etc/nginx/sites-available/api.artisankala.com
```

Replace:
- `api.artisankala.com` with your actual domain
- `/var/www/artisan-kala-api` with your actual application path

### Enable Site

```bash
# Create symbolic link
sudo ln -s /etc/nginx/sites-available/api.artisankala.com /etc/nginx/sites-enabled/

# Remove default site
sudo rm /etc/nginx/sites-enabled/default

# Test Nginx configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

---

## 7. SSL Certificate with Let's Encrypt

### Obtain SSL Certificate

```bash
sudo certbot --nginx -d api.artisankala.com
```

Follow the prompts:
- Enter your email address
- Agree to terms of service
- Choose whether to redirect HTTP to HTTPS (recommended: yes)

### Auto-Renewal

Certbot automatically sets up a cron job for renewal. Test it:

```bash
sudo certbot renew --dry-run
```

---

## 8. Deploy Laravel Application

### Clone Repository

```bash
cd /var/www
sudo mkdir artisan-kala-api
sudo chown deploy:deploy artisan-kala-api
cd artisan-kala-api

# Clone your repository
git clone https://github.com/your-username/artisan-kala-api.git .
```

### Install Dependencies

```bash
composer install --optimize-autoloader --no-dev
```

### Configure Environment

```bash
cp .env.example .env
nano .env
```

Update these values:

```env
APP_NAME="Artisan Kala API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.artisankala.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=artisan_kala
DB_USERNAME=artisan_kala_user
DB_PASSWORD=your_secure_password_here

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password_here
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Add your other credentials (Mailgun, Razorpay, Google OAuth, Cloudflare R2)
```

### Generate Application Key

```bash
php artisan key:generate
```

### Run Migrations

```bash
php artisan migrate --force
```

### Optimize Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Set Permissions

```bash
sudo chown -R deploy:www-data /var/www/artisan-kala-api
sudo chmod -R 755 /var/www/artisan-kala-api
sudo chmod -R 775 /var/www/artisan-kala-api/storage
sudo chmod -R 775 /var/www/artisan-kala-api/bootstrap/cache
```

---

## 9. Configure Supervisor for Queue Workers

### Copy Supervisor Configuration

```bash
sudo cp /path/to/deployment/supervisor/laravel-worker.conf /etc/supervisor/conf.d/
```

### Update Configuration

```bash
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

Replace `/var/www/artisan-kala-api` with your actual application path.

### Start Queue Workers

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
sudo supervisorctl status
```

---

## 10. Configure Laravel Scheduler

### Add Cron Job

```bash
crontab -e
```

Add this line:

```cron
* * * * * cd /var/www/artisan-kala-api && php artisan schedule:run >> /dev/null 2>&1
```

### Verify Scheduler

```bash
# Check scheduled tasks
php artisan schedule:list
```

The scheduler will run:
- `orders:auto-cancel` command hourly
- `auth:clear-resets` command daily

---

## 11. Security Hardening

### Disable Directory Listing

Already configured in Nginx config with `autoindex off;`

### Hide Server Version

```bash
sudo nano /etc/nginx/nginx.conf
```

Add inside `http` block:

```nginx
server_tokens off;
```

### Configure Fail2Ban (Optional)

```bash
sudo apt install fail2ban -y

# Create jail for Nginx
sudo nano /etc/fail2ban/jail.local
```

Add:

```ini
[nginx-http-auth]
enabled = true
port = http,https
logpath = /var/log/nginx/error.log

[nginx-noscript]
enabled = true
port = http,https
logpath = /var/log/nginx/access.log
```

Restart Fail2Ban:

```bash
sudo systemctl restart fail2ban
```

### Regular Security Updates

```bash
# Enable automatic security updates
sudo apt install unattended-upgrades -y
sudo dpkg-reconfigure --priority=low unattended-upgrades
```

---

## 12. Monitoring and Maintenance

### Log Rotation

Laravel logs are automatically rotated. Configure Nginx log rotation:

```bash
sudo nano /etc/logrotate.d/nginx
```

Ensure it contains:

```
/var/log/nginx/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data adm
    sharedscripts
    prerotate
        if [ -d /etc/logrotate.d/httpd-prerotate ]; then \
            run-parts /etc/logrotate.d/httpd-prerotate; \
        fi
    endscript
    postrotate
        invoke-rc.d nginx rotate >/dev/null 2>&1
    endscript
}
```

### Monitor Disk Space

```bash
df -h
```

### Monitor Memory Usage

```bash
free -h
```

### Monitor Queue Workers

```bash
sudo supervisorctl status
```

### View Application Logs

```bash
tail -f /var/www/artisan-kala-api/storage/logs/laravel.log
```

### Backup Database

Create a backup script:

```bash
nano ~/backup-db.sh
```

Add:

```bash
#!/bin/bash
BACKUP_DIR="/home/deploy/backups"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

mysqldump -u artisan_kala_user -p'your_secure_password_here' artisan_kala | gzip > $BACKUP_DIR/artisan_kala_$DATE.sql.gz

# Keep only last 7 days of backups
find $BACKUP_DIR -name "artisan_kala_*.sql.gz" -mtime +7 -delete
```

Make executable and add to cron:

```bash
chmod +x ~/backup-db.sh
crontab -e
```

Add:

```cron
0 2 * * * /home/deploy/backup-db.sh
```

---

## Deployment Script

For future deployments, use this script:

```bash
#!/bin/bash
cd /var/www/artisan-kala-api

# Enable maintenance mode
php artisan down

# Pull latest changes
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev

# Run migrations
php artisan migrate --force

# Clear and cache config
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart queue workers
sudo supervisorctl restart laravel-worker:*

# Reload PHP-FPM
sudo systemctl reload php8.3-fpm

# Disable maintenance mode
php artisan up

echo "Deployment completed successfully!"
```

---

## Troubleshooting

### Check Nginx Error Logs

```bash
sudo tail -f /var/log/nginx/error.log
```

### Check PHP-FPM Logs

```bash
sudo tail -f /var/log/php8.3-fpm.log
```

### Check Laravel Logs

```bash
tail -f /var/www/artisan-kala-api/storage/logs/laravel.log
```

### Check Queue Worker Status

```bash
sudo supervisorctl status
sudo supervisorctl tail laravel-worker:laravel-worker_00 stdout
```

### Test Database Connection

```bash
php artisan tinker
>>> DB::connection()->getPdo();
```

### Test Redis Connection

```bash
php artisan tinker
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');
```

---

## Quick Reference Commands

```bash
# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
sudo systemctl restart mysql
sudo systemctl restart redis-server
sudo supervisorctl restart laravel-worker:*

# View logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/php8.3-fpm.log
tail -f storage/logs/laravel.log

# Clear Laravel cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# Queue management
php artisan queue:work --daemon
php artisan queue:restart
php artisan queue:failed
php artisan queue:retry all
```

---

## Support

For issues or questions, refer to:
- Laravel Documentation: https://laravel.com/docs
- Nginx Documentation: https://nginx.org/en/docs/
- PHP-FPM Documentation: https://www.php.net/manual/en/install.fpm.php

---

**Last Updated:** 2024
**Version:** 1.0
