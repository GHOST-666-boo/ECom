# CI/CD Pipeline Documentation

This directory contains GitHub Actions workflows for automated testing and deployment of the Vriddhi e-commerce platform.

## Overview

The CI/CD pipeline consists of two main workflows:

1. **Test Workflow** (`test.yml`) - Runs on every push and pull request
2. **Deploy Workflow** (`deploy.yml`) - Runs on push to main branch after tests pass

## Workflows

### 1. Test Workflow

**Trigger:** Push or pull request to `main` or `develop` branches

**What it does:**
- Sets up PHP 8.3 environment with required extensions
- Configures MySQL 8.0 and Redis 7.0 services
- Installs Composer dependencies
- Runs database migrations
- Executes Pest test suite
- Checks code style with Laravel Pint
- Performs static analysis with PHPStan/Larastan

**Services:**
- MySQL 8.0 (port 3306)
- Redis 7.0 (port 6379)

**Duration:** ~5-10 minutes

### 2. Deploy Workflow

**Trigger:** Push to `main` branch or manual dispatch

**What it does:**

#### Job 1: Test
- Runs the same tests as the test workflow
- Must pass before deployment proceeds

#### Job 2: Deploy Frontend
- Builds the React frontend with Vite
- Deploys to Vercel with production configuration
- Uses environment variables from GitHub Secrets

#### Job 3: Deploy Backend
- Connects to VPS via SSH
- Pulls latest code from main branch
- Installs/updates Composer dependencies
- Runs database migrations
- Clears and rebuilds Laravel cache
- Restarts queue workers via Supervisor
- Reloads PHP-FPM

**Duration:** ~10-15 minutes

## Setup Instructions

### Prerequisites

1. **GitHub Repository**
   - Repository must be connected to GitHub
   - Actions must be enabled (Settings > Actions > General)

2. **Vercel Account**
   - Project must be created on Vercel
   - Frontend repository must be linked

3. **VPS Server**
   - Ubuntu 24.04 with Nginx, PHP 8.3-FPM, MySQL, Redis
   - SSH access configured
   - Laravel application deployed at `/var/www/vriddhi-api`
   - Supervisor configured for queue workers
   - Deploy user with appropriate permissions

### Step 1: Configure GitHub Secrets

See [SETUP_SECRETS.md](./SETUP_SECRETS.md) for detailed instructions.

Required secrets:
- `VERCEL_TOKEN`
- `VERCEL_ORG_ID`
- `VERCEL_PROJECT_ID`
- `VITE_API_BASE_URL`
- `VITE_GOOGLE_CLIENT_ID`
- `VPS_HOST`
- `VPS_USER`
- `VPS_SSH_KEY`
- `VPS_PORT` (optional, defaults to 22)

### Step 2: Configure VPS Permissions

The deploy user needs sudo permissions for specific commands. Add to `/etc/sudoers.d/deploy`:

```bash
deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl restart laravel-worker:*
deploy ALL=(ALL) NOPASSWD: /bin/systemctl reload php8.3-fpm
```

### Step 3: Test the Pipeline

1. Make a small change to your code
2. Commit and push to a feature branch
3. Create a pull request to `main`
4. Watch the test workflow run
5. Merge the pull request
6. Watch the deploy workflow run

## Workflow Files

### test.yml

```yaml
name: Run Tests
on: [push, pull_request]
jobs:
  test:
    - Setup PHP 8.3
    - Install dependencies
    - Run migrations
    - Run Pest tests
    - Run Laravel Pint
    - Run PHPStan
```

### deploy.yml

```yaml
name: Deploy to Production
on:
  push:
    branches: [main]
jobs:
  test: [Same as test.yml]
  deploy-frontend:
    - Build React app
    - Deploy to Vercel
  deploy-backend:
    - SSH to VPS
    - Pull code
    - Update dependencies
    - Run migrations
    - Clear/rebuild cache
    - Restart services
```

## Monitoring and Debugging

### Viewing Workflow Runs

1. Go to your repository on GitHub
2. Click the "Actions" tab
3. Select a workflow run to view details
4. Click on individual jobs to see logs

### Common Issues

#### Tests Fail

**Symptom:** Test job fails with database errors

**Solution:**
- Check MySQL service is running in workflow
- Verify database credentials in workflow match test configuration
- Ensure migrations are running before tests

#### Frontend Deployment Fails

**Symptom:** Deploy-frontend job fails

**Solution:**
- Verify Vercel secrets are correct
- Check Vercel token hasn't expired
- Ensure Vercel project is linked correctly
- Check build logs for frontend errors

#### Backend Deployment Fails

**Symptom:** Deploy-backend job fails with SSH errors

**Solution:**
- Test SSH connection manually
- Verify VPS_SSH_KEY is the complete private key
- Check VPS_HOST and VPS_USER are correct
- Ensure deploy user has correct permissions

**Symptom:** Deployment succeeds but site doesn't work

**Solution:**
- SSH to VPS and check Laravel logs: `tail -f /var/www/vriddhi-api/storage/logs/laravel.log`
- Check Nginx error logs: `sudo tail -f /var/log/nginx/error.log`
- Verify .env file has correct production values
- Check queue workers are running: `sudo supervisorctl status`

### Manual Deployment

If automated deployment fails, you can deploy manually:

```bash
# SSH to VPS
ssh deploy@your-vps-ip

# Navigate to project
cd /var/www/vriddhi-api

# Pull latest code
git pull origin main

# Update dependencies
composer install --optimize-autoloader --no-dev

# Run migrations
php artisan migrate --force

# Clear and rebuild cache
php artisan config:clear && php artisan config:cache
php artisan route:clear && php artisan route:cache
php artisan view:clear && php artisan view:cache

# Restart services
sudo supervisorctl restart laravel-worker:*
sudo systemctl reload php8.3-fpm
```

## Rollback Procedure

If a deployment causes issues:

1. **Immediate rollback:**
   ```bash
   ssh deploy@your-vps-ip
   cd /var/www/vriddhi-api
   git reset --hard HEAD~1
   composer install --optimize-autoloader --no-dev
   php artisan migrate:rollback
   php artisan config:cache
   php artisan route:cache
   sudo supervisorctl restart laravel-worker:*
   sudo systemctl reload php8.3-fpm
   ```

2. **Revert via GitHub:**
   - Find the last working commit
   - Create a revert commit
   - Push to main branch
   - Let the pipeline redeploy

## Performance Optimization

### Caching Dependencies

The workflows cache Composer and npm dependencies to speed up builds:

```yaml
- uses: actions/cache@v3
  with:
    path: vendor
    key: composer-${{ hashFiles('**/composer.lock') }}
```

### Parallel Jobs

Frontend and backend deployments run in parallel after tests pass, reducing total deployment time.

### Conditional Steps

PHPStan runs with `continue-on-error: true` to not block deployments on static analysis warnings.

## Security Considerations

1. **Secrets Management**
   - Never commit secrets to repository
   - Rotate SSH keys regularly
   - Use least privilege for deploy user

2. **SSH Security**
   - Use SSH keys, not passwords
   - Consider IP whitelisting on VPS
   - Monitor SSH access logs

3. **Deployment Safety**
   - Tests must pass before deployment
   - Database migrations run with `--force` flag
   - Queue workers restart to pick up new code

## Maintenance

### Updating Workflows

When modifying workflows:
1. Test changes on a feature branch first
2. Use `workflow_dispatch` trigger for manual testing
3. Monitor first few runs after changes

### Updating Dependencies

When updating PHP, Node, or service versions:
1. Update in workflows first
2. Test thoroughly
3. Update VPS to match
4. Document version requirements

## Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Vercel Deployment Documentation](https://vercel.com/docs/deployments/overview)
- [Laravel Deployment Best Practices](https://laravel.com/docs/deployment)
- [Supervisor Documentation](http://supervisord.org/)

## Support

For issues with the CI/CD pipeline:
1. Check workflow logs in GitHub Actions
2. Review this documentation
3. Check VPS logs for deployment issues
4. Consult [SETUP_SECRETS.md](./SETUP_SECRETS.md) for configuration help
