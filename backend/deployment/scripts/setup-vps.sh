#!/bin/bash

################################################################################
# Artisan Kala API - VPS Setup Script
# 
# This script automates the setup of a VPS server for the Artisan Kala Laravel API
# 
# Requirements:
# - Ubuntu 24.04 LTS
# - Root or sudo access
# - Domain name configured to point to server IP
#
# Usage:
#   chmod +x setup-vps.sh
#   sudo ./setup-vps.sh
#
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration variables (update these)
DOMAIN="api.artisankala.com"
APP_PATH="/var/www/artisan-kala-api"
DEPLOY_USER="deploy"
DB_NAME="artisan_kala"
DB_USER="artisan_kala_user"
DB_PASSWORD=""  # Will be prompted
REDIS_PASSWORD=""  # Will be prompted
EMAIL=""  # For Let's Encrypt

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

check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root or with sudo"
        exit 1
    fi
}

prompt_config() {
    print_info "Configuration Setup"
    echo ""
    
    read -p "Enter domain name [api.artisankala.com]: " input_domain
    DOMAIN=${input_domain:-$DOMAIN}
    
    read -p "Enter application path [/var/www/artisan-kala-api]: " input_path
    APP_PATH=${input_path:-$APP_PATH}
    
    read -p "Enter deploy user [deploy]: " input_user
    DEPLOY_USER=${input_user:-$DEPLOY_USER}
    
    read -p "Enter database name [artisan_kala]: " input_db
    DB_NAME=${input_db:-$DB_NAME}
    
    read -p "Enter database user [artisan_kala_user]: " input_db_user
    DB_USER=${input_db_user:-$DB_USER}
    
    read -sp "Enter database password: " DB_PASSWORD
    echo ""
    
    read -sp "Enter Redis password: " REDIS_PASSWORD
    echo ""
    
    read -p "Enter email for Let's Encrypt: " EMAIL
    
    echo ""
    print_info "Configuration complete!"
    echo ""
}

update_system() {
    print_info "Updating system packages..."
    apt update && apt upgrade -y
    print_success "System updated"
}

install_nginx() {
    print_info "Installing Nginx..."
    apt install nginx -y
    systemctl enable nginx
    systemctl start nginx
    print_success "Nginx installed"
}

install_php() {
    print_info "Installing PHP 8.3 and extensions..."
    add-apt-repository ppa:ondrej/php -y
    apt update
    apt install php8.3-fpm php8.3-cli php8.3-mysql php8.3-redis \
        php8.3-mbstring php8.3-xml php8.3-bcmath php8.3-curl \
        php8.3-zip php8.3-gd php8.3-intl php8.3-imagick -y
    print_success "PHP 8.3 installed"
}

install_mysql() {
    print_info "Installing MySQL 8.0..."
    apt install mysql-server -y
    systemctl enable mysql
    systemctl start mysql
    print_success "MySQL installed"
}

configure_mysql() {
    print_info "Configuring MySQL database..."
    mysql -e "CREATE DATABASE ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -e "CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
    mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
    print_success "MySQL database configured"
}

install_redis() {
    print_info "Installing Redis..."
    apt install redis-server -y
    systemctl enable redis-server
    
    # Configure Redis
    sed -i "s/^# requirepass .*/requirepass ${REDIS_PASSWORD}/" /etc/redis/redis.conf
    sed -i "s/^bind .*/bind 127.0.0.1/" /etc/redis/redis.conf
    
    systemctl restart redis-server
    print_success "Redis installed and configured"
}

install_composer() {
    print_info "Installing Composer..."
    cd /tmp
    curl -sS https://getcomposer.org/installer -o composer-setup.php
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
    print_success "Composer installed"
}

install_supervisor() {
    print_info "Installing Supervisor..."
    apt install supervisor -y
    systemctl enable supervisor
    systemctl start supervisor
    print_success "Supervisor installed"
}

install_certbot() {
    print_info "Installing Certbot..."
    apt install certbot python3-certbot-nginx -y
    print_success "Certbot installed"
}

create_deploy_user() {
    print_info "Creating deploy user..."
    if id "$DEPLOY_USER" &>/dev/null; then
        print_info "User $DEPLOY_USER already exists"
    else
        adduser --disabled-password --gecos "" $DEPLOY_USER
        usermod -aG sudo $DEPLOY_USER
        print_success "Deploy user created"
    fi
}

configure_php_fpm() {
    print_info "Configuring PHP-FPM..."
    
    # Update PHP-FPM pool configuration
    sed -i "s/^user = .*/user = ${DEPLOY_USER}/" /etc/php/8.3/fpm/pool.d/www.conf
    sed -i "s/^group = .*/group = ${DEPLOY_USER}/" /etc/php/8.3/fpm/pool.d/www.conf
    
    # Update PHP settings
    sed -i "s/^memory_limit = .*/memory_limit = 256M/" /etc/php/8.3/fpm/php.ini
    sed -i "s/^upload_max_filesize = .*/upload_max_filesize = 10M/" /etc/php/8.3/fpm/php.ini
    sed -i "s/^post_max_size = .*/post_max_size = 10M/" /etc/php/8.3/fpm/php.ini
    sed -i "s/^expose_php = .*/expose_php = Off/" /etc/php/8.3/fpm/php.ini
    
    # Create PHP error log directory
    mkdir -p /var/log/php
    chown ${DEPLOY_USER}:${DEPLOY_USER} /var/log/php
    
    systemctl restart php8.3-fpm
    print_success "PHP-FPM configured"
}

setup_nginx_config() {
    print_info "Setting up Nginx configuration..."
    
    # Create application directory
    mkdir -p $APP_PATH
    chown ${DEPLOY_USER}:${DEPLOY_USER} $APP_PATH
    
    # Note: Nginx config should be copied manually or via deployment
    print_info "Please copy your Nginx configuration to /etc/nginx/sites-available/${DOMAIN}"
    print_info "Then run: sudo ln -s /etc/nginx/sites-available/${DOMAIN} /etc/nginx/sites-enabled/"
}

setup_ssl() {
    print_info "Setting up SSL certificate..."
    if [ -z "$EMAIL" ]; then
        print_error "Email not provided. Skipping SSL setup."
        print_info "Run manually: sudo certbot --nginx -d ${DOMAIN}"
    else
        certbot --nginx -d ${DOMAIN} --non-interactive --agree-tos --email ${EMAIL} --redirect
        print_success "SSL certificate obtained"
    fi
}

configure_firewall() {
    print_info "Configuring firewall..."
    ufw allow OpenSSH
    ufw allow 'Nginx Full'
    ufw --force enable
    print_success "Firewall configured"
}

setup_supervisor() {
    print_info "Setting up Supervisor for queue workers..."
    print_info "Please copy your Supervisor configuration to /etc/supervisor/conf.d/"
    print_info "Then run: sudo supervisorctl reread && sudo supervisorctl update"
}

setup_cron() {
    print_info "Setting up Laravel Scheduler cron job..."
    
    # Add cron job for deploy user
    (crontab -u ${DEPLOY_USER} -l 2>/dev/null; echo "* * * * * cd ${APP_PATH} && php artisan schedule:run >> /dev/null 2>&1") | crontab -u ${DEPLOY_USER} -
    
    print_success "Cron job configured"
}

print_summary() {
    echo ""
    echo "=========================================="
    print_success "VPS Setup Complete!"
    echo "=========================================="
    echo ""
    echo "Configuration Summary:"
    echo "  Domain: ${DOMAIN}"
    echo "  App Path: ${APP_PATH}"
    echo "  Deploy User: ${DEPLOY_USER}"
    echo "  Database: ${DB_NAME}"
    echo "  Database User: ${DB_USER}"
    echo ""
    echo "Next Steps:"
    echo "  1. Copy Nginx configuration to /etc/nginx/sites-available/${DOMAIN}"
    echo "  2. Enable site: sudo ln -s /etc/nginx/sites-available/${DOMAIN} /etc/nginx/sites-enabled/"
    echo "  3. Test Nginx: sudo nginx -t"
    echo "  4. Reload Nginx: sudo systemctl reload nginx"
    echo "  5. Clone your Laravel application to ${APP_PATH}"
    echo "  6. Run: composer install --optimize-autoloader --no-dev"
    echo "  7. Configure .env file with database and Redis credentials"
    echo "  8. Run: php artisan key:generate"
    echo "  9. Run: php artisan migrate --force"
    echo "  10. Copy Supervisor config to /etc/supervisor/conf.d/"
    echo "  11. Start workers: sudo supervisorctl reread && sudo supervisorctl update"
    echo ""
    echo "Credentials saved to: /root/artisan-kala-credentials.txt"
    echo ""
}

save_credentials() {
    print_info "Saving credentials..."
    cat > /root/artisan-kala-credentials.txt <<EOF
Artisan Kala API - Server Credentials
======================================

Database:
  Name: ${DB_NAME}
  User: ${DB_USER}
  Password: ${DB_PASSWORD}

Redis:
  Password: ${REDIS_PASSWORD}

Domain: ${DOMAIN}
App Path: ${APP_PATH}
Deploy User: ${DEPLOY_USER}

Generated: $(date)
EOF
    chmod 600 /root/artisan-kala-credentials.txt
    print_success "Credentials saved to /root/artisan-kala-credentials.txt"
}

# Main execution
main() {
    echo "=========================================="
    echo "  Artisan Kala API - VPS Setup Script"
    echo "=========================================="
    echo ""
    
    check_root
    prompt_config
    
    print_info "Starting VPS setup..."
    echo ""
    
    update_system
    create_deploy_user
    install_nginx
    install_php
    install_mysql
    configure_mysql
    install_redis
    install_composer
    install_supervisor
    install_certbot
    configure_php_fpm
    setup_nginx_config
    configure_firewall
    setup_supervisor
    setup_cron
    save_credentials
    
    # SSL setup (optional, can fail if DNS not configured)
    if [ ! -z "$EMAIL" ]; then
        setup_ssl || print_info "SSL setup failed. Run manually: sudo certbot --nginx -d ${DOMAIN}"
    fi
    
    print_summary
}

# Run main function
main
