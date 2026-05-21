# Plant Nursery Management System

## Setup

- **XAMPP PHP app** — no build tools, package managers, tests, linters, or CI.
- Place under XAMPP `htdocs`, access via `http://localhost/plant_nursery/`.
- **Database:** MySQL `plant_nursery` on `localhost` / `root` / empty password (`config/database.php:6`).
- Import `plant_nursery.sql` (MariaDB 10.4, utf8mb4) via phpMyAdmin or CLI.
- **Default accounts** (from SQL dump):
  - Admin: `admin` / `admin123`
  - Seller: `seller` / `seller123`
  - Customer: `customer` / `customer123`

## Architecture

- **Plain PHP + MySQL** — `mysqli_connect`, no ORM, no PDO, no framework.
- **Passwords stored in plaintext** — `login.php:132` compares directly.
- **Session-based auth** (`$_SESSION['user_id']`, `$_SESSION['role']`). Login gate in `includes/header.php:6`.
- **Roles:** `customer`, `admin`, `seller` (also `employee`/`staff` — all treated same, redirected to `seller/`), `supplier` (redirected to `supplier_panel/dashboard.php`).
- **Registration:** customers auto-login; admin/seller/supplier accounts created with `IsActive=0` (pending approval in `admin/approve_accounts.php`).

## Entrypoints

| Role | Landing |
|---|---|
| Customer | `shop/shop.php` |
| Admin | `index.php` (dashboard) |
| Seller/Staff | `seller/sellerpage.php` |
| Supplier | `supplier_panel/dashboard.php` |

## Directory / CRUD pattern

Each entity module follows: `{entity}/{page,create,edit,delete}.php`.
- `plant/`, `plantcategory/`, `planttype/`, `supplier/`, `customer/`, `employee/`, `order/`, `refund/`, `user/`
- `includes/` — shared `header.php` (sidebar nav + auth gate) and `footer.php`
- `shop/` — customer-facing: `shop.php`, `cart.php`, `checkout.php`, `my_orders.php`, `view_order.php`, `cancel_order.php`, `request_refund.php`, `mark_successful.php`
- `seller/` — seller/staff: `orders.php`, `refunds.php`, `approve_order.php`, `process_refund.php`
- `supplier_panel/` — supplier persona: `dashboard.php`, `products.php`, `create.php`, `edit.php`, `delete.php`, `restock.php` (own `includes/` header/footer)
- `admin/` — currently only `approve_accounts.php`

## Path conventions

`includes/header.php` detects subdirectory depth and prepends `../` to asset/CSS paths automatically. Same in `footer.php` for JS.

## Gotchas

- No `.htaccess` or URL rewriting.
- `picture/` directory holds plant images and login background (`.jpg`, `.webp`).
- No Composer, no npm — pure PHP + Bootstrap 5 (bundled in `assets/dist/`).
- Each persona has its own `includes/header.php` with sidebar; some include Bootstrap CSS, others don't. If form controls don't render correctly, check that Bootstrap CSS is loaded.
- Sidebar username is uppercased with `strtoupper()` in all persona headers.
- `supplier_panel/products.php` has a search input that filters by name/scientific name, works alongside the stock filter.
- `Changes.md` has a log of recent feature changes.
- Supplier users have `SupplierID` FK in `user` table (added via `ALTER TABLE user ADD COLUMN SupplierID int NULL DEFAULT NULL`).
- `order` table has `PaymentMethod` column (`varchar(50)`, added via `ALTER TABLE`). Set during customer checkout (`shop/checkout.php`). Supported values: `Cash on Delivery`, `GCash`, `Bank Transfer`. Displayed in customer order detail, seller view, and admin panels.
- Cancellation: customers cancel via `shop/cancel_order.php`. Only allowed when `Status = 'Pending'` (not yet shipped/approved). Sets `Status = 'Cancelled'`. Seller and admin `approve_order.php` both reject cancelled orders.
