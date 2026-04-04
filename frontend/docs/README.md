# Artisan Kala Frontend Documentation

This directory contains comprehensive documentation for deploying and managing the Artisan Kala frontend application.

## Documentation Index

### Deployment Guides

1. **[VERCEL_QUICK_START.md](./VERCEL_QUICK_START.md)** - 5-minute quick start guide
   - Fast setup for experienced users
   - Essential configuration only
   - Common issues and solutions

2. **[VERCEL_DEPLOYMENT.md](./VERCEL_DEPLOYMENT.md)** - Complete deployment guide
   - Detailed step-by-step instructions
   - Comprehensive troubleshooting
   - Best practices and security
   - Domain configuration
   - Monitoring and analytics

3. **[DEPLOYMENT_CHECKLIST.md](./DEPLOYMENT_CHECKLIST.md)** - Interactive checklist
   - Track deployment progress
   - Ensure nothing is missed
   - Testing verification
   - Post-deployment tasks

## Quick Links

### Getting Started

- **New to Vercel?** Start with [VERCEL_QUICK_START.md](./VERCEL_QUICK_START.md)
- **Need detailed instructions?** See [VERCEL_DEPLOYMENT.md](./VERCEL_DEPLOYMENT.md)
- **Want to track progress?** Use [DEPLOYMENT_CHECKLIST.md](./DEPLOYMENT_CHECKLIST.md)

### External Resources

- [Vercel Documentation](https://vercel.com/docs)
- [Vite Deployment Guide](https://vitejs.dev/guide/static-deploy.html)
- [Vercel CLI Reference](https://vercel.com/docs/cli)
- [Vercel Support](https://vercel.com/support)

## Deployment Overview

The Artisan Kala frontend is a React SPA built with Vite and deployed to Vercel. The deployment process includes:

1. **Project Import** - Connect GitHub repository to Vercel
2. **Build Configuration** - Configure build command and output directory
3. **Environment Variables** - Set API URL, Google OAuth, and Razorpay keys
4. **Automatic Deployments** - Enable CI/CD on push to main branch
5. **Domain Setup** - Configure custom domain (optional)

## Key Features

### Automatic Deployments

- **Production**: Deploys automatically on push to `main` branch
- **Preview**: Creates preview deployments for all other branches
- **Pull Requests**: Automatic preview URLs commented on PRs

### Environment Variables

Three required environment variables:

| Variable | Purpose | Example |
|----------|---------|---------|
| `VITE_API_BASE_URL` | Backend API endpoint | `https://api.artisankala.com/api/v1` |
| `VITE_GOOGLE_CLIENT_ID` | Google OAuth authentication | `123456789-abc.apps.googleusercontent.com` |
| `VITE_RAZORPAY_KEY_ID` | Payment processing | `rzp_live_xxxxxxxxxxxxx` |

### Build Configuration

Defined in `vercel.json` and `package.json`:

- **Framework**: Vite
- **Build Command**: `npm run build`
- **Output Directory**: `dist`
- **Install Command**: `npm install`

### Security Headers

Configured in `vercel.json`:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: same-origin`
- `Strict-Transport-Security: max-age=31536000; includeSubDomains`

### Performance Optimizations

- **CDN**: Global content delivery via Vercel Edge Network
- **Caching**: Static assets cached for 1 year
- **HTTPS**: Automatic SSL certificate
- **Compression**: Automatic Brotli/Gzip compression

## Prerequisites

Before deploying, ensure you have:

- ✅ GitHub account with repository access
- ✅ Vercel account ([sign up here](https://vercel.com))
- ✅ Production API endpoint URL
- ✅ Google OAuth Client ID
- ✅ Razorpay Key ID

## Deployment Workflow

### Initial Deployment

1. Import project to Vercel
2. Configure root directory: `artisan-kala-frontend`
3. Add environment variables
4. Deploy

### Continuous Deployment

1. Make changes in feature branch
2. Push to GitHub
3. Vercel creates preview deployment
4. Create pull request
5. Review preview deployment
6. Merge to `main`
7. Automatic production deployment

## Common Tasks

### Update Environment Variables

1. Go to Vercel project dashboard
2. Click "Settings" → "Environment Variables"
3. Update variable value
4. Redeploy to apply changes

### Rollback Deployment

1. Go to "Deployments" tab
2. Find previous working deployment
3. Click three dots → "Promote to Production"

### View Deployment Logs

1. Go to "Deployments" tab
2. Click on deployment
3. View build logs and runtime logs

### Add Custom Domain

1. Go to "Settings" → "Domains"
2. Click "Add Domain"
3. Configure DNS records
4. Wait for SSL certificate

## Troubleshooting

### Build Fails

1. Check build logs in Vercel dashboard
2. Test locally: `npm run build`
3. Verify dependencies in `package.json`
4. Clear build cache and redeploy

### API Calls Fail

1. Verify `VITE_API_BASE_URL` is correct
2. Check API CORS configuration
3. Ensure API is accessible from internet

### Environment Variables Not Working

1. Ensure variables are prefixed with `VITE_`
2. Verify variables are set for correct environment
3. Redeploy after adding variables

For more troubleshooting, see [VERCEL_DEPLOYMENT.md - Troubleshooting](./VERCEL_DEPLOYMENT.md#troubleshooting).

## Support

### Documentation

- [VERCEL_QUICK_START.md](./VERCEL_QUICK_START.md) - Quick setup guide
- [VERCEL_DEPLOYMENT.md](./VERCEL_DEPLOYMENT.md) - Complete guide
- [DEPLOYMENT_CHECKLIST.md](./DEPLOYMENT_CHECKLIST.md) - Progress tracker

### External Support

- [Vercel Documentation](https://vercel.com/docs)
- [Vercel Support](https://vercel.com/support)
- [Vercel Community](https://github.com/vercel/vercel/discussions)
- [Vercel Status](https://www.vercel-status.com)

## Best Practices

### Security

- ✅ Never commit `.env` files
- ✅ Use Vercel environment variables
- ✅ Use different keys for production/preview
- ✅ Enable deployment protection

### Performance

- ✅ Monitor bundle size
- ✅ Optimize images
- ✅ Use lazy loading
- ✅ Review Core Web Vitals

### Workflow

- ✅ Use preview deployments for testing
- ✅ Review deployment logs
- ✅ Monitor analytics
- ✅ Set up deployment notifications

## Project Structure

```
artisan-kala-frontend/
├── docs/
│   ├── README.md                    # This file
│   ├── VERCEL_QUICK_START.md       # Quick start guide
│   ├── VERCEL_DEPLOYMENT.md        # Complete deployment guide
│   └── DEPLOYMENT_CHECKLIST.md     # Deployment checklist
├── src/                             # Source code
├── public/                          # Static assets
├── vercel.json                      # Vercel configuration
├── package.json                     # Dependencies and scripts
├── vite.config.js                   # Vite configuration
└── .env.example                     # Environment variables template
```

## Configuration Files

### vercel.json

Defines Vercel-specific configuration:
- Build settings
- Rewrites for SPA routing
- Security headers
- Cache headers
- Environment variable references

### package.json

Defines build scripts:
- `npm run dev` - Development server
- `npm run build` - Production build
- `npm run preview` - Preview production build

### vite.config.js

Defines Vite build configuration:
- React plugin
- Build output settings
- Development server settings

## Environment Variables

### Required Variables

All variables must be prefixed with `VITE_` to be accessible in the frontend:

- `VITE_API_BASE_URL` - Backend API endpoint (no trailing slash)
- `VITE_GOOGLE_CLIENT_ID` - Google OAuth Client ID
- `VITE_RAZORPAY_KEY_ID` - Razorpay Key ID (production: `rzp_live_*`)

### Environment-Specific Values

Set different values for each environment:

- **Production**: Live API, production OAuth/Razorpay keys
- **Preview**: Staging API, test OAuth/Razorpay keys
- **Development**: Local API, test OAuth/Razorpay keys

## Deployment Requirements

### Requirements from Spec

This deployment satisfies the following requirements:

- **Requirement 12.1**: Deploy frontend to Vercel with automatic HTTPS and CDN
- **Requirement 12.7**: Automatically deploy frontend to Vercel when tests pass

### Task 71 Completion

This documentation completes Task 71 and its sub-task:

- **Task 71**: Deploy frontend to Vercel
- **Task 71.1**: Configure Vercel project
  - ✅ Instructions for connecting GitHub repository
  - ✅ Build command configuration: `npm run build`
  - ✅ Environment variables: `VITE_API_BASE_URL`, `VITE_GOOGLE_CLIENT_ID`, `VITE_RAZORPAY_KEY_ID`
  - ✅ Automatic deployments on push to main branch

## Next Steps

After completing deployment:

1. ✅ Test all functionality on production
2. ✅ Configure custom domain (if needed)
3. ✅ Set up monitoring and alerts
4. ✅ Enable deployment protection
5. ✅ Document any custom configurations
6. ✅ Share access with team members

---

**Last Updated**: 2024
**Version**: 1.0
**Maintained By**: Artisan Kala Development Team
