# Deployment Troubleshooting Guide

This guide provides solutions to common issues encountered during deployment and operation of the Artisan Kala platform.

## Table of Contents

1. [Environment Configuration Issues](#environment-configuration-issues)
2. [Database Issues](#database-issues)
3. [Redis Issues](#redis-issues)
4. [Web Server Issues](#web-server-issues)
5. [PHP-FPM Issues](#php-fpm-issues)
6. [Queue Worker Issues](#queue-worker-issues)
7. [Email Issues](#email-issues)
8. [File Upload Issues](#file-upload-issues)
9. [Payment Gateway Issues](#payment-gateway-issues)
10. [Authentication Issues](#authentication-issues)
11. [Performance Issues](#performance-issues)
12. [Deployment Issues](#deployment-issues)

## Environment Configuration Issues

### APP_KEY Not Set

**Symptoms:**
- Error: "No application encryption key has been specified"
- 500 Internal Server Error

**Diagnosis:**
```bash
grep APP_KEY .env
```

**Solution:**
```bash
php artisan key:generate
php artisan config:cache
```

**Prevention:**
- Always run `key:generate` after copying `.env.example`
- Include in deployment checklist

### Configuration Not Updating

**Symptoms:**
- Changes to `.env` not taking effect
- Old configuration values still in use

**Diagnosis:**
```bash
php artisan config:show
```

**Solution:**
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Prevention:**
- Always clear cache after `.env` changes
- Use `config:cache` in production

### APP_DEBUG=true in Production

**Symptoms:**
- Sensitive information exposed in error pages
- Stack traces visible to users

**Diagnosis:**
```bash
grep APP_DEBUG .env
```

**Solution:**
```bash
# Edit .env
nano .env
# Set: APP_DEBUG=false

# Clear cache
php artisan config:cache
```

**Prevention:**
- Use `.env.production.example` as template
- Include in deployment checklist

## Database Issues

### Connection Refused

**Symptoms:**
- Error: "SQLSTATE[HY000] [2002] Connection refused"
- Cannot connect to database

**Diagnosis:**
```bash
# Check MySQL is running
sudo systemctl status mysql

# Test connection
mysql -u artisan_kala_user -p artisan_kala
```

**Solution:**
```bash
# Start MySQL
sudo systemctl start mysql

# Enable on boot
sudo systemctl enable mysql

# Check MySQL logs
sudo tail -f /var/log/mysql/error.log
```

### Access Denied

**Symptoms:**
- Error: "SQLSTATE[HY000] [1045] Access denied for user"
- Authentication failed

**Diagnosis:**
```bash
# Check user exists
sudo mysql -u root -p
SELECT User, Host FROM mysql.user WHERE User='artisan_kala_user';
SHOW GRANTS FOR 'artisan_kala_user'@'localhost';
```

**Solution:**
```bash
# Recreate user
sudo mysql -u root -p
DROP USER IF EXISTS 'artisan_kala_user'@'localhost';
CREATE USER 'artisan_kala_user'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD';
GRANT ALL PRIVILEGES ON artisan_kala.* TO 'artisan_kala_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Update .env with correct password
nano .env

# Test connection
php artisan tinker
DB::connection()->getPdo();
```

### Migration Failed

**Symptoms:**
- Error during `php artisan migrate`
- Database schema incomplete

**Diagnosis:**
```bash
# Check migration status
php artisan migrate:status

# Check database exists
mysql -u artisan_kala_user -p -e "SHOW DATABASES;"
```

**Solution:**
```bash
# Rollback and retry
php artisan migrate:rollback
php artisan migrate

# If rollback fails, reset
php artisan migrate:fresh --force

# Check for syntax errors in migrations
php artisan migrate --pretend
```

**Prevention:**
- Test migrations locally first
- Backup database before migrations
- Use transactions in migrations

### Too Many Connections

**Symptoms:**
- Error: "SQLSTATE[HY000] [1040] Too many connections"
- Application intermittently fails

**Diagnosis:**
```bash
# Check current connections
mysql -u root -p -e "SHOW PROCESSLIST;"

# Check max connections
mysql -u root -p -e "SHOW VARIABLES LIKE 'max_connections';"
```

**Solution:**
```bash
# Increase max connections
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
# Add: max_connections = 200

# Restart MySQL
sudo systemctl restart mysql

# Optimize connection pooling in Laravel
# Edit config/database.php
'mysql' => [
    'pool' => [
        'min_connections' => 1,
        'max_connections' => 10,
    ],
],
```

## Redis Issues

### Connection Refused

**Symptoms:**
- Error: "Connection refused [tcp://127.0.0.1:6379]"
- Cache and sessions not working

**Diagnosis:**
```bash
# Check Redis is running
sudo systemctl status redis-server

# Test connection
redis-cli ping
```

**Solution:**
```bash
# Start Redis
sudo systemctl start redis-server

# Enable on boot
sudo systemctl enable redis-server

# Check Redis logs
sudo tail -f /var/log/redis/redis-server.log
```

### Authentication Failed

**Symptoms:**
- Error: "NOAUTH Authentication required"
- Redis commands fail

**Diagnosis:**
```bash
# Test with password
redis-cli -a YOUR_PASSWORD ping
```

**Solution:**
```bash
# Verify password in Redis config
sudo nano /etc/redis/redis.conf
# Find: requirepass YOUR_PASSWORD

# Verify password in .env
grep REDIS_PASSWORD .env

# Restart Redis
sudo systemctl restart redis-server
```

### Memory Issues

**Symptoms:**
- Error: "OOM command not allowed when used memory > 'maxmemory'"
- Cache operations fail

**Diagnosis:**
```bash
redis-cli info memory
```

**Solution:**
```bash
# Increase max memory
sudo nano /etc/redis/redis.conf
# Set: maxmemory 256mb
# Set: maxmemory-policy allkeys-lru

# Restart Redis
sudo systemctl restart redis-server

# Clear cache if needed
php artisan cache:clear
```

## Web Server Issues

### 502 Bad Gateway

**Symptoms:**
- Nginx returns 502 error
- Application not accessible

**Diagnosis:**
```bash
# Check PHP-FPM is running
sudo systemctl status php8.3-fpm

# Check Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Check PHP-FPM logs
sudo tail -f /var/log/php8.3-fpm.log
```

**Solution:**
```bash
# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Restart Nginx
sudo systemctl restart nginx

# Check socket permissions
ls -la /run/php/php8.3-fpm.sock

# Verify Nginx config
sudo nginx -t
```

### 404 Not Found

**Symptoms:**
- All routes return 404
- Only homepage works

**Diagnosis:**
```bash
# Check Nginx config
sudo nginx -t

# Check Laravel routes
php artisan route:list
```

**Solution:**
```bash
# Verify try_files directive in Nginx
sudo nano /etc/nginx/sites-available/api.artisankala.com
# Should have: try_files $uri $uri/ /index.php?$query_string;

# Reload Nginx
sudo systemctl reload nginx

# Clear route cache
php artisan route:clear
php artisan route:cache
```

### SSL Certificate Issues

**Symptoms:**
- HTTPS not working
- Certificate expired warning

**Diagnosis:**
```bash
# Check certificate expiry
sudo certbot certificates

# Test SSL
curl -I https://api.artisankala.com
```

**Solution:**
```bash
# Renew certificate
sudo certbot renew

# Force renewal
sudo certbot renew --force-renewal

# Reload Nginx
sudo systemctl reload nginx

# Check auto-renewal
sudo systemctl status certbot.timer
```

### Rate Limiting Triggered

**Symptoms:**
- Error: "429 Too Many Requests"
- Users blocked from API

**Diagnosis:**
```bash
# Check Nginx rate limit zones
sudo nginx -T | grep limit_req

# Check Laravel rate limiting
grep -r "RateLimiter" app/
```

**Solution:**
```bash
# Adjust Nginx rate limits
sudo nano /etc/nginx/sites-available/api.artisankala.com
# Modify: limit_req_zone

# Reload Nginx
sudo systemctl reload nginx

# Clear rate limit cache
redis-cli FLUSHDB
```

## PHP-FPM Issues

### PHP-FPM Not Starting

**Symptoms:**
- PHP-FPM service fails to start
- 502 Bad Gateway errors

**Diagnosis:**
```bash
# Check service status
sudo systemctl status php8.3-fpm

# Check for errors
sudo journalctl -u php8.3-fpm -n 50
```

**Solution:**
```bash
# Check PHP-FPM config syntax
sudo php-fpm8.3 -t

# Fix configuration errors
sudo nano /etc/php/8.3/fpm/pool.d/www.conf

# Restart service
sudo systemctl restart php8.3-fpm
```

### Memory Limit Exceeded

**Symptoms:**
- Error: "Allowed memory size exhausted"
- Large requests fail

**Diagnosis:**
```bash
# Check current limit
php -i | grep memory_limit
```

**Solution:**
```bash
# Increase memory limit
sudo nano /etc/php/8.3/fpm/php.ini
# Set: memory_limit = 256M

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm
```

### Upload Size Limit

**Symptoms:**
- Error: "The file exceeds your upload_max_filesize"
- Image uploads fail

**Diagnosis:**
```bash
# Check current limits
php -i | grep -E 'upload_max_filesize|post_max_size'
```

**Solution:**
```bash
# Increase limits
sudo nano /etc/php/8.3/fpm/php.ini
# Set: upload_max_filesize = 10M
# Set: post_max_size = 10M

# Also update Nginx
sudo nano /etc/nginx/sites-available/api.artisankala.com
# Set: client_max_body_size 10M;

# Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl reload nginx
```

## Queue Worker Issues

### Workers Not Processing Jobs

**Symptoms:**
- Jobs stuck in queue
- Emails not sending
- Background tasks not running

**Diagnosis:**
```bash
# Check Supervisor status
sudo supervisorctl status

# Check queue
php artisan queue:work --once

# Check failed jobs
php artisan queue:failed
```

**Solution:**
```bash
# Restart workers
sudo supervisorctl restart laravel-worker:*

# Check worker logs
sudo supervisorctl tail laravel-worker:laravel-worker_00 stdout

# Retry failed jobs
php artisan queue:retry all
```

### Worker Memory Leaks

**Symptoms:**
- Workers consuming excessive memory
- Workers crashing frequently

**Diagnosis:**
```bash
# Monitor memory usage
top -p $(pgrep -f "queue:work")

# Check worker logs
sudo supervisorctl tail laravel-worker:laravel-worker_00 stdout
```

**Solution:**
```bash
# Configure memory limit in Supervisor
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
# Add to command: --memory=128

# Restart workers
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart laravel-worker:*
```

### Jobs Timing Out

**Symptoms:**
- Jobs marked as failed after timeout
- Long-running jobs not completing

**Diagnosis:**
```bash
# Check timeout settings
grep timeout config/queue.php
```

**Solution:**
```bash
# Increase timeout
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
# Add to command: --timeout=300

# Restart workers
sudo supervisorctl restart laravel-worker:*

# Or increase in code
// In job class
public $timeout = 300;
```

## Email Issues

### Mail Not Sending

**Symptoms:**
- Emails not received
- No errors in logs

**Diagnosis:**
```bash
# Check mail configuration
php artisan config:show mail

# Test mail
php artisan tinker
Mail::raw('Test', function($msg) {
    $msg->to('test@example.com')->subject('Test');
});
```

**Solution:**
```bash
# Verify Mailgun credentials
# Check .env file

# Test SMTP connection
telnet smtp.mailgun.org 587

# Check queue for mail jobs
php artisan queue:work --once

# Check failed jobs
php artisan queue:failed
```

### SMTP Authentication Failed

**Symptoms:**
- Error: "Failed to authenticate on SMTP server"
- Emails not sending

**Diagnosis:**
```bash
# Check credentials
grep MAIL_ .env
```

**Solution:**
```bash
# Verify Mailgun credentials in dashboard
# Update .env with correct credentials

# Clear config cache
php artisan config:clear
php artisan config:cache

# Test again
php artisan tinker
Mail::raw('Test', function($msg) {
    $msg->to('test@example.com')->subject('Test');
});
```

### Domain Not Verified

**Symptoms:**
- Error: "Domain not verified"
- Mailgun rejects emails

**Diagnosis:**
```bash
# Check Mailgun dashboard
# Verify DNS records
```

**Solution:**
1. Log in to Mailgun dashboard
2. Navigate to Sending → Domains
3. Check domain verification status
4. Add required DNS records (SPF, DKIM, CNAME)
5. Wait for DNS propagation (up to 48 hours)
6. Verify domain in Mailgun

## File Upload Issues

### R2 Connection Failed

**Symptoms:**
- Error: "Error executing PutObject"
- Image uploads fail

**Diagnosis:**
```bash
# Test R2 connection
php artisan tinker
Storage::disk('r2')->put('test.txt', 'test');
Storage::disk('r2')->exists('test.txt');
```

**Solution:**
```bash
# Verify R2 credentials in .env
grep AWS_ .env

# Check bucket name
# Check endpoint URL
# Check access key permissions

# Clear config cache
php artisan config:cache

# Test again
```

### Permission Denied

**Symptoms:**
- Error: "The stream or file could not be opened"
- Cannot write to storage

**Diagnosis:**
```bash
# Check permissions
ls -la storage/
ls -la bootstrap/cache/
```

**Solution:**
```bash
# Fix ownership
sudo chown -R deploy:www-data /var/www/artisan-kala-api

# Fix permissions
sudo chmod -R 755 /var/www/artisan-kala-api
sudo chmod -R 775 /var/www/artisan-kala-api/storage
sudo chmod -R 775 /var/www/artisan-kala-api/bootstrap/cache

# Create missing directories
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
```

### File Size Limit

**Symptoms:**
- Large files fail to upload
- Error: "The file exceeds your upload_max_filesize"

**Solution:**
See [Upload Size Limit](#upload-size-limit) in PHP-FPM Issues

## Payment Gateway Issues

### Razorpay Webhook Failed

**Symptoms:**
- Payments not updating order status
- Webhook signature verification failed

**Diagnosis:**
```bash
# Check webhook logs
tail -f storage/logs/laravel.log | grep razorpay

# Test webhook locally
curl -X POST https://api.artisankala.com/api/v1/webhooks/razorpay \
  -H "Content-Type: application/json" \
  -d '{"event":"payment.captured"}'
```

**Solution:**
```bash
# Verify webhook secret in .env
grep RAZORPAY_WEBHOOK_SECRET .env

# Check webhook URL in Razorpay dashboard
# Should be: https://api.artisankala.com/api/v1/webhooks/razorpay

# Verify signature verification code
# Check app/Http/Controllers/WebhookController.php

# Clear config cache
php artisan config:cache
```

### Payment Captured But Order Pending

**Symptoms:**
- Payment successful in Razorpay
- Order status still pending

**Diagnosis:**
```bash
# Check webhook delivery in Razorpay dashboard
# Check Laravel logs for webhook processing

tail -f storage/logs/laravel.log
```

**Solution:**
```bash
# Manually update order status
php artisan tinker
$order = Order::where('payment_id', 'pay_XXXXX')->first();
$order->status = 'confirmed';
$order->save();

# Check webhook configuration
# Ensure webhook is active in Razorpay
# Verify webhook secret matches .env
```

## Authentication Issues

### Google OAuth Failed

**Symptoms:**
- Error: "Invalid token"
- Google login not working

**Diagnosis:**
```bash
# Check Google credentials
grep GOOGLE_ .env

# Test token verification
php artisan tinker
// Verify token manually
```

**Solution:**
```bash
# Verify Google Client ID and Secret
# Check authorized redirect URIs in Google Console
# Should include: https://artisankala.com/auth/google/callback

# Clear config cache
php artisan config:cache

# Check frontend is sending correct token
```

### Sanctum Token Invalid

**Symptoms:**
- Error: "Unauthenticated"
- API returns 401

**Diagnosis:**
```bash
# Check Sanctum configuration
php artisan config:show sanctum

# Check token in database
php artisan tinker
PersonalAccessToken::all();
```

**Solution:**
```bash
# Verify SANCTUM_STATEFUL_DOMAINS in .env
# Should match frontend domain

# Clear config cache
php artisan config:cache

# Check CORS configuration
# Verify frontend sends correct Authorization header
```

### Session Expired

**Symptoms:**
- Users logged out frequently
- Session not persisting

**Diagnosis:**
```bash
# Check session configuration
php artisan config:show session

# Check Redis connection
redis-cli -a YOUR_PASSWORD ping
```

**Solution:**
```bash
# Increase session lifetime
nano .env
# Set: SESSION_LIFETIME=10080 (7 days)

# Clear config cache
php artisan config:cache

# Check Redis is working
php artisan tinker
Cache::put('test', 'value', 60);
Cache::get('test');
```

## Performance Issues

### Slow Response Times

**Symptoms:**
- API responses taking > 1 second
- Timeouts on frontend

**Diagnosis:**
```bash
# Enable query logging
php artisan tinker
DB::enableQueryLog();
// Make request
DB::getQueryLog();

# Check slow query log
sudo tail -f /var/log/mysql/mysql-slow.log

# Monitor server resources
top
htop
```

**Solution:**
```bash
# Enable OPcache
sudo nano /etc/php/8.3/fpm/php.ini
# Set: opcache.enable=1

# Optimize database queries
# Add indexes for frequently queried columns

# Enable Redis caching
# Verify cache is working
php artisan cache:clear

# Optimize Composer autoloader
composer dump-autoload --optimize

# Use Laravel optimization
php artisan optimize
```

### High Memory Usage

**Symptoms:**
- Server running out of memory
- OOM killer terminating processes

**Diagnosis:**
```bash
# Check memory usage
free -h

# Check process memory
ps aux --sort=-%mem | head

# Check PHP memory limit
php -i | grep memory_limit
```

**Solution:**
```bash
# Increase server RAM (if needed)

# Optimize PHP memory limit
sudo nano /etc/php/8.3/fpm/php.ini
# Set: memory_limit = 256M

# Optimize MySQL
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
# Adjust: innodb_buffer_pool_size

# Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl restart mysql
```

### Database Queries Slow

**Symptoms:**
- Queries taking > 100ms
- N+1 query problems

**Diagnosis:**
```bash
# Enable slow query log
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
# Add:
# slow_query_log = 1
# long_query_time = 0.1
# slow_query_log_file = /var/log/mysql/mysql-slow.log

# Restart MySQL
sudo systemctl restart mysql

# Check slow queries
sudo tail -f /var/log/mysql/mysql-slow.log
```

**Solution:**
```bash
# Add indexes
php artisan tinker
Schema::table('products', function($table) {
    $table->index(['category_id', 'is_active']);
});

# Use eager loading
// In code: Product::with('category')->get();

# Optimize queries
// Use select() to limit columns
// Use cursor() for large datasets
```

## Deployment Issues

### Git Pull Failed

**Symptoms:**
- Deployment fails at git pull
- Merge conflicts

**Diagnosis:**
```bash
# Check git status
cd /var/www/artisan-kala-api
git status
```

**Solution:**
```bash
# Stash local changes
git stash

# Pull latest
git pull origin main

# If conflicts, reset
git fetch origin
git reset --hard origin/main

# Restore permissions
sudo chown -R deploy:www-data .
```

### Composer Install Failed

**Symptoms:**
- Deployment fails at composer install
- Dependency conflicts

**Diagnosis:**
```bash
# Check Composer version
composer --version

# Check PHP version
php --version

# Try install manually
composer install --no-interaction
```

**Solution:**
```bash
# Update Composer
composer self-update

# Clear Composer cache
composer clear-cache

# Install with verbose output
composer install --no-interaction --verbose

# If memory issues
php -d memory_limit=-1 /usr/local/bin/composer install
```

### Migration Failed During Deployment

**Symptoms:**
- Deployment fails at migrate step
- Database schema incomplete

**Diagnosis:**
```bash
# Check migration status
php artisan migrate:status

# Check for errors
php artisan migrate --pretend
```

**Solution:**
```bash
# Rollback last migration
php artisan migrate:rollback --step=1

# Fix migration file
nano database/migrations/XXXX_migration_file.php

# Run migration again
php artisan migrate --force

# If all else fails, restore from backup
```

## Getting Help

### Collecting Diagnostic Information

When reporting issues, include:

```bash
# System information
uname -a
lsb_release -a

# Service status
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status mysql
sudo systemctl status redis-server

# Laravel information
php artisan --version
php artisan config:show

# Recent logs
tail -n 100 storage/logs/laravel.log
sudo tail -n 100 /var/log/nginx/error.log
```

### Log Locations

- Laravel: `storage/logs/laravel.log`
- Nginx access: `/var/log/nginx/artisan-kala-api-access.log`
- Nginx error: `/var/log/nginx/artisan-kala-api-error.log`
- PHP-FPM: `/var/log/php8.3-fpm.log`
- MySQL: `/var/log/mysql/error.log`
- Redis: `/var/log/redis/redis-server.log`
- Supervisor: `/var/log/supervisor/supervisord.log`

### Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Redis Documentation](https://redis.io/documentation)
- [Deployment Checklist](DEPLOYMENT_CHECKLIST.md)
- [Environment Setup Guide](ENVIRONMENT_SETUP.md)

