# System Changes Summary

## Registration & Account Management
- Added role-based registration (Customer, Admin, Seller/Staff) in login page
- Admin approval required for Admin and Seller/Staff accounts before login
- Automatic redirect to appropriate dashboard after registration/approval
- Seller and Staff are treated as the same persona

## User & Customer Management
- **Customers Page**: Shows only customers with transaction history (orders)
- **Users Page**: New page showing all registered users with customer role, includes filtering options
- Account deactivation functionality:
  - Admin can activate/deactivate any user account
  - Account owners can deactivate their own accounts (requires password confirmation)

## Checkout & Shipping
- Shipping information automatically linked to current user's account
- Customer record created and linked to user if not exists during checkout
- Form pre-filled with existing customer information when available

## Cancel Order & Payment Method
- **Cancel Order**: Customers can cancel their own orders while status is "Pending" (not yet shipped/approved). Cancel button appears on My Orders list and Order Detail page. Cancelled orders display with red badge and cannot be approved by sellers.
- **Payment Method**: Customers select payment method (Cash on Delivery, GCash, Bank Transfer) during checkout. Displayed on order success page, customer order detail, seller view, and admin panels. Added `PaymentMethod` column to `order` table.
- Seller and admin approve flows now reject cancelled orders with an error message.

## Supplier Persona
- Added a new `supplier` user role/persona with a dedicated dashboard and product management interface
- Suppliers can manage only their own products (plants linked to their supplier company)
- **New files** (`supplier_panel/`): `dashboard.php`, `products.php`, `create.php`, `edit.php`, `delete.php`, `restock.php`, `includes/header.php`, `includes/footer.php`
- **Database**: Added `SupplierID int NULL` column to `user` table (FK to `supplier.SupplierID`)
- **Registration**: Supplier role in dropdown → creates `supplier` record → links `user.SupplierID` → `IsActive=0`
- **Login**: Supplier redirected to `supplier_panel/dashboard.php`
- **Approval**: Admin approve/reject with supplier record cleanup on reject; purple role badge for supplier
- **Product management**: Filtered by `plant.SupplierID` matching the supplier's linked company
- **Restock**: Dedicated page where supplier adds quantity to existing stock

## Email Verification
- Added email verification for all personas upon registration
- After registration, a verification email is sent via Resend API with a unique link
- Users must verify their email before admin can approve the account
- Admin approval page shows email verification status (Verified/Pending)
- Approve button is disabled for unverified accounts with a helpful message
- All new accounts (including customers) now require admin approval after email verification
- Login gate checks email verification status before account approval status
- Existing users are automatically marked as email verified (migration friendly)
- New columns added to `user` table: `EmailVerified` (TINYINT), `VerificationToken` (VARCHAR 64)
- Admin-created accounts (via create pages) are auto-verified
