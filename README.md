# NSG Procurement Management System

A modular Core PHP + MySQL procurement application for Noor Staffing Group's global offices. Procurement is independent of the NSG Asset Management System; future integration is intended through versioned REST APIs and external reference IDs.

## Requirements
- PHP 8.2+; MySQL 8.0+; Apache with mod_rewrite
- PHP extensions: PDO MySQL, mbstring, intl, zip, gd, xml
- Composer 2

## Install (XAMPP)
1. Extract to `C:\xampp\htdocs\nsg_procurement`.
2. Create database `nsg_procurement` using utf8mb4.
3. Copy `.env.example` to `.env`; set DB credentials, APP_URL and a random APP_KEY.
4. Run `composer install`.
5. Run `php scripts/migrate.php`.
6. Create administrator: `php scripts/create_admin.php admin@noorstaffing.com "NSG Administrator" "Use-A-Unique-Password-Here"` (12+ chars).
7. Configure Apache DocumentRoot to this project's `public` directory (recommended). For a subdirectory XAMPP install, open `/nsg_procurement/public/` and set APP_URL accordingly.

## Security / production
- Never expose `app`, `config`, `database`, `scripts`, `storage`, `.env`, or `vendor` via the public web root. Only `public/` should be web-accessible.
- Set APP_DEBUG=false; use HTTPS; rotate APP_KEY; use a least-privilege MySQL account; keep backups off-host.
- Configure SMTP and schedule `php scripts/send_mail_queue.php` every few minutes.
- Uploaded files must be stored outside public/ and served through an authorization-checked download controller. Do not place confidential vendor quotations/invoices in a public directory.
- Configure branch and user scopes before production use. Review all permissions and approval workflows with Finance/IT.

## Modules
Dashboard, branches/departments, catalog, vendors, purchase requests, requisitions, quotations, purchase orders, approvals, deliveries, invoices, payments, budgets, imports, exports and reports.

## Bulk import
Vendor CSV/XLS/XLSX imports are supported. The current import page supports vendor files and reports imported versus failed row counts. A downloadable template is provided. Detailed per-row error downloads, pre-import preview, and bulk imports for other entities are follow-on enhancements.

## Reports
CSV exports are built in. PDF export uses Dompdf. Excel import uses PhpSpreadsheet. Excel export is available for the supported report datasets. Filtered/saved report builder is a future enhancement.

## Tests
Run `php scripts/smoke_test.php` for syntax/config checks. A live MySQL 8 instance is required to validate migrations, foreign keys, approval transactions and data integrity.
