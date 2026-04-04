# CI/CD Pipeline Architecture

## Visual Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                         DEVELOPER WORKFLOW                           │
└─────────────────────────────────────────────────────────────────────┘
                                   │
                                   │ git push
                                   ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         GITHUB REPOSITORY                            │
│                                                                       │
│  ┌─────────────────┐                    ┌─────────────────┐        │
│  │  Feature Branch │                    │   Main Branch   │        │
│  │   (develop)     │                    │     (main)      │        │
│  └────────┬────────┘                    └────────┬────────┘        │
│           │                                      │                  │
│           │ Push/PR                              │ Push/Merge       │
│           ▼                                      ▼                  │
│  ┌─────────────────┐                    ┌─────────────────┐        │
│  │  Test Workflow  │                    │ Deploy Workflow │        │
│  │   (test.yml)    │                    │  (deploy.yml)   │        │
│  └─────────────────┘                    └─────────────────┘        │
└───────────┬─────────────────────────────────────┬───────────────────┘
            │                                     │
            ▼                                     ▼
┌───────────────────────────┐    ┌──────────────────────────────────┐
│     TEST WORKFLOW         │    │      DEPLOY WORKFLOW             │
│                           │    │                                  │
│  ┌─────────────────────┐ │    │  ┌────────────────────────────┐ │
│  │  Setup Environment  │ │    │  │  Job 1: Run Tests          │ │
│  │  - PHP 8.3          │ │    │  │  (Same as test workflow)   │ │
│  │  - MySQL 8.0        │ │    │  └────────────┬───────────────┘ │
│  │  - Redis 7.0        │ │    │               │                  │
│  └──────────┬──────────┘ │    │               │ Tests Pass?      │
│             │             │    │               │                  │
│             ▼             │    │      ┌────────┴────────┐         │
│  ┌─────────────────────┐ │    │      │                 │         │
│  │  Install Dependencies│ │    │      ▼                 ▼         │
│  │  - Composer packages│ │    │  ┌─────────┐      ┌─────────┐   │
│  └──────────┬──────────┘ │    │  │ Deploy  │      │ Deploy  │   │
│             │             │    │  │Frontend │      │Backend  │   │
│             ▼             │    │  │(Parallel)│     │(Parallel)│  │
│  ┌─────────────────────┐ │    │  └────┬────┘      └────┬────┘   │
│  │  Run Migrations     │ │    │       │                │         │
│  └──────────┬──────────┘ │    │       │                │         │
│             │             │    └───────┼────────────────┼─────────┘
│             ▼             │            │                │
│  ┌─────────────────────┐ │            ▼                ▼
│  │  Run Tests          │ │    ┌──────────────┐  ┌──────────────┐
│  │  - Pest/PHPUnit     │ │    │   VERCEL     │  │     VPS      │
│  │  - Laravel Pint     │ │    │              │  │              │
│  │  - PHPStan          │ │    │  React SPA   │  │  Laravel API │
│  └──────────┬──────────┘ │    │  + CDN       │  │  + Nginx     │
│             │             │    │  + HTTPS     │  │  + PHP-FPM   │
│             ▼             │    │              │  │  + MySQL     │
│  ┌─────────────────────┐ │    │              │  │  + Redis     │
│  │  Report Results     │ │    │              │  │  + Supervisor│
│  │  ✓ Pass / ✗ Fail    │ │    └──────────────┘  └──────────────┘
│  └─────────────────────┘ │
└───────────────────────────┘
```

## Detailed Job Flow

### Test Workflow (test.yml)

```
┌─────────────────────────────────────────────────────────────┐
│                    TEST WORKFLOW                             │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  1. Checkout Code                                           │
│     └─> actions/checkout@v4                                 │
│                                                              │
│  2. Setup PHP 8.3                                           │
│     └─> shivammathur/setup-php@v2                           │
│         ├─> Extensions: dom, curl, mbstring, zip, etc.      │
│         └─> No coverage (faster)                            │
│                                                              │
│  3. Start Services                                          │
│     ├─> MySQL 8.0 (port 3306)                               │
│     │   └─> Health check: mysqladmin ping                   │
│     └─> Redis 7.0 (port 6379)                               │
│         └─> Health check: redis-cli ping                    │
│                                                              │
│  4. Copy .env                                               │
│     └─> .env.example → .env                                 │
│                                                              │
│  5. Install Dependencies                                    │
│     └─> composer install --optimize-autoloader              │
│                                                              │
│  6. Generate App Key                                        │
│     └─> php artisan key:generate                            │
│                                                              │
│  7. Run Migrations                                          │
│     └─> php artisan migrate --force                         │
│                                                              │
│  8. Run Pest Tests                                          │
│     └─> php artisan test                                    │
│         ├─> Unit tests                                      │
│         ├─> Feature tests                                   │
│         └─> Property-based tests                            │
│                                                              │
│  9. Run Laravel Pint                                        │
│     └─> ./vendor/bin/pint --test                            │
│         └─> Check code style (PSR-12)                       │
│                                                              │
│  10. Run PHPStan                                            │
│      └─> ./vendor/bin/phpstan analyse                       │
│          └─> Static analysis (Level 5)                      │
│          └─> Continue on error (non-blocking)               │
│                                                              │
│  11. Report Results                                         │
│      ├─> ✓ All checks passed                                │
│      └─> ✗ Failed (with logs)                               │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Deploy Workflow (deploy.yml)

```
┌─────────────────────────────────────────────────────────────┐
│                   DEPLOY WORKFLOW                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  JOB 1: TEST (Sequential)                          │    │
│  │  └─> Same as test workflow                         │    │
│  │      └─> Must pass before deployment               │    │
│  └────────────────┬───────────────────────────────────┘    │
│                   │                                          │
│                   │ Tests Passed ✓                           │
│                   │                                          │
│       ┌───────────┴───────────┐                             │
│       │                       │                             │
│       ▼                       ▼                             │
│  ┌─────────────────┐    ┌─────────────────┐               │
│  │  JOB 2: DEPLOY  │    │  JOB 3: DEPLOY  │               │
│  │    FRONTEND     │    │     BACKEND     │               │
│  │   (Parallel)    │    │    (Parallel)   │               │
│  └─────────────────┘    └─────────────────┘               │
│                                                              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              JOB 2: DEPLOY FRONTEND                          │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  1. Checkout Code                                           │
│     └─> actions/checkout@v4                                 │
│                                                              │
│  2. Setup Node.js 20                                        │
│     └─> actions/setup-node@v4                               │
│         └─> Cache npm dependencies                          │
│                                                              │
│  3. Install Dependencies                                    │
│     └─> npm ci (clean install)                              │
│                                                              │
│  4. Build Frontend                                          │
│     └─> npm run build                                       │
│         ├─> VITE_API_BASE_URL (from secrets)                │
│         └─> VITE_GOOGLE_CLIENT_ID (from secrets)            │
│                                                              │
│  5. Deploy to Vercel                                        │
│     └─> amondnet/vercel-action@v25                          │
│         ├─> VERCEL_TOKEN (from secrets)                     │
│         ├─> VERCEL_ORG_ID (from secrets)                    │
│         ├─> VERCEL_PROJECT_ID (from secrets)                │
│         └─> --prod flag                                     │
│                                                              │
│  6. Verify Deployment                                       │
│     └─> Vercel provides deployment URL                      │
│                                                              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              JOB 3: DEPLOY BACKEND                           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  1. SSH to VPS                                              │
│     └─> appleboy/ssh-action@v1.0.3                          │
│         ├─> VPS_HOST (from secrets)                         │
│         ├─> VPS_USER (from secrets)                         │
│         ├─> VPS_SSH_KEY (from secrets)                      │
│         └─> VPS_PORT (from secrets, default 22)             │
│                                                              │
│  2. Navigate to Project                                     │
│     └─> cd /var/www/artisan-kala-api                        │
│                                                              │
│  3. Pull Latest Code                                        │
│     └─> git pull origin main                                │
│                                                              │
│  4. Install Dependencies                                    │
│     └─> composer install --optimize-autoloader --no-dev     │
│                                                              │
│  5. Run Migrations                                          │
│     └─> php artisan migrate --force                         │
│                                                              │
│  6. Clear Caches                                            │
│     ├─> php artisan config:clear                            │
│     ├─> php artisan route:clear                             │
│     ├─> php artisan view:clear                              │
│     └─> php artisan cache:clear                             │
│                                                              │
│  7. Rebuild Caches                                          │
│     ├─> php artisan config:cache                            │
│     ├─> php artisan route:cache                             │
│     └─> php artisan view:cache                              │
│                                                              │
│  8. Restart Queue Workers                                   │
│     └─> sudo supervisorctl restart laravel-worker:*         │
│                                                              │
│  9. Reload PHP-FPM                                          │
│     └─> sudo systemctl reload php8.3-fpm                    │
│                                                              │
│  10. Verify Deployment                                      │
│      └─> php artisan --version                              │
│                                                              │
│  11. Success Notification                                   │
│      └─> "Deployment completed successfully!"               │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Trigger Conditions

```
┌─────────────────────────────────────────────────────────────┐
│                    TRIGGER CONDITIONS                        │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  TEST WORKFLOW (test.yml)                                   │
│  ├─> Push to main branch                                    │
│  ├─> Push to develop branch                                 │
│  ├─> Pull request to main                                   │
│  └─> Pull request to develop                                │
│                                                              │
│  DEPLOY WORKFLOW (deploy.yml)                               │
│  ├─> Push to main branch                                    │
│  └─> Manual trigger (workflow_dispatch)                     │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Secrets Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    GITHUB SECRETS                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Frontend Secrets                                           │
│  ├─> VERCEL_TOKEN ──────────────┐                           │
│  ├─> VERCEL_ORG_ID ─────────────┤                           │
│  ├─> VERCEL_PROJECT_ID ─────────┼─> Deploy Frontend Job     │
│  ├─> VITE_API_BASE_URL ─────────┤                           │
│  └─> VITE_GOOGLE_CLIENT_ID ─────┘                           │
│                                                              │
│  Backend Secrets                                            │
│  ├─> VPS_HOST ──────────────────┐                           │
│  ├─> VPS_USER ──────────────────┤                           │
│  ├─> VPS_SSH_KEY ───────────────┼─> Deploy Backend Job      │
│  └─> VPS_PORT (optional) ───────┘                           │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Deployment Timeline

```
Time    Test Workflow              Deploy Workflow
─────   ──────────────────────     ───────────────────────────
0:00    ┌─ Start                   
0:30    ├─ Setup PHP & Services    
1:00    ├─ Install Dependencies    
2:00    ├─ Run Migrations          
3:00    ├─ Run Tests               
5:00    ├─ Run Pint                
6:00    ├─ Run PHPStan             
7:00    └─ Complete ✓              ┌─ Start
                                   ├─ Run Tests (7 min)
                                   │
7:00                               ├─ Tests Pass ✓
                                   │
                                   ├─┬─ Deploy Frontend
                                   │ │  ├─ Setup Node
                                   │ │  ├─ Install deps
                                   │ │  ├─ Build (2 min)
                                   │ │  └─ Deploy Vercel
                                   │ │
                                   │ └─ Deploy Backend
                                   │    ├─ SSH to VPS
                                   │    ├─ Pull code
                                   │    ├─ Install deps
                                   │    ├─ Migrate
                                   │    ├─ Cache
                                   │    └─ Restart services
                                   │
10:00                              └─ Complete ✓
```

## Error Handling Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    ERROR HANDLING                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Test Failure                                               │
│  ├─> Workflow stops                                         │
│  ├─> No deployment triggered                                │
│  ├─> GitHub notification sent                               │
│  └─> Developer fixes and re-pushes                          │
│                                                              │
│  Frontend Deploy Failure                                    │
│  ├─> Backend still deploys (parallel)                       │
│  ├─> Vercel keeps previous version live                     │
│  ├─> GitHub notification sent                               │
│  └─> Can rollback via Vercel dashboard                      │
│                                                              │
│  Backend Deploy Failure                                     │
│  ├─> Frontend still deploys (parallel)                      │
│  ├─> VPS keeps previous version running                     │
│  ├─> GitHub notification sent                               │
│  └─> Can rollback via git reset                             │
│                                                              │
│  PHPStan Failure                                            │
│  ├─> Workflow continues (continue-on-error)                 │
│  ├─> Warning logged                                         │
│  └─> Deployment proceeds                                    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Service Dependencies

```
┌─────────────────────────────────────────────────────────────┐
│                  SERVICE DEPENDENCIES                        │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  GitHub Actions Runner                                      │
│  └─> Ubuntu Latest                                          │
│      ├─> PHP 8.3                                            │
│      │   ├─> Extensions: dom, curl, mbstring, etc.          │
│      │   └─> Composer                                       │
│      ├─> Node.js 20                                         │
│      │   └─> npm                                            │
│      ├─> MySQL 8.0 (Docker container)                       │
│      └─> Redis 7.0 (Docker container)                       │
│                                                              │
│  Vercel Platform                                            │
│  ├─> Build environment                                      │
│  ├─> CDN (global)                                           │
│  ├─> HTTPS (automatic)                                      │
│  └─> Deployment history                                     │
│                                                              │
│  VPS Server                                                 │
│  └─> Ubuntu 24.04                                           │
│      ├─> Nginx                                              │
│      ├─> PHP 8.3-FPM                                        │
│      ├─> MySQL 8.0                                          │
│      ├─> Redis 7.0                                          │
│      ├─> Supervisor                                         │
│      └─> Git                                                │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Legend

```
┌─────────────────────────────────────────────────────────────┐
│                         LEGEND                               │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────┐                                                    │
│  │ Box │  = Process or Component                            │
│  └─────┘                                                    │
│                                                              │
│  ───────>  = Sequential flow                                │
│                                                              │
│  ═══════>  = Parallel flow                                  │
│                                                              │
│  ✓  = Success                                               │
│  ✗  = Failure                                               │
│                                                              │
│  (Parallel)  = Jobs run simultaneously                      │
│  (Sequential) = Jobs run one after another                  │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```
