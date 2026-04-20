#!/bin/bash
# ============================================
# RUN THIS ONCE on Namecheap via SSH
# ============================================

# 1. Clone the repo into home directory
cd ~
git clone https://github.com/ishadow13i/delivery-tracker.git

# 2. Install dependencies
cd delivery-tracker
composer install --no-dev --optimize-autoloader

# 3. Copy and configure .env
cp .env.example .env
php artisan key:generate

echo ""
echo "============================================"
echo " NOW EDIT .env WITH YOUR DATABASE DETAILS:"
echo "   nano ~/delivery-tracker/.env"
echo ""
echo " Set these values:"
echo "   APP_URL=https://yourdomain.com"
echo "   DB_DATABASE=your_cpanel_db_name"
echo "   DB_USERNAME=your_cpanel_db_user"
echo "   DB_PASSWORD=your_cpanel_db_password"
echo "============================================"
echo ""
echo " AFTER editing .env, run:"
echo "   cd ~/delivery-tracker"
echo "   php artisan migrate --force"
echo "   php artisan db:seed --force"
echo "   php artisan config:cache"
echo "   php artisan route:cache"
echo "   php artisan view:cache"
echo ""
echo " THEN link public_html:"
echo "   rm -rf ~/public_html"
echo "   ln -s ~/delivery-tracker/public ~/public_html"
echo "============================================"
