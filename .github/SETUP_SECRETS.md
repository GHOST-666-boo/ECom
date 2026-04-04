# GitHub Secrets Setup Guide

This document explains how to configure GitHub Secrets for the CI/CD pipeline.

## Required Secrets

### Frontend Deployment (Vercel)

1. **VERCEL_TOKEN**
   - Description: Vercel authentication token
   - How to get:
     1. Go to https://vercel.com/account/tokens
     2. Click "Create Token"
     3. Give it a name (e.g., "GitHub Actions")
     4. Copy the token

2. **VERCEL_ORG_ID**
   - Description: Your Vercel organization/team ID
   - How to get:
     1. Install Vercel CLI: `npm i -g vercel`
     2. Run `vercel link` in your frontend directory
     3. Open `.vercel/project.json`
     4. Copy the `orgId` value

3. **VERCEL_PROJECT_ID**
   - Description: Your Vercel project ID
   - How to get:
     1. Same as above - run `vercel link`
     2. Open `.vercel/project.json`
     3. Copy the `projectId` value

4. **VITE_API_BASE_URL**
   - Description: Production API URL
   - Example: `https://api.artisankala.com`

5. **VITE_GOOGLE_CLIENT_ID**
   - Description: Google OAuth client ID for production
   - How to get:
     1. Go to https://console.cloud.google.com/
     2. Select your project
     3. Navigate to "APIs & Services" > "Credentials"
     4. Copy the OAuth 2.0 Client ID

### Backend Deployment (VPS)

1. **VPS_HOST**
   - Description: Your VPS IP address or domain
   - Example: `123.45.67.89` or `server.artisankala.com`

2. **VPS_USER**
   - Description: SSH user for deployment
   - Example: `deploy`
   - Note: Should be the user that owns `/var/www/artisan-kala-api`

3. **VPS_SSH_KEY**
   - Description: Private SSH key for authentication
   - How to get:
     1. Generate SSH key pair on your local machine:
        ```bash
        ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github_actions
        ```
     2. Copy the public key to your VPS:
        ```bash
        ssh-copy-id -i ~/.ssh/github_actions.pub deploy@your-vps-ip
        ```
     3. Copy the **private key** content:
        ```bash
        cat ~/.ssh/github_actions
        ```
     4. Paste the entire private key (including BEGIN and END lines) as the secret value

4. **VPS_PORT** (Optional)
   - Description: SSH port (defaults to 22)
   - Example: `22`
   - Only needed if you use a non-standard SSH port

## How to Add Secrets to GitHub

1. Go to your GitHub repository
2. Click on "Settings" tab
3. In the left sidebar, click "Secrets and variables" > "Actions"
4. Click "New repository secret"
5. Enter the secret name (exactly as listed above)
6. Paste the secret value
7. Click "Add secret"
8. Repeat for all required secrets

## Verifying Secrets

After adding all secrets, you can verify they're set correctly:

1. Go to "Settings" > "Secrets and variables" > "Actions"
2. You should see all the secret names listed (values are hidden)
3. Make sure there are no typos in the secret names

## Testing the Pipeline

Once all secrets are configured:

1. Push a commit to the `main` branch
2. Go to "Actions" tab in your repository
3. Watch the "Deploy to Production" workflow run
4. Check each job (test, deploy-frontend, deploy-backend) for success

## Troubleshooting

### Frontend deployment fails

- Verify `VERCEL_TOKEN`, `VERCEL_ORG_ID`, and `VERCEL_PROJECT_ID` are correct
- Check that the Vercel token has not expired
- Ensure the Vercel project is linked to your repository

### Backend deployment fails

- Verify `VPS_HOST`, `VPS_USER`, and `VPS_SSH_KEY` are correct
- Test SSH connection manually:
  ```bash
  ssh -i ~/.ssh/github_actions deploy@your-vps-ip
  ```
- Ensure the deploy user has sudo permissions for:
  - `supervisorctl restart laravel-worker:*`
  - `systemctl reload php8.3-fpm`
- Check that `/var/www/artisan-kala-api` exists and is a git repository
- Verify the deploy user owns the directory:
  ```bash
  sudo chown -R deploy:www-data /var/www/artisan-kala-api
  ```

### Tests fail

- Check the test logs in the Actions tab
- Ensure all required services (MySQL, Redis) are running in the workflow
- Verify database credentials in the workflow match your test configuration

## Security Best Practices

1. **Never commit secrets to the repository**
   - Always use GitHub Secrets for sensitive data
   - Add `.env` files to `.gitignore`

2. **Rotate secrets regularly**
   - Update SSH keys every 6-12 months
   - Regenerate API tokens periodically

3. **Use least privilege**
   - The VPS deploy user should only have permissions needed for deployment
   - Don't use root user for deployments

4. **Monitor access**
   - Review GitHub Actions logs regularly
   - Check VPS access logs for unauthorized attempts

## Additional Resources

- [GitHub Actions Secrets Documentation](https://docs.github.com/en/actions/security-guides/encrypted-secrets)
- [Vercel CLI Documentation](https://vercel.com/docs/cli)
- [SSH Key Authentication Guide](https://www.ssh.com/academy/ssh/keygen)
