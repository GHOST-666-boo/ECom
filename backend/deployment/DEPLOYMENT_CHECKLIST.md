# Deployment Checklist

Use this checklist to ensure all deployment steps are completed correctly.

## Pre-Deployment

### Server Preparation
- [ ] VPS provisioned with Ubuntu 24.04 LTS
- [ ] At least 2GB RAM available
- [ ] Domain name configured (A record pointing to server IP)
- [ ] SSH access configured
- [ ] Firewall rules planned

### Local Preparation
- [ ] All code committed to Git repository
- [ ] All tests passing locally
- [ ] Environment variables documented
- [ ] Database migrations tested
- [ ] Deployment scripts reviewed

## Initial Server Setup

### System Configuration
- [ ] System packages updated (`apt update && apt upgrade`)
- [ ] Deploy user created
- [ ] SSH key authentication configured
- [ ] Firewall configured (UFW)
  - [ ] SSH allowed
  - [ ] HTTP/HTTPS allowed
  - [ ] Firewall enabled

### Software Installation
- [ ] Nginx installed and running
- [ ] PHP 8.3-FPM installed with all required extensions
- [ ] MySQL 8.0 installed and secured
- [ ] Redis 7.0 installed and configured
- [ ] Composer installed globally
- [ ] Supervisor installed
- [ ] Certbot installed

### Database Setup
- [ ] MySQL database created
- [ ] MySQL user created with proper privileges
- [ ] Database credentials documented securely
- [ ] MySQL configuration optimized

### Redis Setup
- [ ] Redis password configured
- [ ] Redis bound to localhost only
- [ ] Redis persistence enabled
- [ ] Dangerous commands disabled

### PHP-FPM Configuration
- [ ] PHP-FPM pool configured for deploy user
- [ ] PHP settings optimized (memory_limit, upload_max_filesize, etc.)
- [ ] OPcache enabled
- [ ] Error logging configured
- [ ] PHP-FPM restarted

## Nginx Configuration

### Server Block Setup
- [ ] Nginx configuration file created
- [ ] Domain name updated in configuration
- [ ] Application path updated in configuration
- [ ] PHP-FPM socket path correct
- [ ] Configuration syntax tested (`nginx -t`)
- [ ] Site enabled (symlink created)
- [ ] Default site disabled
- [ ] Nginx reloaded

### Security Headers
- [ ] HSTS header configured
- [ ] X-Frame-Options set to DENY
- [ ] X-Content-Type-Options set to nosniff
- [ ] X-XSS-Protection enabled
- [ ] Referrer-Policy configured
- [ ] Server tokens disabled
- [ ] X-Powered-By header removed

### Rate Limiting
- [ ] API rate limiting configured (60 req/min)
- [ ] Auth rate limiting configured (5 req/min)
- [ ] Rate limit zones defined

### SSL/TLS
- [ ] Let's Encrypt certificate obtained
- [ ] Certificate auto-renewal configured
- [ ] HTTP to HTTPS redirect working
- [ ] SSL configuration tested (A+ rating on SSL Labs)
- [ ] OCSP stapling enabled

## Laravel Application Deployment

### Code Deployment
- [ ] Repository cloned to application directory
- [ ] Correct branch checked out
- [ ] Composer dependencies installed (--no-dev --optimize-autoloader)
- [ ] Node dependencies installed (if needed)
- [ ] Assets compiled (if needed)

### Environment Configuration
- [ ] .env file created from .env.production.example
- [ ] APP_ENV set to production
- [ ] APP_DEBUG set to false
- [ ] APP_KEY generated
- [ ] APP_URL set correctly
- [ ] Database credentials configured
- [ ] Redis credentials configured
- [ ] Mail configuration set (Mailgun)
- [ ] Razorpay credentials configured
- [ ] Google OAuth credentials configured
- [ ] Cloudflare R2 credentials configured
- [ ] SANCTUM_STATEFUL_DOMAINS configured
- [ ] FRONTEND_URL configured

### Database Setup
- [ ] Migrations run (`php artisan migrate --force`)
- [ ] Seeders run (if needed)
- [ ] Database connection tested

### Laravel Optimization
- [ ] Config cached (`php artisan config:cache`)
- [ ] Routes cached (`php artisan route:cache`)
- [ ] Views cached (`php artisan view:cache`)
- [ ] Events cached (`php artisan event:cache`)
- [ ] Application optimized (`php artisan optimize`)

### File Permissions
- [ ] Application owned by deploy:www-data
- [ ] Directories set to 755
- [ ] Files set to 644
- [ ] storage/ directory set to 775
- [ ] bootstrap/cache/ directory set to 775

## Queue Workers Configuration

### Supervisor Setup
- [ ] Supervisor configuration file created
- [ ] Application path updated in configuration
- [ ] Number of workers configured (2)
- [ ] User set to deploy
- [ ] Log paths configured
- [ ] Supervisor configuration reloaded
- [ ] Workers started
- [ ] Worker status verified

### Queue Testing
- [ ] Test job dispatched
- [ ] Job processed successfully
- [ ] Worker logs checked
- [ ] Failed jobs table checked

## Laravel Scheduler

### Cron Configuration
- [ ] Cron job added to deploy user's crontab
- [ ] Cron job syntax verified
- [ ] Scheduled tasks listed (`php artisan schedule:list`)
- [ ] Scheduler tested manually (`php artisan schedule:run`)

### Scheduled Commands
- [ ] orders:auto-cancel scheduled (hourly)
- [ ] auth:clear-resets scheduled (daily)
- [ ] Other scheduled tasks verified

## Testing and Verification

### Application Testing
- [ ] Homepage accessible (https://api.artisankala.com)
- [ ] Health check endpoint working (/health)
- [ ] API endpoints responding correctly
- [ ] Authentication working (login, register)
- [ ] Google OAuth working
- [ ] Password reset working
- [ ] Product listing working
- [ ] Cart operations working
- [ ] Order placement working
- [ ] Payment integration working (test mode)
- [ ] Email notifications sending
- [ ] File uploads working (R2)

### Performance Testing
- [ ] Page load times acceptable
- [ ] Database queries optimized (no N+1)
- [ ] Redis caching working
- [ ] Static assets loading from CDN
- [ ] Gzip compression working

### Security Testing
- [ ] HTTPS enforced (HTTP redirects to HTTPS)
- [ ] Security headers present
- [ ] Rate limiting working
- [ ] CORS configured correctly
- [ ] SQL injection prevention verified
- [ ] XSS prevention verified
- [ ] CSRF protection working
- [ ] File upload validation working

### Monitoring Setup
- [ ] Application logs accessible
- [ ] Nginx logs accessible
- [ ] PHP-FPM logs accessible
- [ ] Queue worker logs accessible
- [ ] Error tracking configured (optional: Sentry)
- [ ] Uptime monitoring configured (optional)

## Backup and Recovery

### Backup Configuration
- [ ] Database backup script created
- [ ] Backup cron job configured
- [ ] Backup retention policy set
- [ ] Backup location secured
- [ ] Backup restoration tested

### Disaster Recovery
- [ ] Recovery procedure documented
- [ ] Database restoration tested
- [ ] Application restoration tested
- [ ] Rollback procedure documented

## Documentation

### Internal Documentation
- [ ] Server credentials documented securely
- [ ] API credentials documented securely
- [ ] Deployment procedure documented
- [ ] Troubleshooting guide created
- [ ] Contact information for support

### External Documentation
- [ ] API documentation published
- [ ] Frontend integration guide provided
- [ ] Webhook documentation provided

## Post-Deployment

### Monitoring (First 24 Hours)
- [ ] Application logs monitored
- [ ] Error logs monitored
- [ ] Queue workers monitored
- [ ] Database performance monitored
- [ ] Server resources monitored (CPU, RAM, disk)

### Performance Optimization
- [ ] Slow queries identified and optimized
- [ ] Cache hit rates checked
- [ ] CDN performance verified
- [ ] Database indexes verified

### Security Audit
- [ ] Security scan performed
- [ ] Vulnerability assessment completed
- [ ] Penetration testing (if required)
- [ ] Security patches applied

## Maintenance

### Regular Tasks
- [ ] Weekly security updates scheduled
- [ ] Monthly performance review scheduled
- [ ] Quarterly security audit scheduled
- [ ] Log rotation configured
- [ ] Disk space monitoring configured

### Update Procedure
- [ ] Deployment script tested
- [ ] Rollback procedure tested
- [ ] Maintenance window scheduled
- [ ] Stakeholders notified

## Sign-Off

### Deployment Team
- [ ] Developer sign-off
- [ ] DevOps sign-off
- [ ] QA sign-off
- [ ] Project manager sign-off

### Production Readiness
- [ ] All checklist items completed
- [ ] All tests passing
- [ ] All stakeholders notified
- [ ] Go-live approved

---

**Deployment Date:** _______________

**Deployed By:** _______________

**Verified By:** _______________

**Notes:**
_______________________________________________
_______________________________________________
_______________________________________________
