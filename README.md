# Inventory Management System

A multi-warehouse inventory management system built with Laravel and Filament. Tracks products, stock levels, purchase orders, sales, and stock transfers across multiple locations with full audit logging and role-based access control.

---

## Features

- **Role-based access control** — Admin, Manager, and Viewer roles with scoped permissions
- **Multi-warehouse stock tracking** — Stock levels tracked per product per warehouse, not as a single global quantity
- **Purchase orders** — Create orders with line items; receiving a PO automatically updates stock
- **Sales** — Process customer transactions with line items; stock is deducted on save
- **Stock transfers** — Move products between warehouses with full audit trail
- **Stock movement log** — Immutable audit log of every stock change (in, out, transfer) with historical pricing
- **Low stock alerts** — Configurable reorder levels per product per warehouse
- **Auto-generated reference numbers** — PO numbers, sale numbers, and transfer numbers generated automatically

---

## Database Structure

The system is made up of 11 resources run in this order to satisfy foreign key dependencies:

| # | Resource | Depends On |
|---|----------|-----------|
| 1 | User | — |
| 2 | Supplier | — |
| 3 | Warehouse | — |
| 4 | Product | Supplier |
| 5 | ProductStock | Product, Warehouse |
| 6 | StockMovement | Product, Warehouse, User |
| 7 | PurchaseOrder | Supplier |
| 8 | PurchaseOrderItem | PurchaseOrder, Product, Warehouse |
| 9 | Sale | User |
| 10 | SaleItem | Sale, Product |
| 11 | StockTransfer | Product, Warehouse, User |

---

## Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js & NPM

---

## Installation

**1. Clone the repository**

```bash
git clone https://github.com/your-username/inventory-management-system.git
cd inventory-management-system
```

**2. Install PHP dependencies**

```bash
composer install
```

**3. Install frontend dependencies**

```bash
npm install && npm run build
```

**4. Set up environment**

```bash
cp .env.example .env
php artisan key:generate
```

**5. Configure your database in `.env`**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_db
DB_USERNAME=root
DB_PASSWORD=
```

**6. Run migrations and seed**

```bash
php artisan migrate --seed
```

**7. Start the development server**

```bash
php artisan serve
```

Then visit `http://localhost:8000` to access the admin panel.

---

## Default Admin Credentials

After seeding, log in with:

```
Email:    admin@admin.com
Password: password
```

---

## User Roles

Roles and permissions are managed via [Spatie Laravel Permission](https://github.com/spatie/laravel-permission) and [Filament Shield](https://github.com/bezhanSalleh/filament-shield). There are no hardcoded roles — admin users can create and configure roles with granular permissions directly from the admin panel.

To set up permissions after installation:

```bash
php artisan shield:generate --all
```

This generates permissions for all Filament resources automatically. From there, create roles and assign permissions through the admin panel under **Shield → Roles**.

---

## License

This project is open-sourced under the [MIT license](LICENSE).
