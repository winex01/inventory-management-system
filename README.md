# 📦 Complete Inventory Management System

A multi-warehouse inventory management system built with Laravel. Tracks products, stock levels, purchase orders, sales, and stock transfers across multiple locations.

---

## 🗂️ Database Resources

### 1. User
Represents anyone who can log into the system. Controls authentication and role-based permissions.

Each user has a **role** that determines what actions they can perform:
- `admin` — full access
- `manager` — create/edit
- `viewer` — read-only

Also used for auditing (tracking who created or modified records).

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name', 255);
    $table->string('email', 255)->unique();
    $table->string('password', 255);
    $table->enum('role', ['admin', 'manager', 'viewer']);
    $table->timestamps();
});
```

---

### 2. Supplier
Represents a vendor or company you purchase products from.

Stores contact information for suppliers. Links to **Products** and **Purchase Orders**. Used for restocking and vendor management.

```php
Schema::create('suppliers', function (Blueprint $table) {
    $table->id();
    $table->string('name', 255);
    $table->string('contact_person', 255)->nullable();
    $table->string('phone', 50)->nullable();
    $table->string('email', 255)->nullable();
    $table->text('address')->nullable();
    $table->timestamps();
});
```

---

### 3. Warehouse
Represents a physical location where products are stored.

Defines all storage locations (buildings, rooms, shelves, bins). Each product's quantity is tracked **per warehouse**. Enables multi-location inventory management and stock transfers between locations.

```php
Schema::create('warehouses', function (Blueprint $table) {
    $table->id();
    $table->string('name', 255);
    $table->string('code', 50)->unique();
    $table->text('address')->nullable();
    $table->string('manager_name', 255)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

---

### 4. Product
Represents an item you track, store, buy, or sell. Core of the inventory system.

Stores product details including SKU, name, description, and unit price. Links to a **Supplier**. Stock quantity is **not** stored here — it is tracked per warehouse via `ProductStock`.

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('sku', 100)->unique();
    $table->string('name', 255);
    $table->text('description')->nullable();
    $table->decimal('unit_price', 10, 2)->default(0);
    $table->foreignId('supplier_id')->constrained()->onDelete('set null');
    $table->timestamps();
});
```

---

### 5. ProductStock
Tracks the quantity of a specific product in a specific warehouse.

Replaces the `current_quantity` field on Product. Each record represents stock for one product at one warehouse. Total stock is the **sum of all records** for that product. Used for location-specific inventory counts and low stock alerts.

```php
Schema::create('product_stock', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
    $table->integer('quantity')->default(0);
    $table->integer('reorder_level')->default(0);
    $table->timestamps();
    $table->unique(['product_id', 'warehouse_id']);
});
```

---

### 6. StockMovement
Records every change in product quantity (audit log).

Logs every stock increase (`in`), decrease (`out`), and transfer (`transfer`). Stores the historical unit price at time of movement. Links to a reference record (purchase order or sale) for traceability. Records which user performed the action.

```php
Schema::create('stock_movements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->foreignId('warehouse_id')->constrained()->onDelete('restrict');
    $table->enum('type', ['in', 'out', 'transfer']);
    $table->integer('quantity');
    $table->decimal('unit_price_at_time', 10, 2);
    $table->bigInteger('reference_id');
    $table->string('reference_type', 50); // 'purchase_order', 'sale', 'stock_transfer'
    $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
    $table->timestamp('created_at');
});
```

---

### 7. PurchaseOrder
Represents an order placed with a supplier to restock products.

Created before goods arrive. Tracks PO number, supplier, order date, expected delivery, and status. When marked as `received`, it triggers stock movements and increases `ProductStock` quantities.

**Statuses:** `pending` · `received` · `cancelled`

```php
Schema::create('purchase_orders', function (Blueprint $table) {
    $table->id();
    $table->string('po_number', 100)->unique();
    $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
    $table->date('order_date');
    $table->date('expected_delivery')->nullable();
    $table->enum('status', ['pending', 'received', 'cancelled'])->default('pending');
    $table->decimal('total_amount', 12, 2)->default(0);
    $table->timestamps();
});
```

---

### 8. PurchaseOrderItem
Represents individual line items within a purchase order.

Breaks down what products were ordered, in what quantity, at what price, and which warehouse they go to. One purchase order can have multiple items. Used when receiving to know which warehouse to add stock to.

```php
Schema::create('purchase_order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_id')->constrained()->onDelete('restrict');
    $table->foreignId('warehouse_id')->constrained()->onDelete('restrict');
    $table->integer('quantity');
    $table->decimal('unit_price', 10, 2);
    $table->decimal('total', 12, 2);
});
```

---

### 9. Sale
Represents a transaction where products leave inventory to a customer.

Records customer purchases. Tracks sale number, customer name (optional), sale date, total amount, and which user processed the sale. When created, it triggers stock movements and decreases `ProductStock` quantities.

```php
Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->string('sale_number', 100)->unique();
    $table->string('customer_name', 255)->nullable();
    $table->date('sale_date');
    $table->decimal('total_amount', 12, 2)->default(0);
    $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
    $table->timestamps();
});
```

---

### 10. SaleItem
Represents individual line items within a sale.

Breaks down what products were sold, in what quantity, and at what price. One sale can have multiple products. Used when processing a sale to know which products to deduct from stock.

```php
Schema::create('sale_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_id')->constrained()->onDelete('restrict');
    $table->integer('quantity');
    $table->decimal('unit_price', 10, 2);
    $table->decimal('total', 12, 2);
});
```

---

### 11. StockTransfer
Represents moving stock from one warehouse to another.

Records transfer requests between locations. When completed, it decreases stock at the source warehouse, increases stock at the destination warehouse, and logs two `StockMovement` records.

**Statuses:** `pending` · `completed` · `cancelled`

```php
Schema::create('stock_transfers', function (Blueprint $table) {
    $table->id();
    $table->string('transfer_number', 100)->unique();
    $table->foreignId('product_id')->constrained()->onDelete('restrict');
    $table->foreignId('from_warehouse_id')->constrained('warehouses')->onDelete('restrict');
    $table->foreignId('to_warehouse_id')->constrained('warehouses')->onDelete('restrict');
    $table->integer('quantity');
    $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
    $table->timestamp('transferred_at')->nullable();
    $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
    $table->timestamps();
});
```

---

## 🔗 Migration Order

Run migrations in this order to satisfy foreign key dependencies:

| Order | Resource           | Depends On                        |
|-------|--------------------|-----------------------------------|
| 1     | User               | —                                 |
| 2     | Supplier           | —                                 |
| 3     | Warehouse          | —                                 |
| 4     | Product            | Supplier                          |
| 5     | ProductStock       | Product, Warehouse                |
| 6     | StockMovement      | Product, Warehouse, User          |
| 7     | PurchaseOrder      | Supplier                          |
| 8     | PurchaseOrderItem  | PurchaseOrder, Product, Warehouse |
| 9     | Sale               | User                              |
| 10    | SaleItem           | Sale, Product                     |
| 11    | StockTransfer      | Product, Warehouse, User          |
