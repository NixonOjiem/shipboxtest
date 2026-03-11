# Backend API Project

This project is a Laravel-based backend API with role and permission management powered by **Spatie Laravel Permission**. It provides endpoints for managing products, orders, users, and roles, with authentication handled via Laravel Sanctum.

## Clone the project

git clone https://github.com/NixonOjiem/shipboxtest

cd shipboxtest

run

composer install

cp .env.example .env
Note: Update your DB_DATABASE, DB_USERNAME, and DB_PASSWORD in .env

## Project initializiation

1. php artisan key:generate
2. php artisan migrate
3. php artisan db:seed # Seeds Roles, Permissions, and a default Admin
4. user: email:admin@app.com password: secretAdmin123

## start server

php artisan serve

## Roles & Permissions

Roles are managed via Spatie HasRoles trait in the User model.
Seeder: RolesAndPermissionsSeeder

Creates default roles: Admin and Seller
Assigns CRUD permissions:

Seller:

1. read products
2. create orders
3. read orders
4. update orders

Admin: Full access

run
php artisan db:seed

## Controllers

1. UserController → Manage users (roles)
2. RoleController → Manage roles & permissions
3. ProductController → CRUD for products
4. OrderController → CRUD for orders

## Policies

OrderPolicy → Only Admins or the Seller who owns the order can update orders.

## Stock Management

1. Trait → Handles increasing/decreasing product stock.
2. Observer → Listens to Order updates:
   On status changes (delivered, returned, etc.), stock is automatically adjusted. Registered in AppServiceProvider

## Order Statuses

Orders can have the following statuses:

1. onhold
2. returned
3. delivered
4. refunded
5. outofstock
6. cancelled
7. shipped
8. to prepare

## Authentication

Uses Laravel Sanctum for API token authentication.
