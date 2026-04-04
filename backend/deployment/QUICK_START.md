# Quick Start Guide - VPS Deployment

This is a condensed guide for quickly deploying the Artisan Kala API to a VPS.

## Prerequisites

- Ubuntu 24.04 VPS (2GB+ RAM)
- Domain name pointing to server IP
- SSH access

## Step 1: Initial Setup (One-time)

```bash
# On your local machine, copy setup script to server
scp deployment/scripts/setup-vps.sh root@your-server-ip:~

# SSH into server
ssh root@your-server-ip

# Run setup script
chmod +x setup-vps.sh
sudo ./setup-vps.sh
```

Follow the prompts to configure domain, database, and Redis.

## Step 2: Configure Nginx

```bash
# Copy Nginx config
sudo cp /path/to/deployment/nginx/api.artisankala.com.conf /etc/nginx/sites-available/api.artisankala.com

# Edit and update domain/paths
sudo nano /etc/nginx/sites-available/api.artisankala.com

# Enable site
sudo ln -s /etc/nginx/sites-available/api.artisankala.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## Step 3: Deploy Laravel Application

```bash
# Switch to deploy user
su - deploy

# Clone repository
cd /var/www/artisan-kala-api
git clone https://github.com/your-username/artisan-kala-api.git .

# Install dependencies
composer install --optimize-autoloader --no-dev

# Configure environment
cp .env.example .env
nano .env  # Update all credentials

# Generate key and run migrations
php artisan key:generate
php artisan migrate --force

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
sudo chown -R deploy:www-data /var/www/artisan-kala-api
sudo chmod -R 755 /var/www/artisan-kala-api
sudo chmod -R 775 /var/www/artisan-kala-api/storage
sudo chmod -R 775 /var/www/artisan-kala-api/bootstrap/cache
```

## Step 4: Configure Supervisor

```bash
# Copy Supervisor config
sudo cp /path/to/deployment/supervisor/laravel-worker.conf /etc/supervisor/conf.d/

# Update paths if needed
sudo nano /etc/supervisor/conf.d/laravel-worker.conf

# Start workers
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## Step 5: Configure Scheduler

```bash
# Add cron job
crontab -e

# Add this line:
* * * * * cd /var/www/artisan-kala-api && php artisan schedule:run >> /dev/null 2>&1
```

## Step 6: Obtain SSL Certificate

```bash
sudo certbot --nginx -d api.artisankala.com
```

## Verify Installation

```bash
# Test API
curl https://api.artisankala.com/health

# Check queue workers
sudo supervisorctl status

# Check logs
tail -f /var/www/artisan-kala-api/storage/logs/laravel.log
```

## Future Deployments

For updates, use the deployment script:

```bash
# Copy deploy script to server
scp deployment/scripts/deploy.sh deploy@your-server-ip:~

# SSH and run
ssh deploy@your-server-ip
chmod +x deploy.sh
./deploy.sh
```

## Essential Commands

```bash
# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
sudo supervisorctl restart laravel-worker:*

# View logs
tail -f storage/logs/laravel.log
sudo tail -f /var/log/nginx/error.log

# Clear cache
php artisan cache:clear
php artisan config:clear

# Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Troubleshooting

**502 Bad Gateway:**
```bash
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

**Queue not processing:**
```bash
sudo supervisorctl restart laravel-worker:*
sudo supervisorctl tail laravel-worker:laravel-worker_00 stdout
```

**Permission errors:**
```bash
sudo chown -R deploy:www-data /var/www/artisan-kala-api
sudo chmod -R 775 /var/www/artisan-kala-api/storage
```

## Need More Details?

See the full documentation:
- [VPS Setup Guide](../docs/VPS_SETUP.md)
- [Deployment README](README.md)
