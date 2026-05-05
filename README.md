# Reservation Calculator

A Laravel-based reservation management and rate calculation web application.

## Requirements

- PHP 8.0+
- Composer
- XAMPP (or any PHP server)

## Setup After Cloning

Run these commands **in order** inside the project folder:

```bash
# 1. Install PHP dependencies
composer install

# 2. Create environment file (Windows)
copy .env.example .env

# 2. Create environment file (Mac/Linux)
cp .env.example .env

# 3. Generate application key (required to run)
php artisan key:generate

# 4. Start the development server
php artisan serve
```

Then open your browser at: [http://127.0.0.1:8000](http://127.0.0.1:8000)

> **XAMPP Users:** If `php` is not in your PATH, use the full path:
> `C:\xampp\php\php.exe artisan key:generate`
> `C:\xampp\php\php.exe artisan serve`

## Features

- **Dashboard** — View, filter, and paginate reservation records from the API.
- **Detailed Records** — Click any reservation number to see a full per-passenger breakdown.
- **Export Excel** — Download a full Excel spreadsheet with calculated fee breakdown.
- **Print / PDF** — Generate a printable report for any date range or reservation list.
- **Setup** — Configure all rates, fees, and business rules (Airfare, Hangar, AOF, Environmental, VAT, SC/PWD discounts, Promo periods, Peak periods) with a flexible UI.

## Setup Configuration

Navigate to `/setup` in the app to configure:

- **Base Airfare Rates** (Member / Guest)
- **Promo Rates** with date ranges and Peak Period exclusions
- **Hangar Fee**, **Aviation Operational Fee (AOF)**, and **Environmental Fee**
- **VAT Rate** and **SC/PWD discount rules** (Remove VAT toggle)
- **Infant policy** (pup = Y treated as free airfare)

All settings are saved to `storage/app/settings.json`.
