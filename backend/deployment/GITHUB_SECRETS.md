# GitHub Secrets Setup Guide

This guide explains how to configure GitHub Secrets for automated deployment of the Artisan Kala platform.

## Table of Contents

1. [Overview](#overview)
2. [Required Secrets](#required-secrets)
3. [Setting Up Secrets](#setting-up-secrets)
4. [Vercel Deployment Secrets](#vercel-deployment-secrets)
5. [VPS Deployment Secrets](#vps-deployment-secrets)
6. [Frontend Environment Secrets](#frontend-environment-secrets)
7. [Security Best Practices](#security-best-practices)
8. [Verification](#verification)
9. [Troubleshooting](#troubleshooting)

## Overview

GitHub Secrets are encrypted environment variables used in GitHub Actions workflows. They allow secure storage of sensitive credentials needed for automated deployments.

**Workflows using secrets:**
- `.github/workflows/deploy.yml` - Automated deployment to Vercel and VPS
- `.github/workflows/test.yml` - Automated testing (no secrets required)

## Required Secrets

### Vercel Deployment (Frontend)

| Secret Name | Description | Required |
|------------|-------------|----------|
| `VERCEL_TOKEN` | Vercel authentication token | Yes |
| `VERCEL_ORG_ID` | Vercel organization ID | Yes |
| `VERCEL_PROJECT_ID` | Vercel project ID | Yes |
| `VITE_API_BASE_URL` | Backend API URL | Yes |
| `VITE_GOOGLE_CLIENT_ID` | Google OAuth Client ID | Yes |

### VPS Deployment (Backend)

| Secret Name | Description | Required |
|------------|-------------|----------|
| `VPS_HOST` | VPS server IP or hostname | Yes |
| `VPS_USER` | SSH username (usually `deploy`) | Yes |
| `VPS_SSH_KEY` | Private SSH key for authentication | Yes |
| `VPS_PORT` | SSH port (default: 22) | No |

## Setting Up Secrets

### Step 1: Access Repository Settings

1. Navigate to your GitHub repository
2. Click **Settings** (top menu)
3. In the left sidebar, click **Secrets and variables** → **Actions**
4. Click **New repository secret**

### Step 2: Add Each Secret

For each secret:
1. Enter the **Name** (exactly as shown in tables above)
2. Enter the **Value** (see sections below for how to obtain)
3. Click **Add secret**

**Important:**
- Secret names are case-sensitive
- Secrets cannot be viewed after creation (only updated)
- Use repository secrets (not environment secrets) for simplicity

## Vercel Deployment Secrets

### VERCEL_TOKEN

**What it is:** Authentication token for Vercel API

**How to obtain:**

1. Log in to [Vercel Dashboard](https://vercel.com/dashboard)
2. Click your profile picture → **Settings**
3. Navigate to **Tokens**
4. Click **Create Token**
5. Name it: `GitHub Actions - Artisan Kala`
6. Set scope: **Full Account**
7. Set expiration: **No Expiration** (or set reminder to rotate)
8. Click **Create Token**
9. **Copy the token immediately** (you won't see it again)

**Example value:**
```
vercel_1234567890abcdefghijklmnopqrstuvwxyz
```

### VERCEL_ORG_ID

**What it is:** Your Vercel organization/team ID

**How to obtain:**

1. Log in to [Vercel Dashboard](https://vercel.com/dashboard)
2. Navigate to **Settings** → **General**
3. Scroll to **Organization ID** or **Team ID**
4. Copy the ID

**Alternative method:**
```bash
# Install Vercel CLI
npm i -g vercel

# Login
vercel login

# Link project (run in frontend directory)
cd artisan-kala-frontend
vercel link

# View project settings
cat .vercel/project.json
```

**Example value:**
```
team_abc123xyz789
```

### VERCEL_PROJECT_ID

**What it is:** Your Vercel project ID

**How to obtain:**

1. Log in to [Vercel Dashboard](https://vercel.com/dashboard)
2. Select your project: **artisan-kala-frontend**
3. Go to **Settings** → **General**
4. Scroll to **Project ID**
5. Copy the ID

**Alternative method:**
```bash
# After running 'vercel link' above
cat .vercel/project.json
```

**Example value:**
```
prj_abc123xyz789def456
```

### VITE_API_BASE_URL

**What it is:** Backend API URL for frontend to connect to

**Value:**
```
https://api.artisankala.com
```

**Important:**
- Must be the full URL with `https://`
- No trailing slash
- Must match your actual API domain
- Used by frontend to make API requests

### VITE_GOOGLE_CLIENT_ID

**What it is:** Google OAuth Client ID for frontend authentication

**How to obtain:**

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project
3. Navigate to **APIs & Services** → **Credentials**
4. Find your OAuth 2.0 Client ID
5. Copy the **Client ID** (not the secret)

**Example value:**
```
123456789012-abcdefghijklmnopqrstuvwxyz.apps.googleusercontent.com
```

**Important:**
- This is the same Client ID used in backend `.env`
- This is public and safe to expose in frontend code
- Do NOT use the Client Secret here

## VPS Deployment Secrets

### VPS_HOST

**What it is:** IP address or hostname of your VPS server

**Value examples:**
```
203.0.113.42
```
or
```
api.artisankala.com
```

**Recommendation:**
- Use IP address for reliability (DNS-independent)
- Ensure IP is static (not dynamic)

### VPS_USER

**What it is:** SSH username for deployment

**Value:**
```
deploy
```

**Important:**
- Should be the non-root user created during VPS setup
- Must have sudo privileges for service restarts
- Must have write access to `/var/www/artisan-kala-api`

### VPS_SSH_KEY

**What it is:** Private SSH key for passwordless authentication

**How to generate:**

```bash
# On your local machine
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/github_actions_deploy

# This creates two files:
# - github_actions_deploy (private key) - for GitHub Secret
# - github_actions_deploy.pub (public key) - for VPS
```

**How to add public key to VPS:**

```bash
# Copy public key to VPS
ssh-copy-id -i ~/.ssh/github_actions_deploy.pub deploy@YOUR_VPS_IP

# Or manually:
cat ~/.ssh/github_actions_deploy.pub
# Copy the output

# SSH into VPS
ssh deploy@YOUR_VPS_IP

# Add to authorized_keys
mkdir -p ~/.ssh
chmod 700 ~/.ssh
nano ~/.ssh/authorized_keys
# Paste the public key
chmod 600 ~/.ssh/authorized_keys
```

**How to add private key to GitHub:**

```bash
# Display private key
cat ~/.ssh/github_actions_deploy

# Copy the ENTIRE output including:
# -----BEGIN OPENSSH PRIVATE KEY-----
# ... key content ...
# -----END OPENSSH PRIVATE KEY-----
```

Paste this entire content as the `VPS_SSH_KEY` secret value.

**Example value:**
```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
QyNTUxOQAAACBqL9Zq8Qx7K5J3N2M4P6R8S1T9U0V2W3X4Y5Z6A7B8C9D0EwAAAJgK1234
... (many more lines) ...
-----END OPENSSH PRIVATE KEY-----
```

**Security:**
- Never share the private key
- Keep the private key file secure on your local machine
- Consider using a passphrase (requires additional workflow configuration)
- Rotate keys periodically (every 6-12 months)

### VPS_PORT (Optional)

**What it is:** SSH port number

**Default value:**
```
22
```

**When to set:**
- Only if you changed the default SSH port
- Common alternative: `2222`

**If not set:**
- Workflow defaults to port 22

## Security Best Practices

### Secret Rotation

**Recommended rotation schedule:**
- `VERCEL_TOKEN`: Every 6 months
- `VPS_SSH_KEY`: Every 6-12 months
- `VITE_GOOGLE_CLIENT_ID`: Only when compromised
- Other secrets: When compromised or team member leaves

**How to rotate:**
1. Generate new credential
2. Update GitHub Secret
3. Test deployment
4. Revoke old credential

### Access Control

**Limit repository access:**
- Only give write access to trusted team members
- Use branch protection rules
- Require pull request reviews
- Enable two-factor authentication

**Audit secret usage:**
- Review workflow runs regularly
- Check for failed deployments
- Monitor for unauthorized access attempts

### Secret Hygiene

**Do:**
- Use descriptive secret names
- Document what each secret is for
- Keep secrets up to date
- Remove unused secrets

**Don't:**
- Commit secrets to code
- Share secrets via chat/email
- Use the same secret across multiple projects
- Log secret values in workflows

### Monitoring

**Set up alerts for:**
- Failed deployments
- Unauthorized access attempts
- Expired tokens
- SSH key usage

**GitHub provides:**
- Workflow run history
- Deployment logs (secrets are masked)
- Security alerts

## Verification

### Step 1: Verify Secrets Are Set

1. Go to repository **Settings** → **Secrets and variables** → **Actions**
2. Verify all required secrets are listed
3. Check for typos in secret names

### Step 2: Test Vercel Deployment

**Trigger deployment:**
```bash
# Make a small change and push
git commit --allow-empty -m "Test deployment"
git push origin main
```

**Check workflow:**
1. Go to **Actions** tab in GitHub
2. Click on the latest workflow run
3. Expand **deploy-frontend** job
4. Check for errors in **Deploy to Vercel** step

**Expected output:**
```
✓ Deployment complete
✓ Production: https://artisan-kala-frontend.vercel.app
```

### Step 3: Test VPS Deployment

**Check workflow:**
1. In the same workflow run
2. Expand **deploy-backend** job
3. Check for errors in **Deploy to VPS** step

**Expected output:**
```
✓ Pulling latest code
✓ Installing dependencies
✓ Running migrations
✓ Clearing cache
✓ Restarting services
✓ Deployment completed successfully!
```

### Step 4: Verify Application

**Frontend:**
```bash
curl https://artisankala.com
# Should return HTML
```

**Backend:**
```bash
curl https://api.artisankala.com/health
# Should return {"status": "ok"}
```

## Troubleshooting

### Vercel Deployment Failed

**Error:** `Invalid token`

**Solution:**
1. Verify `VERCEL_TOKEN` is correct
2. Check token hasn't expired
3. Regenerate token if needed
4. Update GitHub Secret

**Error:** `Project not found`

**Solution:**
1. Verify `VERCEL_PROJECT_ID` is correct
2. Check project exists in Vercel dashboard
3. Ensure token has access to the project

### VPS Deployment Failed

**Error:** `Permission denied (publickey)`

**Solution:**
1. Verify `VPS_SSH_KEY` contains the complete private key
2. Check public key is in `~/.ssh/authorized_keys` on VPS
3. Verify `VPS_USER` is correct
4. Test SSH connection manually:
   ```bash
   ssh -i ~/.ssh/github_actions_deploy deploy@YOUR_VPS_IP
   ```

**Error:** `Host key verification failed`

**Solution:**
1. SSH into VPS manually once to accept host key
2. Or add to workflow:
   ```yaml
   - name: Add VPS to known hosts
     run: ssh-keyscan -H ${{ secrets.VPS_HOST }} >> ~/.ssh/known_hosts
   ```

**Error:** `git pull failed`

**Solution:**
1. SSH into VPS
2. Check Git repository status:
   ```bash
   cd /var/www/artisan-kala-api
   git status
   ```
3. Resolve any conflicts or uncommitted changes
4. Ensure deploy user has read access to repository

**Error:** `composer install failed`

**Solution:**
1. Check Composer is installed on VPS
2. Verify PHP version is correct (8.3)
3. Check for memory issues:
   ```bash
   php -d memory_limit=-1 /usr/local/bin/composer install
   ```

**Error:** `php artisan migrate failed`

**Solution:**
1. Check database credentials in `.env`
2. Verify database is running
3. Check migration files for errors
4. Review Laravel logs on VPS

### Frontend Environment Variables Not Working

**Error:** `API_BASE_URL is undefined`

**Solution:**
1. Verify `VITE_API_BASE_URL` is set in GitHub Secrets
2. Check secret name has `VITE_` prefix (required by Vite)
3. Rebuild frontend:
   ```bash
   npm run build
   ```
4. Check build output includes environment variables

### Secrets Not Updating

**Issue:** Changed secret but deployment still uses old value

**Solution:**
1. Secrets are cached during workflow run
2. Trigger a new workflow run:
   ```bash
   git commit --allow-empty -m "Refresh secrets"
   git push
   ```
3. Check workflow uses latest secret value

## Advanced Configuration

### Using Environment Secrets

For multiple environments (staging, production):

1. Create environments in repository settings
2. Add environment-specific secrets
3. Update workflow to use environments:
   ```yaml
   deploy-frontend:
     environment: production
     steps:
       - name: Deploy
         env:
           API_URL: ${{ secrets.VITE_API_BASE_URL }}
   ```

### Using GitHub Actions Variables

For non-sensitive configuration:

1. Go to **Settings** → **Secrets and variables** → **Actions**
2. Click **Variables** tab
3. Add variables (e.g., `DEPLOYMENT_REGION`, `NODE_VERSION`)
4. Use in workflow: `${{ vars.DEPLOYMENT_REGION }}`

### Encrypted Secrets in Repository

For team collaboration:

1. Use `git-crypt` or `sops` to encrypt secrets file
2. Commit encrypted file to repository
3. Decrypt during workflow run
4. Requires additional setup

## Additional Resources

- [GitHub Secrets Documentation](https://docs.github.com/en/actions/security-guides/encrypted-secrets)
- [Vercel CLI Documentation](https://vercel.com/docs/cli)
- [SSH Key Authentication](https://www.ssh.com/academy/ssh/public-key-authentication)
- [GitHub Actions Security Best Practices](https://docs.github.com/en/actions/security-guides/security-hardening-for-github-actions)

## Support

If you encounter issues:

1. Check workflow logs in GitHub Actions tab
2. Review error messages carefully
3. Test credentials manually before adding to GitHub
4. Consult the [Deployment Checklist](DEPLOYMENT_CHECKLIST.md)
5. Review the [Environment Setup Guide](ENVIRONMENT_SETUP.md)

