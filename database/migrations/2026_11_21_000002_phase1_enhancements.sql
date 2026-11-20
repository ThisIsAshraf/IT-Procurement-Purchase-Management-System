SET
    NAMES utf8mb4;

SET
    FOREIGN_KEY_CHECKS = 0;

CREATE TABLE
    IF NOT EXISTS recurring_purchases (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        schedule_code VARCHAR(40) NOT NULL UNIQUE,
        title VARCHAR(180) NOT NULL,
        vendor_id BIGINT UNSIGNED NOT NULL,
        branch_id BIGINT UNSIGNED NOT NULL,
        department_id BIGINT UNSIGNED NOT NULL,
        category_id BIGINT UNSIGNED NULL,
        currency_code CHAR(3) NOT NULL,
        amount DECIMAL(18, 2) NOT NULL,
        frequency ENUM ('monthly', 'quarterly', 'semi_annual', 'annual') NOT NULL DEFAULT 'annual',
        start_date DATE NOT NULL,
        next_renewal_date DATE NOT NULL,
        auto_create_requisition TINYINT (1) NOT NULL DEFAULT 0,
        owner_user_id BIGINT UNSIGNED NOT NULL,
        status ENUM ('active', 'paused', 'cancelled') NOT NULL DEFAULT 'active',
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_rec_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id),
        CONSTRAINT fk_rec_branch FOREIGN KEY (branch_id) REFERENCES branches (id),
        CONSTRAINT fk_rec_dept FOREIGN KEY (department_id) REFERENCES departments (id),
        CONSTRAINT fk_rec_cat FOREIGN KEY (category_id) REFERENCES item_categories (id),
        CONSTRAINT fk_rec_curr FOREIGN KEY (currency_code) REFERENCES currencies (code),
        CONSTRAINT fk_rec_owner FOREIGN KEY (owner_user_id) REFERENCES users (id),
        CHECK (amount >= 0)
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS saved_reports (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        report_name VARCHAR(150) NOT NULL,
        report_type ENUM (
            'spend_summary',
            'po_register',
            'invoice_aging',
            'budget_vs_actual',
            'vendor_performance'
        ) NOT NULL,
        filters_json JSON NOT NULL,
        columns_json JSON NULL,
        is_shared TINYINT (1) NOT NULL DEFAULT 0,
        created_by BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_saved_report_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS vendor_performance_reviews (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        vendor_id BIGINT UNSIGNED NOT NULL,
        purchase_order_id BIGINT UNSIGNED NULL,
        delivery_score TINYINT UNSIGNED NOT NULL,
        quality_score TINYINT UNSIGNED NOT NULL,
        pricing_score TINYINT UNSIGNED NOT NULL,
        communication_score TINYINT UNSIGNED NOT NULL,
        comments TEXT NULL,
        reviewed_by BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_vpr_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE,
        CONSTRAINT fk_vpr_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders (id) ON DELETE SET NULL,
        CONSTRAINT fk_vpr_user FOREIGN KEY (reviewed_by) REFERENCES users (id),
        CHECK (delivery_score BETWEEN 1 AND 5),
        CHECK (quality_score BETWEEN 1 AND 5),
        CHECK (pricing_score BETWEEN 1 AND 5),
        CHECK (communication_score BETWEEN 1 AND 5)
    ) ENGINE = InnoDB;

INSERT IGNORE INTO permissions (permission_key, module, description)
VALUES
    (
        'purchase_requests.approve',
        'purchase_requests',
        'Approve or reject purchase requests'
    ),
    (
        'purchase_orders.approve',
        'purchase_orders',
        'Approve purchase orders'
    ),
    (
        'purchase_orders.cancel',
        'purchase_orders',
        'Cancel purchase orders'
    ),
    (
        'invoices.create',
        'invoices',
        'Create and match vendor invoices'
    ),
    (
        'payments.create',
        'payments',
        'Record vendor payments and allocations'
    ),
    (
        'roles.manage',
        'users',
        'Manage roles and permission matrices'
    ),
    (
        'employees.manage',
        'organization',
        'Manage employee directory records'
    );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT
    r.id,
    p.id
FROM
    roles r
    CROSS JOIN permissions p
WHERE
    r.role_key = 'super_admin';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT
    r.id,
    p.id
FROM
    roles r
    JOIN permissions p ON p.permission_key IN (
        'purchase_requests.approve',
        'purchase_orders.approve',
        'employees.manage'
    )
WHERE
    r.role_key = 'it_manager';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT
    r.id,
    p.id
FROM
    roles r
    JOIN permissions p ON p.permission_key IN ('purchase_orders.cancel')
WHERE
    r.role_key = 'procurement_officer';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT
    r.id,
    p.id
FROM
    roles r
    JOIN permissions p ON p.permission_key IN ('invoices.create', 'payments.create')
WHERE
    r.role_key = 'finance_officer';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT
    r.id,
    p.id
FROM
    roles r
    JOIN permissions p ON p.permission_key IN ('purchase_requests.approve')
WHERE
    r.role_key = 'department_manager';

SET
    FOREIGN_KEY_CHECKS = 1;