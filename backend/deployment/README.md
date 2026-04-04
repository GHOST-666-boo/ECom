# Deployment Configuration

This directory contains all deployment-related configuration files and scripts for the Artisan Kala API.

## Directory Structure

```
deployment/
├── nginx/
│   └── api.artisankala.com.conf    # Nginx server configuration
├── supervisor/
│   └── laravel-worker.conf         # Supervisor queue worker configuration
├── scripts/
│   ├── setup-vps.sh                # Initial VPS setup script
│   └── deploy.sh                   # Deployment script for updates
└── README.md                       # This file
```

## Quick Start

### Initial Server Setup

1. **Prepare your VPS:**
   - Ubuntu 24.04 LTS
   - At least 2GB RAM
   - Domain name configured (A record pointing to server IP)

2. **Run the setup script:**
   ```bash
   # Copy setup script to server
   scp deployment/scripts/setup-vps.sh root@your-server-ip:~
   
   # SSH into server
   ssh root@your-server-ip
   
   # Make executable and run
   chmod +x setup-vps.sh
   sudo ./setup-vps.sh
   ```

3. **Follow the prompts** to configure:
   - Domain name
   - Application path
   - Database credentials
   - Redis password
   - Email for SSL certificate

4. **Complete manual steps** (see output):
   - Copy Nginx configuration
   - Clone Laravel application
   - Configure environment variables
   - Run migrations

### Nginx Configuration

The Nginx configuration includes:
- SSL/TLS with Let's Encrypt
- Security headers (HSTS, X-Frame-Options, etc.)
- Rate limiting for API and auth endpoints
- Gzip compression
- Static asset caching
- PHP-FPM integration

**Installation:**
```bash
# Copy configuration
sudo cp deployment/nginx/api.artisankala.com.conf /etc/nginx/sites-available/api.artisankala.com

# Update domain and paths in the file
sudo nano /etc/nginx/sites-available/api.artisankala.com

# Enable site
sudo ln -s /etc/nginx/sites-available/api.artisankala.com /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### Supervisor Configuration

Supervisor manages Laravel queue workers for background job processing.

**Installation:**
```bash
# Copy configuration
sudo cp deployment/supervisor/laravel-worker.conf /etc/supervisor/conf.d/

# Update application path in the file
sudo nano /etc/supervisor/conf.d/laravel-worker.conf

# Reload Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start laravel-worker:*

# Check status
sudo supervisorctl status
```

**Worker Management:**
```bash
# View logs
sudo supervisorctl tail laravel-worker:laravel-worker_00 stdout

# Restart workers
sudo supervisorctl restart laravel-worker:*

# Stop workers
sudo supervisorctl stop laravel-worker:*
```

### Laravel Scheduler

The scheduler runs automated tasks like order auto-cancellation.

**Setup:**
```bash
# Add to crontab
crontab -e

# Add this line:
* * * * * cd /var/www/artisan-kala-api && php artisan schedule:run >> /dev/null 2>&1
```

**Scheduled Tasks:**
- `orders:auto-cancel` - Runs hourly to cancel pending orders after 48 hours
- `auth:clear-resets` - Runs daily to clear expired password reset tokens

## Deployment Process

### For Updates and New Features

Use the deployment script for zero-downtime deployments:

```bash
# Copy deployment script to server
scp deployment/scripts/deploy.sh deploy@your-server-ip:~

# SSH into server
ssh deploy@your-server-ip

# Make executable
chmod +x deploy.sh

# Run deployment
./deploy.sh
```

**The script will:**
1. Create a database backup
2. Enable maintenance mode
3. Pull latest code from Git
4. Install Composer dependencies
5. Run database migrations
6. Clear and rebuild cache
7. Restart queue workers
8. Reload PHP-FPM
9. Disable maintenance mode

**Rollback on failure:**
The script automatically rolls back if any step fails.

### Manual Deployment Steps

If you prefer manual deployment:

```bash
cd /var/www/artisan-kala-api

# Enable maintenance mode
php artisan down

# Pull latest code
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev

# Run migrations
php artisan migrate --force

# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# Restart queue workers
sudo supervisorctl restart laravel-worker:*

# Reload PHP-FPM
sudo systemctl reload php8.3-fpm

# Disable maintenance mode
php artisan up
```

## Environment Configuration

### Quick Setup

For detailed environment configuration instructions, see:
- **[Environment Setup Guide](ENVIRONMENT_SETUP.md)** - Complete guide for configuring all environment variables
- **[GitHub Secrets Setup](GITHUB_SECRETS.md)** - Guide for configuring CI/CD secrets
- **[Security Best Practices](SECURITY_BEST_PRACTICES.md)** - Comprehensive security guidelines

### Required Environment Variables

A template is provided at `deployment/.env.production.example`. Copy this to `.env` on your VPS and configure all values.

**Critical settings:**
```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:... # Generate with: php artisan key:generate
```

**See [Environment Setup Guide](ENVIRONMENT_SETUP.md) for:**
- How to obtain all credentials
- Step-by-step configuration instructions
- Verification procedures
- Troubleshooting common issues

### Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong database password (20+ characters)
- [ ] Strong Redis password (20+ characters)
- [ ] Firewall configured (UFW)
- [ ] SSL certificate installed
- [ ] Security headers configured in Nginx
- [ ] File permissions set correctly (755 for directories, 644 for files)
- [ ] Storage and cache directories writable (775)
- [ ] `.env` file not committed to Git
- [ ] `.env` file permissions set to 600
- [ ] Fail2Ban configured (optional)
- [ ] Regular backups scheduled

**See [Security Best Practices](SECURITY_BEST_PRACTICES.md) for complete security guidelines.**

## Monitoring

### Check Application Status

```bash
# Check if application is running
curl https://api.artisankala.com/health

# View Laravel logs
tail -f /var/www/artisan-kala-api/storage/logs/laravel.log

# View Nginx access logs
sudo tail -f /var/log/nginx/artisan-kala-api-access.log

# View Nginx error logs
sudo tail -f /var/log/nginx/artisan-kala-api-error.log

# View PHP-FPM logs
sudo tail -f /var/log/php8.3-fpm.log

# Check queue worker status
sudo supervisorctl status

# View queue worker logs
sudo supervisorctl tail laravel-worker:laravel-worker_00 stdout
```

### Performance Monitoring

```bash
# Check disk space
df -h

# Check memory usage
free -h

# Check CPU usage
top

# Check MySQL status
sudo systemctl status mysql

# Check Redis status
redis-cli ping

# Check active connections
sudo netstat -tulpn | grep LISTEN
```

## Backup and Recovery

### Database Backup

```bash
# Manual backup
mysqldump -u artisan_kala_user -p artisan_kala | gzip > backup_$(date +%Y%m%d).sql.gz

# Restore from backup
gunzip < backup_20240101.sql.gz | mysql -u artisan_kala_user -p artisan_kala
```

### Automated Backups

Create a backup script and add to cron:

```bash
# Create backup script
nano ~/backup-db.sh

# Add to crontab (daily at 2 AM)
0 2 * * * /home/deploy/backup-db.sh
```

## Troubleshooting

For comprehensive troubleshooting guidance, see **[Troubleshooting Guide](TROUBLESHOOTING.md)**.

### Quick Fixes

**502 Bad Gateway:**
```bash
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

**Queue Jobs Not Processing:**
```bash
sudo supervisorctl restart laravel-worker:*
```

**Database Connection Failed:**
```bash
php artisan tinker
DB::connection()->getPdo();
```

**Configuration Not Updating:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

**Permission Denied:**
```bash
sudo chown -R deploy:www-data /var/www/artisan-kala-api
sudo chmod -R 755 /var/www/artisan-kala-api
sudo chmod -R 775 /var/www/artisan-kala-api/storage
```

### Getting Help

See detailed solutions in:
- [Troubleshooting Guide](TROUBLESHOOTING.md) - Comprehensive issue resolution
- [Environment Setup Guide](ENVIRONMENT_SETUP.md) - Configuration issues
- [GitHub Secrets Guide](GITHUB_SECRETS.md) - CI/CD issues
- [Security Best Practices](SECURITY_BEST_PRACTICES.md) - Security concerns

## Support

For detailed setup instructions, see:
- **[Environment Setup Guide](ENVIRONMENT_SETUP.md)** - Complete environment configuration
- **[GitHub Secrets Setup](GITHUB_SECRETS.md)** - CI/CD secrets configuration
- **[Security Best Practices](SECURITY_BEST_PRACTICES.md)** - Security guidelines
- **[Troubleshooting Guide](TROUBLESHOOTING.md)** - Issue resolution
- **[Deployment Checklist](DEPLOYMENT_CHECKLIST.md)** - Pre/post-deployment tasks
- **[Quick Start Guide](QUICK_START.md)** - Condensed deployment guide
- [VPS Setup Guide](../docs/VPS_SETUP.md) - Initial server setup
- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [Nginx Documentation](https://nginx.org/en/docs/)

## Version History

- **v1.0** - Initial deployment configuration
  - Nginx with SSL and security headers
  - Supervisor for queue workers
  - Automated setup and deployment scripts
