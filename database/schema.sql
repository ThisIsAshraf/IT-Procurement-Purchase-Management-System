SET
    NAMES utf8mb4;

SET
    FOREIGN_KEY_CHECKS = 0;

CREATE TABLE
    IF NOT EXISTS schema_migrations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(190) NOT NULL UNIQUE,
        batch INT UNSIGNED NOT NULL,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE = InnoDB;

CREATE TABLE
    countries (
        id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        iso2 CHAR(2) NOT NULL UNIQUE,
        iso3 CHAR(3) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE = InnoDB;

CREATE TABLE
    currencies (
        code CHAR(3) PRIMARY KEY,
        name VARCHAR(80) NOT NULL,
        symbol VARCHAR(8) NOT NULL,
        minor_unit TINYINT UNSIGNED NOT NULL DEFAULT 2,
        is_active TINYINT (1) NOT NULL DEFAULT 1
    ) ENGINE = InnoDB;

CREATE TABLE
    branches (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        country_id SMALLINT UNSIGNED NOT NULL,
        branch_code VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(120) NOT NULL,
        address_line1 VARCHAR(190),
        address_line2 VARCHAR(190),
        city VARCHAR(100),
        region VARCHAR(100),
        postal_code VARCHAR(30),
        default_currency CHAR(3) NOT NULL,
        timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
        is_active TINYINT (1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_branch_country FOREIGN KEY (country_id) REFERENCES countries (id),
        CONSTRAINT fk_branch_currency FOREIGN KEY (default_currency) REFERENCES currencies (code)
    ) ENGINE = InnoDB;

CREATE TABLE
    departments (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        department_code VARCHAR(30) NOT NULL UNIQUE,
        is_active TINYINT (1) NOT NULL DEFAULT 1
    ) ENGINE = InnoDB;

CREATE TABLE
    employees (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_number VARCHAR(50) UNIQUE,
        full_name VARCHAR(160) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        branch_id BIGINT UNSIGNED NOT NULL,
        department_id BIGINT UNSIGNED NOT NULL,
        manager_employee_id BIGINT UNSIGNED NULL,
        job_title VARCHAR(120),
        employment_status ENUM ('active', 'inactive', 'terminated') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_employee_branch FOREIGN KEY (branch_id) REFERENCES branches (id),
        CONSTRAINT fk_employee_department FOREIGN KEY (department_id) REFERENCES departments (id),
        CONSTRAINT fk_employee_manager FOREIGN KEY (manager_employee_id) REFERENCES employees (id)
    ) ENGINE = InnoDB;

CREATE TABLE
    users (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_id BIGINT UNSIGNED NULL UNIQUE,
        full_name VARCHAR(160) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        status ENUM ('active', 'inactive', 'locked') NOT NULL DEFAULT 'active',
        last_login_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_user_employee FOREIGN KEY (employee_id) REFERENCES employees (id)
    ) ENGINE = InnoDB;

CREATE TABLE
    roles (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        role_key VARCHAR(80) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        description VARCHAR(255),
        is_system TINYINT (1) NOT NULL DEFAULT 0,
        is_active TINYINT (1) NOT NULL DEFAULT 1
    ) ENGINE = InnoDB;

CREATE TABLE
    permissions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        permission_key VARCHAR(120) NOT NULL UNIQUE,
        module VARCHAR(80) NOT NULL,
        description VARCHAR(255)
    ) ENGINE = InnoDB;

CREATE TABLE
    user_roles (
        user_id BIGINT UNSIGNED NOT NULL,
        role_id BIGINT UNSIGNED NOT NULL,
        assigned_by BIGINT UNSIGNED NULL,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, role_id),
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
        FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_by) REFERENCES users (id)
    ) ENGINE = InnoDB;

CREATE TABLE
    role_permissions (
        role_id BIGINT UNSIGNED NOT NULL,
        permission_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (role_id, permission_id),
        FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
        FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

CREATE TABLE
    user_branch_scopes (
        user_id BIGINT UNSIGNED NOT NULL,
        branch_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (user_id, branch_id),
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
        FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

CREATE TABLE
    item_categories (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        parent_id BIGINT UNSIGNED NULL,
        category_code VARCHAR(40) NOT NULL UNIQUE,
        name VARCHAR(120) NOT NULL,
        is_active TINYINT (1) NOT NULL DEFAULT 1,
        FOREIGN KEY (parent_id) REFERENCES item_categories (id)
    ) ENGINE = InnoDB;

CREATE TABLE
    units_of_measure (
        id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        unit_code VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(60) NOT NULL
    ) ENGINE = InnoDB;

CREATE TABLE
    catalog_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_id BIGINT UNSIGNED NOT NULL,
        unit_id SMALLINT UNSIGNED NOT NULL,
        sku VARCHAR(80) UNIQUE,
        item_name VARCHAR(190) NOT NULL,
        description TEXT,
        is_active TINYINT (1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES item_categories (id),
        FOREIGN KEY (unit_id) REFERENCES units_of_measure (id)
    ) ENGINE = InnoDB;

CREATE TABLE
    vendors (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        vendor_code VARCHAR(40) NOT NULL UNIQUE,
        legal_name VARCHAR(190) NOT NULL,
        tax_registration_no VARCHAR(100),
        website VARCHAR(190),
        status ENUM ('pending', 'approved', 'blocked', 'inactive') NOT NULL DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE = InnoDB;

CREATE TABLE
    vendor_contacts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        vendor_id BIGINT UNSIGNED NOT NULL,
        contact_name VARCHAR(160) NOT NULL,
        email VARCHAR(190),
        phone VARCHAR(50),
        job_title VARCHAR(100),
        is_primary TINYINT (1) NOT NULL DEFAULT 0,
        FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE,
        INDEX idx_vendor_contact_email (email)
    ) ENGINE = InnoDB;

CREATE TABLE
    vendor_addresses (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        vendor_id BIGINT UNSIGNED NOT NULL,
        country_id SMALLINT UNSIGNED NOT NULL,
        address_type ENUM ('billing', 'shipping', 'office', 'other') NOT NULL DEFAULT 'office',
        address_line1 VARCHAR(190) NOT NULL,
        address_line2 VARCHAR(190),
        city VARCHAR(100),
        region VARCHAR(100),
        postal_code VARCHAR(30),
        FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE,
        FOREIGN KEY (country_id) REFERENCES countries (id)
    ) ENGINE = InnoDB;

CREATE TABLE
    payment_terms (
        id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        term_name VARCHAR(80) NOT NULL UNIQUE,
        net_days SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        description VARCHAR(255)
    ) ENGINE = InnoDB;

CREATE TABLE
    exchange_rates (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        from_currency CHAR(3) NOT NULL,
        to_currency CHAR(3) NOT NULL,
        rate DECIMAL(20, 10) NOT NULL,
        effective_date DATE NOT NULL,
        source VARCHAR(100),
        created_by BIGINT UNSIGNED NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_exchange_rate (from_currency, to_currency, effective_date),
        FOREIGN KEY (from_currency) REFERENCES currencies (code),
        FOREIGN KEY (to_currency) REFERENCES currencies (code),
        FOREIGN KEY (created_by) REFERENCES users (id),
        CHECK (rate > 0)
    ) ENGINE = InnoDB;

CREATE TABLE
    budgets (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        branch_id BIGINT UNSIGNED NOT NULL,
        department_id BIGINT UNSIGNED NOT NULL,
        fiscal_year SMALLINT UNSIGNED NOT NULL,
        currency_code CHAR(3) NOT NULL,
        budget_name VARCHAR(150) NOT NULL,
        status ENUM ('draft', 'active', 'closed') NOT NULL DEFAULT 'draft',
        UNIQUE KEY uq_budget (
            branch_id,
            department_id,
            fiscal_year,
            budget_name
        ),
        FOREIGN KEY (branch_id) REFERENCES branches (id),
        FOREIGN KEY (department_id) REFERENCES departments (id),
        FOREIGN KEY (currency_code) REFERENCES currencies (code)
    ) ENGINE = InnoDB;

CREATE TABLE
    budget_allocations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        budget_id BIGINT UNSIGNED NOT NULL,
        category_id BIGINT UNSIGNED NULL,
        allocated_amount DECIMAL(18, 2) NOT NULL DEFAULT 0,
        FOREIGN KEY (budget_id) REFERENCES budgets (id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES item_categories (id),
        CHECK (allocated_amount >= 0)
    ) ENGINE = InnoDB;

CREATE TABLE
    purchase_requests (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        request_no VARCHAR(40) NOT NULL UNIQUE,
        requester_user_id BIGINT UNSIGNED NOT NULL,
        branch_id BIGINT UNSIGNED NOT NULL,
        department_id BIGINT UNSIGNED NOT NULL,
        currency_code CHAR(3) NOT NULL,
        required_by DATE NULL,
        business_justification TEXT NOT NULL,
        status ENUM (
            'draft',
            'submitted',
            'under_review',
            'approved',
            'rejected',
            'converted',
            'cancelled'
        ) NOT NULL DEFAULT 'draft',
        submitted_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (requester_user_id) REFERENCES users (id),
        FOREIGN KEY (branch_id) REFERENCES branches (id),
        FOREIGN KEY (department_id) REFERENCES departments (id),
        FOREIGN KEY (currency_code) REFERENCES currencies (code),
        INDEX idx_request_status (status, created_at)
    ) ENGINE = InnoDB;

CREATE TABLE
    purchase_request_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        purchase_request_id BIGINT UNSIGNED NOT NULL,
        catalog_item_id BIGINT UNSIGNED NULL,
        description VARCHAR(255) NOT NULL,
        quantity DECIMAL(14, 3) NOT NULL,
        unit_id SMALLINT UNSIGNED NOT NULL,
        estimated_unit_price DECIMAL(18, 4) NULL,
        FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests (id) ON DELETE CASCADE,
        FOREIGN KEY (catalog_item_id) REFERENCES catalog_items (id),
        FOREIGN KEY (unit_id) REFERENCES units_of_measure (id),
        CHECK (quantity > 0),
        CHECK (
            estimated_unit_price IS NULL
            OR estimated_unit_price >= 0
        )
    ) ENGINE = InnoDB;

CREATE TABLE
    requisitions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        requisition_no VARCHAR(40) NOT NULL UNIQUE,
        purchase_request_id BIGINT UNSIGNED NULL UNIQUE,
        prepared_by BIGINT UNSIGNED NOT NULL,
        branch_id BIGINT UNSIGNED NOT NULL,
        department_id BIGINT UNSIGNED NOT NULL,
        currency_code CHAR(3) NOT NULL,
        status ENUM (
            'draft',
            'sourcing',
            'pending_approval',
            'approved',
            'rejected',
            'ordered',
            'cancelled'
        ) NOT NULL DEFAULT 'draft',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests (id),
        FOREIGN KEY (prepared_by) REFERENCES users (id),
        FOREIGN KEY (branch_id) REFERENCES branches (id),
        FOREIGN KEY (department_id) REFERENCES departments (id),
        FOREIGN KEY (currency_code) REFERENCES currencies (code)
    ) ENGINE = InnoDB;

CREATE TABLE
    requisition_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        requisition_id BIGINT UNSIGNED NOT NULL,
        source_request_item_id BIGINT UNSIGNED NULL,
        catalog_item_id BIGINT UNSIGNED NULL,
        description VARCHAR(255) NOT NULL,
        quantity DECIMAL(14, 3) NOT NULL,
        unit_id SMALLINT UNSIGNED NOT NULL,
        target_unit_price DECIMAL(18, 4) NULL,
        FOREIGN KEY (requisition_id) REFERENCES requisitions (id) ON DELETE CASCADE,
        FOREIGN KEY (source_request_item_id) REFERENCES purchase_request_items (id),
        FOREIGN KEY (catalog_item_id) REFERENCES catalog_items (id),
        FOREIGN KEY (unit_id) REFERENCES units_of_measure (id),
        CHECK (quantity > 0)
    ) ENGINE = InnoDB;

CREATE TABLE
    quotations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        quotation_no VARCHAR(80) NOT NULL,
        requisition_id BIGINT UNSIGNED NOT NULL,
        vendor_id BIGINT UNSIGNED NOT NULL,
        currency_code CHAR(3) NOT NULL,
        payment_term_id SMALLINT UNSIGNED NULL,
        quote_date DATE NOT NULL,
        valid_until DATE NULL,
        delivery_days SMALLINT UNSIGNED NULL,
        tax_amount DECIMAL(18, 2) NOT NULL DEFAULT 0,
        shipping_amount DECIMAL(18, 2) NOT NULL DEFAULT 0,
        discount_amount DECIMAL(18, 2) NOT NULL DEFAULT 0,
        status ENUM (
            'received',
            'shortlisted',
            'selected',
            'rejected',
            'expired'
        ) NOT NULL DEFAULT 'received',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_vendor_quote (vendor_id, quotation_no),
        FOREIGN KEY (requisition_id) REFERENCES requisitions (id),
        FOREIGN KEY (vendor_id) REFERENCES vendors (id),
        FOREIGN KEY (currency_code) REFERENCES currencies (code),
        FOREIGN KEY (payment_term_id) REFERENCES payment_terms (id)
    ) ENGINE = InnoDB;

CREATE TABLE
    quotation_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        quotation_id BIGINT UNSIGNED NOT NULL,
        requisition_item_id BIGINT UNSIGNED NOT NULL,
        description VARCHAR(255) NOT NULL,
        quantity DECIMAL(14, 3) NOT NULL,
        unit_price DECIMAL(18, 4) NOT NULL,
        tax_rate DECIMAL(7, 4) NOT NULL DEFAULT 0,
        warranty_months SMALLINT UNSIGNED NULL,
        FOREIGN KEY (quotation_id) REFERENCES quotations (id) ON DELETE CASCADE,
        FOREIGN KEY (requisition_item_id) REFERENCES requisition_items (id),
        CHECK (quantity > 0),
        CHECK (unit_price >= 0)
    ) ENGINE = InnoDB;

CREATE TABLE
    quotation_comparisons (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        requisition_id BIGINT UNSIGNED NOT NULL,
        comparison_title VARCHAR(150) NOT NULL,
        prepared_by BIGINT UNSIGNED NOT NULL,
        recommendation TEXT,
        status ENUM ('draft', 'submitted', 'approved', 'rejected') NOT NULL DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (requisition_id) REFERENCES requisitions (id),
        FOREIGN KEY (prepared_by) REFERENCES users (id)
    ) ENGINE = InnoDB;

CREATE TABLE
    quotation_comparison_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        comparison_id BIGINT UNSIGNED NOT NULL,
        quotation_id BIGINT UNSIGNED NOT NULL,
        evaluation_score DECIMAL(8, 3) NULL,
        is_recommended TINYINT (1) NOT NULL DEFAULT 0,
        evaluation_notes TEXT,
        FOREIGN KEY (comparison_id) REFERENCES quotation_comparisons (id) ON DELETE CASCADE,
        FOREIGN KEY (quotation_id) REFERENCES quotations (id),
        UNIQUE KEY uq_comparison_quote (comparison_id, quotation_id)
    ) ENGINE = InnoDB;

CREATE TABLE
    purchase_orders (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        po_number VARCHAR(40) NOT NULL UNIQUE,
        requisition_id BIGINT UNSIGNED NOT NULL,
        quotation_id BIGINT UNSIGNED NULL,
        vendor_id BIGINT UNSIGNED NOT NULL,
        ship_to_branch_id BIGINT UNSIGNED NOT NULL,
        currency_code CHAR(3) NOT NULL,
        exchange_rate DECIMAL(20, 10) NOT NULL DEFAULT 1,
        base_currency_code CHAR(3) NOT NULL,
        order_date DATE NOT NULL,
        expected_delivery_date DATE NULL,
        status ENUM (
            'draft',
            'pending_approval',
            'approved',
            'sent',
            'partially_received',
            'received',
            'closed',
            'cancelled'
        ) NOT NULL DEFAULT 'draft',
        subtotal DECIMAL(18, 2) NOT NULL DEFAULT 0,
        tax_total DECIMAL(18, 2) NOT NULL DEFAULT 0,
        shipping_total DECIMAL(18, 2) NOT NULL DEFAULT 0,
        discount_total DECIMAL(18, 2) NOT NULL DEFAULT 0,
        total_amount DECIMAL(18, 2) NOT NULL DEFAULT 0,
        base_total_amount DECIMAL(18, 2) NOT NULL DEFAULT 0,
        terms TEXT,
        created_by BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (requisition_id) REFERENCES requisitions (id),
        FOREIGN KEY (quotation_id) REFERENCES quotations (id),
        FOREIGN KEY (vendor_id) REFERENCES vendors (id),
        FOREIGN KEY (ship_to_branch_id) REFERENCES branches (id),
        FOREIGN KEY (currency_code) REFERENCES currencies (code),
        FOREIGN KEY (base_currency_code) REFERENCES currencies (code),
        FOREIGN KEY (created_by) REFERENCES users (id),
        CHECK (exchange_rate > 0)
    ) ENGINE = InnoDB;

CREATE TABLE
    purchase_order_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        purchase_order_id BIGINT UNSIGNED NOT NULL,
        requisition_item_id BIGINT UNSIGNED NULL,
        catalog_item_id BIGINT UNSIGNED NULL,
        description VARCHAR(255) NOT NULL,
        quantity_ordered DECIMAL(14, 3) NOT NULL,
        unit_id SMALLINT UNSIGNED NOT NULL,
        unit_price DECIMAL(18, 4) NOT NULL,
        tax_rate DECIMAL(7, 4) NOT NULL DEFAULT 0,
        line_total DECIMAL(18, 2) NOT NULL,
        FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders (id) ON DELETE CASCADE,
        FOREIGN KEY (requisition_item_id) REFERENCES requisition_items (id),
        FOREIGN KEY (catalog_item_id) REFERENCES catalog_items (id),
        FOREIGN KEY (unit_id) REFERENCES units_of_measure (id),
        CHECK (quantity_ordered > 0)
    ) ENGINE = InnoDB;

CREATE TABLE
    approval_workflows (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        workflow_name VARCHAR(120) NOT NULL,
        document_type ENUM (
            'purchase_request',
            'requisition',
            'quotation_comparison',
            'purchase_order',
            'invoice',
            'payment'
        ) NOT NULL,
        branch_id BIGINT UNSIGNED NULL,
        department_id BIGINT UNSIGNED NULL,
        min_amount DECIMAL(18, 2) NULL,
        max_amount DECIMAL(18, 2) NULL,
        currency_code CHAR(3) NULL,
        is_active TINYINT (1) NOT NULL DEFAULT 1,
        FOREIGN KEY (branch_id) REFERENCES branches (id),
        FOREIGN KEY (department_id) REFERENCES departments (id),
        FOREIGN KEY (currency_code) REFERENCES currencies (code),
        INDEX idx_workflow_match (document_type, is_active)
    ) ENGINE = InnoDB;

CREATE TABLE
    approval_steps (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        workflow_id BIGINT UNSIGNED NOT NULL,
        step_order SMALLINT UNSIGNED NOT NULL,
        approver_role_id BIGINT UNSIGNED NULL,
        approver_user_id BIGINT UNSIGNED NULL,
        approval_mode ENUM ('any', 'all') NOT NULL DEFAULT 'any',
        step_name VARCHAR(120) NOT NULL,
        UNIQUE KEY uq_workflow_step (workflow_id, step_order),
        FOREIGN KEY (workflow_id) REFERENCES approval_workflows (id) ON DELETE CASCADE,
        FOREIGN KEY (approver_role_id) REFERENCES roles (id),
        FOREIGN KEY (approver_user_id) REFERENCES users (id),
        CHECK (
            approver_role_id IS NOT NULL
            OR approver_user_id IS NOT NULL
        )
    ) ENGINE = InnoDB;

CREATE TABLE
    approval_instances (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        workflow_id BIGINT UNSIGNED NOT NULL,
        document_type VARCHAR(40) NOT NULL,
        document_id BIGINT UNSIGNED NOT NULL,
        current_step_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
        status ENUM ('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
        initiated_by BIGINT UNSIGNED NOT NULL,
        initiated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME NULL,
        FOREIGN KEY (workflow_id) REFERENCES approval_workflows (id),
        FOREIGN KEY (initiated_by) REFERENCES users (id),
        INDEX idx_approval_document (document_type, document_id)
    ) ENGINE = InnoDB;

CREATE TABLE
    approval_actions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        approval_instance_id BIGINT UNSIGNED NOT NULL,
        approval_step_id BIGINT UNSIGNED NOT NULL,
        actor_user_id BIGINT UNSIGNED NOT NULL,
        decision ENUM ('approved', 'rejected', 'returned', 'delegated') NOT NULL,
        comments TEXT,
        acted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (approval_instance_id) REFERENCES approval_instances (id),
        FOREIGN KEY (approval_step_id) REFERENCES approval_steps (id),
        FOREIGN KEY (actor_user_id) REFERENCES users (id)
    ) ENGINE = InnoDB;

CREATE TABLE
    approval_delegations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        delegator_user_id BIGINT UNSIGNED NOT NULL,
        delegate_user_id BIGINT UNSIGNED NOT NULL,
        starts_at DATETIME NOT NULL,
        ends_at DATETIME NOT NULL,
        reason VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (delegator_user_id) REFERENCES users (id),
        FOREIGN KEY (delegate_user_id) REFERENCES users (id),
        CHECK (ends_at > starts_at)
    ) ENGINE = InnoDB;

CREATE TABLE
    deliveries (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        delivery_no VARCHAR(50) NOT NULL UNIQUE,
        purchase_order_id BIGINT UNSIGNED NOT NULL,
        vendor_delivery_ref VARCHAR(100),
        received_at DATETIME NOT NULL,
        received_by BIGINT UNSIGNED NOT NULL,
        delivery_status ENUM ('partial', 'complete', 'rejected') NOT NULL DEFAULT 'partial',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders (id),
        FOREIGN KEY (received_by) REFERENCES users (id)
    ) ENGINE = InnoDB;

CREATE TABLE
    delivery_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        delivery_id BIGINT UNSIGNED NOT NULL,
        purchase_order_item_id BIGINT UNSIGNED NOT NULL,
        quantity_received DECIMAL(14, 3) NOT NULL,
        quantity_rejected DECIMAL(14, 3) NOT NULL DEFAULT 0,
        condition_notes VARCHAR(255),
        FOREIGN KEY (delivery_id) REFERENCES deliveries (id) ON DELETE CASCADE,
        FOREIGN KEY (purchase_order_item_id) REFERENCES purchase_order_items (id),
        CHECK (quantity_received >= 0),
        CHECK (quantity_rejected >= 0)
    ) ENGINE = InnoDB;

CREATE TABLE
    invoices (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        invoice_no VARCHAR(100) NOT NULL,
        vendor_id BIGINT UNSIGNED NOT NULL,
        purchase_order_id BIGINT UNSIGNED NOT NULL,
        currency_code CHAR(3) NOT NULL,
        invoice_date DATE NOT NULL,
        due_date DATE NULL,
        subtotal DECIMAL(18, 2) NOT NULL DEFAULT 0,
        tax_total DECIMAL(18, 2) NOT NULL DEFAULT 0,
        total_amount DECIMAL(18, 2) NOT NULL,
        status ENUM (
            'received',
            'under_review',
            'approved',
            'partially_paid',
            'paid',
            'disputed',
            'void'
        ) NOT NULL DEFAULT 'received',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_vendor_invoice (vendor_id, invoice_no),
        FOREIGN KEY (vendor_id) REFERENCES vendors (id),
        FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders (id),
        FOREIGN KEY (currency_code) REFERENCES currencies (code),
        CHECK (total_amount >= 0)
    ) ENGINE = InnoDB;

CREATE TABLE
    invoice_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        invoice_id BIGINT UNSIGNED NOT NULL,
        purchase_order_item_id BIGINT UNSIGNED NULL,
        description VARCHAR(255) NOT NULL,
        quantity DECIMAL(14, 3) NOT NULL,
        unit_price DECIMAL(18, 4) NOT NULL,
        line_total DECIMAL(18, 2) NOT NULL,
        FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE CASCADE,
        FOREIGN KEY (purchase_order_item_id) REFERENCES purchase_order_items (id)
    ) ENGINE = InnoDB;

CREATE TABLE
    payments (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        payment_reference VARCHAR(100) NOT NULL UNIQUE,
        vendor_id BIGINT UNSIGNED NOT NULL,
        currency_code CHAR(3) NOT NULL,
        payment_date DATE NOT NULL,
        payment_method ENUM (
            'bank_transfer',
            'card',
            'cash',
            'cheque',
            'online',
            'other'
        ) NOT NULL,
        amount DECIMAL(18, 2) NOT NULL,
        transaction_reference VARCHAR(150),
        status ENUM ('pending', 'completed', 'failed', 'void') NOT NULL DEFAULT 'pending',
        recorded_by BIGINT UNSIGNED NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (vendor_id) REFERENCES vendors (id),
        FOREIGN KEY (currency_code) REFERENCES currencies (code),
        FOREIGN KEY (recorded_by) REFERENCES users (id),
        CHECK (amount > 0)
    ) ENGINE = InnoDB;

CREATE TABLE
    payment_allocations (
        payment_id BIGINT UNSIGNED NOT NULL,
        invoice_id BIGINT UNSIGNED NOT NULL,
        allocated_amount DECIMAL(18, 2) NOT NULL,
        PRIMARY KEY (payment_id, invoice_id),
        FOREIGN KEY (payment_id) REFERENCES payments (id),
        FOREIGN KEY (invoice_id) REFERENCES invoices (id),
        CHECK (allocated_amount > 0)
    ) ENGINE = InnoDB;

CREATE TABLE
    attachments (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        storage_disk VARCHAR(30) NOT NULL DEFAULT 'local',
        storage_path VARCHAR(500) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        mime_type VARCHAR(120) NOT NULL,
        file_size BIGINT UNSIGNED NOT NULL,
        sha256 CHAR(64),
        uploaded_by BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (uploaded_by) REFERENCES users (id)
    ) ENGINE = InnoDB;

CREATE TABLE
    attachment_links (
        attachment_id BIGINT UNSIGNED NOT NULL,
        entity_type VARCHAR(50) NOT NULL,
        entity_id BIGINT UNSIGNED NOT NULL,
        document_kind VARCHAR(50),
        PRIMARY KEY (attachment_id, entity_type, entity_id),
        FOREIGN KEY (attachment_id) REFERENCES attachments (id) ON DELETE CASCADE,
        INDEX idx_attachment_entity (entity_type, entity_id)
    ) ENGINE = InnoDB;

CREATE TABLE
    notification_templates (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        template_key VARCHAR(100) NOT NULL UNIQUE,
        subject_template VARCHAR(255) NOT NULL,
        body_template MEDIUMTEXT NOT NULL,
        is_active TINYINT (1) NOT NULL DEFAULT 1
    ) ENGINE = InnoDB;

CREATE TABLE
    notification_queue (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        recipient_email VARCHAR(190) NOT NULL,
        template_id BIGINT UNSIGNED NULL,
        subject VARCHAR(255) NOT NULL,
        body MEDIUMTEXT NOT NULL,
        payload JSON NULL,
        status ENUM ('pending', 'sending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
        attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
        available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        sent_at DATETIME NULL,
        last_error TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (template_id) REFERENCES notification_templates (id),
        INDEX idx_mail_queue (status, available_at)
    ) ENGINE = InnoDB;

CREATE TABLE
    audit_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NULL,
        action VARCHAR(100) NOT NULL,
        entity_type VARCHAR(80) NOT NULL,
        entity_id BIGINT UNSIGNED NULL,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        before_data JSON NULL,
        after_data JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id),
        INDEX idx_audit_entity (entity_type, entity_id),
        INDEX idx_audit_created (created_at)
    ) ENGINE = InnoDB;

CREATE TABLE
    settings (
        setting_key VARCHAR(120) PRIMARY KEY,
        setting_value TEXT NULL,
        is_encrypted TINYINT (1) NOT NULL DEFAULT 0,
        updated_by BIGINT UNSIGNED NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (updated_by) REFERENCES users (id)
    ) ENGINE = InnoDB;

CREATE TABLE
    import_batches (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        import_type VARCHAR(80) NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        total_rows INT UNSIGNED NOT NULL DEFAULT 0,
        imported_rows INT UNSIGNED NOT NULL DEFAULT 0,
        failed_rows INT UNSIGNED NOT NULL DEFAULT 0,
        status ENUM ('previewed', 'completed', 'failed') NOT NULL DEFAULT 'previewed',
        uploaded_by BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (uploaded_by) REFERENCES users (id)
    ) ENGINE = InnoDB;

CREATE TABLE
    import_row_errors (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        import_batch_id BIGINT UNSIGNED NOT NULL,
        row_number INT UNSIGNED NOT NULL,
        field_name VARCHAR(100),
        error_message VARCHAR(500) NOT NULL,
        row_data JSON NULL,
        FOREIGN KEY (import_batch_id) REFERENCES import_batches (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

SET
    FOREIGN_KEY_CHECKS = 1;