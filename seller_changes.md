# Supplier Persona - System Changes

## Overview
Added a new `supplier` user role/persona with a dedicated dashboard and product management interface. Suppliers can manage only their own products (plants linked to their supplier company).

## Database
- **`user` table**: Added `SupplierID int NULL` column (FK to `supplier.SupplierID`)
  ```sql
  ALTER TABLE user ADD COLUMN SupplierID int NULL DEFAULT NULL AFTER EmployeeID;
  ```

## New Files (`supplier_panel/`)

| File | Description |
|---|---|
| `dashboard.php` | Supplier dashboard with stats: total products, low stock count, total stock quantity, stock value. Shows recent products table. |
| `products.php` | Product listing with low-stock filter. Per-row actions: Edit, Restock, Delete. |
| `create.php` | Add new product form (auto-linked to supplier's company, no supplier field). Image upload supported. |
| `edit.php` | Edit product form with ownership verification. Image replace supported. |
| `delete.php` | Delete product with ownership check. |
| `restock.php` | Dedicated restock page: shows current stock, supplier enters additional quantity, stock increments. |
| `includes/header.php` | Supplier sidebar (Dashboard, My Products), auth gate (supplier role), supplier name display. |
| `includes/footer.php` | Closing tags. |

## Modified Files

### `login.php`
- Added `supplier` to registration role dropdown
- Supplier registration: creates `supplier` record, links `user.SupplierID`, sets `IsActive=0`
- Supplier login redirects to `supplier_panel/dashboard.php`

### `admin/approve_accounts.php`
- Fetches supplier data (name, email, contact) for display
- Handles supplier record cleanup on rejection
- Added purple role badge for `supplier`

### `AGENTS.md`
- Updated roles list, entrypoints table, and directory list to include `supplier_panel/`
- Noted `SupplierID` column in `user` table

## Flow

1. **Registration**: User registers as "Supplier" → creates `supplier` record (Name, Contact, Email, Address) → creates `user` with `SupplierID` linked → `IsActive=0`
2. **Approval**: Admin approves via `admin/approve_accounts.php`
3. **Login**: Supplier logs in → redirected to `supplier_panel/dashboard.php`
4. **Product management**: Supplier sees only plants where `plant.SupplierID` matches their linked supplier company
5. **Restock**: Dedicated page where supplier adds quantity to existing stock (no need to edit the whole product)
