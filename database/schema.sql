-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 21, 2026 at 02:29 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12
SET
    SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET
    time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;

/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;

/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;

/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nsg_procurement`
--
-- --------------------------------------------------------
--
-- Table structure for table `api_keys`
--
CREATE TABLE
    `api_keys` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `client_name` varchar(120) NOT NULL,
        `key_prefix` varchar(16) NOT NULL,
        `key_hash` char(64) NOT NULL,
        `role_id` bigint (20) UNSIGNED NOT NULL,
        `rate_limit_per_min` int (10) UNSIGNED NOT NULL DEFAULT 120,
        `scopes_json` longtext CHARACTER
        SET
            utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid (`scopes_json`)),
            `allowed_ips` varchar(255) DEFAULT NULL,
            `expires_at` date DEFAULT NULL,
            `last_used_at` datetime DEFAULT NULL,
            `request_count` bigint (20) UNSIGNED NOT NULL DEFAULT 0,
            `is_active` tinyint (1) NOT NULL DEFAULT 1,
            `created_by` bigint (20) UNSIGNED NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `approval_actions`
--
CREATE TABLE
    `approval_actions` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `approval_instance_id` bigint (20) UNSIGNED NOT NULL,
        `approval_step_id` bigint (20) UNSIGNED NOT NULL,
        `actor_user_id` bigint (20) UNSIGNED NOT NULL,
        `decision` enum ('approved', 'rejected', 'returned', 'delegated') NOT NULL,
        `comments` text DEFAULT NULL,
        `acted_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `approval_delegations`
--
CREATE TABLE
    `approval_delegations` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `delegator_user_id` bigint (20) UNSIGNED NOT NULL,
        `delegate_user_id` bigint (20) UNSIGNED NOT NULL,
        `starts_at` datetime NOT NULL,
        `ends_at` datetime NOT NULL,
        `reason` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    );

-- --------------------------------------------------------
--
-- Table structure for table `approval_instances`
--
CREATE TABLE
    `approval_instances` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `workflow_id` bigint (20) UNSIGNED NOT NULL,
        `document_type` varchar(40) NOT NULL,
        `document_id` bigint (20) UNSIGNED NOT NULL,
        `current_step_order` smallint (5) UNSIGNED NOT NULL DEFAULT 1,
        `status` enum ('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
        `initiated_by` bigint (20) UNSIGNED NOT NULL,
        `initiated_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `completed_at` datetime DEFAULT NULL
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `approval_steps`
--
CREATE TABLE
    `approval_steps` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `workflow_id` bigint (20) UNSIGNED NOT NULL,
        `step_order` smallint (5) UNSIGNED NOT NULL,
        `approver_role_id` bigint (20) UNSIGNED DEFAULT NULL,
        `approver_user_id` bigint (20) UNSIGNED DEFAULT NULL,
        `approval_mode` enum ('any', 'all') NOT NULL DEFAULT 'any',
        `step_name` varchar(120) NOT NULL
    );

-- --------------------------------------------------------
--
-- Table structure for table `approval_workflows`
--
CREATE TABLE
    `approval_workflows` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `workflow_name` varchar(120) NOT NULL,
        `document_type` enum (
            'purchase_request',
            'requisition',
            'quotation_comparison',
            'purchase_order',
            'invoice',
            'payment'
        ) NOT NULL,
        `branch_id` bigint (20) UNSIGNED DEFAULT NULL,
        `department_id` bigint (20) UNSIGNED DEFAULT NULL,
        `min_amount` decimal(18, 2) DEFAULT NULL,
        `max_amount` decimal(18, 2) DEFAULT NULL,
        `currency_code` char(3) DEFAULT NULL,
        `is_active` tinyint (1) NOT NULL DEFAULT 1
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `attachments`
--
CREATE TABLE
    `attachments` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `storage_disk` varchar(30) NOT NULL DEFAULT 'local',
        `storage_path` varchar(500) NOT NULL,
        `original_name` varchar(255) NOT NULL,
        `mime_type` varchar(120) NOT NULL,
        `file_size` bigint (20) UNSIGNED NOT NULL,
        `sha256` char(64) DEFAULT NULL,
        `uploaded_by` bigint (20) UNSIGNED NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `attachment_links`
--
CREATE TABLE
    `attachment_links` (
        `attachment_id` bigint (20) UNSIGNED NOT NULL,
        `entity_type` varchar(50) NOT NULL,
        `entity_id` bigint (20) UNSIGNED NOT NULL,
        `document_kind` varchar(50) DEFAULT NULL
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `audit_logs`
--
CREATE TABLE
    `audit_logs` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `user_id` bigint (20) UNSIGNED DEFAULT NULL,
        `action` varchar(100) NOT NULL,
        `entity_type` varchar(80) NOT NULL,
        `entity_id` bigint (20) UNSIGNED DEFAULT NULL,
        `ip_address` varchar(45) DEFAULT NULL,
        `user_agent` varchar(255) DEFAULT NULL,
        `before_data` longtext CHARACTER
        SET
            utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid (`before_data`)),
            `after_data` longtext CHARACTER
        SET
            utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid (`after_data`)),
            `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `branches`
--
CREATE TABLE
    `branches` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `country_id` smallint (5) UNSIGNED NOT NULL,
        `branch_code` varchar(20) NOT NULL,
        `name` varchar(120) NOT NULL,
        `address_line1` varchar(190) DEFAULT NULL,
        `address_line2` varchar(190) DEFAULT NULL,
        `city` varchar(100) DEFAULT NULL,
        `region` varchar(100) DEFAULT NULL,
        `postal_code` varchar(30) DEFAULT NULL,
        `default_currency` char(3) NOT NULL,
        `timezone` varchar(64) NOT NULL DEFAULT 'UTC',
        `is_active` tinyint (1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `budgets`
--
CREATE TABLE
    `budgets` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `branch_id` bigint (20) UNSIGNED NOT NULL,
        `department_id` bigint (20) UNSIGNED NOT NULL,
        `fiscal_year` smallint (5) UNSIGNED NOT NULL,
        `currency_code` char(3) NOT NULL,
        `budget_name` varchar(150) NOT NULL,
        `status` enum ('draft', 'active', 'closed') NOT NULL DEFAULT 'draft'
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `budget_allocations`
--
CREATE TABLE
    `budget_allocations` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `budget_id` bigint (20) UNSIGNED NOT NULL,
        `category_id` bigint (20) UNSIGNED DEFAULT NULL,
        `allocated_amount` decimal(18, 2) NOT NULL DEFAULT 0.00
    );

-- --------------------------------------------------------
--
-- Table structure for table `catalog_items`
--
CREATE TABLE
    `catalog_items` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `category_id` bigint (20) UNSIGNED NOT NULL,
        `unit_id` smallint (5) UNSIGNED NOT NULL,
        `sku` varchar(80) DEFAULT NULL,
        `item_name` varchar(190) NOT NULL,
        `description` text DEFAULT NULL,
        `is_active` tinyint (1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `countries`
--
CREATE TABLE
    `countries` (
        `id` smallint (5) UNSIGNED NOT NULL,
        `iso2` char(2) NOT NULL,
        `iso3` char(3) NOT NULL,
        `name` varchar(100) NOT NULL
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `countries`
--
INSERT INTO
    `countries` (`id`, `iso2`, `iso3`, `name`)
VALUES
    (1, 'US', 'USA', 'United States'),
    (2, 'BD', 'BGD', 'Bangladesh'),
    (3, 'PH', 'PHL', 'Philippines'),
    (4, 'IN', 'IND', 'India'),
    (5, 'PE', 'PER', 'Peru'),
    (6, 'CO', 'COL', 'Colombia'),
    (7, 'CA', 'CAN', 'Canada'),
    (8, 'GB', 'GBR', 'United Kingdom'),
    (9, 'AU', 'AUS', 'Australia'),
    (10, 'DE', 'DEU', 'Germany'),
    (11, 'AE', 'ARE', 'United Arab Emirates'),
    (12, 'SG', 'SGP', 'Singapore'),
    (13, 'MX', 'MEX', 'Mexico'),
    (14, 'BR', 'BRA', 'Brazil');

-- --------------------------------------------------------
--
-- Table structure for table `currencies`
--
CREATE TABLE
    `currencies` (
        `code` char(3) NOT NULL,
        `name` varchar(80) NOT NULL,
        `symbol` varchar(8) NOT NULL,
        `minor_unit` tinyint (3) UNSIGNED NOT NULL DEFAULT 2,
        `is_active` tinyint (1) NOT NULL DEFAULT 1
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `currencies`
--
INSERT INTO
    `currencies` (
        `code`,
        `name`,
        `symbol`,
        `minor_unit`,
        `is_active`
    )
VALUES
    ('AUD', 'Australian Dollar', '$', 2, 1),
    ('BDT', 'Bangladeshi Taka', '৳', 2, 1),
    ('CAD', 'Canadian Dollar', '$', 2, 1),
    ('COP', 'Colombian Peso', '$', 2, 1),
    ('EUR', 'Euro', '€', 2, 1),
    ('GBP', 'Pound Sterling', '£', 2, 1),
    ('INR', 'Indian Rupee', '₹', 2, 1),
    ('PEN', 'Peruvian Sol', 'S/', 2, 1),
    ('PHP', 'Philippine Peso', '₱', 2, 1),
    ('USD', 'US Dollar', '$', 2, 1);

-- --------------------------------------------------------
--
-- Table structure for table `deliveries`
--
CREATE TABLE
    `deliveries` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `delivery_no` varchar(50) NOT NULL,
        `purchase_order_id` bigint (20) UNSIGNED NOT NULL,
        `vendor_delivery_ref` varchar(100) DEFAULT NULL,
        `received_at` datetime NOT NULL,
        `received_by` bigint (20) UNSIGNED NOT NULL,
        `delivery_status` enum ('partial', 'complete', 'rejected') NOT NULL DEFAULT 'partial',
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `delivery_items`
--
CREATE TABLE
    `delivery_items` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `delivery_id` bigint (20) UNSIGNED NOT NULL,
        `purchase_order_item_id` bigint (20) UNSIGNED NOT NULL,
        `quantity_received` decimal(14, 3) NOT NULL,
        `quantity_rejected` decimal(14, 3) NOT NULL DEFAULT 0.000,
        `condition_notes` varchar(255) DEFAULT NULL
    );

-- --------------------------------------------------------
--
-- Table structure for table `departments`
--
CREATE TABLE
    `departments` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `name` varchar(120) NOT NULL,
        `department_code` varchar(30) NOT NULL,
        `is_active` tinyint (1) NOT NULL DEFAULT 1
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `employees`
--
CREATE TABLE
    `employees` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `employee_number` varchar(50) DEFAULT NULL,
        `full_name` varchar(160) NOT NULL,
        `email` varchar(190) NOT NULL,
        `branch_id` bigint (20) UNSIGNED NOT NULL,
        `department_id` bigint (20) UNSIGNED NOT NULL,
        `manager_employee_id` bigint (20) UNSIGNED DEFAULT NULL,
        `job_title` varchar(120) DEFAULT NULL,
        `employment_status` enum ('active', 'inactive', 'terminated') NOT NULL DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `exchange_rates`
--
CREATE TABLE
    `exchange_rates` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `from_currency` char(3) NOT NULL,
        `to_currency` char(3) NOT NULL,
        `rate` decimal(20, 10) NOT NULL,
        `effective_date` date NOT NULL,
        `source` varchar(100) DEFAULT NULL,
        `created_by` bigint (20) UNSIGNED DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    );

-- --------------------------------------------------------
--
-- Table structure for table `import_batches`
--
CREATE TABLE
    `import_batches` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `import_type` varchar(80) NOT NULL,
        `original_filename` varchar(255) NOT NULL,
        `total_rows` int (10) UNSIGNED NOT NULL DEFAULT 0,
        `imported_rows` int (10) UNSIGNED NOT NULL DEFAULT 0,
        `failed_rows` int (10) UNSIGNED NOT NULL DEFAULT 0,
        `status` enum ('previewed', 'completed', 'failed') NOT NULL DEFAULT 'previewed',
        `uploaded_by` bigint (20) UNSIGNED NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `import_row_errors`
--
CREATE TABLE
    `import_row_errors` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `import_batch_id` bigint (20) UNSIGNED NOT NULL,
        `row_number` int (10) UNSIGNED NOT NULL,
        `field_name` varchar(100) DEFAULT NULL,
        `error_message` varchar(500) NOT NULL,
        `row_data` longtext CHARACTER
        SET
            utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid (`row_data`))
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `invoices`
--
CREATE TABLE
    `invoices` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `invoice_no` varchar(100) NOT NULL,
        `vendor_id` bigint (20) UNSIGNED NOT NULL,
        `purchase_order_id` bigint (20) UNSIGNED NOT NULL,
        `currency_code` char(3) NOT NULL,
        `invoice_date` date NOT NULL,
        `due_date` date DEFAULT NULL,
        `subtotal` decimal(18, 2) NOT NULL DEFAULT 0.00,
        `tax_total` decimal(18, 2) NOT NULL DEFAULT 0.00,
        `total_amount` decimal(18, 2) NOT NULL,
        `status` enum (
            'received',
            'under_review',
            'approved',
            'partially_paid',
            'paid',
            'disputed',
            'void'
        ) NOT NULL DEFAULT 'received',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    );

-- --------------------------------------------------------
--
-- Table structure for table `invoice_items`
--
CREATE TABLE
    `invoice_items` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `invoice_id` bigint (20) UNSIGNED NOT NULL,
        `purchase_order_item_id` bigint (20) UNSIGNED DEFAULT NULL,
        `description` varchar(255) NOT NULL,
        `quantity` decimal(14, 3) NOT NULL,
        `unit_price` decimal(18, 4) NOT NULL,
        `line_total` decimal(18, 2) NOT NULL
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `item_categories`
--
CREATE TABLE
    `item_categories` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `parent_id` bigint (20) UNSIGNED DEFAULT NULL,
        `category_code` varchar(40) NOT NULL,
        `name` varchar(120) NOT NULL,
        `is_active` tinyint (1) NOT NULL DEFAULT 1
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `notification_queue`
--
CREATE TABLE
    `notification_queue` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `recipient_email` varchar(190) NOT NULL,
        `template_id` bigint (20) UNSIGNED DEFAULT NULL,
        `subject` varchar(255) NOT NULL,
        `body` mediumtext NOT NULL,
        `payload` longtext CHARACTER
        SET
            utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid (`payload`)),
            `status` enum ('pending', 'sending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
            `attempts` tinyint (3) UNSIGNED NOT NULL DEFAULT 0,
            `available_at` datetime NOT NULL DEFAULT current_timestamp(),
            `sent_at` datetime DEFAULT NULL,
            `last_error` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `notification_templates`
--
CREATE TABLE
    `notification_templates` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `template_key` varchar(100) NOT NULL,
        `subject_template` varchar(255) NOT NULL,
        `body_template` mediumtext NOT NULL,
        `is_active` tinyint (1) NOT NULL DEFAULT 1
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `payments`
--
CREATE TABLE
    `payments` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `payment_reference` varchar(100) NOT NULL,
        `vendor_id` bigint (20) UNSIGNED NOT NULL,
        `currency_code` char(3) NOT NULL,
        `payment_date` date NOT NULL,
        `payment_method` enum (
            'bank_transfer',
            'card',
            'cash',
            'cheque',
            'online',
            'other'
        ) NOT NULL,
        `amount` decimal(18, 2) NOT NULL,
        `transaction_reference` varchar(150) DEFAULT NULL,
        `status` enum ('pending', 'completed', 'failed', 'void') NOT NULL DEFAULT 'pending',
        `recorded_by` bigint (20) UNSIGNED NOT NULL,
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    );

-- --------------------------------------------------------
--
-- Table structure for table `payment_allocations`
--
CREATE TABLE
    `payment_allocations` (
        `payment_id` bigint (20) UNSIGNED NOT NULL,
        `invoice_id` bigint (20) UNSIGNED NOT NULL,
        `allocated_amount` decimal(18, 2) NOT NULL
    );

-- --------------------------------------------------------
--
-- Table structure for table `payment_terms`
--
CREATE TABLE
    `payment_terms` (
        `id` smallint (5) UNSIGNED NOT NULL,
        `term_name` varchar(80) NOT NULL,
        `net_days` smallint (5) UNSIGNED NOT NULL DEFAULT 0,
        `description` varchar(255) DEFAULT NULL
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `payment_terms`
--
INSERT INTO
    `payment_terms` (`id`, `term_name`, `net_days`, `description`)
VALUES
    (
        1,
        'Due on receipt',
        0,
        'Payment due upon receipt'
    ),
    (2, 'Net 15', 15, 'Payment due within 15 days'),
    (3, 'Net 30', 30, 'Payment due within 30 days'),
    (4, 'Net 45', 45, 'Payment due within 45 days'),
    (5, 'Net 60', 60, 'Payment due within 60 days'),
    (6, 'Instant Payment', 0, 'Cash on Delivery');

-- --------------------------------------------------------
--
-- Table structure for table `permissions`
--
CREATE TABLE
    `permissions` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `permission_key` varchar(120) NOT NULL,
        `module` varchar(80) NOT NULL,
        `description` varchar(255) DEFAULT NULL
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--
INSERT INTO
    `permissions` (`id`, `permission_key`, `module`, `description`)
VALUES
    (
        1,
        'purchase_requests.approve',
        'purchase_requests',
        'Approve or reject purchase requests'
    ),
    (
        2,
        'purchase_orders.approve',
        'purchase_orders',
        'Approve purchase orders'
    ),
    (
        3,
        'purchase_orders.cancel',
        'purchase_orders',
        'Cancel purchase orders'
    ),
    (
        4,
        'invoices.create',
        'invoices',
        'Create and match vendor invoices'
    ),
    (
        5,
        'payments.create',
        'payments',
        'Record vendor payments and allocations'
    ),
    (
        6,
        'roles.manage',
        'users',
        'Manage roles and permission matrices'
    ),
    (
        7,
        'employees.manage',
        'organization',
        'Manage employee directory records'
    ),
    (
        8,
        'dashboard.view',
        'dashboard',
        'View dashboard'
    ),
    (
        9,
        'users.manage',
        'users',
        'Manage users and roles'
    ),
    (
        10,
        'organization.manage',
        'organization',
        'Manage branches and departments'
    ),
    (11, 'catalog.view', 'catalog', 'View catalog'),
    (12, 'catalog.manage', 'catalog', 'Manage catalog'),
    (13, 'vendors.view', 'vendors', 'View vendors'),
    (14, 'vendors.manage', 'vendors', 'Manage vendors'),
    (
        15,
        'purchase_requests.view',
        'purchase_requests',
        'View purchase requests'
    ),
    (
        16,
        'purchase_requests.create',
        'purchase_requests',
        'Create purchase requests'
    ),
    (
        17,
        'purchase_requests.manage',
        'purchase_requests',
        'Manage all purchase requests'
    ),
    (
        18,
        'requisitions.manage',
        'requisitions',
        'Manage requisitions'
    ),
    (
        19,
        'quotations.manage',
        'quotations',
        'Manage vendor quotations'
    ),
    (
        20,
        'quotations.compare',
        'quotations',
        'Compare quotations'
    ),
    (
        21,
        'purchase_orders.view',
        'purchase_orders',
        'View purchase orders'
    ),
    (
        22,
        'purchase_orders.manage',
        'purchase_orders',
        'Create and manage purchase orders'
    ),
    (
        23,
        'approvals.act',
        'approvals',
        'Act on assigned approvals'
    ),
    (
        24,
        'deliveries.manage',
        'deliveries',
        'Manage deliveries and receiving'
    ),
    (
        25,
        'invoices.manage',
        'invoices',
        'Manage invoices'
    ),
    (
        26,
        'payments.manage',
        'payments',
        'Manage payments'
    ),
    (27, 'budgets.manage', 'budgets', 'Manage budgets'),
    (28, 'reports.view', 'reports', 'View reports'),
    (29, 'reports.export', 'reports', 'Export reports'),
    (
        30,
        'imports.manage',
        'imports',
        'Import bulk data'
    ),
    (
        31,
        'settings.manage',
        'settings',
        'Manage application settings'
    ),
    (32, 'audit.view', 'audit', 'View audit logs'),
    (
        33,
        'requisitions.view',
        'requisitions',
        'View requisitions'
    ),
    (34, 'vendors.create', 'vendors', 'Create vendors'),
    (35, 'vendors.edit', 'vendors', 'Edit vendors'),
    (
        36,
        'requisitions.create',
        'requisitions',
        'Create requisitions'
    ),
    (
        37,
        'quotations.view',
        'quotations',
        'View quotations'
    ),
    (
        38,
        'quotations.create',
        'quotations',
        'Create quotations'
    ),
    (
        39,
        'purchase_orders.create',
        'purchase_orders',
        'Create purchase orders'
    ),
    (
        40,
        'approvals.view',
        'approvals',
        'View approval inbox'
    ),
    (
        41,
        'deliveries.view',
        'deliveries',
        'View deliveries'
    ),
    (42, 'invoices.view', 'invoices', 'View invoices'),
    (43, 'payments.view', 'payments', 'View payments'),
    (44, 'budgets.view', 'budgets', 'View budgets'),
    (
        45,
        'organization.view',
        'organization',
        'View organization'
    ),
    (
        46,
        'organization.create',
        'organization',
        'Create organization records'
    ),
    (
        47,
        'catalog.create',
        'catalog',
        'Create catalog items'
    ),
    (48, 'imports.view', 'imports', 'View imports');

-- --------------------------------------------------------
--
-- Table structure for table `purchase_orders`
--
CREATE TABLE
    `purchase_orders` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `po_number` varchar(40) NOT NULL,
        `requisition_id` bigint (20) UNSIGNED NOT NULL,
        `quotation_id` bigint (20) UNSIGNED DEFAULT NULL,
        `vendor_id` bigint (20) UNSIGNED NOT NULL,
        `ship_to_branch_id` bigint (20) UNSIGNED NOT NULL,
        `currency_code` char(3) NOT NULL,
        `exchange_rate` decimal(20, 10) NOT NULL DEFAULT 1.0000000000,
        `base_currency_code` char(3) NOT NULL,
        `order_date` date NOT NULL,
        `expected_delivery_date` date DEFAULT NULL,
        `status` enum (
            'draft',
            'pending_approval',
            'approved',
            'sent',
            'partially_received',
            'received',
            'closed',
            'cancelled'
        ) NOT NULL DEFAULT 'draft',
        `subtotal` decimal(18, 2) NOT NULL DEFAULT 0.00,
        `tax_total` decimal(18, 2) NOT NULL DEFAULT 0.00,
        `shipping_total` decimal(18, 2) NOT NULL DEFAULT 0.00,
        `discount_total` decimal(18, 2) NOT NULL DEFAULT 0.00,
        `total_amount` decimal(18, 2) NOT NULL DEFAULT 0.00,
        `base_total_amount` decimal(18, 2) NOT NULL DEFAULT 0.00,
        `terms` text DEFAULT NULL,
        `created_by` bigint (20) UNSIGNED NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    );

-- --------------------------------------------------------
--
-- Table structure for table `purchase_order_items`
--
CREATE TABLE
    `purchase_order_items` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `purchase_order_id` bigint (20) UNSIGNED NOT NULL,
        `requisition_item_id` bigint (20) UNSIGNED DEFAULT NULL,
        `catalog_item_id` bigint (20) UNSIGNED DEFAULT NULL,
        `description` varchar(255) NOT NULL,
        `quantity_ordered` decimal(14, 3) NOT NULL,
        `unit_id` smallint (5) UNSIGNED NOT NULL,
        `unit_price` decimal(18, 4) NOT NULL,
        `tax_rate` decimal(7, 4) NOT NULL DEFAULT 0.0000,
        `line_total` decimal(18, 2) NOT NULL
    );

-- --------------------------------------------------------
--
-- Table structure for table `purchase_requests`
--
CREATE TABLE
    `purchase_requests` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `request_no` varchar(40) NOT NULL,
        `requester_user_id` bigint (20) UNSIGNED NOT NULL,
        `branch_id` bigint (20) UNSIGNED NOT NULL,
        `department_id` bigint (20) UNSIGNED NOT NULL,
        `currency_code` char(3) NOT NULL,
        `required_by` date DEFAULT NULL,
        `business_justification` text NOT NULL,
        `status` enum (
            'draft',
            'submitted',
            'under_review',
            'approved',
            'rejected',
            'converted',
            'cancelled'
        ) NOT NULL DEFAULT 'draft',
        `submitted_at` datetime DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `purchase_request_items`
--
CREATE TABLE
    `purchase_request_items` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `purchase_request_id` bigint (20) UNSIGNED NOT NULL,
        `catalog_item_id` bigint (20) UNSIGNED DEFAULT NULL,
        `description` varchar(255) NOT NULL,
        `quantity` decimal(14, 3) NOT NULL,
        `unit_id` smallint (5) UNSIGNED NOT NULL,
        `estimated_unit_price` decimal(18, 4) DEFAULT NULL
    );

-- --------------------------------------------------------
--
-- Table structure for table `quotations`
--
CREATE TABLE
    `quotations` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `quotation_no` varchar(80) NOT NULL,
        `requisition_id` bigint (20) UNSIGNED NOT NULL,
        `vendor_id` bigint (20) UNSIGNED NOT NULL,
        `currency_code` char(3) NOT NULL,
        `payment_term_id` smallint (5) UNSIGNED DEFAULT NULL,
        `quote_date` date NOT NULL,
        `valid_until` date DEFAULT NULL,
        `delivery_days` smallint (5) UNSIGNED DEFAULT NULL,
        `tax_amount` decimal(18, 2) NOT NULL DEFAULT 0.00,
        `shipping_amount` decimal(18, 2) NOT NULL DEFAULT 0.00,
        `discount_amount` decimal(18, 2) NOT NULL DEFAULT 0.00,
        `status` enum (
            'received',
            'shortlisted',
            'selected',
            'rejected',
            'expired'
        ) NOT NULL DEFAULT 'received',
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `quotation_comparisons`
--
CREATE TABLE
    `quotation_comparisons` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `requisition_id` bigint (20) UNSIGNED NOT NULL,
        `comparison_title` varchar(150) NOT NULL,
        `prepared_by` bigint (20) UNSIGNED NOT NULL,
        `recommendation` text DEFAULT NULL,
        `status` enum ('draft', 'submitted', 'approved', 'rejected') NOT NULL DEFAULT 'draft',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `quotation_comparison_items`
--
CREATE TABLE
    `quotation_comparison_items` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `comparison_id` bigint (20) UNSIGNED NOT NULL,
        `quotation_id` bigint (20) UNSIGNED NOT NULL,
        `evaluation_score` decimal(8, 3) DEFAULT NULL,
        `is_recommended` tinyint (1) NOT NULL DEFAULT 0,
        `evaluation_notes` text DEFAULT NULL
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `quotation_items`
--
CREATE TABLE
    `quotation_items` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `quotation_id` bigint (20) UNSIGNED NOT NULL,
        `requisition_item_id` bigint (20) UNSIGNED NOT NULL,
        `description` varchar(255) NOT NULL,
        `quantity` decimal(14, 3) NOT NULL,
        `unit_price` decimal(18, 4) NOT NULL,
        `tax_rate` decimal(7, 4) NOT NULL DEFAULT 0.0000,
        `warranty_months` smallint (5) UNSIGNED DEFAULT NULL
    );

-- --------------------------------------------------------
--
-- Table structure for table `recurring_purchases`
--
CREATE TABLE
    `recurring_purchases` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `schedule_code` varchar(40) NOT NULL,
        `title` varchar(180) NOT NULL,
        `vendor_id` bigint (20) UNSIGNED NOT NULL,
        `branch_id` bigint (20) UNSIGNED NOT NULL,
        `department_id` bigint (20) UNSIGNED NOT NULL,
        `category_id` bigint (20) UNSIGNED DEFAULT NULL,
        `currency_code` char(3) NOT NULL,
        `amount` decimal(18, 2) NOT NULL,
        `frequency` enum ('monthly', 'quarterly', 'semi_annual', 'annual') NOT NULL DEFAULT 'annual',
        `start_date` date NOT NULL,
        `next_renewal_date` date NOT NULL,
        `auto_create_requisition` tinyint (1) NOT NULL DEFAULT 0,
        `owner_user_id` bigint (20) UNSIGNED NOT NULL,
        `status` enum ('active', 'paused', 'cancelled') NOT NULL DEFAULT 'active',
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
    );

-- --------------------------------------------------------
--
-- Table structure for table `requisitions`
--
CREATE TABLE
    `requisitions` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `requisition_no` varchar(40) NOT NULL,
        `purchase_request_id` bigint (20) UNSIGNED DEFAULT NULL,
        `prepared_by` bigint (20) UNSIGNED NOT NULL,
        `branch_id` bigint (20) UNSIGNED NOT NULL,
        `department_id` bigint (20) UNSIGNED NOT NULL,
        `currency_code` char(3) NOT NULL,
        `status` enum (
            'draft',
            'sourcing',
            'pending_approval',
            'approved',
            'rejected',
            'ordered',
            'cancelled'
        ) NOT NULL DEFAULT 'draft',
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `requisition_items`
--
CREATE TABLE
    `requisition_items` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `requisition_id` bigint (20) UNSIGNED NOT NULL,
        `source_request_item_id` bigint (20) UNSIGNED DEFAULT NULL,
        `catalog_item_id` bigint (20) UNSIGNED DEFAULT NULL,
        `description` varchar(255) NOT NULL,
        `quantity` decimal(14, 3) NOT NULL,
        `unit_id` smallint (5) UNSIGNED NOT NULL,
        `target_unit_price` decimal(18, 4) DEFAULT NULL
    );

-- --------------------------------------------------------
--
-- Table structure for table `roles`
--
CREATE TABLE
    `roles` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `role_key` varchar(80) NOT NULL,
        `name` varchar(100) NOT NULL,
        `description` varchar(255) DEFAULT NULL,
        `is_system` tinyint (1) NOT NULL DEFAULT 0,
        `is_active` tinyint (1) NOT NULL DEFAULT 1
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--
INSERT INTO
    `roles` (
        `id`,
        `role_key`,
        `name`,
        `description`,
        `is_system`,
        `is_active`
    )
VALUES
    (
        1,
        'super_admin',
        'Super Admin',
        'Full system access',
        1,
        1
    ),
    (
        2,
        'it_manager',
        'IT Manager',
        'IT procurement oversight',
        1,
        1
    ),
    (
        3,
        'procurement_officer',
        'Procurement Officer',
        'Manage sourcing and purchasing',
        1,
        1
    ),
    (
        4,
        'finance_officer',
        'Finance Officer',
        'Manage budgets, invoices and payments',
        1,
        1
    ),
    (
        5,
        'department_manager',
        'Department Manager',
        'Department requests and approvals',
        1,
        1
    ),
    (
        6,
        'employee',
        'Employee',
        'Submit and track own requests',
        1,
        1
    ),
    (
        7,
        'auditor',
        'Auditor',
        'Read-only audit access',
        1,
        1
    );

-- --------------------------------------------------------
--
-- Table structure for table `role_permissions`
--
CREATE TABLE
    `role_permissions` (
        `role_id` bigint (20) UNSIGNED NOT NULL,
        `permission_id` bigint (20) UNSIGNED NOT NULL
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--
INSERT INTO
    `role_permissions` (`role_id`, `permission_id`)
VALUES
    (1, 1),
    (1, 2),
    (1, 3),
    (1, 4),
    (1, 5),
    (1, 6),
    (1, 7),
    (1, 8),
    (1, 9),
    (1, 10),
    (1, 11),
    (1, 12),
    (1, 13),
    (1, 14),
    (1, 15),
    (1, 16),
    (1, 17),
    (1, 18),
    (1, 19),
    (1, 20),
    (1, 21),
    (1, 22),
    (1, 23),
    (1, 24),
    (1, 25),
    (1, 26),
    (1, 27),
    (1, 28),
    (1, 29),
    (1, 30),
    (1, 31),
    (1, 32),
    (1, 33),
    (1, 34),
    (1, 35),
    (1, 36),
    (1, 37),
    (1, 38),
    (1, 39),
    (1, 40),
    (1, 41),
    (1, 42),
    (1, 43),
    (1, 44),
    (1, 45),
    (1, 46),
    (1, 47),
    (1, 48),
    (2, 1),
    (2, 2),
    (2, 3),
    (2, 7),
    (2, 8),
    (2, 10),
    (2, 11),
    (2, 12),
    (2, 13),
    (2, 14),
    (2, 15),
    (2, 16),
    (2, 17),
    (2, 18),
    (2, 19),
    (2, 20),
    (2, 21),
    (2, 22),
    (2, 23),
    (2, 24),
    (2, 28),
    (2, 29),
    (2, 30),
    (2, 33),
    (2, 34),
    (2, 35),
    (2, 36),
    (2, 37),
    (2, 38),
    (2, 39),
    (2, 40),
    (2, 41),
    (2, 45),
    (2, 46),
    (2, 47),
    (2, 48),
    (3, 1),
    (3, 2),
    (3, 3),
    (3, 8),
    (3, 11),
    (3, 12),
    (3, 13),
    (3, 14),
    (3, 15),
    (3, 16),
    (3, 17),
    (3, 18),
    (3, 19),
    (3, 20),
    (3, 21),
    (3, 22),
    (3, 23),
    (3, 24),
    (3, 28),
    (3, 29),
    (3, 30),
    (3, 33),
    (3, 34),
    (3, 35),
    (3, 36),
    (3, 37),
    (3, 38),
    (3, 39),
    (3, 40),
    (3, 41),
    (3, 47),
    (3, 48),
    (4, 1),
    (4, 4),
    (4, 5),
    (4, 8),
    (4, 15),
    (4, 16),
    (4, 17),
    (4, 23),
    (4, 25),
    (4, 26),
    (4, 27),
    (4, 28),
    (4, 29),
    (4, 40),
    (4, 42),
    (4, 43),
    (4, 44),
    (5, 8),
    (5, 15),
    (5, 16),
    (5, 17),
    (5, 23),
    (5, 28),
    (5, 40),
    (6, 8),
    (6, 15),
    (6, 16),
    (6, 28),
    (7, 8),
    (7, 28),
    (7, 29),
    (7, 32);

-- --------------------------------------------------------
--
-- Table structure for table `saved_reports`
--
CREATE TABLE
    `saved_reports` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `report_name` varchar(150) NOT NULL,
        `report_type` enum (
            'spend_summary',
            'po_register',
            'invoice_aging',
            'budget_vs_actual',
            'vendor_performance'
        ) NOT NULL,
        `filters_json` longtext CHARACTER
        SET
            utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid (`filters_json`)),
            `columns_json` longtext CHARACTER
        SET
            utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid (`columns_json`)),
            `is_shared` tinyint (1) NOT NULL DEFAULT 0,
            `created_by` bigint (20) UNSIGNED NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `schema_migrations`
--
CREATE TABLE
    `schema_migrations` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `migration` varchar(190) NOT NULL,
        `batch` int (10) UNSIGNED NOT NULL,
        `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `schema_migrations`
--
INSERT INTO
    `schema_migrations` (`id`, `migration`, `batch`, `applied_at`)
VALUES
    (
        1,
        '2026_11_20_000001_initial_schema',
        1,
        '2026-11-20 07:00:27'
    ),
    (
        2,
        '2026_11_21_000002_phase1_enhancements',
        2,
        '2026-11-20 07:00:27'
    ),
    (
        5,
        '2026_11_22_000003_settings_sso_api',
        3,
        '2026-11-20 16:16:15'
    );

-- --------------------------------------------------------
--
-- Table structure for table `settings`
--
CREATE TABLE
    `settings` (
        `setting_key` varchar(120) NOT NULL,
        `setting_value` text DEFAULT NULL,
        `is_encrypted` tinyint (1) NOT NULL DEFAULT 0,
        `updated_by` bigint (20) UNSIGNED DEFAULT NULL,
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--
INSERT INTO
    `settings` (
        `setting_key`,
        `setting_value`,
        `is_encrypted`,
        `updated_by`,
        `updated_at`
    )
VALUES
    (
        'app_title',
        'NSG Procurement',
        0,
        3,
        '2026-11-20 16:20:38'
    ),
    (
        'backup_frequency',
        'daily',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    (
        'backup_hour',
        '02:00',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    (
        'backup_retention_days',
        '30',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    (
        'base_currency',
        'USD',
        0,
        3,
        '2026-11-20 17:01:24'
    ),
    (
        'cat_code_mode',
        'name_slug',
        0,
        3,
        '2026-11-20 17:52:29'
    ),
    (
        'cat_code_prefix',
        'CAT',
        0,
        3,
        '2026-11-20 17:52:29'
    ),
    (
        'cat_include_global_prefix',
        '0',
        0,
        3,
        '2026-11-20 17:57:06'
    ),
    (
        'cat_seq_digits',
        '3',
        0,
        3,
        '2026-11-20 17:52:29'
    ),
    (
        'company_logo_path',
        'assets/uploads/company_logo_1795191638.png',
        0,
        3,
        '2026-11-20 16:20:38'
    ),
    (
        'date_format',
        'Y-m-d',
        0,
        3,
        '2026-11-20 17:01:42'
    ),
    (
        'dept_code_mode',
        'name_slug',
        0,
        3,
        '2026-11-20 19:27:43'
    ),
    (
        'dept_code_prefix',
        'DEPT',
        0,
        3,
        '2026-11-20 19:27:43'
    ),
    (
        'dept_include_global_prefix',
        '1',
        0,
        3,
        '2026-11-20 19:28:00'
    ),
    (
        'dept_seq_digits',
        '3',
        0,
        3,
        '2026-11-20 19:27:43'
    ),
    (
        'emp_code_tag',
        'EMP',
        0,
        3,
        '2026-11-20 19:27:43'
    ),
    (
        'emp_include_branch_code',
        '1',
        0,
        3,
        '2026-11-20 17:52:29'
    ),
    (
        'emp_include_global_prefix',
        '1',
        0,
        3,
        '2026-11-20 17:52:29'
    ),
    (
        'emp_include_tag_with_branch',
        '0',
        0,
        3,
        '2026-11-20 17:52:37'
    ),
    (
        'emp_seq_digits',
        '4',
        0,
        3,
        '2026-11-20 17:52:29'
    ),
    (
        'global_id_prefix',
        'NSG',
        0,
        3,
        '2026-11-20 16:20:38'
    ),
    (
        'login_footer_note',
        'Authorized Personnel Only. All activities are monitored and audited.',
        0,
        3,
        '2026-11-20 16:20:38'
    ),
    (
        'login_headline',
        'Enterprise Procurement Intelligence',
        0,
        3,
        '2026-11-20 16:20:38'
    ),
    (
        'login_subtitle',
        'Streamlined sourcing, multi-branch approvals, vendor governance, and audit compliance for Noor Staffing Group.',
        0,
        3,
        '2026-11-20 16:20:38'
    ),
    (
        'logo_max_height',
        '38',
        0,
        3,
        '2026-11-20 16:20:38'
    ),
    (
        'logo_max_width',
        '170',
        0,
        3,
        '2026-11-20 16:20:38'
    ),
    (
        'multi_currency_display',
        '1',
        0,
        3,
        '2026-11-20 17:01:24'
    ),
    (
        'pdf_footer_note',
        'Confidential — Internal Noor Staffing Group Procurement Document',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    (
        'pdf_header_title',
        '',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    (
        'pdf_subtitle_color',
        '#0D6EFD',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    (
        'pdf_table_header_bg',
        '#F1F3F5',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    (
        'po_declaration_text',
        'This Purchase Order is governed by Noor Staffing Group standard procurement terms and delivery compliance policies.',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    (
        'po_heading_color',
        '#831B30',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    (
        'po_signatory_name',
        'Procurement Director',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    ('show_logo', '1', 0, 3, '2026-11-20 16:20:38'),
    (
        'sidebar_active_color',
        '#0284C7',
        0,
        3,
        '2026-11-20 16:20:38'
    ),
    (
        'sidebar_bg_color',
        '#5856a4',
        0,
        3,
        '2026-11-20 17:00:23'
    ),
    (
        'sidebar_text_color',
        '#E2E8F0',
        0,
        3,
        '2026-11-20 16:20:38'
    ),
    (
        'sku_include_prefix',
        '1',
        0,
        3,
        '2026-11-20 17:52:29'
    ),
    (
        'sku_seq_digits',
        '4',
        0,
        3,
        '2026-11-20 17:52:29'
    ),
    (
        'smtp_encryption',
        'tls',
        0,
        3,
        '2026-11-21 12:10:03'
    ),
    (
        'smtp_from_email',
        'alam.office.my@gmail.com',
        0,
        3,
        '2026-11-21 12:10:34'
    ),
    (
        'smtp_from_name',
        'NSG Procurement Desk',
        0,
        3,
        '2026-11-21 12:10:03'
    ),
    (
        'smtp_host',
        'smtp.gmail.com',
        0,
        3,
        '2026-11-21 12:10:03'
    ),
    (
        'smtp_password',
        'cbkk hghv ybom dooq',
        0,
        3,
        '2026-11-21 12:10:03'
    ),
    ('smtp_port', '587', 0, 3, '2026-11-21 12:10:03'),
    (
        'smtp_username',
        'alam.office.my@gmail.com',
        0,
        3,
        '2026-11-21 12:10:03'
    ),
    (
        'sso_google_client_id',
        '',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    (
        'sso_google_client_secret',
        '',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    (
        'sso_google_enabled',
        '0',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    (
        'sso_microsoft_client_id',
        '',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    (
        'sso_microsoft_client_secret',
        '',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    (
        'sso_microsoft_enabled',
        '0',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    (
        'sso_microsoft_tenant',
        'common',
        0,
        NULL,
        '2026-11-20 16:16:15'
    ),
    (
        'system_timezone',
        'Asia/Dhaka',
        0,
        3,
        '2026-11-20 17:01:42'
    ),
    (
        'time_format',
        'h:i:s A',
        0,
        3,
        '2026-11-20 17:01:42'
    );

-- --------------------------------------------------------
--
-- Table structure for table `units_of_measure`
--
CREATE TABLE
    `units_of_measure` (
        `id` smallint (5) UNSIGNED NOT NULL,
        `unit_code` varchar(20) NOT NULL,
        `name` varchar(60) NOT NULL
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `units_of_measure`
--
INSERT INTO
    `units_of_measure` (`id`, `unit_code`, `name`)
VALUES
    (1, 'EA', 'Each'),
    (2, 'BOX', 'Box'),
    (3, 'LICENSE', 'License'),
    (4, 'MONTH', 'Month'),
    (5, 'YEAR', 'Year'),
    (6, 'HOUR', 'Hour'),
    (7, 'SERVICE', 'Service');

-- --------------------------------------------------------
--
-- Table structure for table `users`
--
CREATE TABLE
    `users` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `employee_id` bigint (20) UNSIGNED DEFAULT NULL,
        `full_name` varchar(160) NOT NULL,
        `email` varchar(190) NOT NULL,
        `password_hash` varchar(255) NOT NULL,
        `status` enum ('active', 'inactive', 'locked') NOT NULL DEFAULT 'active',
        `last_login_at` datetime DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `users`
--
INSERT INTO
    `users` (
        `id`,
        `employee_id`,
        `full_name`,
        `email`,
        `password_hash`,
        `status`,
        `last_login_at`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        3,
        NULL,
        'NSG Administrator',
        'admin@noorstaffing.com',
        '$2y$10$KthilMZn7S5PrQ.ZqdmWLObWXryW0ylPN2yj6DiyCJJNtMEKS4Mne',
        'active',
        '2026-11-21 17:02:49',
        '2026-11-20 07:03:30',
        '2026-11-21 11:02:49'
    ),
    (
        4,
        NULL,
        'Ash',
        'alamshihab526@gmail.com',
        '$2y$10$iKDi5NrbOKAqmNK6bDU.feUFisiCBxgd/O2K6k3gc74gHxL8EPBTy',
        'active',
        '2026-11-21 18:43:04',
        '2026-11-21 12:41:39',
        '2026-11-21 12:43:04'
    ),
    (
        5,
        NULL,
        'JIHAD',
        'hello.ash.ge@gmail.com',
        '$2y$10$Hvf1vKf2dz1EomGGkOJMk.cQPMDSEeEdkcf0rHJroQyzQkDFIDiU2',
        'active',
        '2026-11-21 19:06:47',
        '2026-11-21 12:42:26',
        '2026-11-21 13:06:47'
    );

-- --------------------------------------------------------
--
-- Table structure for table `user_branch_scopes`
--
CREATE TABLE
    `user_branch_scopes` (
        `user_id` bigint (20) UNSIGNED NOT NULL,
        `branch_id` bigint (20) UNSIGNED NOT NULL
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `user_roles`
--
CREATE TABLE
    `user_roles` (
        `user_id` bigint (20) UNSIGNED NOT NULL,
        `role_id` bigint (20) UNSIGNED NOT NULL,
        `assigned_by` bigint (20) UNSIGNED DEFAULT NULL,
        `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--
INSERT INTO
    `user_roles` (
        `user_id`,
        `role_id`,
        `assigned_by`,
        `assigned_at`
    )
VALUES
    (3, 1, NULL, '2026-11-20 07:03:30'),
    (4, 2, 3, '2026-11-21 12:41:39'),
    (4, 3, 3, '2026-11-21 12:41:39'),
    (4, 4, 3, '2026-11-21 12:41:39'),
    (5, 3, 3, '2026-11-21 12:42:26'),
    (5, 4, 3, '2026-11-21 12:42:26'),
    (5, 5, 3, '2026-11-21 12:42:26');

-- --------------------------------------------------------
--
-- Table structure for table `vendors`
--
CREATE TABLE
    `vendors` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `vendor_code` varchar(40) NOT NULL,
        `legal_name` varchar(190) NOT NULL,
        `tax_registration_no` varchar(100) DEFAULT NULL,
        `website` varchar(190) DEFAULT NULL,
        `status` enum ('pending', 'approved', 'blocked', 'inactive') NOT NULL DEFAULT 'pending',
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `vendor_addresses`
--
CREATE TABLE
    `vendor_addresses` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `vendor_id` bigint (20) UNSIGNED NOT NULL,
        `country_id` smallint (5) UNSIGNED NOT NULL,
        `address_type` enum ('billing', 'shipping', 'office', 'other') NOT NULL DEFAULT 'office',
        `address_line1` varchar(190) NOT NULL,
        `address_line2` varchar(190) DEFAULT NULL,
        `city` varchar(100) DEFAULT NULL,
        `region` varchar(100) DEFAULT NULL,
        `postal_code` varchar(30) DEFAULT NULL
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `vendor_contacts`
--
CREATE TABLE
    `vendor_contacts` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `vendor_id` bigint (20) UNSIGNED NOT NULL,
        `contact_name` varchar(160) NOT NULL,
        `email` varchar(190) DEFAULT NULL,
        `phone` varchar(50) DEFAULT NULL,
        `job_title` varchar(100) DEFAULT NULL,
        `is_primary` tinyint (1) NOT NULL DEFAULT 0
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `vendor_performance_reviews`
--
CREATE TABLE
    `vendor_performance_reviews` (
        `id` bigint (20) UNSIGNED NOT NULL,
        `vendor_id` bigint (20) UNSIGNED NOT NULL,
        `purchase_order_id` bigint (20) UNSIGNED DEFAULT NULL,
        `delivery_score` tinyint (3) UNSIGNED NOT NULL,
        `quality_score` tinyint (3) UNSIGNED NOT NULL,
        `pricing_score` tinyint (3) UNSIGNED NOT NULL,
        `communication_score` tinyint (3) UNSIGNED NOT NULL,
        `comments` text DEFAULT NULL,
        `reviewed_by` bigint (20) UNSIGNED NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    );

--
-- Indexes for dumped tables
--
--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `key_hash` (`key_hash`),
ADD KEY `fk_apikey_role` (`role_id`),
ADD KEY `fk_apikey_user` (`created_by`);

--
-- Indexes for table `approval_actions`
--
ALTER TABLE `approval_actions` ADD PRIMARY KEY (`id`),
ADD KEY `approval_instance_id` (`approval_instance_id`),
ADD KEY `approval_step_id` (`approval_step_id`),
ADD KEY `actor_user_id` (`actor_user_id`);

--
-- Indexes for table `approval_delegations`
--
ALTER TABLE `approval_delegations` ADD PRIMARY KEY (`id`),
ADD KEY `delegator_user_id` (`delegator_user_id`),
ADD KEY `delegate_user_id` (`delegate_user_id`);

--
-- Indexes for table `approval_instances`
--
ALTER TABLE `approval_instances` ADD PRIMARY KEY (`id`),
ADD KEY `workflow_id` (`workflow_id`),
ADD KEY `initiated_by` (`initiated_by`),
ADD KEY `idx_approval_document` (`document_type`, `document_id`);

--
-- Indexes for table `approval_steps`
--
ALTER TABLE `approval_steps` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `uq_workflow_step` (`workflow_id`, `step_order`),
ADD KEY `approver_role_id` (`approver_role_id`),
ADD KEY `approver_user_id` (`approver_user_id`);

--
-- Indexes for table `approval_workflows`
--
ALTER TABLE `approval_workflows` ADD PRIMARY KEY (`id`),
ADD KEY `branch_id` (`branch_id`),
ADD KEY `department_id` (`department_id`),
ADD KEY `currency_code` (`currency_code`),
ADD KEY `idx_workflow_match` (`document_type`, `is_active`);

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments` ADD PRIMARY KEY (`id`),
ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `attachment_links`
--
ALTER TABLE `attachment_links` ADD PRIMARY KEY (`attachment_id`, `entity_type`, `entity_id`),
ADD KEY `idx_attachment_entity` (`entity_type`, `entity_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs` ADD PRIMARY KEY (`id`),
ADD KEY `user_id` (`user_id`),
ADD KEY `idx_audit_entity` (`entity_type`, `entity_id`),
ADD KEY `idx_audit_created` (`created_at`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `branch_code` (`branch_code`),
ADD KEY `fk_branch_country` (`country_id`),
ADD KEY `fk_branch_currency` (`default_currency`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `uq_budget` (
    `branch_id`,
    `department_id`,
    `fiscal_year`,
    `budget_name`
),
ADD KEY `department_id` (`department_id`),
ADD KEY `currency_code` (`currency_code`);

--
-- Indexes for table `budget_allocations`
--
ALTER TABLE `budget_allocations` ADD PRIMARY KEY (`id`),
ADD KEY `budget_id` (`budget_id`),
ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `catalog_items`
--
ALTER TABLE `catalog_items` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `sku` (`sku`),
ADD KEY `category_id` (`category_id`),
ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `iso2` (`iso2`),
ADD UNIQUE KEY `iso3` (`iso3`),
ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `currencies`
--
ALTER TABLE `currencies` ADD PRIMARY KEY (`code`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `delivery_no` (`delivery_no`),
ADD KEY `purchase_order_id` (`purchase_order_id`),
ADD KEY `received_by` (`received_by`);

--
-- Indexes for table `delivery_items`
--
ALTER TABLE `delivery_items` ADD PRIMARY KEY (`id`),
ADD KEY `delivery_id` (`delivery_id`),
ADD KEY `purchase_order_item_id` (`purchase_order_item_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `department_code` (`department_code`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `email` (`email`),
ADD UNIQUE KEY `employee_number` (`employee_number`),
ADD KEY `fk_employee_branch` (`branch_id`),
ADD KEY `fk_employee_department` (`department_id`),
ADD KEY `fk_employee_manager` (`manager_employee_id`);

--
-- Indexes for table `exchange_rates`
--
ALTER TABLE `exchange_rates` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `uq_exchange_rate` (`from_currency`, `to_currency`, `effective_date`),
ADD KEY `to_currency` (`to_currency`),
ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `import_batches`
--
ALTER TABLE `import_batches` ADD PRIMARY KEY (`id`),
ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `import_row_errors`
--
ALTER TABLE `import_row_errors` ADD PRIMARY KEY (`id`),
ADD KEY `import_batch_id` (`import_batch_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `uq_vendor_invoice` (`vendor_id`, `invoice_no`),
ADD KEY `purchase_order_id` (`purchase_order_id`),
ADD KEY `currency_code` (`currency_code`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items` ADD PRIMARY KEY (`id`),
ADD KEY `invoice_id` (`invoice_id`),
ADD KEY `purchase_order_item_id` (`purchase_order_item_id`);

--
-- Indexes for table `item_categories`
--
ALTER TABLE `item_categories` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `category_code` (`category_code`),
ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `notification_queue`
--
ALTER TABLE `notification_queue` ADD PRIMARY KEY (`id`),
ADD KEY `template_id` (`template_id`),
ADD KEY `idx_mail_queue` (`status`, `available_at`);

--
-- Indexes for table `notification_templates`
--
ALTER TABLE `notification_templates` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `template_key` (`template_key`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `payment_reference` (`payment_reference`),
ADD KEY `vendor_id` (`vendor_id`),
ADD KEY `currency_code` (`currency_code`),
ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `payment_allocations`
--
ALTER TABLE `payment_allocations` ADD PRIMARY KEY (`payment_id`, `invoice_id`),
ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `payment_terms`
--
ALTER TABLE `payment_terms` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `term_name` (`term_name`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `permission_key` (`permission_key`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `po_number` (`po_number`),
ADD KEY `requisition_id` (`requisition_id`),
ADD KEY `quotation_id` (`quotation_id`),
ADD KEY `vendor_id` (`vendor_id`),
ADD KEY `ship_to_branch_id` (`ship_to_branch_id`),
ADD KEY `currency_code` (`currency_code`),
ADD KEY `base_currency_code` (`base_currency_code`),
ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items` ADD PRIMARY KEY (`id`),
ADD KEY `purchase_order_id` (`purchase_order_id`),
ADD KEY `requisition_item_id` (`requisition_item_id`),
ADD KEY `catalog_item_id` (`catalog_item_id`),
ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `purchase_requests`
--
ALTER TABLE `purchase_requests` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `request_no` (`request_no`),
ADD KEY `requester_user_id` (`requester_user_id`),
ADD KEY `branch_id` (`branch_id`),
ADD KEY `department_id` (`department_id`),
ADD KEY `currency_code` (`currency_code`),
ADD KEY `idx_request_status` (`status`, `created_at`);

--
-- Indexes for table `purchase_request_items`
--
ALTER TABLE `purchase_request_items` ADD PRIMARY KEY (`id`),
ADD KEY `purchase_request_id` (`purchase_request_id`),
ADD KEY `catalog_item_id` (`catalog_item_id`),
ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `uq_vendor_quote` (`vendor_id`, `quotation_no`),
ADD KEY `requisition_id` (`requisition_id`),
ADD KEY `currency_code` (`currency_code`),
ADD KEY `payment_term_id` (`payment_term_id`);

--
-- Indexes for table `quotation_comparisons`
--
ALTER TABLE `quotation_comparisons` ADD PRIMARY KEY (`id`),
ADD KEY `requisition_id` (`requisition_id`),
ADD KEY `prepared_by` (`prepared_by`);

--
-- Indexes for table `quotation_comparison_items`
--
ALTER TABLE `quotation_comparison_items` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `uq_comparison_quote` (`comparison_id`, `quotation_id`),
ADD KEY `quotation_id` (`quotation_id`);

--
-- Indexes for table `quotation_items`
--
ALTER TABLE `quotation_items` ADD PRIMARY KEY (`id`),
ADD KEY `quotation_id` (`quotation_id`),
ADD KEY `requisition_item_id` (`requisition_item_id`);

--
-- Indexes for table `recurring_purchases`
--
ALTER TABLE `recurring_purchases` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `schedule_code` (`schedule_code`),
ADD KEY `fk_rec_vendor` (`vendor_id`),
ADD KEY `fk_rec_branch` (`branch_id`),
ADD KEY `fk_rec_dept` (`department_id`),
ADD KEY `fk_rec_cat` (`category_id`),
ADD KEY `fk_rec_curr` (`currency_code`),
ADD KEY `fk_rec_owner` (`owner_user_id`);

--
-- Indexes for table `requisitions`
--
ALTER TABLE `requisitions` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `requisition_no` (`requisition_no`),
ADD UNIQUE KEY `purchase_request_id` (`purchase_request_id`),
ADD KEY `prepared_by` (`prepared_by`),
ADD KEY `branch_id` (`branch_id`),
ADD KEY `department_id` (`department_id`),
ADD KEY `currency_code` (`currency_code`);

--
-- Indexes for table `requisition_items`
--
ALTER TABLE `requisition_items` ADD PRIMARY KEY (`id`),
ADD KEY `requisition_id` (`requisition_id`),
ADD KEY `source_request_item_id` (`source_request_item_id`),
ADD KEY `catalog_item_id` (`catalog_item_id`),
ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `role_key` (`role_key`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions` ADD PRIMARY KEY (`role_id`, `permission_id`),
ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `saved_reports`
--
ALTER TABLE `saved_reports` ADD PRIMARY KEY (`id`),
ADD KEY `fk_saved_report_user` (`created_by`);

--
-- Indexes for table `schema_migrations`
--
ALTER TABLE `schema_migrations` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `migration` (`migration`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings` ADD PRIMARY KEY (`setting_key`),
ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `units_of_measure`
--
ALTER TABLE `units_of_measure` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `unit_code` (`unit_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `email` (`email`),
ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `user_branch_scopes`
--
ALTER TABLE `user_branch_scopes` ADD PRIMARY KEY (`user_id`, `branch_id`),
ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles` ADD PRIMARY KEY (`user_id`, `role_id`),
ADD KEY `role_id` (`role_id`),
ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `vendor_code` (`vendor_code`);

--
-- Indexes for table `vendor_addresses`
--
ALTER TABLE `vendor_addresses` ADD PRIMARY KEY (`id`),
ADD KEY `vendor_id` (`vendor_id`),
ADD KEY `country_id` (`country_id`);

--
-- Indexes for table `vendor_contacts`
--
ALTER TABLE `vendor_contacts` ADD PRIMARY KEY (`id`),
ADD KEY `vendor_id` (`vendor_id`),
ADD KEY `idx_vendor_contact_email` (`email`);

--
-- Indexes for table `vendor_performance_reviews`
--
ALTER TABLE `vendor_performance_reviews` ADD PRIMARY KEY (`id`),
ADD KEY `fk_vpr_vendor` (`vendor_id`),
ADD KEY `fk_vpr_po` (`purchase_order_id`),
ADD KEY `fk_vpr_user` (`reviewed_by`);

--
-- AUTO_INCREMENT for dumped tables
--
--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approval_actions`
--
ALTER TABLE `approval_actions` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approval_delegations`
--
ALTER TABLE `approval_delegations` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approval_instances`
--
ALTER TABLE `approval_instances` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approval_steps`
--
ALTER TABLE `approval_steps` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approval_workflows`
--
ALTER TABLE `approval_workflows` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_allocations`
--
ALTER TABLE `budget_allocations` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `catalog_items`
--
ALTER TABLE `catalog_items` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries` MODIFY `id` smallint (5) UNSIGNED NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 15;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_items`
--
ALTER TABLE `delivery_items` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exchange_rates`
--
ALTER TABLE `exchange_rates` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `import_batches`
--
ALTER TABLE `import_batches` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `import_row_errors`
--
ALTER TABLE `import_row_errors` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item_categories`
--
ALTER TABLE `item_categories` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_queue`
--
ALTER TABLE `notification_queue` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_templates`
--
ALTER TABLE `notification_templates` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_terms`
--
ALTER TABLE `payment_terms` MODIFY `id` smallint (5) UNSIGNED NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 7;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 50;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_requests`
--
ALTER TABLE `purchase_requests` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_request_items`
--
ALTER TABLE `purchase_request_items` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation_comparisons`
--
ALTER TABLE `quotation_comparisons` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation_comparison_items`
--
ALTER TABLE `quotation_comparison_items` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation_items`
--
ALTER TABLE `quotation_items` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recurring_purchases`
--
ALTER TABLE `recurring_purchases` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requisitions`
--
ALTER TABLE `requisitions` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requisition_items`
--
ALTER TABLE `requisition_items` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 8;

--
-- AUTO_INCREMENT for table `saved_reports`
--
ALTER TABLE `saved_reports` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schema_migrations`
--
ALTER TABLE `schema_migrations` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 6;

--
-- AUTO_INCREMENT for table `units_of_measure`
--
ALTER TABLE `units_of_measure` MODIFY `id` smallint (5) UNSIGNED NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 6;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_addresses`
--
ALTER TABLE `vendor_addresses` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_contacts`
--
ALTER TABLE `vendor_contacts` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_performance_reviews`
--
ALTER TABLE `vendor_performance_reviews` MODIFY `id` bigint (20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--
--
-- Constraints for table `api_keys`
--
ALTER TABLE `api_keys` ADD CONSTRAINT `fk_apikey_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
ADD CONSTRAINT `fk_apikey_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `approval_actions`
--
ALTER TABLE `approval_actions` ADD CONSTRAINT `approval_actions_ibfk_1` FOREIGN KEY (`approval_instance_id`) REFERENCES `approval_instances` (`id`),
ADD CONSTRAINT `approval_actions_ibfk_2` FOREIGN KEY (`approval_step_id`) REFERENCES `approval_steps` (`id`),
ADD CONSTRAINT `approval_actions_ibfk_3` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `approval_delegations`
--
ALTER TABLE `approval_delegations` ADD CONSTRAINT `approval_delegations_ibfk_1` FOREIGN KEY (`delegator_user_id`) REFERENCES `users` (`id`),
ADD CONSTRAINT `approval_delegations_ibfk_2` FOREIGN KEY (`delegate_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `approval_instances`
--
ALTER TABLE `approval_instances` ADD CONSTRAINT `approval_instances_ibfk_1` FOREIGN KEY (`workflow_id`) REFERENCES `approval_workflows` (`id`),
ADD CONSTRAINT `approval_instances_ibfk_2` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `approval_steps`
--
ALTER TABLE `approval_steps` ADD CONSTRAINT `approval_steps_ibfk_1` FOREIGN KEY (`workflow_id`) REFERENCES `approval_workflows` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `approval_steps_ibfk_2` FOREIGN KEY (`approver_role_id`) REFERENCES `roles` (`id`),
ADD CONSTRAINT `approval_steps_ibfk_3` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `approval_workflows`
--
ALTER TABLE `approval_workflows` ADD CONSTRAINT `approval_workflows_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
ADD CONSTRAINT `approval_workflows_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
ADD CONSTRAINT `approval_workflows_ibfk_3` FOREIGN KEY (`currency_code`) REFERENCES `currencies` (`code`);

--
-- Constraints for table `attachments`
--
ALTER TABLE `attachments` ADD CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `attachment_links`
--
ALTER TABLE `attachment_links` ADD CONSTRAINT `attachment_links_ibfk_1` FOREIGN KEY (`attachment_id`) REFERENCES `attachments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs` ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `branches`
--
ALTER TABLE `branches` ADD CONSTRAINT `fk_branch_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
ADD CONSTRAINT `fk_branch_currency` FOREIGN KEY (`default_currency`) REFERENCES `currencies` (`code`);

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets` ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
ADD CONSTRAINT `budgets_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
ADD CONSTRAINT `budgets_ibfk_3` FOREIGN KEY (`currency_code`) REFERENCES `currencies` (`code`);

--
-- Constraints for table `budget_allocations`
--
ALTER TABLE `budget_allocations` ADD CONSTRAINT `budget_allocations_ibfk_1` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `budget_allocations_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `item_categories` (`id`);

--
-- Constraints for table `catalog_items`
--
ALTER TABLE `catalog_items` ADD CONSTRAINT `catalog_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `item_categories` (`id`),
ADD CONSTRAINT `catalog_items_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units_of_measure` (`id`);

--
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries` ADD CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`),
ADD CONSTRAINT `deliveries_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `delivery_items`
--
ALTER TABLE `delivery_items` ADD CONSTRAINT `delivery_items_ibfk_1` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `delivery_items_ibfk_2` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees` ADD CONSTRAINT `fk_employee_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
ADD CONSTRAINT `fk_employee_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
ADD CONSTRAINT `fk_employee_manager` FOREIGN KEY (`manager_employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `exchange_rates`
--
ALTER TABLE `exchange_rates` ADD CONSTRAINT `exchange_rates_ibfk_1` FOREIGN KEY (`from_currency`) REFERENCES `currencies` (`code`),
ADD CONSTRAINT `exchange_rates_ibfk_2` FOREIGN KEY (`to_currency`) REFERENCES `currencies` (`code`),
ADD CONSTRAINT `exchange_rates_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `import_batches`
--
ALTER TABLE `import_batches` ADD CONSTRAINT `import_batches_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `import_row_errors`
--
ALTER TABLE `import_row_errors` ADD CONSTRAINT `import_row_errors_ibfk_1` FOREIGN KEY (`import_batch_id`) REFERENCES `import_batches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices` ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`),
ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`),
ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`currency_code`) REFERENCES `currencies` (`code`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items` ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`);

--
-- Constraints for table `item_categories`
--
ALTER TABLE `item_categories` ADD CONSTRAINT `item_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `item_categories` (`id`);

--
-- Constraints for table `notification_queue`
--
ALTER TABLE `notification_queue` ADD CONSTRAINT `notification_queue_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `notification_templates` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments` ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`),
ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`currency_code`) REFERENCES `currencies` (`code`),
ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `payment_allocations`
--
ALTER TABLE `payment_allocations` ADD CONSTRAINT `payment_allocations_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
ADD CONSTRAINT `payment_allocations_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`);

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders` ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`),
ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`),
ADD CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`),
ADD CONSTRAINT `purchase_orders_ibfk_4` FOREIGN KEY (`ship_to_branch_id`) REFERENCES `branches` (`id`),
ADD CONSTRAINT `purchase_orders_ibfk_5` FOREIGN KEY (`currency_code`) REFERENCES `currencies` (`code`),
ADD CONSTRAINT `purchase_orders_ibfk_6` FOREIGN KEY (`base_currency_code`) REFERENCES `currencies` (`code`),
ADD CONSTRAINT `purchase_orders_ibfk_7` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items` ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`requisition_item_id`) REFERENCES `requisition_items` (`id`),
ADD CONSTRAINT `purchase_order_items_ibfk_3` FOREIGN KEY (`catalog_item_id`) REFERENCES `catalog_items` (`id`),
ADD CONSTRAINT `purchase_order_items_ibfk_4` FOREIGN KEY (`unit_id`) REFERENCES `units_of_measure` (`id`);

--
-- Constraints for table `purchase_requests`
--
ALTER TABLE `purchase_requests` ADD CONSTRAINT `purchase_requests_ibfk_1` FOREIGN KEY (`requester_user_id`) REFERENCES `users` (`id`),
ADD CONSTRAINT `purchase_requests_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
ADD CONSTRAINT `purchase_requests_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
ADD CONSTRAINT `purchase_requests_ibfk_4` FOREIGN KEY (`currency_code`) REFERENCES `currencies` (`code`);

--
-- Constraints for table `purchase_request_items`
--
ALTER TABLE `purchase_request_items` ADD CONSTRAINT `purchase_request_items_ibfk_1` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `purchase_request_items_ibfk_2` FOREIGN KEY (`catalog_item_id`) REFERENCES `catalog_items` (`id`),
ADD CONSTRAINT `purchase_request_items_ibfk_3` FOREIGN KEY (`unit_id`) REFERENCES `units_of_measure` (`id`);

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations` ADD CONSTRAINT `quotations_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`),
ADD CONSTRAINT `quotations_ibfk_2` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`),
ADD CONSTRAINT `quotations_ibfk_3` FOREIGN KEY (`currency_code`) REFERENCES `currencies` (`code`),
ADD CONSTRAINT `quotations_ibfk_4` FOREIGN KEY (`payment_term_id`) REFERENCES `payment_terms` (`id`);

--
-- Constraints for table `quotation_comparisons`
--
ALTER TABLE `quotation_comparisons` ADD CONSTRAINT `quotation_comparisons_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`),
ADD CONSTRAINT `quotation_comparisons_ibfk_2` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `quotation_comparison_items`
--
ALTER TABLE `quotation_comparison_items` ADD CONSTRAINT `quotation_comparison_items_ibfk_1` FOREIGN KEY (`comparison_id`) REFERENCES `quotation_comparisons` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `quotation_comparison_items_ibfk_2` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`);

--
-- Constraints for table `quotation_items`
--
ALTER TABLE `quotation_items` ADD CONSTRAINT `quotation_items_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `quotation_items_ibfk_2` FOREIGN KEY (`requisition_item_id`) REFERENCES `requisition_items` (`id`);

--
-- Constraints for table `recurring_purchases`
--
ALTER TABLE `recurring_purchases` ADD CONSTRAINT `fk_rec_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
ADD CONSTRAINT `fk_rec_cat` FOREIGN KEY (`category_id`) REFERENCES `item_categories` (`id`),
ADD CONSTRAINT `fk_rec_curr` FOREIGN KEY (`currency_code`) REFERENCES `currencies` (`code`),
ADD CONSTRAINT `fk_rec_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
ADD CONSTRAINT `fk_rec_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`),
ADD CONSTRAINT `fk_rec_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`);

--
-- Constraints for table `requisitions`
--
ALTER TABLE `requisitions` ADD CONSTRAINT `requisitions_ibfk_1` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`),
ADD CONSTRAINT `requisitions_ibfk_2` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`),
ADD CONSTRAINT `requisitions_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
ADD CONSTRAINT `requisitions_ibfk_4` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
ADD CONSTRAINT `requisitions_ibfk_5` FOREIGN KEY (`currency_code`) REFERENCES `currencies` (`code`);

--
-- Constraints for table `requisition_items`
--
ALTER TABLE `requisition_items` ADD CONSTRAINT `requisition_items_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `requisition_items_ibfk_2` FOREIGN KEY (`source_request_item_id`) REFERENCES `purchase_request_items` (`id`),
ADD CONSTRAINT `requisition_items_ibfk_3` FOREIGN KEY (`catalog_item_id`) REFERENCES `catalog_items` (`id`),
ADD CONSTRAINT `requisition_items_ibfk_4` FOREIGN KEY (`unit_id`) REFERENCES `units_of_measure` (`id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions` ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_reports`
--
ALTER TABLE `saved_reports` ADD CONSTRAINT `fk_saved_report_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `settings`
--
ALTER TABLE `settings` ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users` ADD CONSTRAINT `fk_user_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `user_branch_scopes`
--
ALTER TABLE `user_branch_scopes` ADD CONSTRAINT `user_branch_scopes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `user_branch_scopes_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles` ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `user_roles_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `vendor_addresses`
--
ALTER TABLE `vendor_addresses` ADD CONSTRAINT `vendor_addresses_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `vendor_addresses_ibfk_2` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`);

--
-- Constraints for table `vendor_contacts`
--
ALTER TABLE `vendor_contacts` ADD CONSTRAINT `vendor_contacts_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_performance_reviews`
--
ALTER TABLE `vendor_performance_reviews` ADD CONSTRAINT `fk_vpr_po` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_vpr_user` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`),
ADD CONSTRAINT `fk_vpr_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;

/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;

/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;