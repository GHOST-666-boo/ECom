# Vercel Deployment Guide for Artisan Kala Frontend

This guide provides comprehensive instructions for deploying the Artisan Kala React frontend to Vercel with automatic deployments, environment variable configuration, and production best practices.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Initial Setup](#initial-setup)
3. [Connecting GitHub Repository](#connecting-github-repository)
4. [Configuring Build Settings](#configuring-build-settings)
5. [Setting Environment Variables](#setting-environment-variables)
6. [Enabling Automatic Deployments](#enabling-automatic-deployments)
7. [Domain Configuration](#domain-configuration)
8. [Monitoring and Logs](#monitoring-and-logs)
9. [Troubleshooting](#troubleshooting)

## Prerequisites

Before deploying to Vercel, ensure you have:

- A GitHub account with the Artisan Kala repository
- A Vercel account (sign up at [vercel.com](https://vercel.com))
- Production API endpoint URL
- Google OAuth Client ID for production
- Razorpay Key ID for production
- Admin access to the GitHub repository

## Initial Setup

### Step 1: Create Vercel Account

1. Go to [vercel.com](https://vercel.com)
2. Click "Sign Up" and choose "Continue with GitHub"
3. Authorize Vercel to access your GitHub account
4. Complete the account setup process

### Step 2: Install Vercel CLI (Optional)

For local testing and manual deployments:

```bash
npm install -g vercel
vercel login
```

## Connecting GitHub Repository

### Step 1: Import Project

1. Log in to your Vercel dashboard at [vercel.com/dashboard](https://vercel.com/dashboard)
2. Click "Add New..." → "Project"
3. In the "Import Git Repository" section, find your GitHub organization/account
4. If this is your first time, click "Add GitHub Account" and authorize Vercel
5. Search for "artisan-kala" or navigate to find the repository
6. Click "Import" next to the Artisan Kala repository

### Step 2: Configure Project Settings

On the project configuration screen:

1. **Project Name**: `artisan-kala-frontend` (or your preferred name)
2. **Framework Preset**: Vercel should auto-detect "Vite" - if not, select it manually
3. **Root Directory**: `artisan-kala-frontend` (if your frontend is in a subdirectory)
   - Click "Edit" next to Root Directory
   - Enter `artisan-kala-frontend`
   - Click "Continue"

## Configuring Build Settings

### Build Configuration

Vercel will automatically detect the build settings from `package.json`, but verify:

1. **Build Command**: `npm run build`
2. **Output Directory**: `dist`
3. **Install Command**: `npm install`
4. **Development Command**: `npm run dev`

These settings are also defined in `vercel.json` for consistency.

### Build & Development Settings (Advanced)

If you need to customize:

1. In your project dashboard, go to "Settings" → "General"
2. Scroll to "Build & Development Settings"
3. Verify or update:
   - **Framework Preset**: Vite
   - **Build Command**: `npm run build`
   - **Output Directory**: `dist`
   - **Install Command**: `npm install`

### Node.js Version

Vercel uses Node.js 20.x by default. To specify a version:

1. Add to `package.json`:
```json
{
  "engines": {
    "node": ">=18.0.0"
  }
}
```

## Setting Environment Variables

Environment variables are critical for connecting to your API and third-party services.

### Step 1: Navigate to Environment Variables

1. In your Vercel project dashboard, click "Settings"
2. Click "Environment Variables" in the left sidebar

### Step 2: Add Required Variables

Add the following environment variables:

#### VITE_API_BASE_URL

- **Key**: `VITE_API_BASE_URL`
- **Value**: Your production API URL (e.g., `https://api.artisankala.com/api/v1`)
- **Environments**: Select "Production", "Preview", and "Development"
- Click "Save"

**Important**: Do NOT include a trailing slash in the URL.

#### VITE_GOOGLE_CLIENT_ID

- **Key**: `VITE_GOOGLE_CLIENT_ID`
- **Value**: Your Google OAuth Client ID for production
- **Environments**: Select "Production" and "Preview"
- Click "Save"

**How to get Google Client ID**:
1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Select your project or create a new one
3. Navigate to "APIs & Services" → "Credentials"
4. Create OAuth 2.0 Client ID (Web application)
5. Add your Vercel domain to "Authorized JavaScript origins"
6. Copy the Client ID

#### VITE_RAZORPAY_KEY_ID

- **Key**: `VITE_RAZORPAY_KEY_ID`
- **Value**: Your Razorpay Key ID for production
- **Environments**: Select "Production" and "Preview"
- Click "Save"

**How to get Razorpay Key ID**:
1. Log in to [Razorpay Dashboard](https://dashboard.razorpay.com)
2. Go to "Settings" → "API Keys"
3. Generate keys for production mode
4. Copy the Key ID (starts with `rzp_live_`)

### Step 3: Environment-Specific Variables

For different environments:

- **Production**: Used for your main domain (e.g., artisankala.com)
- **Preview**: Used for preview deployments (pull requests)
- **Development**: Used for local development via `vercel dev`

You can set different values for each environment. For example:

- Production: `https://api.artisankala.com/api/v1`
- Preview: `https://staging-api.artisankala.com/api/v1`
- Development: `http://localhost:8000/api/v1`

### Step 4: Verify Environment Variables

After adding all variables:

1. Go to "Deployments" tab
2. Click "Redeploy" on the latest deployment
3. Select "Use existing Build Cache" (optional)
4. Click "Redeploy"

## Enabling Automatic Deployments

Automatic deployments trigger on every push to your repository.

### Step 1: Configure Git Integration

1. In project "Settings", go to "Git"
2. Verify the connected repository is correct
3. **Production Branch**: Set to `main` (or your production branch)
   - Deployments from this branch go to production
4. **Preview Branches**: Enable "All branches"
   - Creates preview deployments for all other branches

### Step 2: Enable Automatic Deployments

Automatic deployments are enabled by default. Verify:

1. In "Settings" → "Git"
2. Ensure "Automatically deploy new commits" is enabled
3. Configure deployment protection (optional):
   - Enable "Deployment Protection" for production
   - Require approval before deploying to production

### Step 3: Configure Pull Request Comments

Vercel can comment on pull requests with preview URLs:

1. In "Settings" → "Git"
2. Enable "Comments on Pull Requests"
3. Enable "Comments on Commits"

### Step 4: Test Automatic Deployment

1. Make a small change to your repository (e.g., update README)
2. Commit and push to a feature branch
3. Create a pull request to `main`
4. Vercel will automatically create a preview deployment
5. Check the PR for a comment with the preview URL
6. Merge the PR to trigger a production deployment

## Domain Configuration

### Step 1: Add Custom Domain

1. In project dashboard, go to "Settings" → "Domains"
2. Click "Add Domain"
3. Enter your domain (e.g., `artisankala.com` or `www.artisankala.com`)
4. Click "Add"

### Step 2: Configure DNS

Vercel will provide DNS configuration instructions:

**For apex domain (artisankala.com)**:
- Type: `A`
- Name: `@`
- Value: `76.76.21.21` (Vercel's IP)

**For www subdomain**:
- Type: `CNAME`
- Name: `www`
- Value: `cname.vercel-dns.com`

### Step 3: Verify Domain

1. Update your DNS records with your domain registrar
2. Wait for DNS propagation (can take up to 48 hours, usually faster)
3. Vercel will automatically verify and issue SSL certificate
4. Your site will be available at your custom domain with HTTPS

### Step 4: Redirect www to apex (or vice versa)

1. In "Settings" → "Domains"
2. Click the three dots next to a domain
3. Select "Redirect to..." and choose the primary domain

## Monitoring and Logs

### Deployment Logs

1. Go to "Deployments" tab
2. Click on any deployment
3. View build logs, function logs, and deployment details

### Runtime Logs

1. In project dashboard, click "Logs" (if available in your plan)
2. View real-time logs from your application
3. Filter by severity, time range, or search terms

### Analytics

1. Go to "Analytics" tab
2. View page views, top pages, and performance metrics
3. Upgrade to Pro plan for detailed analytics

### Performance Monitoring

1. Go to "Speed Insights" (Pro plan feature)
2. View Core Web Vitals and performance scores
3. Identify performance bottlenecks

## Troubleshooting

### Build Failures

**Issue**: Build fails with "Command failed: npm run build"

**Solutions**:
1. Check build logs for specific errors
2. Verify all dependencies are in `package.json`
3. Test build locally: `npm run build`
4. Check Node.js version compatibility
5. Clear build cache and redeploy

**Issue**: Environment variables not available during build

**Solutions**:
1. Ensure variables are prefixed with `VITE_`
2. Verify variables are set for the correct environment
3. Redeploy after adding variables

### Runtime Errors

**Issue**: API calls fail with CORS errors

**Solutions**:
1. Verify `VITE_API_BASE_URL` is correct
2. Check API CORS configuration allows your Vercel domain
3. Ensure API is accessible from the internet

**Issue**: Google OAuth not working

**Solutions**:
1. Verify `VITE_GOOGLE_CLIENT_ID` is correct
2. Add Vercel domain to Google OAuth authorized origins
3. Check browser console for specific errors

**Issue**: Razorpay integration fails

**Solutions**:
1. Verify `VITE_RAZORPAY_KEY_ID` is correct
2. Ensure using production key (starts with `rzp_live_`)
3. Check Razorpay dashboard for API status

### Deployment Issues

**Issue**: Deployment stuck in "Building" state

**Solutions**:
1. Cancel deployment and retry
2. Check Vercel status page: [vercel-status.com](https://www.vercel-status.com)
3. Contact Vercel support if issue persists

**Issue**: Old version still showing after deployment

**Solutions**:
1. Hard refresh browser (Ctrl+Shift+R or Cmd+Shift+R)
2. Clear browser cache
3. Check deployment logs to ensure build succeeded
4. Verify correct branch was deployed

### DNS Issues

**Issue**: Domain not resolving after 48 hours

**Solutions**:
1. Verify DNS records are correct
2. Use DNS checker tool: [dnschecker.org](https://dnschecker.org)
3. Check for conflicting DNS records
4. Contact domain registrar support

**Issue**: SSL certificate not issued

**Solutions**:
1. Ensure DNS is properly configured
2. Wait for DNS propagation to complete
3. Remove and re-add domain in Vercel
4. Check for CAA records blocking certificate issuance

## Best Practices

### Security

1. **Never commit `.env` files** - Use Vercel environment variables
2. **Use environment-specific variables** - Different keys for production/preview
3. **Enable Deployment Protection** - Require approval for production deploys
4. **Review security headers** - Configured in `vercel.json`

### Performance

1. **Enable caching** - Configured in `vercel.json` for static assets
2. **Optimize images** - Use lazy loading and modern formats
3. **Monitor bundle size** - Keep JavaScript bundles small
4. **Use CDN** - Vercel automatically serves via global CDN

### Workflow

1. **Use preview deployments** - Test changes before merging to main
2. **Review deployment logs** - Check for warnings or errors
3. **Monitor analytics** - Track performance and user behavior
4. **Set up notifications** - Get alerts for deployment failures

## Additional Resources

- [Vercel Documentation](https://vercel.com/docs)
- [Vite Deployment Guide](https://vitejs.dev/guide/static-deploy.html)
- [Vercel CLI Reference](https://vercel.com/docs/cli)
- [Environment Variables Guide](https://vercel.com/docs/concepts/projects/environment-variables)
- [Custom Domains Guide](https://vercel.com/docs/concepts/projects/domains)

## Support

- **Vercel Support**: [vercel.com/support](https://vercel.com/support)
- **Community**: [github.com/vercel/vercel/discussions](https://github.com/vercel/vercel/discussions)
- **Status Page**: [vercel-status.com](https://www.vercel-status.com)

---

**Last Updated**: 2024
**Version**: 1.0
