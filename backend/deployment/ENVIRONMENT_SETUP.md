# Environment Variables Setup Guide

This guide provides detailed instructions for configuring environment variables for the Artisan Kala API in production.

## Table of Contents

1. [Backend Environment Variables](#backend-environment-variables)
2. [Obtaining Credentials](#obtaining-credentials)
3. [Security Best Practices](#security-best-practices)
4. [Verification](#verification)
5. [Troubleshooting](#troubleshooting)

## Backend Environment Variables

### Step 1: Copy the Example File

On your VPS, navigate to the application directory and copy the example file:

```bash
cd /var/www/artisan-kala-api
cp deployment/.env.production.example .env
```

### Step 2: Generate Application Key

Generate a unique application key:

```bash
php artisan key:generate
```

This will automatically update the `APP_KEY` in your `.env` file.

### Step 3: Configure Core Application Settings

Edit the `.env` file:

```bash
nano .env
```

Update these core settings:

```env
APP_NAME="Artisan Kala API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.artisankala.com
FRONTEND_URL=https://artisankala.com
```

**Important:**
- `APP_ENV` must be `production`
- `APP_DEBUG` must be `false` to prevent sensitive information leakage
- `APP_URL` should match your API domain
- `FRONTEND_URL` should match your frontend domain (used for CORS)

### Step 4: Configure Database

Update database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=artisan_kala
DB_USERNAME=artisan_kala_user
DB_PASSWORD=YOUR_SECURE_DATABASE_PASSWORD_HERE
```

**Security Tips:**
- Use a strong, unique password (minimum 20 characters)
- Generate password with: `openssl rand -base64 32`
- Never use default passwords like "password" or "root"

### Step 5: Configure Redis

Update Redis credentials:

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=YOUR_REDIS_PASSWORD_HERE
REDIS_PORT=6379
REDIS_CLIENT=predis

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

**Security Tips:**
- Always set a Redis password
- Generate password with: `openssl rand -base64 32`
- Ensure Redis is bound to localhost only (check `/etc/redis/redis.conf`)

### Step 6: Configure Mail Service (Mailgun)

#### Option A: SMTP Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@mg.yourdomain.com
MAIL_PASSWORD=YOUR_MAILGUN_SMTP_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@artisankala.com
MAIL_FROM_NAME="Artisan Kala"
```

#### Option B: Mailgun API Configuration

```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.yourdomain.com
MAILGUN_SECRET=YOUR_MAILGUN_API_KEY
MAILGUN_ENDPOINT=api.mailgun.net
MAIL_FROM_ADDRESS=noreply@artisankala.com
MAIL_FROM_NAME="Artisan Kala"
```

**Where to find credentials:**
- Log in to [Mailgun Dashboard](https://app.mailgun.com/)
- Navigate to Sending → Domains
- Select your domain
- Find API Keys and SMTP credentials

### Step 7: Configure Razorpay Payment Gateway

```env
RAZORPAY_KEY_ID=rzp_live_XXXXXXXXXXXXXXXX
RAZORPAY_KEY_SECRET=YOUR_RAZORPAY_KEY_SECRET
RAZORPAY_WEBHOOK_SECRET=YOUR_RAZORPAY_WEBHOOK_SECRET
```

**Where to find credentials:**
- Log in to [Razorpay Dashboard](https://dashboard.razorpay.com/)
- Navigate to Settings → API Keys
- Generate live mode keys (not test mode)
- For webhook secret: Settings → Webhooks → Create webhook → Copy secret

**Important:**
- Use live mode keys for production
- Keep test mode keys for staging/development
- Never commit these keys to version control

### Step 8: Configure Google OAuth

```env
GOOGLE_CLIENT_ID=YOUR_CLIENT_ID.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=YOUR_GOOGLE_CLIENT_SECRET
```

**Where to find credentials:**
- Go to [Google Cloud Console](https://console.cloud.google.com/)
- Navigate to APIs & Services → Credentials
- Create OAuth 2.0 Client ID (Web application)
- Add authorized redirect URIs:
  - `https://artisankala.com/auth/google/callback`
  - `https://api.artisankala.com/api/v1/auth/google/callback`
- Copy Client ID and Client Secret

### Step 9: Configure Cloudflare R2 Storage

```env
AWS_ACCESS_KEY_ID=YOUR_R2_ACCESS_KEY_ID
AWS_SECRET_ACCESS_KEY=YOUR_R2_SECRET_ACCESS_KEY
AWS_DEFAULT_REGION=auto
AWS_BUCKET=artisan-kala-images
AWS_ENDPOINT=https://YOUR_ACCOUNT_ID.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_URL=https://images.artisankala.com
FILESYSTEM_DISK=r2
```

**Where to find credentials:**
- Log in to [Cloudflare Dashboard](https://dash.cloudflare.com/)
- Navigate to R2 → Overview
- Create a bucket named `artisan-kala-images`
- Go to R2 → Manage R2 API Tokens
- Create API token with read/write permissions
- Copy Access Key ID and Secret Access Key
- Find your Account ID in the R2 overview page

**Custom Domain Setup (Optional but Recommended):**
- In R2 bucket settings, add custom domain: `images.artisankala.com`
- Add CNAME record in Cloudflare DNS: `images.artisankala.com` → `YOUR_BUCKET.r2.cloudflarestorage.com`
- Update `AWS_URL` to use custom domain

### Step 10: Configure Laravel Sanctum

```env
SANCTUM_STATEFUL_DOMAINS=artisankala.com,www.artisankala.com
SANCTUM_TOKEN_EXPIRATION=10080
SESSION_DOMAIN=.artisankala.com
```

**Configuration Notes:**
- `SANCTUM_STATEFUL_DOMAINS`: Comma-separated list of frontend domains
- `SANCTUM_TOKEN_EXPIRATION`: Token expiry in minutes (10080 = 7 days)
- `SESSION_DOMAIN`: Use leading dot for subdomain support

### Step 11: Configure Logging

```env
LOG_CHANNEL=stack
LOG_LEVEL=error
LOG_DEPRECATIONS_CHANNEL=null
```

**Log Levels:**
- `emergency`: System is unusable
- `alert`: Action must be taken immediately
- `critical`: Critical conditions
- `error`: Error conditions (recommended for production)
- `warning`: Warning conditions
- `notice`: Normal but significant
- `info`: Informational messages
- `debug`: Debug-level messages (never use in production)

### Step 12: Disable Development Tools

```env
TELESCOPE_ENABLED=false
DEBUGBAR_ENABLED=false
```

**Important:**
- Never enable Telescope or Debugbar in production
- These tools expose sensitive application information
- Use proper monitoring tools instead (Sentry, New Relic, etc.)

## Obtaining Credentials

### Database Credentials

Created during VPS setup:

```bash
# Create database
sudo mysql -u root -p
CREATE DATABASE artisan_kala CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'artisan_kala_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON artisan_kala.* TO 'artisan_kala_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Redis Password

Set during Redis installation:

```bash
# Edit Redis configuration
sudo nano /etc/redis/redis.conf

# Find and uncomment/set:
requirepass YOUR_REDIS_PASSWORD

# Restart Redis
sudo systemctl restart redis-server

# Test connection
redis-cli -a YOUR_REDIS_PASSWORD ping
```

### Mailgun Setup

1. Sign up at [Mailgun](https://www.mailgun.com/)
2. Add and verify your domain
3. Configure DNS records (SPF, DKIM, CNAME)
4. Wait for domain verification (can take up to 48 hours)
5. Obtain API keys and SMTP credentials

### Razorpay Setup

1. Sign up at [Razorpay](https://razorpay.com/)
2. Complete KYC verification
3. Switch to live mode
4. Generate API keys
5. Configure webhook URL: `https://api.artisankala.com/api/v1/webhooks/razorpay`
6. Copy webhook secret

### Google OAuth Setup

1. Create project in [Google Cloud Console](https://console.cloud.google.com/)
2. Enable Google+ API
3. Configure OAuth consent screen
4. Create OAuth 2.0 credentials
5. Add authorized domains and redirect URIs
6. Copy Client ID and Secret

### Cloudflare R2 Setup

1. Sign up for [Cloudflare](https://www.cloudflare.com/)
2. Navigate to R2 Object Storage
3. Create bucket
4. Generate API token
5. (Optional) Configure custom domain
6. Copy credentials

## Security Best Practices

### File Permissions

Ensure `.env` file has correct permissions:

```bash
chmod 600 .env
chown deploy:deploy .env
```

**Never:**
- Make `.env` world-readable (chmod 644 or 777)
- Commit `.env` to version control
- Share `.env` via email or chat

### Password Management

**Generate Strong Passwords:**

```bash
# Generate 32-character password
openssl rand -base64 32

# Generate 64-character password
openssl rand -base64 48
```

**Password Requirements:**
- Minimum 20 characters for database and Redis
- Use mix of uppercase, lowercase, numbers, symbols
- Unique password for each service
- Store securely (use password manager)

### Secrets Management

**For Team Environments:**

Consider using a secrets management service:
- AWS Secrets Manager
- HashiCorp Vault
- 1Password for Teams
- Bitwarden

**Never:**
- Share credentials via Slack, email, or chat
- Store credentials in documentation
- Use the same password across services
- Commit credentials to Git

### Environment Variable Validation

After configuration, validate all variables are set:

```bash
php artisan config:show
```

Check for any `null` or empty values that should be set.

### Backup Configuration

**Backup `.env` file securely:**

```bash
# Encrypt backup
gpg --symmetric --cipher-algo AES256 .env

# This creates .env.gpg
# Store this encrypted file in a secure location
# Never store unencrypted .env backups
```

**Restore from backup:**

```bash
gpg --decrypt .env.gpg > .env
chmod 600 .env
```

## Verification

### Step 1: Test Database Connection

```bash
php artisan tinker
DB::connection()->getPdo();
# Should return PDO object without errors
exit
```

### Step 2: Test Redis Connection

```bash
php artisan tinker
Cache::put('test', 'value', 60);
Cache::get('test');
# Should return 'value'
exit
```

### Step 3: Test Mail Configuration

```bash
php artisan tinker
Mail::raw('Test email', function($msg) {
    $msg->to('your-email@example.com')->subject('Test');
});
# Check your email inbox
exit
```

### Step 4: Test File Upload (R2)

```bash
php artisan tinker
Storage::disk('r2')->put('test.txt', 'Hello World');
Storage::disk('r2')->exists('test.txt');
# Should return true
Storage::disk('r2')->delete('test.txt');
exit
```

### Step 5: Run Configuration Cache

```bash
php artisan config:cache
php artisan config:show
```

Verify all values are correct.

### Step 6: Test Application

```bash
# Test health endpoint
curl https://api.artisankala.com/health

# Test API endpoint
curl https://api.artisankala.com/api/v1/categories
```

## Troubleshooting

### Database Connection Failed

**Error:** `SQLSTATE[HY000] [1045] Access denied`

**Solutions:**
1. Verify database credentials in `.env`
2. Check user has correct privileges:
   ```bash
   sudo mysql -u root -p
   SHOW GRANTS FOR 'artisan_kala_user'@'localhost';
   ```
3. Ensure MySQL is running:
   ```bash
   sudo systemctl status mysql
   ```

### Redis Connection Failed

**Error:** `Connection refused [tcp://127.0.0.1:6379]`

**Solutions:**
1. Check Redis is running:
   ```bash
   sudo systemctl status redis-server
   ```
2. Test Redis connection:
   ```bash
   redis-cli -a YOUR_PASSWORD ping
   ```
3. Verify Redis password in `.env` matches `/etc/redis/redis.conf`

### Mail Sending Failed

**Error:** `Connection could not be established with host`

**Solutions:**
1. Verify Mailgun credentials
2. Check domain is verified in Mailgun dashboard
3. Ensure DNS records are configured correctly
4. Test SMTP connection:
   ```bash
   telnet smtp.mailgun.org 587
   ```
5. Check firewall allows outbound port 587

### File Upload Failed (R2)

**Error:** `Error executing "PutObject"`

**Solutions:**
1. Verify R2 credentials in `.env`
2. Check bucket name is correct
3. Ensure API token has write permissions
4. Test connection:
   ```bash
   php artisan tinker
   Storage::disk('r2')->put('test.txt', 'test');
   ```

### Configuration Cache Issues

**Error:** `Configuration values not updating`

**Solution:**
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Permission Denied Errors

**Error:** `The stream or file could not be opened`

**Solution:**
```bash
# Fix ownership
sudo chown -R deploy:www-data /var/www/artisan-kala-api

# Fix permissions
sudo chmod -R 755 /var/www/artisan-kala-api
sudo chmod -R 775 /var/www/artisan-kala-api/storage
sudo chmod -R 775 /var/www/artisan-kala-api/bootstrap/cache
```

### APP_KEY Not Set

**Error:** `No application encryption key has been specified`

**Solution:**
```bash
php artisan key:generate
php artisan config:cache
```

## Environment-Specific Configurations

### Staging Environment

For staging, use a separate `.env` with:

```env
APP_ENV=staging
APP_DEBUG=true  # Can be true for staging
APP_URL=https://staging-api.artisankala.com

# Use test mode credentials
RAZORPAY_KEY_ID=rzp_test_XXXXXXXXXXXXXXXX
```

### Development Environment

For local development:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Use local services
DB_HOST=127.0.0.1
REDIS_HOST=127.0.0.1

# Use test credentials
MAIL_MAILER=log  # Emails logged instead of sent
```

## Additional Resources

- [Laravel Configuration Documentation](https://laravel.com/docs/configuration)
- [Laravel Environment Configuration](https://laravel.com/docs/configuration#environment-configuration)
- [Mailgun Documentation](https://documentation.mailgun.com/)
- [Razorpay Documentation](https://razorpay.com/docs/)
- [Cloudflare R2 Documentation](https://developers.cloudflare.com/r2/)

## Support

If you encounter issues not covered in this guide:

1. Check Laravel logs: `tail -f storage/logs/laravel.log`
2. Check Nginx error logs: `sudo tail -f /var/log/nginx/error.log`
3. Check PHP-FPM logs: `sudo tail -f /var/log/php8.3-fpm.log`
4. Review the [Deployment Checklist](DEPLOYMENT_CHECKLIST.md)
5. Consult the [Troubleshooting Guide](TROUBLESHOOTING.md)

