# CI/CD Quick Reference

Quick commands and checks for the Vriddhi CI/CD pipeline.

## GitHub Actions Status

Check workflow status:
```bash
# View in browser
https://github.com/YOUR_USERNAME/YOUR_REPO/actions

# Or use GitHub CLI
gh run list
gh run view RUN_ID
gh run watch
```

## Manual Deployment Trigger

Trigger deployment without pushing code:
```bash
# Using GitHub CLI
gh workflow run deploy.yml

# Or via GitHub UI
Actions > Deploy to Production > Run workflow
```

## Testing Locally Before Push

### Run Backend Tests
```bash
cd vriddhi-api
composer test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
```

### Run Frontend Build
```bash
cd vriddhi-frontend
npm run build
```

## VPS Quick Checks

### Check Application Status
```bash
ssh deploy@your-vps-ip

# Check Laravel version
cd /var/www/vriddhi-api && php artisan --version

# Check queue workers
sudo supervisorctl status

# Check PHP-FPM
sudo systemctl status php8.3-fpm

# Check Nginx
sudo systemctl status nginx
```

### View Logs
```bash
# Laravel logs
tail -f /var/www/vriddhi-api/storage/logs/laravel.log

# Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Queue worker logs
sudo supervisorctl tail laravel-worker:laravel-worker_00 stdout
```

### Restart Services
```bash
# Restart queue workers
sudo supervisorctl restart laravel-worker:*

# Reload PHP-FPM
sudo systemctl reload php8.3-fpm

# Restart Nginx
sudo systemctl restart nginx
```

## Vercel Quick Checks

### Check Deployment Status
```bash
# Using Vercel CLI
vercel ls
vercel inspect DEPLOYMENT_URL
```

### View Logs
```bash
vercel logs DEPLOYMENT_URL
```

### Rollback Frontend
```bash
# List deployments
vercel ls

# Promote previous deployment
vercel promote PREVIOUS_DEPLOYMENT_URL
```

## Common Deployment Commands

### Full Manual Deployment (Backend)
```bash
ssh deploy@your-vps-ip
cd /var/www/vriddhi-api
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart laravel-worker:*
sudo systemctl reload php8.3-fpm
```

### Quick Cache Clear (Backend)
```bash
ssh deploy@your-vps-ip
cd /var/www/vriddhi-api
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Rebuild Cache (Backend)
```bash
ssh deploy@your-vps-ip
cd /var/www/vriddhi-api
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Troubleshooting Commands

### Check Database Connection
```bash
ssh deploy@your-vps-ip
cd /var/www/vriddhi-api
php artisan tinker
>>> DB::connection()->getPdo();
```

### Check Redis Connection
```bash
ssh deploy@your-vps-ip
redis-cli ping
# Should return: PONG
```

### Check Disk Space
```bash
ssh deploy@your-vps-ip
df -h
```

### Check Memory Usage
```bash
ssh deploy@your-vps-ip
free -h
```

### Check Running Processes
```bash
ssh deploy@your-vps-ip
ps aux | grep php
ps aux | grep nginx
```

## Emergency Procedures

### Site Down - Quick Fix
```bash
ssh deploy@your-vps-ip

# Restart all services
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
sudo supervisorctl restart laravel-worker:*

# Check status
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo supervisorctl status
```

### Rollback Last Deployment
```bash
ssh deploy@your-vps-ip
cd /var/www/vriddhi-api

# Revert to previous commit
git reset --hard HEAD~1

# Reinstall dependencies
composer install --optimize-autoloader --no-dev

# Rollback migrations (if needed)
php artisan migrate:rollback

# Rebuild cache
php artisan config:cache
php artisan route:cache

# Restart services
sudo supervisorctl restart laravel-worker:*
sudo systemctl reload php8.3-fpm
```

### Clear All Caches
```bash
ssh deploy@your-vps-ip
cd /var/www/vriddhi-api

# Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# OPcache
sudo systemctl reload php8.3-fpm

# Redis
redis-cli FLUSHALL
```

## Monitoring Commands

### Check Recent Deployments
```bash
# GitHub CLI
gh run list --workflow=deploy.yml --limit 5

# Git log
git log --oneline -10
```

### Check Application Health
```bash
# Test API endpoint
curl https://api.vriddhi.com/health

# Test frontend
curl https://vriddhi.com
```

### Monitor Queue Jobs
```bash
ssh deploy@your-vps-ip
cd /var/www/vriddhi-api

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

## Performance Checks

### Check Response Times
```bash
# API response time
curl -w "@-" -o /dev/null -s https://api.vriddhi.com/api/v1/products <<'EOF'
time_namelookup:  %{time_namelookup}\n
time_connect:  %{time_connect}\n
time_starttransfer:  %{time_starttransfer}\n
time_total:  %{time_total}\n
EOF
```

### Check Database Performance
```bash
ssh deploy@your-vps-ip
cd /var/www/vriddhi-api
php artisan tinker
>>> DB::enableQueryLog();
>>> // Run some queries
>>> DB::getQueryLog();
```

### Check Cache Hit Rate
```bash
ssh deploy@your-vps-ip
redis-cli INFO stats | grep keyspace
```

## Useful Aliases

Add these to your `~/.bashrc` or `~/.zshrc`:

```bash
# SSH to VPS
alias vps='ssh deploy@your-vps-ip'

# Deploy commands
alias deploy-status='gh run list --workflow=deploy.yml --limit 5'
alias deploy-watch='gh run watch'
alias deploy-trigger='gh workflow run deploy.yml'

# VPS commands (run after 'vps' alias)
alias artisan='cd /var/www/vriddhi-api && php artisan'
alias logs='tail -f /var/www/vriddhi-api/storage/logs/laravel.log'
alias restart-workers='sudo supervisorctl restart laravel-worker:*'
alias restart-php='sudo systemctl reload php8.3-fpm'
```

## GitHub Secrets Management

### List Secrets
```bash
gh secret list
```

### Set Secret
```bash
gh secret set SECRET_NAME
# Paste value and press Ctrl+D
```

### Delete Secret
```bash
gh secret delete SECRET_NAME
```

## Maintenance Windows

For major updates requiring downtime:

1. **Enable maintenance mode:**
   ```bash
   ssh deploy@your-vps-ip
   cd /var/www/vriddhi-api
   php artisan down --secret="maintenance-bypass-token"
   ```

2. **Perform updates**

3. **Disable maintenance mode:**
   ```bash
   php artisan up
   ```

4. **Access during maintenance:**
   ```
   https://api.vriddhi.com/maintenance-bypass-token
   ```

## Quick Links

- [GitHub Actions](https://github.com/YOUR_USERNAME/YOUR_REPO/actions)
- [Vercel Dashboard](https://vercel.com/dashboard)
- [Full Documentation](./README.md)
- [Setup Secrets Guide](./SETUP_SECRETS.md)
