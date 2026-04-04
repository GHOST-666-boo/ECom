# Vercel Deployment Quick Start Guide

This is a condensed guide for quickly deploying the Artisan Kala frontend to Vercel. For detailed instructions, see [VERCEL_DEPLOYMENT.md](./VERCEL_DEPLOYMENT.md).

## Prerequisites Checklist

- [ ] GitHub account with repository access
- [ ] Vercel account ([sign up here](https://vercel.com))
- [ ] Production API URL
- [ ] Google OAuth Client ID
- [ ] Razorpay Key ID

## 5-Minute Setup

### 1. Import Project to Vercel

1. Go to [vercel.com/new](https://vercel.com/new)
2. Click "Continue with GitHub"
3. Find and import your repository
4. Set **Root Directory**: `artisan-kala-frontend`
5. **Don't deploy yet** - click "Environment Variables" first

### 2. Add Environment Variables

Add these three variables (all environments: Production, Preview, Development):

| Variable | Example Value | Where to Get |
|----------|---------------|--------------|
| `VITE_API_BASE_URL` | `https://api.artisankala.com/api/v1` | Your production API endpoint |
| `VITE_GOOGLE_CLIENT_ID` | `123456789-abc.apps.googleusercontent.com` | [Google Cloud Console](https://console.cloud.google.com) → APIs & Services → Credentials |
| `VITE_RAZORPAY_KEY_ID` | `rzp_live_xxxxxxxxxxxxx` | [Razorpay Dashboard](https://dashboard.razorpay.com) → Settings → API Keys |

### 3. Deploy

1. Click "Deploy"
2. Wait 2-3 minutes for build to complete
3. Click the deployment URL to view your site

### 4. Configure Automatic Deployments

1. Go to project "Settings" → "Git"
2. Set **Production Branch**: `main`
3. Enable **Automatically deploy new commits**
4. Enable **Comments on Pull Requests**

Done! Every push to `main` will now automatically deploy to production.

## Build Configuration

Vercel auto-detects these settings from `package.json` and `vercel.json`:

- **Framework**: Vite
- **Build Command**: `npm run build`
- **Output Directory**: `dist`
- **Install Command**: `npm install`

## Custom Domain Setup (Optional)

### Add Domain

1. Go to "Settings" → "Domains"
2. Click "Add Domain"
3. Enter your domain (e.g., `artisankala.com`)

### Configure DNS

Add these records at your domain registrar:

**For apex domain (artisankala.com)**:
```
Type: A
Name: @
Value: 76.76.21.21
```

**For www subdomain**:
```
Type: CNAME
Name: www
Value: cname.vercel-dns.com
```

Wait for DNS propagation (up to 48 hours). Vercel will automatically issue SSL certificate.

## Verify Deployment

### Check Environment Variables

1. Open browser console on your deployed site
2. Check API calls are going to correct endpoint
3. Test Google OAuth login
4. Test Razorpay payment flow (use test mode first)

### Test Automatic Deployment

1. Make a small change (e.g., update a text)
2. Commit and push to a feature branch
3. Create pull request to `main`
4. Vercel will comment with preview URL
5. Merge PR to deploy to production

## Common Issues

### Build Fails

- Check build logs in Vercel dashboard
- Test locally: `npm run build`
- Verify all dependencies are in `package.json`

### API Calls Fail

- Verify `VITE_API_BASE_URL` is correct (no trailing slash)
- Check API CORS allows your Vercel domain
- Check API is accessible from internet

### Google OAuth Not Working

- Add Vercel domain to Google OAuth authorized origins
- Verify `VITE_GOOGLE_CLIENT_ID` is correct
- Check browser console for errors

### Razorpay Not Working

- Verify `VITE_RAZORPAY_KEY_ID` is correct
- Use production key (starts with `rzp_live_`)
- Check Razorpay dashboard for API status

## Important Notes

### Security

- ✅ Never commit `.env` files to Git
- ✅ Use Vercel environment variables for all secrets
- ✅ Use different keys for production and preview environments
- ✅ Security headers are configured in `vercel.json`

### Performance

- ✅ Static assets are automatically cached (configured in `vercel.json`)
- ✅ Site is served via Vercel's global CDN
- ✅ HTTPS is automatic and free
- ✅ Images are lazy-loaded

### Workflow

- ✅ `main` branch → Production deployment
- ✅ Other branches → Preview deployments
- ✅ Pull requests get automatic preview URLs
- ✅ Deployments are atomic (all-or-nothing)

## Next Steps

1. **Set up custom domain** (if not done)
2. **Configure Google OAuth** with production domain
3. **Test payment flow** with Razorpay test mode
4. **Enable deployment protection** for production
5. **Set up monitoring** and analytics

## Resources

- 📖 [Full Deployment Guide](./VERCEL_DEPLOYMENT.md)
- 📖 [Vercel Documentation](https://vercel.com/docs)
- 📖 [Vite Deployment Guide](https://vitejs.dev/guide/static-deploy.html)
- 🆘 [Vercel Support](https://vercel.com/support)

## Deployment Checklist

Use this checklist to ensure everything is configured:

- [ ] Project imported to Vercel
- [ ] Root directory set to `artisan-kala-frontend`
- [ ] All environment variables added
- [ ] Initial deployment successful
- [ ] Automatic deployments enabled
- [ ] Production branch set to `main`
- [ ] Custom domain added (optional)
- [ ] DNS configured (optional)
- [ ] Google OAuth tested
- [ ] Razorpay integration tested
- [ ] API connectivity verified
- [ ] Preview deployments working

---

**Need help?** See the [full deployment guide](./VERCEL_DEPLOYMENT.md) or contact [Vercel Support](https://vercel.com/support).
