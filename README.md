# Maximus Hotel - Backend

This folder contains all the backend components of the Maximus Hotel Laravel application.

## Structure

This is a standard Laravel application structure with the following key directories:

- `app/` - Application logic (Controllers, Models, Mail, Middleware)
- `bootstrap/` - Application bootstrap files
- `config/` - Configuration files
- `database/` - Migrations and database-related files
- `public/` - Public entry point and assets
- `resources/` - Views and other resources
- `routes/` - Route definitions (web.php, api.php)
- `storage/` - Storage for logs, cache, sessions, etc.
- `vendor/` - Composer dependencies (excluded from git)

## Installation

1. Navigate to this directory:
   ```bash
   cd backend
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Copy environment file:
   ```bash
   cp .env.example .env
   ```

4. Generate application key:
   ```bash
   php artisan key:generate
   ```

5. Configure your database in `.env` file

6. Run migrations:
   ```bash
   php artisan migrate
   ```

## Deployment

For deployment, ensure that:
- The web server document root points to `backend/public/`
- Proper file permissions are set on `storage/` and `bootstrap/cache/`
- Environment variables are configured in `.env`

## Framework

- **Laravel Framework**: ^12.41
- **PHP**: Requires PHP 8.2 or higher

