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
