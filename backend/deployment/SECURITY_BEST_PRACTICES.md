# Security Best Practices

This document outlines security best practices for deploying and maintaining the Artisan Kala platform.

## Table of Contents

1. [Environment Security](#environment-security)
2. [Server Hardening](#server-hardening)
3. [Application Security](#application-security)
4. [Database Security](#database-security)
5. [API Security](#api-security)
6. [Authentication & Authorization](#authentication--authorization)
7. [Data Protection](#data-protection)
8. [Monitoring & Incident Response](#monitoring--incident-response)
9. [Third-Party Services](#third-party-services)
10. [Security Checklist](#security-checklist)

## Environment Security

### Environment Variables

**Critical Rules:**

1. **Never commit `.env` to version control**
   ```bash
   # Verify .env is in .gitignore
   grep "\.env" .gitignore
   
   # Check for accidentally committed secrets
   git log --all --full-history -- .env
   ```

2. **Use strong, unique passwords**
   ```bash
   # Generate secure passwords
   openssl rand -base64 32
   
   # Minimum requirements:
   # - Database: 20+ characters
   # - Redis: 20+ characters
   # - API keys: Use provider-generated keys
   ```

3. **Restrict file permissions**
   ```bash
   # .env should be readable only by owner
   chmod 600 .env
   chown deploy:deploy .env
   
   # Verify
   ls -la .env
   # Should show: -rw------- 1 deploy deploy
   ```

4. **Rotate secrets regularly**
   - Database passwords: Every 6 months
   - API keys: When team members leave
   - SSH keys: Every 6-12 months
   - Sanctum tokens: 7-day expiry (configured)

### Production Configuration

**Required settings in `.env`:**

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:... # Generated, never reuse
```

**Verify:**
```bash
# Check production settings
php artisan config:show app | grep -E "env|debug"

# Should show:
# env => production
# debug => false
```

**Consequences of APP_DEBUG=true:**
- Exposes stack traces with file paths
- Reveals database queries
- Shows environment variables
- Leaks sensitive configuration

## Server Hardening

### SSH Security

1. **Disable root login**
   ```bash
   sudo nano /etc/ssh/sshd_config
   # Set: PermitRootLogin no
   
   sudo systemctl restart sshd
   ```

2. **Use SSH keys only**
   ```bash
   sudo nano /etc/ssh/sshd_config
   # Set: PasswordAuthentication no
   # Set: PubkeyAuthentication yes
   
   sudo systemctl restart sshd
   ```

3. **Change default SSH port (optional)**
   ```bash
   sudo nano /etc/ssh/sshd_config
   # Set: Port 2222
   
   sudo systemctl restart sshd
   
   # Update firewall
   sudo ufw allow 2222/tcp
   sudo ufw delete allow 22/tcp
   ```

4. **Limit SSH access**
   ```bash
   sudo nano /etc/ssh/sshd_config
   # Add: AllowUsers deploy
   # Add: MaxAuthTries 3
   # Add: MaxSessions 2
   
   sudo systemctl restart sshd
   ```

### Firewall Configuration

1. **Enable UFW**
   ```bash
   # Allow essential services
   sudo ufw allow 22/tcp    # SSH
   sudo ufw allow 80/tcp    # HTTP
   sudo ufw allow 443/tcp   # HTTPS
   
   # Deny all other incoming
   sudo ufw default deny incoming
   sudo ufw default allow outgoing
   
   # Enable firewall
   sudo ufw enable
   
   # Check status
   sudo ufw status verbose
   ```

2. **Rate limiting**
   ```bash
   # Limit SSH connections
   sudo ufw limit 22/tcp
   ```

### Fail2Ban

1. **Install and configure**
   ```bash
   sudo apt install fail2ban
   
   # Create local config
   sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
   sudo nano /etc/fail2ban/jail.local
   ```

2. **Configure jails**
   ```ini
   [sshd]
   enabled = true
   port = 22
   maxretry = 3
   bantime = 3600
   
   [nginx-limit-req]
   enabled = true
   filter = nginx-limit-req
   logpath = /var/log/nginx/error.log
   maxretry = 5
   bantime = 600
   ```

3. **Start service**
   ```bash
   sudo systemctl enable fail2ban
   sudo systemctl start fail2ban
   
   # Check status
   sudo fail2ban-client status
   ```

### Automatic Security Updates

```bash
# Install unattended-upgrades
sudo apt install unattended-upgrades

# Configure
sudo dpkg-reconfigure -plow unattended-upgrades

# Verify
sudo systemctl status unattended-upgrades
```

## Application Security

### Laravel Security Headers

**Configured in Nginx:**

```nginx
# Security headers
add_header X-Frame-Options "DENY" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "same-origin" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

# Remove fingerprinting headers
server_tokens off;
more_clear_headers Server;
more_clear_headers X-Powered-By;
```

**Verify:**
```bash
curl -I https://api.artisankala.com | grep -E "X-Frame|X-Content|Strict-Transport"
```

### Input Validation

**Always use Form Requests:**

```php
// Good
public function store(CreateProductRequest $request)
{
    $validated = $request->validated();
    // Use $validated data
}

// Bad - Never do this
public function store(Request $request)
{
    $data = $request->all(); // Unvalidated!
    Product::create($data);
}
```

**Validation rules:**
- Use strict validation rules
- Whitelist allowed fields
- Validate file uploads (type, size)
- Sanitize HTML input
- Validate URLs and emails

### SQL Injection Prevention

**Always use Eloquent or Query Builder:**

```php
// Good - Parameterized query
$products = Product::where('category_id', $categoryId)->get();

// Good - Query builder
$products = DB::table('products')
    ->where('category_id', $categoryId)
    ->get();

// Bad - Never do this
$products = DB::select("SELECT * FROM products WHERE category_id = $categoryId");
```

**Never:**
- Use raw SQL with user input
- Concatenate user input into queries
- Trust user input without validation

### XSS Prevention

**Blade templates auto-escape:**

```blade
{{-- Good - Auto-escaped --}}
{{ $product->name }}

{{-- Bad - Unescaped HTML --}}
{!! $product->description !!}

{{-- If you must use unescaped, sanitize first --}}
{!! clean($product->description) !!}
```

**Sanitize HTML:**
```bash
composer require mews/purifier

# In code
use Mews\Purifier\Facades\Purifier;
$clean = Purifier::clean($input);
```

### CSRF Protection

**Enabled by default in Laravel:**

```php
// Verify CSRF middleware is active
// In app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\VerifyCsrfToken::class,
    ],
];
```

**Exceptions for webhooks:**
```php
// In app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    'api/v1/webhooks/*',
];
```

### File Upload Security

**Validation:**
```php
$request->validate([
    'image' => [
        'required',
        'file',
        'mimes:jpeg,png,webp',
        'max:2048', // 2MB
    ],
]);
```

**Storage:**
- Store outside web root
- Use Cloudflare R2 (not local storage)
- Strip EXIF metadata
- Generate unique filenames
- Validate file contents (not just extension)

**Never:**
- Trust user-provided filenames
- Store executable files
- Allow direct access to uploads

## Database Security

### MySQL Hardening

1. **Secure installation**
   ```bash
   sudo mysql_secure_installation
   # - Set root password
   # - Remove anonymous users
   # - Disallow root login remotely
   # - Remove test database
   ```

2. **Create dedicated user**
   ```sql
   -- Never use root for application
   CREATE USER 'artisan_kala_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
   GRANT SELECT, INSERT, UPDATE, DELETE ON artisan_kala.* TO 'artisan_kala_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **Bind to localhost**
   ```bash
   sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
   # Set: bind-address = 127.0.0.1
   
   sudo systemctl restart mysql
   ```

4. **Enable SSL (optional)**
   ```bash
   # Generate certificates
   sudo mysql_ssl_rsa_setup
   
   # Configure MySQL to require SSL
   sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
   # Add: require_secure_transport = ON
   ```

### Redis Hardening

1. **Set password**
   ```bash
   sudo nano /etc/redis/redis.conf
   # Set: requirepass YOUR_STRONG_PASSWORD
   
   sudo systemctl restart redis-server
   ```

2. **Bind to localhost**
   ```bash
   sudo nano /etc/redis/redis.conf
   # Set: bind 127.0.0.1 ::1
   
   sudo systemctl restart redis-server
   ```

3. **Disable dangerous commands**
   ```bash
   sudo nano /etc/redis/redis.conf
   # Add:
   rename-command FLUSHDB ""
   rename-command FLUSHALL ""
   rename-command CONFIG ""
   
   sudo systemctl restart redis-server
   ```

4. **Enable persistence**
   ```bash
   sudo nano /etc/redis/redis.conf
   # Set: appendonly yes
   # Set: appendfsync everysec
   
   sudo systemctl restart redis-server
   ```

### Backup Security

1. **Encrypt backups**
   ```bash
   # Backup with encryption
   mysqldump -u root -p artisan_kala | gzip | gpg --symmetric --cipher-algo AES256 > backup.sql.gz.gpg
   
   # Restore
   gpg --decrypt backup.sql.gz.gpg | gunzip | mysql -u root -p artisan_kala
   ```

2. **Secure backup storage**
   - Store backups off-site
   - Use encrypted storage (S3 with encryption)
   - Restrict access to backups
   - Test restoration regularly

3. **Automated backups**
   ```bash
   # Create backup script
   nano ~/backup-db.sh
   ```
   
   ```bash
   #!/bin/bash
   DATE=$(date +%Y%m%d_%H%M%S)
   BACKUP_DIR="/var/backups/mysql"
   
   # Create backup
   mysqldump -u root -p"$DB_PASSWORD" artisan_kala | gzip > "$BACKUP_DIR/backup_$DATE.sql.gz"
   
   # Encrypt
   gpg --symmetric --cipher-algo AES256 --batch --passphrase "$GPG_PASSPHRASE" "$BACKUP_DIR/backup_$DATE.sql.gz"
   
   # Remove unencrypted
   rm "$BACKUP_DIR/backup_$DATE.sql.gz"
   
   # Keep only last 30 days
   find "$BACKUP_DIR" -name "backup_*.sql.gz.gpg" -mtime +30 -delete
   ```

## API Security

### Rate Limiting

**Configured in Nginx:**
```nginx
# Define rate limit zones
limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;
limit_req_zone $binary_remote_addr zone=auth:10m rate=5r/m;

# Apply to locations
location /api/ {
    limit_req zone=api burst=10 nodelay;
}

location /api/v1/auth/ {
    limit_req zone=auth burst=3 nodelay;
}
```

**Laravel rate limiting:**
```php
// In routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    // API routes
});

Route::middleware(['throttle:5,1'])->group(function () {
    // Auth routes
});
```

### CORS Configuration

**Whitelist only frontend domain:**

```php
// In config/cors.php
'allowed_origins' => [
    env('FRONTEND_URL', 'https://artisankala.com'),
],

'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],

'allowed_headers' => ['Content-Type', 'Authorization'],

'exposed_headers' => [],

'max_age' => 3600,

'supports_credentials' => true,
```

**Verify:**
```bash
curl -H "Origin: https://artisankala.com" \
     -H "Access-Control-Request-Method: POST" \
     -X OPTIONS \
     https://api.artisankala.com/api/v1/products
```

### API Authentication

**Sanctum token security:**

1. **Token expiration**
   ```env
   SANCTUM_TOKEN_EXPIRATION=10080  # 7 days
   ```

2. **Stateful domains**
   ```env
   SANCTUM_STATEFUL_DOMAINS=artisankala.com,www.artisankala.com
   ```

3. **Token storage**
   - Frontend: Store in memory or httpOnly cookies
   - Never: localStorage (vulnerable to XSS)

4. **Token rotation**
   - Implement refresh token mechanism
   - Revoke tokens on password change
   - Allow users to revoke tokens

### Webhook Security

**Verify signatures:**

```php
// Razorpay webhook verification
$signature = $request->header('X-Razorpay-Signature');
$payload = $request->getContent();
$secret = config('services.razorpay.webhook_secret');

$expectedSignature = hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expectedSignature, $signature)) {
    Log::warning('Invalid webhook signature', [
        'ip' => $request->ip(),
        'payload' => $payload,
    ]);
    
    return response()->json(['error' => 'Invalid signature'], 400);
}
```

**Best practices:**
- Always verify signatures
- Log invalid attempts
- Use HTTPS for webhook URLs
- Implement idempotency
- Rate limit webhook endpoints

## Authentication & Authorization

### Password Security

1. **Strong password requirements**
   ```php
   // In validation rules
   'password' => [
       'required',
       'string',
       'min:8',
       'confirmed',
       Password::min(8)
           ->mixedCase()
           ->numbers()
           ->symbols()
           ->uncompromised(),
   ],
   ```

2. **Bcrypt with cost 12**
   ```php
   // Configured in config/hashing.php
   'bcrypt' => [
       'rounds' => 12,
   ],
   ```

3. **Password reset security**
   - 1-hour token expiry
   - Rate limit reset requests
   - Invalidate token after use
   - Send notification on password change

### Google OAuth Security

1. **Verify tokens server-side**
   ```php
   // Never trust client-provided tokens
   $client = new Google_Client(['client_id' => config('services.google.client_id')]);
   $payload = $client->verifyIdToken($idToken);
   
   if (!$payload) {
       throw new AuthenticationException('Invalid token');
   }
   ```

2. **Validate token claims**
   ```php
   // Check audience
   if ($payload['aud'] !== config('services.google.client_id')) {
       throw new AuthenticationException('Invalid audience');
   }
   
   // Check issuer
   if (!in_array($payload['iss'], ['accounts.google.com', 'https://accounts.google.com'])) {
       throw new AuthenticationException('Invalid issuer');
   }
   ```

### Role-Based Access Control

**Enforce admin checks:**

```php
// In middleware
public function handle($request, Closure $next)
{
    if ($request->user()->role !== 'admin') {
        abort(403, 'Unauthorized');
    }
    
    return $next($request);
}

// In routes
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Admin routes
});
```

## Data Protection

### Sensitive Data

**Never log:**
- Passwords
- API keys
- Credit card numbers
- Personal identification numbers

**Mask in logs:**
```php
Log::info('User login', [
    'email' => $user->email,
    'ip' => $request->ip(),
    // Don't log password!
]);
```

### GDPR Compliance

1. **Data minimization**
   - Collect only necessary data
   - Delete data when no longer needed
   - Implement data retention policies

2. **User rights**
   - Right to access (export data)
   - Right to deletion (delete account)
   - Right to rectification (update data)

3. **Consent**
   - Explicit consent for data collection
   - Clear privacy policy
   - Cookie consent

### Encryption

**Data at rest:**
- Database: Use encrypted storage volumes
- Backups: Encrypt with GPG
- Files: Store in encrypted R2 bucket

**Data in transit:**
- HTTPS everywhere (TLS 1.2+)
- Secure database connections
- Encrypted email (TLS)

## Monitoring & Incident Response

### Logging

**What to log:**
- Authentication attempts (success/failure)
- Authorization failures
- Input validation failures
- Webhook signature failures
- Rate limit violations
- Errors and exceptions

**What NOT to log:**
- Passwords
- API keys
- Credit card numbers
- Session tokens

**Log rotation:**
```bash
# Configure logrotate
sudo nano /etc/logrotate.d/laravel

/var/www/artisan-kala-api/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 deploy www-data
    sharedscripts
}
```

### Monitoring

**Set up alerts for:**
- Failed login attempts (> 5 in 5 minutes)
- 500 errors (> 10 in 5 minutes)
- Disk space (> 80% full)
- Memory usage (> 90%)
- Database connections (> 80% of max)

**Tools:**
- Laravel Telescope (staging only)
- Sentry (error tracking)
- New Relic (APM)
- Uptime monitoring (UptimeRobot, Pingdom)

### Incident Response

**Preparation:**
1. Document incident response plan
2. Maintain contact list
3. Keep backups current
4. Test restoration procedures

**Response steps:**
1. Identify and contain
2. Assess impact
3. Notify stakeholders
4. Remediate
5. Document and learn

## Third-Party Services

### API Key Management

**Best practices:**
- Use separate keys for staging/production
- Rotate keys regularly
- Restrict key permissions
- Monitor key usage
- Revoke unused keys

### Service-Specific Security

**Mailgun:**
- Use API key (not SMTP password)
- Verify domain with SPF/DKIM
- Enable webhook signature verification
- Monitor sending reputation

**Razorpay:**
- Use live keys only in production
- Verify webhook signatures
- Enable 2FA on account
- Monitor transactions
- Set up fraud detection

**Cloudflare R2:**
- Use IAM tokens (not root credentials)
- Restrict token permissions
- Enable versioning
- Set up lifecycle policies
- Monitor access logs

**Google OAuth:**
- Restrict authorized domains
- Use separate projects for staging/production
- Enable 2FA on Google account
- Monitor OAuth consent screen
- Review authorized applications

## Security Checklist

### Pre-Deployment

- [ ] `APP_DEBUG=false` in production
- [ ] Strong database password set
- [ ] Strong Redis password set
- [ ] All API keys configured
- [ ] `.env` file permissions set to 600
- [ ] `.env` not committed to Git
- [ ] Security headers configured
- [ ] HTTPS enabled with valid certificate
- [ ] Firewall configured and enabled
- [ ] SSH hardened (key-only, no root)
- [ ] Fail2Ban installed and configured
- [ ] Automatic security updates enabled

### Post-Deployment

- [ ] Verify security headers present
- [ ] Test rate limiting
- [ ] Verify CORS configuration
- [ ] Test authentication flows
- [ ] Verify webhook signatures
- [ ] Check file upload restrictions
- [ ] Review logs for errors
- [ ] Set up monitoring and alerts
- [ ] Document incident response plan
- [ ] Schedule security audits

### Ongoing Maintenance

- [ ] Review logs weekly
- [ ] Update dependencies monthly
- [ ] Rotate secrets every 6 months
- [ ] Security audit quarterly
- [ ] Backup testing quarterly
- [ ] Incident response drill annually
- [ ] Security training for team

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [CIS Benchmarks](https://www.cisecurity.org/cis-benchmarks/)
- [NIST Cybersecurity Framework](https://www.nist.gov/cyberframework)

## Reporting Security Issues

If you discover a security vulnerability:

1. **Do NOT** open a public issue
2. Email: security@artisankala.com
3. Include:
   - Description of vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

We will respond within 48 hours and work with you to resolve the issue.

