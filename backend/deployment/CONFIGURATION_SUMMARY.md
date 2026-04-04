# Configuration Summary

Quick reference guide for all configuration requirements for the Artisan Kala platform.

## Documentation Index

| Document | Purpose | When to Use |
|----------|---------|-------------|
| [Environment Setup Guide](ENVIRONMENT_SETUP.md) | Complete environment variable configuration | Initial setup, credential updates |
| [GitHub Secrets Setup](GITHUB_SECRETS.md) | CI/CD secrets configuration | Setting up automated deployment |
| [Security Best Practices](SECURITY_BEST_PRACTICES.md) | Security guidelines and hardening | Security audit, compliance |
| [Troubleshooting Guide](TROUBLESHOOTING.md) | Issue resolution | When things go wrong |
| [Deployment Checklist](DEPLOYMENT_CHECKLIST.md) | Pre/post-deployment tasks | Before and after deployment |
| [Quick Start Guide](QUICK_START.md) | Condensed deployment steps | Quick reference |
| [README](README.md) | Overview and general info | Starting point |

## Quick Configuration Checklist

### Backend Environment Variables (.env on VPS)

**Core Application:**
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` (generated with `php artisan key:generate`)
- [ ] `APP_URL` (your API domain)
- [ ] `FRONTEND_URL` (your frontend domain)

**Database:**
- [ ] `DB_DATABASE` (database name)
- [ ] `DB_USERNAME` (database user)
- [ ] `DB_PASSWORD` (strong password, 20+ chars)

**Redis:**
- [ ] `REDIS_PASSWORD` (strong password, 20+ chars)

**Email (Mailgun):**
- [ ] `MAIL_MAILER` (smtp or mailgun)
- [ ] `MAIL_USERNAME` or `MAILGUN_DOMAIN`
- [ ] `MAIL_PASSWORD` or `MAILGUN_SECRET`

**Payment (Razorpay):**
- [ ] `RAZORPAY_KEY_ID` (live mode)
- [ ] `RAZORPAY_KEY_SECRET` (live mode)
- [ ] `RAZORPAY_WEBHOOK_SECRET`

**OAuth (Google):**
- [ ] `GOOGLE_CLIENT_ID`
- [ ] `GOOGLE_CLIENT_SECRET`

**Storage (Cloudflare R2):**
- [ ] `AWS_ACCESS_KEY_ID`
- [ ] `AWS_SECRET_ACCESS_KEY`
- [ ] `AWS_BUCKET`
- [ ] `AWS_ENDPOINT`
- [ ] `AWS_URL`

**Sanctum:**
- [ ] `SANCTUM_STATEFUL_DOMAINS`
- [ ] `SESSION_DOMAIN`

### GitHub Secrets

**Vercel (Frontend):**
- [ ] `VERCEL_TOKEN`
- [ ] `VERCEL_ORG_ID`
- [ ] `VERCEL_PROJECT_ID`
- [ ] `VITE_API_BASE_URL`
- [ ] `VITE_GOOGLE_CLIENT_ID`

**VPS (Backend):**
- [ ] `VPS_HOST`
- [ ] `VPS_USER`
- [ ] `VPS_SSH_KEY`
- [ ] `VPS_PORT` (optional, defaults to 22)

## Where to Get Credentials

### Database & Redis
Created during VPS setup:
```bash
# Database
sudo mysql -u root -p
CREATE DATABASE artisan_kala;
CREATE USER 'artisan_kala_user'@'localhost' IDENTIFIED BY 'PASSWORD';
GRANT ALL PRIVILEGES ON artisan_kala.* TO 'artisan_kala_user'@'localhost';

# Redis
sudo nano /etc/redis/redis.conf
# Set: requirepass YOUR_PASSWORD
```

### Mailgun
1. Sign up at [mailgun.com](https://www.mailgun.com/)
2. Add and verify domain
3. Get credentials from dashboard

### Razorpay
1. Sign up at [razorpay.com](https://razorpay.com/)
2. Complete KYC
3. Switch to live mode
4. Generate API keys
5. Create webhook

### Google OAuth
1. Go to [console.cloud.google.com](https://console.cloud.google.com/)
2. Create project
3. Enable Google+ API
4. Create OAuth credentials
5. Add authorized domains

### Cloudflare R2
1. Sign up at [cloudflare.com](https://www.cloudflare.com/)
2. Navigate to R2
3. Create bucket
4. Generate API token

### Vercel
1. Log in to [vercel.com](https://vercel.com/)
2. Create token in Settings → Tokens
3. Get Org ID and Project ID from project settings

### VPS SSH Key
```bash
# Generate key pair
ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github_actions

# Add public key to VPS
ssh-copy-id -i ~/.ssh/github_actions.pub deploy@YOUR_VPS_IP

# Use private key for GitHub Secret
cat ~/.ssh/github_actions
```

## Configuration Files

### Backend (.env)
Location: `/var/www/artisan-kala-api/.env`
Template: `deployment/.env.production.example`
Permissions: `600` (owner read/write only)

### GitHub Secrets
Location: Repository Settings → Secrets and variables → Actions
Access: Repository admins only

### Nginx
Location: `/etc/nginx/sites-available/api.artisankala.com`
Template: `deployment/nginx/api.artisankala.com.conf`

### Supervisor
Location: `/etc/supervisor/conf.d/laravel-worker.conf`
Template: `deployment/supervisor/laravel-worker.conf`

## Verification Commands

### Test Backend Configuration
```bash
# Check all config values
php artisan config:show

# Test database
php artisan tinker
DB::connection()->getPdo();

# Test Redis
Cache::put('test', 'value', 60);
Cache::get('test');

# Test mail
Mail::raw('Test', function($msg) {
    $msg->to('test@example.com')->subject('Test');
});

# Test R2
Storage::disk('r2')->put('test.txt', 'test');
Storage::disk('r2')->exists('test.txt');
```

### Test Frontend Configuration
```bash
# Check environment variables are loaded
npm run build
# Check build output for VITE_ variables
```

### Test Deployment
```bash
# Trigger deployment
git commit --allow-empty -m "Test deployment"
git push origin main

# Check GitHub Actions
# Go to Actions tab in repository
```

## Security Verification

### Backend Security
```bash
# Verify production settings
grep -E "APP_ENV|APP_DEBUG" .env
# Should show: APP_ENV=production, APP_DEBUG=false

# Check file permissions
ls -la .env
# Should show: -rw------- (600)

# Verify security headers
curl -I https://api.artisankala.com | grep -E "X-Frame|Strict-Transport"
```

### Server Security
```bash
# Check firewall
sudo ufw status

# Check SSH config
sudo grep -E "PermitRootLogin|PasswordAuthentication" /etc/ssh/sshd_config
# Should show: PermitRootLogin no, PasswordAuthentication no

# Check services
sudo systemctl status nginx php8.3-fpm mysql redis-server
```

## Common Issues

### Configuration Not Updating
```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

### Permission Errors
```bash
sudo chown -R deploy:www-data /var/www/artisan-kala-api
sudo chmod -R 755 /var/www/artisan-kala-api
sudo chmod -R 775 /var/www/artisan-kala-api/storage
```

### Service Not Starting
```bash
# Check logs
sudo journalctl -u nginx -n 50
sudo journalctl -u php8.3-fpm -n 50
tail -f storage/logs/laravel.log
```

## Maintenance Schedule

### Daily
- Monitor error logs
- Check disk space
- Verify backups completed

### Weekly
- Review security logs
- Check for failed jobs
- Monitor performance metrics

### Monthly
- Update dependencies
- Review access logs
- Test backup restoration

### Quarterly
- Rotate secrets
- Security audit
- Performance optimization

### Annually
- Major version updates
- Comprehensive security review
- Disaster recovery drill

## Support Resources

### Documentation
- [Environment Setup Guide](ENVIRONMENT_SETUP.md) - Detailed configuration
- [GitHub Secrets Setup](GITHUB_SECRETS.md) - CI/CD configuration
- [Security Best Practices](SECURITY_BEST_PRACTICES.md) - Security guidelines
- [Troubleshooting Guide](TROUBLESHOOTING.md) - Issue resolution

### External Resources
- [Laravel Documentation](https://laravel.com/docs)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Redis Documentation](https://redis.io/documentation)

### Getting Help
1. Check [Troubleshooting Guide](TROUBLESHOOTING.md)
2. Review logs (Laravel, Nginx, PHP-FPM)
3. Search Laravel documentation
4. Check GitHub Issues
5. Contact support team

## Emergency Contacts

**Production Issues:**
- DevOps Lead: [contact info]
- Backend Lead: [contact info]
- On-call: [contact info]

**Security Issues:**
- Security Team: security@artisankala.com
- Response Time: 48 hours

**Service Providers:**
- Mailgun Support: [link]
- Razorpay Support: [link]
- Cloudflare Support: [link]
- VPS Provider: [link]

