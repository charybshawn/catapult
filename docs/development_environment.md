# Development Environment Setup

## Overview
This document outlines the local development environment setup for the Catapult v2 project. We use Herd for PHP/web server management and DBngin for database management.

## Required Tools

### Herd
[Herd](https://herd.laravel.com/) is a native macOS application that provides a simple and fast Laravel development environment.

1. Download and install Herd from [https://herd.laravel.com/](https://herd.laravel.com/)
2. Herd automatically configures PHP, Nginx, and other dependencies
3. Verify installation with:
   ```bash
   php --version
   ```
4. Configure Herd to serve your project directory

### DBngin
[DBngin](https://dbngin.com/) is a free, all-in-one database version management tool.

1. Download and install DBngin from [https://dbngin.com/](https://dbngin.com/)
2. Create a new MySQL database:
   - Click "+" to create a new database
   - Select MySQL (version 8.x recommended)
   - Name: `catapult_v2`
   - Port: Default (or specify custom)
   - Username: `root` (default)
   - Password: Leave blank for local development
3. Start the database server

### Composer
Composer is used for PHP dependency management.

1. Herd includes Composer by default
2. Verify installation with:
   ```bash
   composer --version
   ```

## Project Setup

### Environment Configuration
1. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```

2. Update your `.env` file with database credentials:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=catapult_v2
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. Generate application key:
   ```bash
   php artisan key:generate
   ```

### Installing Dependencies
1. Install PHP dependencies:
   ```bash
   composer install
   ```

2. Install required packages:
   ```bash
   composer require filament/filament:"^3.2" laravel/socialite spatie/laravel-permission stripe/stripe-php laravel/cashier
   ```

3. Install Laravel Breeze for authentication (required for Laravel 12):
   ```bash
   composer require laravel/breeze --dev
   php artisan breeze:install blade
   ```

4. Install and configure Filament:
   ```bash
   php artisan filament:install --panels
   ```

5. Publish package configurations:
   ```bash
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   php artisan vendor:publish --tag="cashier-config"
   php artisan vendor:publish --tag="filament-config"
   ```

6. Run migrations:
   ```bash
   php artisan migrate
   ```

7. Seed the database with initial data:
   ```bash
   php artisan db:seed --class=RoleSeeder
   php artisan db:seed --class=AdminUserSeeder
   php artisan db:seed --class=FilamentAdminUserSeeder
   ```

## Accessing the Application
1. Herd automatically serves sites in the configured directory
2. Access your application at: `http://catapult-v2.test` (or the domain configured in Herd)
3. Access the Filament admin panel at: `http://catapult-v2.test/admin`
4. Login to the Filament admin panel with:
   - Email: `filament@catapult.farm`
   - Password: `filament_admin`

## Troubleshooting

### Database Connection Issues
1. Ensure DBngin server is running
2. Verify database credentials in `.env` file
3. Try running:
   ```bash
   php artisan config:clear
   ```

### Web Server Issues
1. Ensure Herd is running
2. Check Herd logs for errors
3. Try restarting Herd

### PHP Version Issues
1. Ensure Herd is using PHP 8.2 or higher
2. Check PHP version with:
   ```bash
   php --version
   ```

### Authentication Issues
1. Laravel 12 requires Breeze for authentication scaffolding
2. If you encounter "Class Route not found" errors, use fully qualified class names in blade templates:
   ```php
   @if (\Illuminate\Support\Facades\Route::has('login'))
   ```
3. Clear cache after making changes:
   ```bash
   php artisan optimize:clear
   ``` 