# GitHub Actions CI/CD Deployment Checklist

Use this checklist to set up and verify the CI/CD pipeline for Vriddhi.

## Pre-Deployment Checklist

### 1. GitHub Repository Setup
- [ ] Repository is created on GitHub
- [ ] Code is pushed to repository
- [ ] GitHub Actions is enabled (Settings > Actions > General)
- [ ] Branch protection rules configured for `main` branch (optional but recommended)

### 2. Vercel Setup
- [ ] Vercel account created
- [ ] Project created on Vercel
- [ ] Project linked to GitHub repository
- [ ] Vercel CLI installed: `npm i -g vercel`
- [ ] Run `vercel link` in frontend directory
- [ ] Note down `orgId` and `projectId` from `.vercel/project.json`
- [ ] Generate Vercel token from https://vercel.com/account/tokens

### 3. VPS Setup
- [ ] VPS provisioned with Ubuntu 24.04
- [ ] Domain name configured and pointing to VPS
- [ ] SSH access configured
- [ ] Laravel application deployed at `/var/www/vriddhi-api`
- [ ] Nginx configured and running
- [ ] PHP 8.3-FPM installed and running
- [ ] MySQL 8.0 installed and running
- [ ] Redis 7.0 installed and running
- [ ] Supervisor configured for queue workers
- [ ] SSL certificate installed (Let's Encrypt)
- [ ] Deploy user created with appropriate permissions

### 4. VPS Permissions
- [ ] Create sudoers file: `sudo nano /etc/sudoers.d/deploy`
- [ ] Add these lines:
  ```
  deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl restart laravel-worker:*
  deploy ALL=(ALL) NOPASSWD: /bin/systemctl reload php8.3-fpm
  ```
- [ ] Set correct permissions: `sudo chmod 0440 /etc/sudoers.d/deploy`
- [ ] Verify: `sudo -l -U deploy`

### 5. SSH Key Setup
- [ ] Generate SSH key pair: `ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github_actions`
- [ ] Copy public key to VPS: `ssh-copy-id -i ~/.ssh/github_actions.pub deploy@your-vps-ip`
- [ ] Test SSH connection: `ssh -i ~/.ssh/github_actions deploy@your-vps-ip`
- [ ] Copy private key content: `cat ~/.ssh/github_actions`

### 6. Google OAuth Setup
- [ ] Google Cloud project created
- [ ] OAuth 2.0 credentials created
- [ ] Production client ID noted
- [ ] Authorized redirect URIs configured

## GitHub Secrets Configuration

### Required Secrets
Configure these in: Repository Settings > Secrets and variables > Actions > New repository secret

#### Frontend Secrets
- [ ] `VERCEL_TOKEN` - Vercel authentication token
- [ ] `VERCEL_ORG_ID` - From `.vercel/project.json`
- [ ] `VERCEL_PROJECT_ID` - From `.vercel/project.json`
- [ ] `VITE_API_BASE_URL` - Production API URL (e.g., `https://api.vriddhi.com`)
- [ ] `VITE_GOOGLE_CLIENT_ID` - Google OAuth client ID for production

#### Backend Secrets
- [ ] `VPS_HOST` - VPS IP address or domain
- [ ] `VPS_USER` - SSH user (e.g., `deploy`)
- [ ] `VPS_SSH_KEY` - Complete private SSH key (including BEGIN/END lines)
- [ ] `VPS_PORT` - SSH port (optional, defaults to 22)

### Verify Secrets
- [ ] All 9 secrets are added
- [ ] No typos in secret names
- [ ] Secret values are correct (no extra spaces or newlines)

## Initial Testing

### 1. Test Workflow
- [ ] Create a test branch: `git checkout -b test-ci`
- [ ] Make a small change: `echo "# Test" >> README.md`
- [ ] Commit and push: `git add . && git commit -m "Test CI" && git push origin test-ci`
- [ ] Create pull request to `main` on GitHub
- [ ] Watch test workflow run in Actions tab
- [ ] Verify all tests pass
- [ ] Check test logs for any warnings

### 2. Test Deployment
- [ ] Merge pull request to `main`
- [ ] Watch deploy workflow run in Actions tab
- [ ] Verify test job passes
- [ ] Verify frontend deployment succeeds
- [ ] Verify backend deployment succeeds
- [ ] Check deployment logs for any errors

### 3. Verify Frontend Deployment
- [ ] Visit frontend URL (e.g., `https://vriddhi.com`)
- [ ] Check homepage loads correctly
- [ ] Verify API connection works
- [ ] Test Google OAuth login
- [ ] Check browser console for errors
- [ ] Verify environment variables are correct

### 4. Verify Backend Deployment
- [ ] SSH to VPS: `ssh deploy@your-vps-ip`
- [ ] Check Laravel version: `cd /var/www/vriddhi-api && php artisan --version`
- [ ] Check queue workers: `sudo supervisorctl status`
- [ ] Check PHP-FPM: `sudo systemctl status php8.3-fpm`
- [ ] Check Nginx: `sudo systemctl status nginx`
- [ ] Test API endpoint: `curl https://api.vriddhi.com/health`
- [ ] Check Laravel logs: `tail -f storage/logs/laravel.log`
- [ ] Check Nginx logs: `sudo tail -f /var/log/nginx/error.log`

## Post-Deployment Verification

### Application Health
- [ ] Frontend loads without errors
- [ ] API responds to requests
- [ ] Database connections work
- [ ] Redis connections work
- [ ] Queue workers are processing jobs
- [ ] Email notifications are being sent
- [ ] File uploads work (Cloudflare R2)
- [ ] Google OAuth works
- [ ] Razorpay integration works (if configured)

### Performance Checks
- [ ] Page load times are acceptable
- [ ] API response times are fast
- [ ] Images load correctly
- [ ] CDN is serving assets
- [ ] Cache is working (Redis)
- [ ] Database queries are optimized

### Security Checks
- [ ] HTTPS is enforced
- [ ] Security headers are present
- [ ] CORS is configured correctly
- [ ] Rate limiting works
- [ ] Authentication works
- [ ] Authorization works
- [ ] No sensitive data in logs

## Monitoring Setup

### GitHub Actions
- [ ] Enable email notifications for failed workflows
- [ ] Add status badge to README (optional)
- [ ] Set up Slack/Discord notifications (optional)

### VPS Monitoring
- [ ] Set up server monitoring (e.g., UptimeRobot, Pingdom)
- [ ] Configure log rotation
- [ ] Set up disk space alerts
- [ ] Configure backup schedule
- [ ] Set up error tracking (e.g., Sentry, Bugsnag)

### Application Monitoring
- [ ] Laravel Telescope installed (optional, for debugging)
- [ ] Laravel Horizon installed (optional, for queue monitoring)
- [ ] Application performance monitoring (optional)

## Documentation Review

- [ ] Read `.github/README.md` - Complete CI/CD documentation
- [ ] Read `.github/SETUP_SECRETS.md` - Secrets configuration guide
- [ ] Read `.github/QUICK_REFERENCE.md` - Quick command reference
- [ ] Read `.github/PIPELINE_DIAGRAM.md` - Visual pipeline flow
- [ ] Read `TASK_70_IMPLEMENTATION_SUMMARY.md` - Implementation summary

## Rollback Preparation

### Document Current State
- [ ] Note current commit hash: `git rev-parse HEAD`
- [ ] Note current deployment time
- [ ] Take database backup
- [ ] Document any manual configuration changes

### Test Rollback Procedure
- [ ] Know how to rollback frontend (Vercel dashboard)
- [ ] Know how to rollback backend (git reset)
- [ ] Know how to rollback database (migrations)
- [ ] Have emergency contact information ready

## Ongoing Maintenance

### Weekly
- [ ] Review GitHub Actions logs
- [ ] Check for failed deployments
- [ ] Review VPS logs for errors
- [ ] Check disk space on VPS
- [ ] Review application error logs

### Monthly
- [ ] Update dependencies (composer, npm)
- [ ] Review and rotate secrets if needed
- [ ] Check for security updates
- [ ] Review performance metrics
- [ ] Test backup restoration

### Quarterly
- [ ] Rotate SSH keys
- [ ] Review and update documentation
- [ ] Audit user permissions
- [ ] Review and optimize workflows
- [ ] Test disaster recovery procedures

## Troubleshooting Checklist

### Tests Fail
- [ ] Check test logs in GitHub Actions
- [ ] Verify database migrations
- [ ] Check for missing dependencies
- [ ] Verify environment variables
- [ ] Run tests locally to reproduce

### Frontend Deployment Fails
- [ ] Verify Vercel secrets are correct
- [ ] Check Vercel token hasn't expired
- [ ] Review build logs in GitHub Actions
- [ ] Check for frontend build errors
- [ ] Verify environment variables

### Backend Deployment Fails
- [ ] Verify VPS secrets are correct
- [ ] Test SSH connection manually
- [ ] Check VPS disk space
- [ ] Review deployment logs
- [ ] Check VPS service status
- [ ] Verify file permissions

### Application Issues After Deployment
- [ ] Check Laravel logs
- [ ] Check Nginx error logs
- [ ] Verify .env configuration
- [ ] Check database connection
- [ ] Check Redis connection
- [ ] Verify queue workers are running
- [ ] Clear and rebuild cache

## Emergency Contacts

Document your emergency contacts:

- [ ] VPS Provider Support: _______________
- [ ] Vercel Support: _______________
- [ ] Domain Registrar: _______________
- [ ] Database Administrator: _______________
- [ ] DevOps Lead: _______________
- [ ] Project Manager: _______________

## Sign-Off

### Initial Setup
- [ ] All checklist items completed
- [ ] Pipeline tested and working
- [ ] Documentation reviewed
- [ ] Team trained on deployment process
- [ ] Emergency procedures documented

**Completed by:** _______________  
**Date:** _______________  
**Signature:** _______________

### Production Deployment
- [ ] All tests passing
- [ ] Stakeholders notified
- [ ] Backup completed
- [ ] Rollback plan ready
- [ ] Monitoring active

**Approved by:** _______________  
**Date:** _______________  
**Signature:** _______________

## Notes

Use this section to document any specific configuration, issues encountered, or deviations from the standard setup:

```
_______________________________________________________________
_______________________________________________________________
_______________________________________________________________
_______________________________________________________________
_______________________________________________________________
```

## Quick Links

- [GitHub Actions](https://github.com/YOUR_USERNAME/YOUR_REPO/actions)
- [Vercel Dashboard](https://vercel.com/dashboard)
- [VPS SSH]: `ssh deploy@your-vps-ip`
- [Frontend URL]: https://vriddhi.com
- [Backend URL]: https://api.vriddhi.com
- [Admin Panel]: https://api.vriddhi.com/admin

---

**Last Updated:** [Date]  
**Version:** 1.0  
**Maintained by:** [Your Name/Team]
