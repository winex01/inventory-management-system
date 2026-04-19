<?php
/*
COMPLETE INVENTORY MANAGEMENT SYSTEM - RESOURCES LIST
=======================================================

RESOURCE 1: User
----------------
Model: User

Purpose: Represents anyone who can log into the system. Controls authentication and permissions.

Explanation: Each user has a role that determines what actions they can perform. Admin has full access, manager can create/edit, viewer is read-only. Also used for auditing (tracking who created or modified records).

Migration Code:
Schema::create('users', function (Blueprint $table) {
    $table->id(); // explanation: unique identifier for each user
    $table->string('name', 255); // explanation: user's full name for display
    $table->string('email', 255)->unique(); // explanation: login email address, must be unique
    $table->string('password', 255); // explanation: hashed password for authentication
    $table->enum('role', ['admin', 'manager', 'viewer']); // explanation: determines permissions (admin=full, manager=edit, viewer=read)
    $table->timestamps(); // explanation: created_at and updated_at timestamps
});


RESOURCE 2: Supplier
--------------------
Model: Supplier

Purpose: Represents a vendor or company you purchase products from.

Explanation: Stores contact information for suppliers. Links to products (which supplier provides them) and purchase orders (orders placed with this supplier). Used for restocking and vendor management.

Migration Code:
Schema::create('suppliers', function (Blueprint $table) {
    $table->id(); // explanation: unique identifier for each supplier
    $table->string('name', 255); // explanation: company or vendor name
    $table->string('contact_person', 255)->nullable(); // explanation: name of primary contact person at supplier
    $table->string('phone', 50)->nullable(); // explanation: contact phone number
    $table->string('email', 255)->nullable(); // explanation: contact email address
    $table->text('address')->nullable(); // explanation: physical or mailing address
    $table->timestamps(); // explanation: created_at and updated_at timestamps
});


RESOURCE 3: Warehouse
---------------------
Model: Warehouse

Purpose: Represents a physical location where products are stored.

Explanation: Defines all storage locations (buildings, rooms, shelves, bins). Each product quantity is tracked per warehouse. Enables multi-location inventory management and stock transfers between locations.

Migration Code:
Schema::create('warehouses', function (Blueprint $table) {
    $table->id(); // explanation: unique identifier for each warehouse
    $table->string('name', 255); // explanation: location name (e.g., "Main Warehouse", "Store Front")
    $table->string('code', 50)->unique(); // explanation: short unique code (e.g., "WH01") for quick reference
    $table->text('address')->nullable(); // explanation: physical address of warehouse
    $table->string('manager_name', 255)->nullable(); // explanation: person responsible for this warehouse
    $table->boolean('is_active')->default(true); // explanation: whether warehouse is currently in use
    $table->timestamps(); // explanation: created_at and updated_at timestamps
});


RESOURCE 4: Product
-------------------
Model: Product

Purpose: Represents an item you track, store, buy, or sell. Core of the inventory system.

Explanation: Stores product details including SKU (unique internal code), name, description, and unit price. Links to a supplier. Quantity is NOT stored here anymore — it's tracked per warehouse in ProductStock model.

Migration Code:
Schema::create('products', function (Blueprint $table) {
    $table->id(); // explanation: unique identifier for each product
    $table->string('sku', 100)->unique(); // explanation: Stock Keeping Unit, unique internal code for product identification
    $table->string('name', 255); // explanation: product name for display and searching
    $table->text('description')->nullable(); // explanation: optional product details or specifications
    $table->decimal('unit_price', 10, 2)->default(0); // explanation: current selling price or cost price per unit
    $table->foreignId('supplier_id')->constrained()->onDelete('set null'); // explanation: links to supplier who provides this product
    $table->timestamps(); // explanation: created_at and updated_at timestamps
});


RESOURCE 5: ProductStock
------------------------
Model: ProductStock

Purpose: Tracks quantity of a specific product in a specific warehouse.

Explanation: This replaces the current_quantity field in Product model. Each record represents stock for one product at one warehouse. Total stock across all warehouses is the sum of all ProductStock records for that product. Used for location-specific inventory counts and low stock alerts.

Migration Code:
Schema::create('product_stock', function (Blueprint $table) {
    $table->id(); // explanation: unique identifier for each stock record
    $table->foreignId('product_id')->constrained()->onDelete('cascade'); // explanation: which product this stock is for
    $table->foreignId('warehouse_id')->constrained()->onDelete('cascade'); // explanation: which warehouse this stock is located in
    $table->integer('quantity')->default(0); // explanation: current quantity of this product at this warehouse
    $table->integer('reorder_level')->default(0); // explanation: minimum quantity before low stock alert (per location)
    $table->timestamps(); // explanation: created_at and updated_at timestamps
    $table->unique(['product_id', 'warehouse_id']); // explanation: prevents duplicate records for same product and warehouse
});


RESOURCE 6: StockMovement
-------------------------
Model: StockMovement

Purpose: Records every change in product quantity (audit log).

Explanation: Logs every stock increase (type='in' from purchase orders) and decrease (type='out' from sales) and transfers (type='transfer'). Stores historical unit price at time of movement. Links to reference (purchase order ID or sale ID) for traceability. Records which user performed the action.

Migration Code:
Schema::create('stock_movements', function (Blueprint $table) {
    $table->id(); // explanation: unique identifier for each movement record
    $table->foreignId('product_id')->constrained()->onDelete('cascade'); // explanation: which product was moved
    $table->foreignId('warehouse_id')->constrained()->onDelete('restrict'); // explanation: which warehouse this movement occurred in
    $table->enum('type', ['in', 'out', 'transfer']); // explanation: 'in'=added stock, 'out'=removed stock, 'transfer'=moved between warehouses
    $table->integer('quantity'); // explanation: how many units were moved
    $table->decimal('unit_price_at_time', 10, 2); // explanation: price per unit when movement happened (historical value)
    $table->bigInteger('reference_id'); // explanation: ID of related record (purchase order, sale, or transfer)
    $table->string('reference_type', 50); // explanation: type of reference ('purchase_order', 'sale', 'stock_transfer')
    $table->foreignId('created_by')->constrained('users')->onDelete('restrict'); // explanation: which user performed this movement
    $table->timestamp('created_at'); // explanation: when the movement occurred (not auto-managed for historical accuracy)
});


RESOURCE 7: PurchaseOrder
-------------------------
Model: PurchaseOrder

Purpose: Represents an order placed with a supplier to restock products.

Explanation: Created before goods arrive. Tracks PO number (unique reference), supplier, order date, expected delivery, and status (pending, received, cancelled). When marked as "received", it triggers stock movements and increases ProductStock quantities.

Migration Code:
Schema::create('purchase_orders', function (Blueprint $table) {
    $table->id(); // explanation: unique identifier for each purchase order
    $table->string('po_number', 100)->unique(); // explanation: unique purchase order reference number for tracking
    $table->foreignId('supplier_id')->constrained()->onDelete('restrict'); // explanation: which supplier the order is placed with
    $table->date('order_date'); // explanation: date when the order was placed
    $table->date('expected_delivery')->nullable(); // explanation: expected date when goods will arrive
    $table->enum('status', ['pending', 'received', 'cancelled'])->default('pending'); // explanation: current state of the order
    $table->decimal('total_amount', 12, 2)->default(0); // explanation: sum of all line items (quantity × unit price)
    $table->timestamps(); // explanation: created_at and updated_at timestamps
});


RESOURCE 8: PurchaseOrderItem
-----------------------------
Model: PurchaseOrderItem

Purpose: Represents individual line items within a purchase order.

Explanation: Breaks down what products were ordered, in what quantity, at what price, and which warehouse they should go to. One purchase order can have multiple items. Used when receiving to know which warehouse to add stock to.

Migration Code:
Schema::create('purchase_order_items', function (Blueprint $table) {
    $table->id(); // explanation: unique identifier for each line item
    $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade'); // explanation: which purchase order this item belongs to
    $table->foreignId('product_id')->constrained()->onDelete('restrict'); // explanation: which product was ordered
    $table->foreignId('warehouse_id')->constrained()->onDelete('restrict'); // explanation: which warehouse to store this product when received
    $table->integer('quantity'); // explanation: how many units were ordered
    $table->decimal('unit_price', 10, 2); // explanation: price per unit at order time (may differ from current product price)
    $table->decimal('total', 12, 2); // explanation: quantity × unit_price, pre-calculated for efficiency
});


RESOURCE 9: Sale
----------------
Model: Sale

Purpose: Represents a transaction where products leave inventory to a customer.

Explanation: Records customer purchases. Tracks sale number (unique reference), customer name (optional), sale date, total amount, and which user processed the sale. When created, it triggers stock movements and decreases ProductStock quantities.

Migration Code:
Schema::create('sales', function (Blueprint $table) {
    $table->id(); // explanation: unique identifier for each sale
    $table->string('sale_number', 100)->unique(); // explanation: unique receipt or invoice reference number
    $table->string('customer_name', 255)->nullable(); // explanation: optional customer name for tracking
    $table->date('sale_date'); // explanation: date when the sale occurred
    $table->decimal('total_amount', 12, 2)->default(0); // explanation: sum of all sale items (quantity × unit price)
    $table->foreignId('created_by')->constrained('users')->onDelete('restrict'); // explanation: which user processed this sale
    $table->timestamps(); // explanation: created_at and updated_at timestamps
});


RESOURCE 10: SaleItem
---------------------
Model: SaleItem

Purpose: Represents individual line items within a sale.

Explanation: Breaks down what products were sold, in what quantity, and at what selling price. One sale can have multiple products. Used when processing sale to know which products to deduct from stock.

Migration Code:
Schema::create('sale_items', function (Blueprint $table) {
    $table->id(); // explanation: unique identifier for each line item
    $table->foreignId('sale_id')->constrained()->onDelete('cascade'); // explanation: which sale this item belongs to
    $table->foreignId('product_id')->constrained()->onDelete('restrict'); // explanation: which product was sold
    $table->integer('quantity'); // explanation: how many units were sold
    $table->decimal('unit_price', 10, 2); // explanation: selling price per unit at time of sale
    $table->decimal('total', 12, 2); // explanation: quantity × unit_price, pre-calculated for efficiency
});


RESOURCE 11: StockTransfer
--------------------------
Model: StockTransfer

Purpose: Represents moving stock from one warehouse to another.

Explanation: Records transfer requests between locations. Tracks transfer number, source warehouse, destination warehouse, product, quantity, and status (pending, completed, cancelled). When completed, it decreases stock at source warehouse, increases stock at destination warehouse, and logs two stock movements.

Migration Code:
Schema::create('stock_transfers', function (Blueprint $table) {
    $table->id(); // explanation: unique identifier for each transfer
    $table->string('transfer_number', 100)->unique(); // explanation: unique reference number for tracking the transfer
    $table->foreignId('product_id')->constrained()->onDelete('restrict'); // explanation: which product is being transferred
    $table->foreignId('from_warehouse_id')->constrained('warehouses')->onDelete('restrict'); // explanation: source warehouse (where stock is taken from)
    $table->foreignId('to_warehouse_id')->constrained('warehouses')->onDelete('restrict'); // explanation: destination warehouse (where stock is sent to)
    $table->integer('quantity'); // explanation: how many units are being transferred
    $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending'); // explanation: current state of the transfer
    $table->timestamp('transferred_at')->nullable(); // explanation: when the transfer was actually completed
    $table->foreignId('created_by')->constrained('users')->onDelete('restrict'); // explanation: which user initiated the transfer
    $table->timestamps(); // explanation: created_at and updated_at timestamps
});


COMPLETE TASK ORDER SUMMARY
===========================

Order | Resource          | Depends On
------|-------------------|--------------------------------
1     | User              | nothing
2     | Supplier          | nothing
3     | Warehouse         | nothing
4     | Product           | Supplier
5     | ProductStock      | Product, Warehouse
6     | StockMovement     | Product, Warehouse, User
7     | PurchaseOrder     | Supplier
8     | PurchaseOrderItem | PurchaseOrder, Product, Warehouse
9     | Sale              | User
10    | SaleItem          | Sale, Product
11    | StockTransfer     | Product, Warehouse, User
