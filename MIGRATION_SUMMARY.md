# Migration Summary

## Scope
- Converted legacy PHP pages into Laravel controllers and Blade views.
- Preserved existing MySQL tables and data; added a safe migration for missing product fields only.
- Maintained legacy routes with .php paths to keep existing URLs functional.

## What Was Converted
- Public pages: landing, product catalog, cart, OTP verify.
- Auth endpoints: login, admin login, register, OTP verify/resend, logout.
- Staff pages: pending orders, product management.
- Admin pages: dashboard, audit log, purchase records, user management.
- Staff/admin APIs: products API and orders API.

## Database
- No destructive migrations. Existing tables remain untouched.
- Adds columns to `products` if missing: `display_order`, `discount`, `stock`.

## Environment
- Laravel configured for MySQL (`scpmm`) and file-based sessions/cache/queues.

## Notes
- Asset URLs still point to `/SCP/assets` to reuse existing static files.
