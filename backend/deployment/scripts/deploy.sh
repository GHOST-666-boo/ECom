#!/bin/bash

################################################################################
# Vriddhi API - Deployment Script
# 
# This script handles deployment of updates to the Laravel API
# 
# Usage:
#   chmod +x deploy.sh
#   ./deploy.sh
#
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_PATH="/var/www/vriddhi-api"
BRANCH="main"

# Functions
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

print_step() {
    echo -e "${BLUE}▶ $1${NC}"
}

check_directory() {
    if [ ! -d "$APP_PATH" ]; then
        print_error "Application directory not found: $APP_PATH"
        exit 1
    fi
}

enable_maintenance_mode() {
    print_step "Enabling maintenance mode..."
    cd $APP_PATH
    php artisan down --retry=60 --secret="deployment-secret-token"
    print_success "Maintenance mode enabled"
}

disable_maintenance_mode() {
    print_step "Disabling maintenance mode..."
    cd $APP_PATH
    php artisan up
    print_success "Maintenance mode disabled"
}

pull_latest_code() {
    print_step "Pulling latest code from Git..."
    cd $APP_PATH
    git fetch origin
    git reset --hard origin/$BRANCH
    print_success "Code updated"
}

install_dependencies() {
    print_step "Installing Composer dependencies..."
    cd $APP_PATH
    composer install --optimize-autoloader --no-dev --no-interaction
    print_success "Dependencies installed"
}

run_migrations() {
    print_step "Running database migrations..."
    cd $APP_PATH
    php artisan migrate --force
    print_success "Migrations completed"
}

clear_cache() {
    print_step "Clearing application cache..."
    cd $APP_PATH
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
    php artisan event:clear
    print_success "Cache cleared"
}

optimize_application() {
    print_step "Optimizing application..."
    cd $APP_PATH
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    php artisan optimize
    print_success "Application optimized"
}

restart_queue_workers() {
    print_step "Restarting queue workers..."
    sudo supervisorctl restart laravel-worker:*
    print_success "Queue workers restarted"
}

reload_php_fpm() {
    print_step "Reloading PHP-FPM..."
    sudo systemctl reload php8.3-fpm
    print_success "PHP-FPM reloaded"
}

set_permissions() {
    print_step "Setting correct permissions..."
    cd $APP_PATH
    sudo chown -R deploy:www-data $APP_PATH
    sudo chmod -R 755 $APP_PATH
    sudo chmod -R 775 $APP_PATH/storage
    sudo chmod -R 775 $APP_PATH/bootstrap/cache
    print_success "Permissions set"
}

run_tests() {
    print_step "Running tests (optional)..."
    cd $APP_PATH
    if php artisan test --parallel; then
        print_success "All tests passed"
        return 0
    else
        print_error "Tests failed"
        return 1
    fi
}

create_backup() {
    print_step "Creating database backup..."
    BACKUP_DIR="$APP_PATH/backups"
    mkdir -p $BACKUP_DIR
    DATE=$(date +%Y%m%d_%H%M%S)
    
    # Read database credentials from .env
    DB_NAME=$(grep DB_DATABASE $APP_PATH/.env | cut -d '=' -f2)
    DB_USER=$(grep DB_USERNAME $APP_PATH/.env | cut -d '=' -f2)
    DB_PASS=$(grep DB_PASSWORD $APP_PATH/.env | cut -d '=' -f2)
    
    mysqldump -u $DB_USER -p"$DB_PASS" $DB_NAME | gzip > $BACKUP_DIR/backup_$DATE.sql.gz
    
    # Keep only last 5 backups
    ls -t $BACKUP_DIR/backup_*.sql.gz | tail -n +6 | xargs -r rm
    
    print_success "Backup created: backup_$DATE.sql.gz"
}

print_deployment_info() {
    echo ""
    echo "=========================================="
    print_success "Deployment Complete!"
    echo "=========================================="
    echo ""
    echo "Deployment Summary:"
    echo "  Branch: $BRANCH"
    echo "  Path: $APP_PATH"
    echo "  Time: $(date)"
    echo ""
    echo "Application Status:"
    cd $APP_PATH
    echo "  Laravel Version: $(php artisan --version)"
    echo "  Environment: $(grep APP_ENV .env | cut -d '=' -f2)"
    echo ""
    echo "Queue Workers:"
    sudo supervisorctl status laravel-worker:*
    echo ""
}

rollback() {
    print_error "Deployment failed! Rolling back..."
    cd $APP_PATH
    git reset --hard HEAD@{1}
    composer install --optimize-autoloader --no-dev --no-interaction
    php artisan migrate:rollback --force
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    sudo supervisorctl restart laravel-worker:*
    php artisan up
    print_info "Rollback completed"
    exit 1
}

# Main execution
main() {
    echo "=========================================="
    echo "  Vriddhi API - Deployment"
    echo "=========================================="
    echo ""
    
    check_directory
    
    # Ask for confirmation
    read -p "Deploy to production? (y/n) " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_info "Deployment cancelled"
        exit 0
    fi
    
    # Optional: Run tests before deployment
    read -p "Run tests before deployment? (y/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        if ! run_tests; then
            print_error "Tests failed. Deployment cancelled."
            exit 1
        fi
    fi
    
    # Create backup
    create_backup
    
    # Deployment steps
    enable_maintenance_mode
    
    # Set trap to rollback on error
    trap rollback ERR
    
    pull_latest_code
    install_dependencies
    run_migrations
    clear_cache
    optimize_application
    set_permissions
    restart_queue_workers
    reload_php_fpm
    
    # Remove trap
    trap - ERR
    
    disable_maintenance_mode
    
    print_deployment_info
}

# Run main function
main
