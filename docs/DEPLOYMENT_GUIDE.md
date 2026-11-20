# Deployment guide

Use PHP 8.2+, MySQL 8, Composer and Apache/Nginx. Point the web server document root to `public/`. Do not deploy the project root as document root. Install dependencies with `composer install --no-dev --classmap-authoritative`; configure secrets via environment; set APP_DEBUG=false; enable HTTPS; restrict filesystem permissions on storage; configure a cron job for the mail queue; take encrypted off-site database and file backups.

Before launch: run schema migrations on staging, verify seeded roles and currency list, create admin, test each role, configure approval workflows, test SMTP, test PDF/CSV imports/exports, verify timezone and branch scope, and obtain Finance approval for currency and payment rules.
