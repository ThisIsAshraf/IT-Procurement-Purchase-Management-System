# NSG Procurement ERP — User & Administrator Guide

**Organization:** Noor Staffing Group  
**Application URL (Local XAMPP):** `http://localhost/nsg_procurement/public/`

---

## Table of Contents
1. [System Overview & End-to-End Lifecycle](#1-system-overview--end-to-end-lifecycle)
2. [Roles & Access Levels](#2-roles--access-levels)
3. [Part I: End-User Guide (Daily Operations)](#3-part-i-end-user-guide-daily-operations)
   - [3.1 Signing In & Navigating the Workspace](#31-signing-in--navigating-the-workspace)
   - [3.2 Submitting a Purchase Request (Employees & Requesters)](#32-submitting-a-purchase-request-employees--requesters)
   - [3.3 Reviewing & Converting Requests to Requisitions](#33-reviewing--converting-requests-to-requisitions)
   - [3.4 Managing Vendors & Performance Scorecards](#34-managing-vendors--performance-scorecards)
   - [3.5 Collecting Quotations & Side-by-Side Vendor Comparison](#35-collecting-quotations--side-by-side-vendor-comparison)
   - [3.6 Generating & Dispatching Purchase Orders](#36-generating--dispatching-purchase-orders)
   - [3.7 Acting on Approvals & Setting Out-of-Office Delegations](#37-acting-on-approvals--setting-out-of-office-delegations)
   - [3.8 Recording Goods Deliveries (Partial & Full Receipts)](#38-recording-goods-deliveries-partial--full-receipts)
   - [3.9 Recording Vendor Invoices & 3-Way Matching](#39-recording-vendor-invoices--3-way-matching)
   - [3.10 Recording Vendor Payments & Multi-Invoice Allocations](#310-recording-vendor-payments--multi-invoice-allocations)
   - [3.11 Managing Department Budgets & Recurring Renewals](#311-managing-department-budgets--recurring-renewals)
   - [3.12 Running & Exporting Reports](#312-running--exporting-reports)
4. [Part II: Administrator Guide](#4-part-ii-administrator-guide)
   - [4.1 Initial Setup, Database Migrations & Admin CLI](#41-initial-setup-database-migrations--admin-cli)
   - [4.2 Organization Structure (Countries, Branches, Departments, Employees)](#42-organization-structure-countries-branches-departments-employees)
   - [4.3 User Accounts, RBAC Permissions & Branch Scoping](#43-user-accounts-rbac-permissions--branch-scoping)
   - [4.4 Master Catalog, Units of Measure, Payment Terms & FX Rates](#44-master-catalog-units-of-measure-payment-terms--fx-rates)
   - [4.5 Configuring Multi-Step Approval Workflows](#45-configuring-multi-step-approval-workflows)
   - [4.6 Bulk Data Imports (CSV / Excel) & Error Logs](#46-bulk-data-imports-csv--excel--error-logs)
   - [4.7 System Settings, Email Templates & SMTP Queue](#47-system-settings-email-templates--smtp-queue)
   - [4.8 Monitoring the Audit Trail](#48-monitoring-the-audit-trail)
   - [4.9 Backup, Security & Troubleshooting](#49-backup-security--troubleshooting)

---

## 1. System Overview & End-to-End Lifecycle

NSG Procurement manages the complete **Procure-to-Pay (P2P)** lifecycle across multiple branches, departments, and currencies:

```text
1. Purchase Request (PR)      -> Employee submits operational/IT need with line items
2. Requisition (REQ)          -> Approved PR converts into a formal sourcing requisition
3. Quotations & Comparison    -> Multiple vendor quotes recorded, scored, and awarded
4. Purchase Order (PO)        -> Generated from winning quote; routed through approval rules
5. Goods Delivery (DEL)       -> Warehouse/IT logs partial or full delivery quantities
6. Vendor Invoice (INV)       -> Automated 3-Way Match checks PO vs. Delivery vs. Invoice
7. Vendor Payment (PAY)       -> Payment recorded and allocated across open invoices; PO closes
```

---

## 2. Roles & Access Levels

The vertical left sidebar automatically displays only the modules permitted by your assigned role(s):

| Role | Primary Responsibilities |
| :--- | :--- |
| **Employee / Requester** | Create and track personal Purchase Requests; view request status. |
| **Department / IT Manager** | Approve/reject department Purchase Requests; act on assigned PO approval steps; set out-of-office delegations. |
| **Procurement Officer** | Onboard vendors, convert approved PRs to Requisitions, collect vendor quotes, run comparison matrices, generate POs, and dispatch POs to vendors. |
| **Receiving / Operations** | Record partial or complete goods deliveries against open Purchase Orders. |
| **Finance Officer** | Manage fiscal budgets and recurring renewals, enter vendor invoices, resolve 3-way match variances, record payments, and allocate funds across invoices. |
| **Super Admin** | Full system configuration: branches, departments, employees, users, RBAC permission matrices, multi-step approval workflows, FX rates, bulk imports, email queue, and audit logs. |

---

## 3. Part I: End-User Guide (Daily Operations)

### 3.1 Signing In & Navigating the Workspace
1. Open `http://localhost/nsg_procurement/public/login`.
2. Enter your corporate email address and password.
3. Upon login, the **Procurement Control Center** (`/`) displays real-time counts for Purchase Requests, Requisitions, Vendors, Purchase Orders, Deliveries, Invoices, Payments, Pending Approvals, and renewals due within 45 days.
4. Use the **left vertical sidebar** to navigate between modules. Your active session and **Sign out** button are pinned at the bottom of the sidebar.

---

### 3.2 Submitting a Purchase Request (Employees & Requesters)
1. Click **Purchase Requests** in the left sidebar.
2. In **New Multi-Item Purchase Request**:
   - Select your **Branch**, **Department**, and **Currency**.
   - Optionally enter a **Required By Date** and provide a clear **Business Justification**.
   - Under **Requested Line Items**, pick an existing **Catalog Item** (or leave as *Custom / Non-catalog*), enter the **Description**, **Quantity**, **Unit**, and optional **Estimated Unit Price**.
3. Click **Submit Request** to send it for review immediately, or **Save as Draft** to edit it later.
4. Open any request's **Details** page (`/purchase_requests/{id}`) to view the live **FY Department Budget Check** (showing available vs. allocated funds), add additional items while open, or upload supporting specification files.

---

### 3.3 Reviewing & Converting Requests to Requisitions
1. **Managers** open **Purchase Requests**, click **Details** on a `submitted` request, verify the department budget snapshot, and click **Approve Request**, **Mark Under Review**, or **Reject**.
2. Once a Purchase Request is **Approved**, a Procurement Officer opens the request and clicks **Convert to Formal Requisition**.
   - The system automatically assigns a sequential `REQ-YYYY-XXXX` number, copies all line items and quantities, links the source PR, and updates the PR status to `converted`.
3. Procurement/IT staff can also create direct sourcing requisitions without a prior employee request using **Create Direct IT / Procurement Requisition** on `/requisitions`.

---

### 3.4 Managing Vendors & Performance Scorecards
1. Go to **Vendors** (`/vendors`) to search existing suppliers by code, legal name, or tax ID, or filter by status (`pending`, `approved`, `blocked`, `inactive`).
2. To register a supplier, complete **Register New Vendor** (including an optional Primary Contact) and click **Save Vendor**.
3. Click **Manage** on any vendor to open their full profile (`/vendors/{id}`):
   - **Company Profile**: Update legal details, tax ID, website, or change approval status to `approved`.
   - **Compliance & Vendor Documents**: Upload W-9/Tax Certificates, Business Registrations, NDAs/MSAs, or Wire Details (PDF, Office docs, images, CSV up to 15 MB) and download them securely anytime.
   - **Contacts & Addresses**: Maintain multiple billing, shipping, and office addresses and contacts.
   - **Performance Scorecard & Reviews**: Score vendors from `1` to `5` on **Delivery**, **Quality**, **Pricing**, and **Communication** (optionally tied to a specific PO).

---

### 3.5 Collecting Quotations & Side-by-Side Vendor Comparison
1. Navigate to **Quotations** (`/quotations`) and select an open **Requisition** from the dropdown.
2. In **Record Vendor Quotation**:
   - Choose the **Vendor**, enter the vendor's **Quote / Reference #**, **Quote Date**, **Valid Until** date, **Lead Time (Delivery Days)**, **Payment Terms**, and any header tax, shipping, or discount amounts.
   - Attach the vendor's official quote PDF/spreadsheet if available.
   - Enter the **Quoted Unit Price**, **Tax Rate %**, and **Warranty (Months)** for each requisition line item, then click **Save Vendor Quotation**.
3. Repeat Step 2 for competing vendors (typically 2–3 quotes).
4. Click **Side-by-Side Vendor Comparison & Scoring** (`/quotations/compare/{requisitionId}`):
   - Compare unit prices, line totals, grand totals (the lowest-priced bid is highlighted automatically), delivery lead times, and payment terms side by side.
   - Enter an **Evaluation Score (0–100)** and **Evaluator Notes** per vendor, select the radio button under **Recommended / Winning Vendor**, enter justification notes, and click **Finalize Award & Approve for PO**.
   - The winning quote is marked `selected`, competing quotes are marked `rejected`, and the Requisition advances to `approved`.

---

### 3.6 Generating & Dispatching Purchase Orders
1. Go to **Purchase Orders** (`/purchase_orders`).
2. In **Generate Purchase Order from Requisition / Awarded Quote**:
   - Select the **Requisition** and its **Selected Quotation** (or choose *Use requisition target prices* for a direct PO and pick a vendor).
   - Confirm the **Ship-To Branch**, **Order Date**, **Expected Delivery Date**, and **PO Terms & Delivery Instructions**.
   - Click **Generate Draft Purchase Order**. The system locks in the latest historical exchange rate to `USD` (`base_total_amount`) so historical reports never shift when FX rates change later.
3. On the PO detail page (`/purchase_orders/{id}`):
   - Click **Submit PO for Approval**. If a matching approval workflow exists for that branch, department, or amount threshold, the PO moves to `pending_approval` and enters the Approvals Inbox; otherwise it auto-approves.
   - Once **Approved**, click **Dispatch PO to Vendor (Email)** to mark the PO `sent` and queue an email notification to the vendor's primary contact.
   - Click **Download PO PDF** anytime to export a formatted printable Purchase Order.

---

### 3.7 Acting on Approvals & Setting Out-of-Office Delegations
1. Open **Approvals Inbox** (`/approvals`).
2. Under **Approval Queue & Decision History**, any pending step assigned to your role or user ID (or delegated to you) displays an action bar:
   - Choose **Approve**, **Reject**, or **Return**, add optional comments, and click **Submit**.
   - Multi-step workflows automatically advance to the next step; final approval updates the underlying PO/document status automatically.
3. **Out-of-Office Approval Delegation**:
   - Before going on leave, select a **Delegate To Colleague**, set **Starts At** and **Ends At** timestamps, add a reason, and click **Save Delegation**. During that window, your colleague can act on your pending approvals on your behalf.

---

### 3.8 Recording Goods Deliveries (Partial & Full Receipts)
1. Go to **Deliveries** (`/deliveries`) and choose an open Purchase Order (`approved`, `sent`, or `partially_received`).
2. Enter the **Vendor Packing Slip / Waybill Ref**, **Received Date & Time**, and **Receiving Notes**.
3. For each line item, verify **Ordered**, **Previously Received**, and **Open Balance**, then enter **Qty Accepted Now** and **Qty Rejected / Damaged** (the system blocks receiving more than the remaining open balance).
4. Click **Confirm Goods Receipt**:
   - If some items remain unfulfilled, the PO status updates to `partially_received`.
   - Once 100% of ordered quantities are accepted, the PO status automatically updates to `received`.

---

### 3.9 Recording Vendor Invoices & 3-Way Matching
1. Go to **Invoices & 3-Way Match** (`/invoices`) and select the **Purchase Order**.
2. Enter the **Vendor Invoice #**, **Invoice Date**, **Payment Due Date**, **Total Tax Amount**, and attach the vendor's PDF invoice.
3. Confirm the **Invoiced Qty** (pre-filled with received quantities) and **Invoiced Unit Price** per line item, then click **Save Invoice & Run 3-Way Match**.
4. On the Invoice detail screen (`/invoices/{id}`), inspect the **Three-Way Match Line Verification** table:
   - The system automatically compares **PO Qty vs. Received Qty vs. Invoiced Qty** and **PO Unit Price vs. Invoiced Unit Price**.
   - If all lines align, the verdict displays **PASSED**; if quantities exceed goods received or unit prices differ by more than `$0.01`, exact line-level discrepancies are flagged.
5. Finance Officers click **Approve Invoice for Payment** (or **Flag as Disputed** / **Void Invoice**).

---

### 3.10 Recording Vendor Payments & Multi-Invoice Allocations
1. Go to **Payments** (`/payments`) and select the **Vendor** from the filter dropdown to load all open unpaid invoices for that supplier.
2. Enter **Payment Date**, **Currency**, **Total Payment Amount**, **Payment Method** (`Bank Transfer / Wire`, `Corporate Card`, `Cheque`, `Online Portal`, `Cash`), and **Bank / Wire Transaction Ref**.
3. In **Allocate Payment to Vendor Invoices**, enter the exact amount to apply against one or multiple open invoices (cannot exceed the payment total or an invoice's remaining balance).
4. Click **Record & Allocate Payment**:
   - Each invoice automatically updates to `partially_paid` or `paid`.
   - When a Purchase Order is fully `received` and all its invoices are `paid`, the PO automatically transitions to `closed`.

---

### 3.11 Managing Department Budgets & Recurring Renewals
1. Open **Budgets & Renewals** (`/budgets`) to view fiscal-year budgets, committed PO spend, remaining balances, and utilization percentages.
2. **Create Department Budget**: Select **Branch**, **Department**, **Fiscal Year**, **Currency**, **Budget Name**, and allocate amounts across item categories.
3. **Schedule Recurring Software / Service Renewal**: Track monthly, quarterly, semi-annual, or annual subscriptions with a **Next Renewal Date**.
4. Click **Generate Renewal Requisition** on any schedule row to immediately create a pre-filled Requisition and advance the schedule's next renewal date by one billing cycle.

---

### 3.12 Running & Exporting Reports
1. Go to **Reports & Exports** (`/reports`).
2. Select a **Report Dataset**:
   - *Spend Summary (Branch / Dept / Currency)*
   - *Purchase Order Register*
   - *Vendor Invoice & Balance Aging*
   - *Budget vs. Committed Spend*
   - *Vendor Spend & Performance Scorecard*
3. Filter by **Branch**, **Fiscal Year**, or **Date Range** and click **Run Report**.
4. Export the filtered dataset to **CSV**, **Excel (.xlsx)**, or **PDF**, or save the filter configuration under **Save Current View As** (personal or shared with all report viewers).

---

## 4. Part II: Administrator Guide

### 4.1 Initial Setup, Database Migrations & Admin CLI
1. **Environment File (`.env`)**: Configure database credentials, `APP_URL`, `APP_DEBUG`, and SMTP mail parameters:
   ```env
   APP_NAME="NSG Procurement"
   APP_ENV=local
   APP_DEBUG=false
   APP_URL=http://localhost/nsg_procurement/public
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=nsg_procurement
   DB_USER=root
   DB_PASS=
   ```
2. **Run Database Migrations**:
   ```bash
   cd /c/xampp/htdocs/nsg_procurement
   php scripts/migrate.php
   ```
   *(To wipe and rebuild a local development database from scratch, run `php scripts/migrate.php --fresh`.)*
3. **Create a Super Admin Account**:
   ```bash
   php scripts/create_admin.php admin@noorstaffing.com "NSG Administrator" 'YourStrongPassword12+'
   ```
   *(Note: Passwords must be at least 12 characters. In Git Bash, wrap passwords containing `$` in single quotes `'...'`.)*

---

### 4.2 Organization Structure (Countries, Branches, Departments, Employees)
1. **Countries & Branches** (`/organization`):
   - Add operating countries (ISO2/ISO3 codes) and create company branches (e.g., `NYC-HQ`, `DHK-OPS`) with default local currencies and timezones.
2. **Departments** (`/departments`):
   - Maintain standardized department codes (`IT`, `HR`, `FIN`, `OPS`, `EXEC`) used across requests, budgets, and approval rules.
3. **Employees** (`/employees`):
   - Maintain the employee directory with branch, department, job title, and reporting manager hierarchy (`manager_employee_id`). Employee records can be linked 1-to-1 to login accounts.

---

### 4.3 User Accounts, RBAC Permissions & Branch Scoping
1. **Users** (`/users`):
   - Create user logins, link them to an Employee record, set status (`active`, `inactive`, `locked`), assign one or more **Roles**, and configure **Branch Scopes**.
   - **Branch Scoping Behavior**:
     - Users with `super_admin` or empty branch restrictions access all branches.
     - Users assigned specific branches (e.g., only Dhaka Branch) are strictly restricted across Purchase Requests, Requisitions, Quotations, POs, Deliveries, Invoices, Budgets, and Reports to those branches.
2. **Roles & RBAC** (`/roles`):
   - View or create custom roles and toggle granular module permissions (`*.view`, `*.create`, `*.edit`, `*.approve`, `*.manage`, `reports.export`, `audit.view`, `settings.manage`).

---

### 4.4 Master Catalog, Units of Measure, Payment Terms & FX Rates
Navigate to **Catalog & FX Rates** (`/catalog`) to maintain:
- **Item Categories & Subcategories** (e.g., `IT-HW`, `IT-SW`, `OFF-SUP`)
- **Units of Measure** (`EA`, `BOX`, `LIC`, `HR`, `MONTH`, `YEAR`)
- **Standard Catalog Items** with SKUs for consistent pricing and spend analysis
- **Payment Terms** (`Net 15`, `Net 30`, `Net 60`, `Due on Receipt`)
- **Historical Exchange Rates**: Record dated conversion rates (e.g., `BDT -> USD`, `EUR -> USD`, `GBP -> USD`). Each Purchase Order captures and freezes the effective rate on its order date without altering historical transactions.

---

### 4.5 Configuring Multi-Step Approval Workflows
1. Go to **Approval Workflows** (`/approval-workflows`).
2. Create an **Approval Rule** specifying:
   - **Document Type** (`purchase_order`, `purchase_request`, `requisition`, `quotation_comparison`, `invoice`, or `payment`)
   - Optional **Branch**, **Department**, **Min/Max Amount**, and **Currency** thresholds (most specific matching rule wins)
   - **Step 1 Name** and **Approver Role** (or specific User)
3. Use **Add Step** on any workflow card to chain sequential approval stages (e.g., Step 1: *IT Manager* -> Step 2: *Finance Officer* -> Step 3: *Executive Director*).

---

### 4.6 Bulk Data Imports (CSV / Excel) & Error Logs
1. Open **Bulk Imports** (`/imports`).
2. Download the sample CSV template for the target entity:
   - **Vendors** (`vendor_code, legal_name, tax_registration_no, website, status, notes`) — upserts by `vendor_code`.
   - **Catalog Items** (`category_code, unit_code, sku, item_name, description`) — upserts by `sku`.
   - **Budgets** (`branch_code, department_code, fiscal_year, currency_code, budget_name, category_code, allocated_amount`).
3. Upload a `.csv`, `.xlsx`, or `.xls` file and click **Run Validated Import**.
4. Valid rows are committed immediately; any invalid rows are logged in **Import Batch History** where you can click **Download Errors CSV** to inspect exact row numbers and validation messages.

---

### 4.7 System Settings, Email Templates & SMTP Queue
1. Go to **Settings & Email Queue** (`/settings`).
2. **System Configuration**: Store key-value operational parameters in `settings`.
3. **Email Notification Templates**: Customize `notification_templates` (subject and HTML body templates).
4. **SMTP Notification Queue**: Outbound PO dispatch emails are queued in `notification_queue`. Click **Send Queued Emails Now** to trigger immediate SMTP delivery via PHPMailer (or schedule `MailService::sendQueued()` via cron/Task Scheduler).

---

### 4.8 Monitoring the Audit Trail
- Open **Audit Logs** (`/audit-logs`) to inspect an immutable chronological log of all logins, role/permission updates, vendor changes, approvals, PO creations, goods receipts, 3-way match approvals, payments, bulk imports, and settings changes—complete with user attribution, entity IDs, and client IP addresses.

---

### 4.9 Backup, Security & Troubleshooting
- **Security Controls Built In**:
  - All `POST` routes enforce CSRF token verification (`csrf_field()`).
  - All SQL queries use prepared PDO statements.
  - Uploaded vendor/quote/invoice attachments are stored outside `public/` inside `storage/uploads/` with MIME validation and SHA-256 checksums, served exclusively through authenticated download routes.
- **Database Backups**: Schedule regular `mysqldump` exports of `nsg_procurement` along with backups of the `storage/uploads/` directory.
- **Production Hardening**: Before deploying to a live server, set `APP_DEBUG=false` in `.env`, enforce HTTPS (`APP_URL=https://...`), and point your web server document root directly to `/public`.