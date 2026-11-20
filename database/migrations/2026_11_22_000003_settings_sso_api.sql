SET
    NAMES utf8mb4;

CREATE TABLE
    IF NOT EXISTS api_keys (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_name VARCHAR(120) NOT NULL,
        key_prefix VARCHAR(16) NOT NULL,
        key_hash CHAR(64) NOT NULL UNIQUE,
        role_id BIGINT UNSIGNED NOT NULL,
        rate_limit_per_min INT UNSIGNED NOT NULL DEFAULT 120,
        scopes_json JSON NULL,
        allowed_ips VARCHAR(255) NULL,
        expires_at DATE NULL,
        last_used_at DATETIME NULL,
        request_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
        is_active TINYINT (1) NOT NULL DEFAULT 1,
        created_by BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_apikey_role FOREIGN KEY (role_id) REFERENCES roles (id),
        CONSTRAINT fk_apikey_user FOREIGN KEY (created_by) REFERENCES users (id)
    ) ENGINE = InnoDB;

INSERT IGNORE INTO settings (setting_key, setting_value)
VALUES
    ('app_title', 'NSG Procurement'),
    ('global_id_prefix', 'NSG'),
    (
        'login_headline',
        'Enterprise Procurement Intelligence'
    ),
    (
        'login_subtitle',
        'Streamlined sourcing, multi-branch approvals, vendor governance, and audit compliance for Noor Staffing Group.'
    ),
    (
        'login_footer_note',
        'Authorized Personnel Only. All activities are monitored and audited.'
    ),
    ('show_logo', '1'),
    ('logo_max_height', '38'),
    ('logo_max_width', '170'),
    ('company_logo_path', ''),
    ('sidebar_bg_color', '#831B30'),
    ('sidebar_text_color', '#E2E8F0'),
    ('sidebar_active_color', '#0284C7'),
    ('system_timezone', 'Asia/Dhaka'),
    ('date_format', 'Y-m-d'),
    ('time_format', 'h:i:s A'),
    ('base_currency', 'USD'),
    ('multi_currency_display', '1'),
    ('pdf_header_title', ''),
    ('pdf_subtitle_color', '#0D6EFD'),
    ('pdf_table_header_bg', '#F1F3F5'),
    (
        'pdf_footer_note',
        'Confidential — Internal Noor Staffing Group Procurement Document'
    ),
    ('po_heading_color', '#831B30'),
    ('po_signatory_name', 'Procurement Director'),
    (
        'po_declaration_text',
        'This Purchase Order is governed by Noor Staffing Group standard procurement terms and delivery compliance policies.'
    ),
    ('smtp_host', 'smtp.gmail.com'),
    ('smtp_port', '587'),
    ('smtp_username', ''),
    ('smtp_password', ''),
    ('smtp_encryption', 'tls'),
    ('smtp_from_email', 'procurement@noorstaffing.com'),
    ('smtp_from_name', 'NSG Procurement Desk'),
    ('sso_google_enabled', '0'),
    ('sso_google_client_id', ''),
    ('sso_google_client_secret', ''),
    ('sso_microsoft_enabled', '0'),
    ('sso_microsoft_client_id', ''),
    ('sso_microsoft_client_secret', ''),
    ('sso_microsoft_tenant', 'common'),
    ('backup_frequency', 'daily'),
    ('backup_hour', '02:00'),
    ('backup_retention_days', '30');