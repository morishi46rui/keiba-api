# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Communication Style

**このプロジェクトでは、すべてのコミュニケーションを日本語で行ってください。**

コードのコメント、コミットメッセージ、レスポンス、エラーメッセージなど、すべての出力は日本語で記述してください。ただし、変数名、関数名、クラス名などのコード自体は英語で記述します。

## Project Overview

This is a Laravel 12 API application for a keiba (horse racing) system. The project uses PostgreSQL 18 as the database and includes Vite with Tailwind CSS 4 for asset building. Docker Compose is used for the development environment.

## Development Commands

### Docker 環境のセットアップ

プロジェクトは Docker Compose を使用して実行します。

```bash
# 初回セットアップ
docker-compose up -d          # コンテナをバックグラウンドで起動
docker-compose exec app composer install  # Composerパッケージをインストール
docker-compose exec app php artisan key:generate  # アプリケーションキーを生成
docker-compose exec app php artisan migrate       # マイグレーションを実行
docker-compose exec app npm install               # NPMパッケージをインストール
docker-compose exec app npm run build             # アセットをビルド
```

### Docker 環境の操作

```bash
docker-compose up -d          # コンテナを起動
docker-compose down           # コンテナを停止・削除
docker-compose ps             # コンテナの状態を確認
docker-compose logs -f        # ログをリアルタイムで表示
docker-compose exec app bash  # アプリケーションコンテナに入る
```

### Docker 環境でのコマンド実行

```bash
# Artisanコマンド
docker-compose exec app php artisan [command]

# Composer
docker-compose exec app composer [command]

# NPM
docker-compose exec app npm [command]

# テスト実行
docker-compose exec app php artisan test
```

### ローカル開発（Docker を使わない場合）

```bash
composer setup  # Runs: install, copy .env, generate key, migrate, npm install, npm build
composer dev    # Starts all services: Laravel server, queue worker, logs (Pail), and Vite
```

Or run services individually:

```bash
php artisan serve           # Start development server (http://localhost:8000)
npm run dev                 # Start Vite dev server for frontend assets
php artisan queue:listen    # Start queue worker
php artisan pail            # Tail application logs
```

### Testing

```bash
composer test              # Clears config cache and runs PHPUnit
php artisan test           # Run all tests
php artisan test --filter TestName  # Run specific test
```

Test configuration uses in-memory SQLite database (see [phpunit.xml](phpunit.xml)).

### Code Quality

```bash
./vendor/bin/pint          # Run Laravel Pint (code formatter)
./vendor/bin/pint --test   # Check formatting without fixing
```

### Database

```bash
php artisan migrate              # Run migrations
php artisan migrate:fresh        # Drop all tables and re-run migrations
php artisan migrate:fresh --seed # Fresh migration with seeders
php artisan db:seed              # Run database seeders
php artisan make:migration name  # Create new migration
```

Default database: SQLite at `database/database.sqlite`

### Asset Building

```bash
npm run build              # Build production assets with Vite
npm run dev                # Start Vite dev server with HMR
```

### Other Useful Commands

```bash
php artisan tinker         # Interactive REPL
php artisan route:list     # List all registered routes
php artisan make:model ModelName -m  # Create model with migration
php artisan make:controller ControllerName  # Create controller
php artisan optimize       # Cache config, routes, and views
php artisan optimize:clear # Clear all cached data
```

## Architecture

### Framework

-   **Laravel 12** (latest version as of January 2025)
-   **PHP 8.2+** required
-   Uses modern Laravel conventions (anonymous migration classes, typed properties)

### Database

-   Default: **PostgreSQL 18** (running in Docker container)
-   Queue connection: `database` (jobs stored in DB)
-   Cache: `redis` driver (running in Docker container)
-   Session: `database` driver
-   Migrations use date-prefixed naming: `0001_01_01_000000_create_users_table.php`

### Docker Services

-   **app**: PHP 8.2-FPM with Laravel application
-   **nginx**: Nginx web server (port 8000)
-   **db**: PostgreSQL 18 database (port 5432)
-   **redis**: Redis for caching (port 6379)
-   **queue**: Laravel queue worker

### Frontend Stack

-   **Vite 7** for asset bundling and HMR
-   **Tailwind CSS 4** via `@tailwindcss/vite` plugin
-   Laravel Vite plugin for seamless integration
-   Resources: `resources/css/app.css`, `resources/js/app.js`
-   Vite ignores storage views to prevent unnecessary rebuilds

### Directory Structure

-   `app/Http/Controllers/` - HTTP controllers (currently only base Controller)
-   `app/Models/` - Eloquent models (User model included)
-   `app/Providers/` - Service providers
-   `routes/web.php` - Web routes
-   `routes/console.php` - Artisan commands
-   `database/migrations/` - Database migrations
-   `database/seeders/` - Database seeders
-   `database/factories/` - Model factories for testing
-   `tests/Feature/` - Feature tests
-   `tests/Unit/` - Unit tests
-   `resources/` - Frontend assets (CSS, JS, views)
-   `config/` - Configuration files

### Testing

-   **PHPUnit 11** for testing framework
-   Uses in-memory SQLite for test database
-   Test environment configured in `phpunit.xml`
-   Includes Faker for generating test data
-   Laravel Collision for better error formatting

### Developer Tools

-   **Laravel Pint** - Opinionated PHP code formatter
-   **Laravel Sail** - Docker development environment (available but not required)
-   **Laravel Pail** - Log tailing with beautiful output
-   **Laravel Tinker** - Powerful REPL for interacting with the application

## Configuration Notes

### Environment

-   Copy `.env.example` to `.env` for local configuration
-   Key variables: `APP_KEY`, `DB_CONNECTION`, `QUEUE_CONNECTION`, `CACHE_STORE`
-   Default locale: `en`
-   Debug mode enabled by default in local environment

### Queue System

-   Default connection: `database`
-   Jobs are stored in the database `jobs` table
-   Use `php artisan queue:listen --tries=1` for development (included in `composer dev`)

### Logging

-   Default channel: `stack` (configured as `single` in .env.example)
-   Use `php artisan pail` for tailing logs during development

## PSR-4 Autoloading

-   `App\` → `app/`
-   `Database\Factories\` → `database/factories/`
-   `Database\Seeders\` → `database/seeders/`
-   `Tests\` → `tests/`

## Current State

This is a fresh Laravel 12 installation with minimal customization. The application currently has:

-   Basic user authentication structure (User model, migrations)
-   Default welcome route
-   Standard Laravel directory structure
-   No custom business logic implemented yet

When adding features, follow Laravel conventions and use Artisan generators where appropriate.
