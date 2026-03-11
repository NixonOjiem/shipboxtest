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

php artisan key:generate
php artisan migrate
php artisan db:seed # Seeds Roles, Permissions, and a default Admin
user: email:admin@app.com password: secretAdmin123

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

UserController → Manage users (roles)
RoleController → Manage roles & permissions
ProductController → CRUD for products
OrderController → CRUD for orders

## Policies

OrderPolicy → Only Admins or the Seller who owns the order can update orders.

## Stock Management

Trait → Handles increasing/decreasing product stock.
Observer → Listens to Order updates:
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
