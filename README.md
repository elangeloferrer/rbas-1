# Auth System

Multi-role authentication system (Customer, Merchant, Admin).

## Stack

-**Backend:** Laravel 13, Sanctum 4, Pest 3, MySQL 8 -**Frontend:** Vue 3, TypeScript 5, Vite 6, Pinia 2, Tailwind CSS v4

## Quick Start

### Backend

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
