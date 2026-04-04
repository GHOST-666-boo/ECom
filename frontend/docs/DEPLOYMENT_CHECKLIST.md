# Vercel Deployment Checklist

Use this checklist to track your deployment progress. Check off items as you complete them.

## Pre-Deployment Setup

### Account Setup
- [ ] Created Vercel account at [vercel.com](https://vercel.com)
- [ ] Connected GitHub account to Vercel
- [ ] Verified repository access

### Credentials Preparation
- [ ] Production API endpoint URL ready
- [ ] Google OAuth Client ID obtained
  - [ ] Created OAuth 2.0 Client in Google Cloud Console
  - [ ] Configured authorized JavaScript origins
- [ ] Razorpay Key ID obtained
  - [ ] Generated production API keys
  - [ ] Noted Key ID (starts with `rzp_live_`)

## Vercel Project Setup

### Import Project
- [ ] Imported repository to Vercel
- [ ] Set project name: `artisan-kala-frontend`
- [ ] Configured root directory: `artisan-kala-frontend`
- [ ] Framework preset detected as "Vite"

### Build Configuration
- [ ] Verified build command: `npm run build`
- [ ] Verified output directory: `dist`
- [ ] Verified install command: `npm install`
- [ ] Reviewed `vercel.json` configuration

### Environment Variables
- [ ] Added `VITE_API_BASE_URL`
  - [ ] Set for Production environment
  - [ ] Set for Preview environment
  - [ ] Set for Development environment (optional)
- [ ] Added `VITE_GOOGLE_CLIENT_ID`
  - [ ] Set for Production environment
  - [ ] Set for Preview environment
- [ ] Added `VITE_RAZORPAY_KEY_ID`
  - [ ] Set for Production environment
  - [ ] Set for Preview environment

### Initial Deployment
- [ ] Clicked "Deploy" button
- [ ] Build completed successfully
- [ ] Deployment URL accessible
- [ ] No console errors in browser

## Automatic Deployment Configuration

### Git Integration
- [ ] Verified connected repository
- [ ] Set production branch to `main`
- [ ] Enabled "All branches" for preview deployments
- [ ] Enabled "Automatically deploy new commits"
- [ ] Enabled "Comments on Pull Requests"
- [ ] Enabled "Comments on Commits"

### Deployment Protection (Optional)
- [ ] Enabled deployment protection for production
- [ ] Configured approval requirements

## Custom Domain Setup (Optional)

### Domain Configuration
- [ ] Added custom domain in Vercel
- [ ] Configured DNS A record for apex domain
  - Type: `A`, Name: `@`, Value: `76.76.21.21`
- [ ] Configured DNS CNAME record for www subdomain
  - Type: `CNAME`, Name: `www`, Value: `cname.vercel-dns.com`
- [ ] Waited for DNS propagation
- [ ] Verified domain is accessible
- [ ] SSL certificate issued automatically
- [ ] Configured domain redirect (www → apex or vice versa)

## Third-Party Service Configuration

### Google OAuth
- [ ] Added Vercel production domain to authorized JavaScript origins
- [ ] Added Vercel preview domain pattern to authorized origins (optional)
- [ ] Tested Google login on production site
- [ ] Verified user creation/login flow

### Razorpay
- [ ] Verified production mode is enabled
- [ ] Tested payment flow on production site
- [ ] Verified webhook configuration (if applicable)
- [ ] Tested payment success scenario
- [ ] Tested payment failure scenario

### API Backend
- [ ] Verified API is accessible from internet
- [ ] Added Vercel domain to API CORS whitelist
- [ ] Tested API connectivity from deployed frontend
- [ ] Verified all API endpoints work correctly

## Testing & Verification

### Functional Testing
- [ ] Tested homepage loads correctly
- [ ] Tested product catalog browsing
- [ ] Tested product detail pages
- [ ] Tested user registration
- [ ] Tested email/password login
- [ ] Tested Google OAuth login
- [ ] Tested shopping cart functionality
- [ ] Tested checkout flow
- [ ] Tested order placement (COD)
- [ ] Tested order placement (Razorpay)
- [ ] Tested order history
- [ ] Tested user profile
- [ ] Tested address management
- [ ] Tested newsletter subscription

### Performance Testing
- [ ] Checked page load times
- [ ] Verified images load correctly
- [ ] Verified lazy loading works
- [ ] Checked mobile responsiveness
- [ ] Tested on different browsers
- [ ] Checked Core Web Vitals (if available)

### Security Testing
- [ ] Verified HTTPS is enabled
- [ ] Checked security headers in browser dev tools
- [ ] Verified no sensitive data in console logs
- [ ] Tested authentication flows
- [ ] Verified API calls use HTTPS

## Automatic Deployment Testing

### Preview Deployments
- [ ] Created feature branch
- [ ] Pushed changes to feature branch
- [ ] Verified preview deployment created
- [ ] Checked preview URL works correctly
- [ ] Verified Vercel commented on commit/PR

### Production Deployments
- [ ] Created pull request to main
- [ ] Reviewed preview deployment
- [ ] Merged pull request
- [ ] Verified production deployment triggered
- [ ] Checked production site updated correctly
- [ ] Verified no breaking changes

## Monitoring & Maintenance

### Setup Monitoring
- [ ] Reviewed deployment logs
- [ ] Set up deployment notifications (email/Slack)
- [ ] Enabled analytics (if available)
- [ ] Enabled Speed Insights (if available)
- [ ] Bookmarked Vercel dashboard

### Documentation
- [ ] Reviewed [VERCEL_DEPLOYMENT.md](./VERCEL_DEPLOYMENT.md)
- [ ] Reviewed [VERCEL_QUICK_START.md](./VERCEL_QUICK_START.md)
- [ ] Documented custom configurations
- [ ] Shared deployment URLs with team
- [ ] Updated project README with deployment info

## Post-Deployment

### Team Communication
- [ ] Notified team of successful deployment
- [ ] Shared production URL
- [ ] Shared Vercel dashboard access (if needed)
- [ ] Documented deployment process for team

### Backup & Recovery
- [ ] Documented environment variables (securely)
- [ ] Noted Vercel project settings
- [ ] Documented DNS configuration
- [ ] Created rollback plan

## Troubleshooting Reference

If you encounter issues, refer to:

- [ ] [VERCEL_DEPLOYMENT.md - Troubleshooting Section](./VERCEL_DEPLOYMENT.md#troubleshooting)
- [ ] [Vercel Documentation](https://vercel.com/docs)
- [ ] [Vercel Status Page](https://www.vercel-status.com)
- [ ] [Vercel Support](https://vercel.com/support)

## Notes

Use this section to document any custom configurations, issues encountered, or important information:

```
[Add your notes here]
```

---

**Deployment Date**: _______________
**Deployed By**: _______________
**Production URL**: _______________
**Vercel Project URL**: _______________

---

**Status**: 
- [ ] Not Started
- [ ] In Progress
- [ ] Completed
- [ ] Issues Encountered (document above)
