<?php

/**
 * @var string $tab
 * @var array<string, string> $tabs
 * @var array<string, string> $s
 * @var array<int, array<string, mixed>> $currencies
 * @var array<int, array<string, mixed>> $allCurrencies
 * @var array<int, array<string, mixed>> $countries
 * @var array<int, array<string, mixed>> $exchangeRates
 * @var array<int, array<string, mixed>> $categories
 * @var array<int, array<string, mixed>> $units
 * @var array<int, array<string, mixed>> $paymentTerms
 * @var array<int, array<string, mixed>> $templates
 * @var array<int, array<string, mixed>> $queue
 * @var int $queuePage
 * @var int $queueTotalPages
 * @var int $queueTotal
 * @var int $pendingQueueCount
 * @var array<int, array<string, mixed>> $roles
 * @var array<int, array<string, mixed>> $apiKeys
 * @var array<int, array<string, mixed>> $archives
 * @var string|null $flash
 * @var string|null $error
 */
$tab = $tab ?? 'branding';
$tabs = $tabs ?? [];
$s = $s ?? [];
$currencies = $currencies ?? [];
$allCurrencies = $allCurrencies ?? [];
$countries = $countries ?? [];
$exchangeRates = $exchangeRates ?? [];
$categories = $categories ?? [];
$units = $units ?? [];
$paymentTerms = $paymentTerms ?? [];
$templates = $templates ?? [];
$queue = $queue ?? [];
$queuePage = $queuePage ?? 1;
$queueTotalPages = $queueTotalPages ?? 1;
$queueTotal = $queueTotal ?? count($queue);
$pendingQueueCount = $pendingQueueCount ?? 0;
$roles = $roles ?? [];
$apiKeys = $apiKeys ?? [];
$archives = $archives ?? [];
$flash = $flash ?? null;
$error = $error ?? null;
?>
<div class="page-header">
    <div>
        <p class="eyebrow">Administration</p>
        <h1>Settings: <?= e($tabs[$tab] ?? 'Site Settings') ?></h1>
    </div>
</div>

<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="settings-tabs">
    <?php foreach ($tabs as $key => $label): ?>
        <a href="<?= e(url('/settings?tab=' . $key)) ?>" class="settings-tab <?= $tab === $key ? 'active' : '' ?>">
            <?= e($label) ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($tab === 'branding'): ?>
    <section class="panel">
        <form method="post" action="<?= e(url('/settings/branding')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="page-header">
                <h2>Branding, ID Prefix &amp; Sidebar Theme</h2>
                <button class="btn primary">&#10003; Save Branding</button>
            </div>

            <div class="grid two">
                <label>Application Title
                    <input name="app_title" value="<?= e($s['app_title'] ?? 'NSG Procurement') ?>" required>
                </label>
                <label>Global Document &amp; Employee ID Prefix
                    <input name="global_id_prefix" value="<?= e($s['global_id_prefix'] ?? 'NSG') ?>" maxlength="10">
                    <small class="subtitle">Preview: <code><?= e(($s['global_id_prefix'] ?? 'NSG') . '-PR-2026-0001') ?></code> · <code><?= e(($s['global_id_prefix'] ?? 'NSG') . '-PO-2026-0001') ?></code></small>
                </label>
            </div>

            <label>Login Page Headline
                <input name="login_headline" value="<?= e($s['login_headline'] ?? 'Enterprise Procurement Intelligence') ?>">
            </label>
            <label>Login Page Subtitle / Description
                <textarea name="login_subtitle" rows="2"><?= e($s['login_subtitle'] ?? '') ?></textarea>
            </label>
            <label>Login Security Footer Note
                <input name="login_footer_note" value="<?= e($s['login_footer_note'] ?? '') ?>">
            </label>

            <div class="panel" style="background:#f8fafc;">
                <div class="page-header">
                    <div>
                        <strong>Application &amp; Login Logo Display</strong><br>
                        <small class="subtitle">Control whether your uploaded logo appears in the sidebar and login screen.</small>
                    </div>
                    <label class="inline">
                        <input type="checkbox" name="show_logo" value="1" <?= ($s['show_logo'] ?? '1') === '1' ? 'checked' : '' ?>>
                        Show Company Logo
                    </label>
                </div>
                <div class="grid two">
                    <label>Logo Max Height (px)<input type="number" name="logo_max_height" value="<?= e($s['logo_max_height'] ?? '38') ?>"></label>
                    <label>Logo Max Width (px)<input type="number" name="logo_max_width" value="<?= e($s['logo_max_width'] ?? '170') ?>"></label>
                </div>
                <?php if (!empty($s['company_logo_path'])): ?>
                    <p>
                        <img src="<?= e(url('/' . ltrim($s['company_logo_path'], '/'))) ?>" alt="Logo" style="max-height:46px; border:1px solid #cbd5e1; padding:4px; background:#fff; border-radius:6px;">
                        <label class="inline" style="margin-left:1rem;"><input type="checkbox" name="remove_logo" value="1"> Remove logo</label>
                    </p>
                <?php endif; ?>
                <label>Upload New Logo (PNG, SVG, JPG, WebP)
                    <input type="file" name="logo_file" accept=".png,.svg,.jpg,.jpeg,.webp">
                </label>
            </div>

            <div class="grid three">
                <label>Sidebar Background
                    <div class="color-pair">
                        <input type="color" value="<?= e($s['sidebar_bg_color'] ?? '#831B30') ?>" oninput="this.nextElementSibling.value=this.value">
                        <input name="sidebar_bg_color" value="<?= e($s['sidebar_bg_color'] ?? '#831B30') ?>">
                    </div>
                </label>
                <label>Text &amp; Icon Color
                    <div class="color-pair">
                        <input type="color" value="<?= e($s['sidebar_text_color'] ?? '#E2E8F0') ?>" oninput="this.nextElementSibling.value=this.value">
                        <input name="sidebar_text_color" value="<?= e($s['sidebar_text_color'] ?? '#E2E8F0') ?>">
                    </div>
                </label>
                <label>Active Link Highlight
                    <div class="color-pair">
                        <input type="color" value="<?= e($s['sidebar_active_color'] ?? '#0284C7') ?>" oninput="this.nextElementSibling.value=this.value">
                        <input name="sidebar_active_color" value="<?= e($s['sidebar_active_color'] ?? '#0284C7') ?>">
                    </div>
                </label>
            </div>

            <button class="btn primary">Save Branding &amp; Theme</button>
        </form>
    </section>

<?php elseif ($tab === 'numbering'): ?>
    <section class="panel">
        <form method="post" action="<?= e(url('/settings/numbering')) ?>" id="numbering-form">
            <?= csrf_field() ?>
            <div class="page-header">
                <div>
                    <h2>Auto-Generated SKU, Category Code &amp; Employee ID Rules</h2>
                    <p class="subtitle">Configure how sequential codes are generated when adding Catalog Items, Categories, and Employees.</p>
                </div>
                <button class="btn primary">&#10003; Save Generator Rules</button>
            </div>

            <label>Global Organization Prefix
                <input id="num_global_prefix" name="global_id_prefix" value="<?= e($s['global_id_prefix'] ?? 'NSG') ?>" maxlength="10">
            </label>

            <div class="grid two">
                <div class="panel" style="background:#f8fafc;">
                    <h3>1. Catalog Item SKU</h3>
                    <label class="inline" style="margin-bottom:0.6rem;">
                        <input type="checkbox" id="num_sku_prefix" name="sku_include_prefix" value="1" <?= ($s['sku_include_prefix'] ?? '1') === '1' ? 'checked' : '' ?>>
                        Include Global Prefix (<code id="lbl_sku_prefix"><?= e($s['global_id_prefix'] ?? 'NSG') ?></code>)
                    </label>
                    <label>Sequence Digits (Zero-Padding)
                        <input type="number" min="2" max="8" id="num_sku_digits" name="sku_seq_digits" value="<?= e($s['sku_seq_digits'] ?? '4') ?>">
                    </label>
                    <small class="subtitle">Preview: <code id="preview_sku"></code></small>
                </div>

                <div class="panel" style="background:#f8fafc;">
                    <h3>2. Item Category Code</h3>
                    <label>Generation Mode
                        <select id="num_cat_mode" name="cat_code_mode">
                            <option value="prefix_seq" <?= ($s['cat_code_mode'] ?? 'prefix_seq') === 'prefix_seq' ? 'selected' : '' ?>>Prefix + Sequence (e.g. CAT-001)</option>
                            <option value="name_slug" <?= ($s['cat_code_mode'] ?? '') === 'name_slug' ? 'selected' : '' ?>>Auto-Slug from Category Name</option>
                        </select>
                    </label>
                    <label>Category Prefix Tag
                        <input id="num_cat_tag" name="cat_code_prefix" value="<?= e($s['cat_code_prefix'] ?? 'CAT') ?>">
                    </label>
                    <label class="inline" style="margin-bottom:0.6rem;">
                        <input type="checkbox" id="num_cat_global" name="cat_include_global_prefix" value="1" <?= ($s['cat_include_global_prefix'] ?? '0') === '1' ? 'checked' : '' ?>>
                        Include Global Prefix
                    </label>
                    <label>Sequence Digits
                        <input type="number" min="2" max="6" id="num_cat_digits" name="cat_seq_digits" value="<?= e($s['cat_seq_digits'] ?? '3') ?>">
                    </label>
                    <small class="subtitle">Preview: <code id="preview_cat"></code></small>
                </div>

                <div class="panel" style="background:#f8fafc;">
                    <h3>3. Employee ID / Code</h3>
                    <label class="inline" style="display:block; margin-bottom:4px;">
                        <input type="checkbox" id="num_emp_global" name="emp_include_global_prefix" value="1" <?= ($s['emp_include_global_prefix'] ?? '1') === '1' ? 'checked' : '' ?>>
                        Include Global Prefix (<code id="lbl_emp_prefix"><?= e($s['global_id_prefix'] ?? 'NSG') ?></code>)
                    </label>
                    <label class="inline" style="display:block; margin-bottom:4px;">
                        <input type="checkbox" id="num_emp_branch" name="emp_include_branch_code" value="1" <?= ($s['emp_include_branch_code'] ?? '1') === '1' ? 'checked' : '' ?>>
                        Include Branch Code (e.g. <code>DHK</code>, <code>NYC</code>)
                    </label>
                    <label class="inline" style="display:block; margin-bottom:0.6rem;">
                        <input type="checkbox" id="num_emp_tag_branch" name="emp_include_tag_with_branch" value="1" <?= ($s['emp_include_tag_with_branch'] ?? '0') === '1' ? 'checked' : '' ?>>
                        Include Employee Tag alongside Branch
                    </label>
                    <div class="grid two">
                        <label>Employee Tag<input id="num_emp_tag" name="emp_code_tag" value="<?= e($s['emp_code_tag'] ?? 'EMP') ?>" placeholder="EMP"></label>
                        <label>Sequence Digits<input type="number" min="2" max="8" id="num_emp_digits" name="emp_seq_digits" value="<?= e($s['emp_seq_digits'] ?? '4') ?>"></label>
                    </div>
                    <small class="subtitle">Preview: <code id="preview_emp"></code></small>
                </div>

                <div class="panel" style="background:#f8fafc;">
                    <h3>4. Department Code</h3>
                    <label>Generation Mode
                        <select id="num_dept_mode" name="dept_code_mode">
                            <option value="name_slug" <?= ($s['dept_code_mode'] ?? 'name_slug') === 'name_slug' ? 'selected' : '' ?>>Auto-Slug from Department Name</option>
                            <option value="prefix_seq" <?= ($s['dept_code_mode'] ?? '') === 'prefix_seq' ? 'selected' : '' ?>>Prefix + Sequence (e.g. DEPT-001)</option>
                        </select>
                    </label>
                    <label>Department Prefix Tag
                        <input id="num_dept_tag" name="dept_code_prefix" value="<?= e($s['dept_code_prefix'] ?? 'DEPT') ?>">
                    </label>
                    <label class="inline" style="margin-bottom:0.6rem;">
                        <input type="checkbox" id="num_dept_global" name="dept_include_global_prefix" value="1" <?= ($s['dept_include_global_prefix'] ?? '0') === '1' ? 'checked' : '' ?>>
                        Include Global Prefix
                    </label>
                    <label>Sequence Digits
                        <input type="number" min="2" max="6" id="num_dept_digits" name="dept_seq_digits" value="<?= e($s['dept_seq_digits'] ?? '3') ?>">
                    </label>
                    <small class="subtitle">Preview: <code id="preview_dept"></code></small>
                </div>
            </div>
        </form>
    </section>

    <script>
        (function() {
            function padSeq(digits, min, max) {
                var d = Math.max(min, Math.min(max, parseInt(digits, 10) || min));
                return '1'.padStart(d, '0');
            }

            function updatePreviews() {
                var globalPrefix = (document.getElementById('num_global_prefix').value.trim() || 'NSG').toUpperCase();
                document.getElementById('lbl_sku_prefix').textContent = globalPrefix;
                document.getElementById('lbl_emp_prefix').textContent = globalPrefix;

                // 1. SKU Preview
                var skuPrefix = document.getElementById('num_sku_prefix').checked;
                var skuSeq = padSeq(document.getElementById('num_sku_digits').value, 2, 8);
                document.getElementById('preview_sku').textContent = (skuPrefix ? globalPrefix + '-' : '') + 'HARDWARE-' + skuSeq;

                // 2. Category Code Preview
                var catMode = document.getElementById('num_cat_mode').value;
                var catTag = (document.getElementById('num_cat_tag').value.trim() || 'CAT').toUpperCase();
                var catGlobal = document.getElementById('num_cat_global').checked;
                var catSeq = padSeq(document.getElementById('num_cat_digits').value, 2, 6);
                if (catMode === 'name_slug') {
                    document.getElementById('preview_cat').textContent = 'HARDWARE (or HARDWARE-' + catSeq + ' if duplicate)';
                } else {
                    document.getElementById('preview_cat').textContent = (catGlobal ? globalPrefix + '-' : '') + catTag + '-' + catSeq;
                }

                // 3. Employee ID Preview
                var empGlobal = document.getElementById('num_emp_global').checked;
                var empBranch = document.getElementById('num_emp_branch').checked;
                var empTagWithBranch = document.getElementById('num_emp_tag_branch').checked;
                var empTag = (document.getElementById('num_emp_tag').value.trim() || 'EMP').toUpperCase();
                var empSeq = padSeq(document.getElementById('num_emp_digits').value, 2, 8);

                var parts = [];
                if (empGlobal) parts.push(globalPrefix);
                if (empBranch) parts.push('DHK');
                if (!empBranch || parts.length === 0) {
                    parts.push(empTag);
                } else if (empTagWithBranch) {
                    parts.push(empTag);
                }
                document.getElementById('preview_emp').textContent = parts.join('-') + '-' + empSeq;

                // 4. Department Code Preview
                var deptMode = document.getElementById('num_dept_mode').value;
                var deptTag = (document.getElementById('num_dept_tag').value.trim() || 'DEPT').toUpperCase();
                var deptGlobal = document.getElementById('num_dept_global').checked;
                var deptSeq = padSeq(document.getElementById('num_dept_digits').value, 2, 6);
                if (deptMode === 'name_slug') {
                    document.getElementById('preview_dept').textContent = 'ITENTERP (or ITENTERP-' + deptSeq + ' if duplicate)';
                } else {
                    document.getElementById('preview_dept').textContent = (deptGlobal ? globalPrefix + '-' : '') + deptTag + '-' + deptSeq;
                }
            }

            var form = document.getElementById('numbering-form');
            if (form) {
                form.addEventListener('input', updatePreviews);
                form.addEventListener('change', updatePreviews);
                updatePreviews();
            }
        })();
    </script>

<?php elseif ($tab === 'timezone'): ?>
    <section class="panel">
        <form method="post" action="<?= e(url('/settings/timezone')) ?>">
            <?= csrf_field() ?>
            <div class="page-header">
                <div>
                    <h2>Date, Time &amp; Timezone</h2>
                    <p class="subtitle">Configure the display timezone and date/time formatting used across logs, reports, and POs.</p>
                </div>
                <button class="btn primary">&#10003; Save Date &amp; Timezone</button>
            </div>

            <label>System Timezone
                <select name="system_timezone">
                    <?php
                    $tzList = ['Asia/Dhaka' => 'Asia/Dhaka (Bangladesh, UTC+06:00)', 'America/New_York' => 'America/New_York (US Eastern)', 'Europe/London' => 'Europe/London (UK)', 'Asia/Dubai' => 'Asia/Dubai (UAE, UTC+04:00)', 'UTC' => 'UTC'];
                    foreach ($tzList as $tzVal => $tzLabel):
                    ?>
                        <option value="<?= e($tzVal) ?>" <?= ($s['system_timezone'] ?? 'Asia/Dhaka') === $tzVal ? 'selected' : '' ?>><?= e($tzLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="grid two">
                <label>Date Format
                    <select name="date_format">
                        <option value="Y-m-d" <?= ($s['date_format'] ?? 'Y-m-d') === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD (e.g. <?= date('Y-m-d') ?>)</option>
                        <option value="d/m/Y" <?= ($s['date_format'] ?? '') === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY (e.g. <?= date('d/m/Y') ?>)</option>
                        <option value="m/d/Y" <?= ($s['date_format'] ?? '') === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY (e.g. <?= date('m/d/Y') ?>)</option>
                    </select>
                </label>
                <label>Time Format
                    <select name="time_format">
                        <option value="h:i:s A" <?= ($s['time_format'] ?? 'h:i:s A') === 'h:i:s A' ? 'selected' : '' ?>>12-Hour AM/PM with Seconds (09:50:34 PM)</option>
                        <option value="H:i:s" <?= ($s['time_format'] ?? '') === 'H:i:s' ? 'selected' : '' ?>>24-Hour Format (21:50:34)</option>
                    </select>
                </label>
            </div>
            <div class="alert" style="background:#f8fafc; border:1px solid #e2e8f0;">
                &#9432; <strong>Current Active Preview:</strong> <?= date(($s['date_format'] ?? 'Y-m-d') . ' ' . ($s['time_format'] ?? 'h:i:s A')) ?> (<?= e($s['system_timezone'] ?? 'Asia/Dhaka') ?>)
            </div>
        </form>
    </section>

<?php elseif ($tab === 'currency'): ?>
    <div class="grid two">
        <section class="panel">
            <form method="post" action="<?= e(url('/settings/currency')) ?>">
                <?= csrf_field() ?>
                <div class="page-header">
                    <h2>Base Currency &amp; Exchange Rates</h2>
                    <button class="btn primary">&#10003; Save Rates</button>
                </div>
                <div class="grid two">
                    <label>Base Reporting Currency
                        <select name="base_currency">
                            <?php foreach ($currencies as $c): ?>
                                <option value="<?= e($c['code']) ?>" <?= ($s['base_currency'] ?? 'USD') === $c['code'] ? 'selected' : '' ?>><?= e($c['code']) ?> — <?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="inline" style="margin-top:1.6rem;">
                        <input type="checkbox" name="multi_currency_display" value="1" <?= ($s['multi_currency_display'] ?? '1') === '1' ? 'checked' : '' ?>>
                        Multi-Currency Display
                    </label>
                </div>
                <hr>
                <h3>Add New Conversion Rate (1 <?= e($s['base_currency'] ?? 'USD') ?> = ?)</h3>
                <div class="grid two">
                    <label>Target Currency
                        <select name="new_currency_code">
                            <option value="">-- Optional --</option>
                            <?php foreach ($currencies as $c): ?><option value="<?= e($c['code']) ?>"><?= e($c['code']) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label>Exchange Rate<input type="number" step="0.000001" min="0" name="new_currency_rate" placeholder="e.g. 119.50"></label>
                </div>
            </form>
            <hr>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>To</th>
                            <th>Rate</th>
                            <th>Effective Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exchangeRates as $er): ?>
                            <tr>
                                <td><strong><?= e($er['from_currency']) ?></strong></td>
                                <td><strong><?= e($er['to_currency']) ?></strong></td>
                                <td><?= e(rtrim(rtrim((string)$er['rate'], '0'), '.')) ?></td>
                                <td><?= e($er['effective_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <h2>How Base Currency Works</h2>
            <ul>
                <li><strong>Reports &amp; Dashboard:</strong> Multi-branch PO summaries consolidate into your Base Currency (<code><?= e($s['base_currency'] ?? 'USD') ?></code>).</li>
                <li><strong>Locked Historical Rates:</strong> Existing Purchase Orders freeze their exchange rate at creation so historical audits never change.</li>
            </ul>
        </section>
    </div>

<?php elseif ($tab === 'forms'): ?>
    <section class="panel">
        <form method="post" action="<?= e(url('/settings/forms')) ?>">
            <?= csrf_field() ?>
            <div class="page-header">
                <h2>PDF Forms, Purchase Orders &amp; Document Styling</h2>
                <button class="btn primary">&#10003; Save Forms &amp; Styles</button>
            </div>
            <div class="grid three">
                <label>PDF Organization Header Title<input name="pdf_header_title" value="<?= e($s['pdf_header_title'] ?? '') ?>" placeholder="Leave blank to inherit Application Title"></label>
                <label>Subtitle &amp; Divider Color<input name="pdf_subtitle_color" value="<?= e($s['pdf_subtitle_color'] ?? '#0D6EFD') ?>"></label>
                <label>Table Header Background<input name="pdf_table_header_bg" value="<?= e($s['pdf_table_header_bg'] ?? '#F1F3F5') ?>"></label>
            </div>
            <label>Optional PDF Footer Disclaimer / Note
                <input name="pdf_footer_note" value="<?= e($s['pdf_footer_note'] ?? '') ?>">
            </label>
            <hr>
            <div class="grid two">
                <label>Purchase Order Heading Color<input name="po_heading_color" value="<?= e($s['po_heading_color'] ?? '#831B30') ?>"></label>
                <label>Default Authorized Signatory<input name="po_signatory_name" value="<?= e($s['po_signatory_name'] ?? 'Procurement Director') ?>"></label>
            </div>
            <label>Standard Purchase Order Terms &amp; Declaration
                <textarea name="po_declaration_text" rows="3"><?= e($s['po_declaration_text'] ?? '') ?></textarea>
            </label>
        </form>
    </section>

<?php elseif ($tab === 'templates'): ?>
    <section class="panel">
        <h2>Email Notification Templates</h2>
        <form method="post" action="<?= e(url('/settings/templates')) ?>" class="grid two">
            <?= csrf_field() ?>
            <label>Template Key<input name="template_key" placeholder="e.g. po_dispatched" required></label>
            <label>Subject Template<input name="subject" placeholder="Purchase Order {{po_number}}" required></label>
            <label>Body HTML Template<textarea name="body_html" rows="3" required></textarea></label>
            <div><button class="btn primary">Save Email Template</button></div>
        </form>
        <hr>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Subject</th>
                        <th>Active</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $t): ?>
                        <tr>
                            <td><code><?= e($t['template_key']) ?></code></td>
                            <td><?= e($t['subject_template']) ?></td>
                            <td><?= (int)$t['is_active'] === 1 ? 'Yes' : 'No' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

<?php elseif ($tab === 'smtp'): ?>
    <div class="grid two">
        <section class="panel">
            <form method="post" action="<?= e(url('/settings/smtp')) ?>">
                <?= csrf_field() ?>
                <div class="page-header">
                    <h2>Outgoing SMTP Mail Configuration</h2>
                    <button class="btn primary">&#10003; Save SMTP</button>
                </div>
                <div class="grid two">
                    <label>SMTP Host<input name="smtp_host" value="<?= e($s['smtp_host'] ?? 'smtp.gmail.com') ?>"></label>
                    <label>Port<input type="number" name="smtp_port" value="<?= e($s['smtp_port'] ?? '587') ?>"></label>
                </div>
                <div class="grid two">
                    <label>SMTP Username<input name="smtp_username" value="<?= e($s['smtp_username'] ?? '') ?>"></label>
                    <label>SMTP Password / App Password<input type="password" name="smtp_password" value="<?= e($s['smtp_password'] ?? '') ?>"></label>
                </div>
                <div class="grid three">
                    <label>Encryption
                        <select name="smtp_encryption">
                            <option value="tls" <?= ($s['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (Port 587)</option>
                            <option value="ssl" <?= ($s['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (Port 465)</option>
                            <option value="" <?= ($s['smtp_encryption'] ?? '') === '' ? 'selected' : '' ?>>None</option>
                        </select>
                    </label>
                    <label>From Email Address<input type="email" name="smtp_from_email" value="<?= e($s['smtp_from_email'] ?? '') ?>"></label>
                    <label>From Display Name<input name="smtp_from_name" value="<?= e($s['smtp_from_name'] ?? 'NSG Procurement Desk') ?>"></label>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="page-header">
                <h2>Send Live Test Email &amp; Pending Queue (<?= (int)$pendingQueueCount ?>)</h2>
                <?php if ($queueTotal > 0): ?>
                    <div class="inline">
                        <form method="post" action="<?= e(url('/settings/smtp-queue/clear')) ?>" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="scope" value="completed">
                            <button class="btn" onclick="return confirm('Clear all Sent and Failed email logs?');">Clear Logs</button>
                        </form>
                        <form method="post" action="<?= e(url('/settings/smtp-queue/clear')) ?>" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="scope" value="all">
                            <button class="btn" style="color:#b91c1c; border-color:#fecaca;" onclick="return confirm('Clear the entire email queue including any pending emails?');">Clear All</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= e(url('/settings/smtp-test')) ?>">
                <?= csrf_field() ?>
                <label>Recipient Email for Testing<input type="email" name="test_recipient" placeholder="you@noorstaffing.com" required></label>
                <button class="btn full-btn">Send Real Test Email</button>
            </form>

            <?php if (!empty($queue)): ?>
                <hr>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Recipient</th>
                                <th>Status</th>
                                <th>Sent / Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($queue as $mq): ?>
                                <tr>
                                    <td><?= e($mq['recipient_email']) ?></td>
                                    <td>
                                        <?php if ($mq['status'] === 'sent'): ?>
                                            <span class="badge success">Sent</span>
                                        <?php elseif ($mq['status'] === 'failed'): ?>
                                            <span class="badge danger">Failed</span>
                                        <?php else: ?>
                                            <span class="badge"><?= e(ucfirst((string)$mq['status'])) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="subtitle"><?= e($mq['sent_at'] ?? ($mq['last_error'] ?? 'Queued')) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($queueTotalPages > 1): ?>
                    <div class="page-header" style="margin-top:0.75rem; margin-bottom:0;">
                        <small class="subtitle">Page <?= (int)$queuePage ?> of <?= (int)$queueTotalPages ?> (<?= (int)$queueTotal ?> total)</small>
                        <div class="inline">
                            <?php if ($queuePage > 1): ?>
                                <a class="btn" href="<?= e(url('/settings?tab=smtp&qpage=' . ($queuePage - 1))) ?>">&larr; Prev</a>
                            <?php endif; ?>
                            <?php if ($queuePage < $queueTotalPages): ?>
                                <a class="btn" href="<?= e(url('/settings?tab=smtp&qpage=' . ($queuePage + 1))) ?>">Next &rarr;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>

<?php elseif ($tab === 'sso'): ?>
    <section class="panel">
        <form method="post" action="<?= e(url('/settings/sso')) ?>">
            <?= csrf_field() ?>
            <div class="page-header">
                <div>
                    <h2>Google &amp; Microsoft Account Sign-In</h2>
                    <p class="subtitle">Allow pre-approved users (Google Workspace or Microsoft 365) to sign in via OAuth 2.0.</p>
                </div>
                <button class="btn primary">&#10003; Save SSO Settings</button>
            </div>

            <div class="panel" style="background:#f8fafc;">
                <div class="page-header">
                    <strong>Sign in with Google (Personal Gmail &amp; Workspace)</strong>
                    <label class="inline"><input type="checkbox" name="sso_google_enabled" value="1" <?= ($s['sso_google_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> Enabled</label>
                </div>
                <div class="grid two">
                    <label>Google Client ID<input name="sso_google_client_id" value="<?= e($s['sso_google_client_id'] ?? '') ?>" placeholder="xxxx.apps.googleusercontent.com"></label>
                    <label>Google Client Secret<input type="password" name="sso_google_client_secret" value="<?= e($s['sso_google_client_secret'] ?? '') ?>" placeholder="GOCSPX-..."></label>
                </div>
                <small class="subtitle">Authorized Redirect URI: <code><?= e(url('/oauth/callback/google')) ?></code></small>
            </div>

            <div class="panel" style="background:#f8fafc;">
                <div class="page-header">
                    <strong>Sign in with Microsoft (Outlook / M365)</strong>
                    <label class="inline"><input type="checkbox" name="sso_microsoft_enabled" value="1" <?= ($s['sso_microsoft_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> Enabled</label>
                </div>
                <div class="grid three">
                    <label>Application (Client) ID<input name="sso_microsoft_client_id" value="<?= e($s['sso_microsoft_client_id'] ?? '') ?>"></label>
                    <label>Client Secret Value<input type="password" name="sso_microsoft_client_secret" value="<?= e($s['sso_microsoft_client_secret'] ?? '') ?>"></label>
                    <label>Tenant Mode<input name="sso_microsoft_tenant" value="<?= e($s['sso_microsoft_tenant'] ?? 'common') ?>"></label>
                </div>
                <small class="subtitle">Redirect URI: <code><?= e(url('/oauth/callback/microsoft')) ?></code></small>
            </div>
        </form>
    </section>

<?php elseif ($tab === 'api'): ?>
    <div class="grid two">
        <section class="panel">
            <h2>Generate New API Key</h2>
            <form method="post" action="<?= e(url('/settings/api-keys')) ?>">
                <?= csrf_field() ?>
                <label>Integration / Client Name *<input name="client_name" placeholder="e.g. SAP ERP Sync, Asset Portal, HR Sync" required></label>
                <div class="grid two">
                    <label>RBAC Role *
                        <select name="role_id" required>
                            <?php foreach ($roles as $rl): ?><option value="<?= e($rl['id']) ?>"><?= e($rl['name']) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label>Rate Limit (Req / Min)<input type="number" name="rate_limit_per_min" value="120"></label>
                </div>
                <label>Granular Permission Scopes</label>
                <div style="margin-bottom:0.75rem;">
                    <?php foreach (['procurement:read', 'procurement:write', 'vendors:read', 'vendors:write', 'invoices:read', 'reports:summary'] as $sc): ?>
                        <label class="inline" style="display:block; margin-bottom:4px;"><input type="checkbox" name="scopes[]" value="<?= e($sc) ?>" checked> <code><?= e($sc) ?></code></label>
                    <?php endforeach; ?>
                </div>
                <div class="grid two">
                    <label>Allowed IP Allowlist (Optional)<input name="allowed_ips" placeholder="e.g. 192.168.1.10"></label>
                    <label>Expiry Date (Optional)<input type="date" name="expires_at"></label>
                </div>
                <button class="btn primary full-btn">Generate API Key</button>
            </form>
        </section>

        <section class="panel">
            <h2>Issued API Keys &amp; RBAC Policies (<?= count($apiKeys) ?>)</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Client / Prefix</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apiKeys as $ak): ?>
                            <tr>
                                <td><strong><?= e($ak['client_name']) ?></strong><br><code><?= e($ak['key_prefix']) ?></code></td>
                                <td><?= e($ak['role_name']) ?></td>
                                <td><?= (int)$ak['is_active'] === 1 ? '<span class="badge success">Active</span>' : '<span class="badge danger">Revoked</span>' ?></td>
                                <td>
                                    <?php if ((int)$ak['is_active'] === 1): ?>
                                        <form method="post" action="<?= e(url('/settings/api-keys/' . $ak['id'] . '/revoke')) ?>">
                                            <?= csrf_field() ?>
                                            <button class="btn">Revoke</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

<?php elseif ($tab === 'backup'): ?>
    <div class="grid two">
        <div>
            <section class="panel">
                <h2>On-Demand Database Snapshot</h2>
                <p class="subtitle">Generates a complete SQL backup archive of all tables and records inside <code>storage/backups/</code>.</p>
                <form method="post" action="<?= e(url('/settings/backup/create')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn primary full-btn">&#9729; Create Backup Now</button>
                </form>
            </section>

            <section class="panel">
                <h2>Upload &amp; Restore Snapshot</h2>
                <p class="subtitle">Restore from a downloaded <code>.sql</code> database backup dump.</p>
                <form method="post" action="<?= e(url('/settings/backup/restore')) ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <label>Select Backup File (<code>.sql</code>)
                        <input type="file" name="backup_file" accept=".sql" required>
                    </label>
                    <button class="btn full-btn" style="background:#f59e0b; border-color:#d97706; color:#fff;" onclick="return confirm('Restore database from this SQL file? Current data will be overwritten.');">
                        &#8634; Upload &amp; Restore Database
                    </button>
                </form>
            </section>

            <section class="panel">
                <h2>Automated Schedule &amp; Retention</h2>
                <form method="post" action="<?= e(url('/settings/backup/policy')) ?>">
                    <?= csrf_field() ?>
                    <label>Auto-Backup Frequency
                        <select name="backup_frequency">
                            <option value="daily" <?= ($s['backup_frequency'] ?? 'daily') === 'daily' ? 'selected' : '' ?>>Daily</option>
                            <option value="weekly" <?= ($s['backup_frequency'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                        </select>
                    </label>
                    <div class="grid two">
                        <label>Preferred Hour<input name="backup_hour" value="<?= e($s['backup_hour'] ?? '02:00') ?>"></label>
                        <label>Retention Policy (Days)<input type="number" name="backup_retention_days" value="<?= e($s['backup_retention_days'] ?? '30') ?>"></label>
                    </div>
                    <button class="btn full-btn">Save Backup Policy</button>
                </form>
            </section>
        </div>

        <section class="panel">
            <h2>Stored Backup Archives (<?= count($archives) ?>)</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Archive Filename</th>
                            <th>Created</th>
                            <th>Size</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($archives)): ?>
                            <tr>
                                <td colspan="4" class="subtitle">No backup snapshots created yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($archives as $ar): ?>
                                <tr>
                                    <td><code><?= e($ar['name']) ?></code></td>
                                    <td><?= e($ar['date']) ?></td>
                                    <td><?= e($ar['size']) ?></td>
                                    <td>
                                        <div class="inline">
                                            <form method="post" action="<?= e(url('/settings/backup/restore')) ?>" class="inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="file" value="<?= e($ar['name']) ?>">
                                                <button class="btn" onclick="return confirm('Restore database from <?= e($ar['name']) ?>?');">Restore</button>
                                            </form>
                                            <a class="btn" href="<?= e(url('/settings/backup/download?file=' . urlencode($ar['name']))) ?>" title="Download SQL">&#11015; Download</a>
                                            <form method="post" action="<?= e(url('/settings/backup/delete')) ?>" class="inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="file" value="<?= e($ar['name']) ?>">
                                                <button class="btn" style="color:#b91c1c; border-color:#fecaca;" onclick="return confirm('Delete backup <?= e($ar['name']) ?> permanently?');">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

<?php elseif ($tab === 'master_data'): ?>
    <div class="grid two">
        <section class="panel">
            <h2>Item Categories (<?= count($categories) ?>)</h2>
            <form method="post" action="<?= e(url('/settings/master/categories')) ?>">
                <?= csrf_field() ?>
                <div class="grid two">
                    <label>Category Name *<input name="name" placeholder="e.g. Computer Hardware" required></label>
                    <label>Category Code <small class="subtitle">(Auto if blank)</small>
                        <input name="category_code" placeholder="Auto: <?= e(\App\Services\CodeGeneratorService::nextCategoryCode()) ?>">
                    </label>
                </div>
                <label>Parent Category (Optional)
                    <select name="parent_id">
                        <option value="">Top-level Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat['id']) ?>"><?= e($cat['name']) ?> (<?= e($cat['category_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="btn primary">Add Category</button>
            </form>
            <hr>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><code><?= e($cat['category_code']) ?></code></td>
                                <td><?= e($cat['name']) ?></td>
                                <td><?= (int)$cat['is_active'] === 1 ? '<span class="badge success">Active</span>' : '<span class="badge">Inactive</span>' ?></td>
                                <td>
                                    <form method="post" action="<?= e(url('/settings/master/categories/' . $cat['id'] . '/delete')) ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <button class="btn" style="color:#b91c1c;" onclick="return confirm('Delete this category?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div>
            <section class="panel">
                <h2>Units of Measure (<?= count($units) ?>)</h2>
                <form method="post" action="<?= e(url('/settings/master/units')) ?>" class="grid two">
                    <?= csrf_field() ?>
                    <label>Unit Code *<input name="unit_code" placeholder="e.g. EA, BOX, KG" required></label>
                    <label>Unit Name *<input name="name" placeholder="e.g. Each, Box, Kilogram" required></label>
                    <div><button class="btn primary">Add Unit</button></div>
                </form>
                <hr>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($units as $u): ?>
                                <tr>
                                    <td><code><?= e($u['unit_code']) ?></code></td>
                                    <td><?= e($u['name']) ?></td>
                                    <td>
                                        <form method="post" action="<?= e(url('/settings/master/units/' . $u['id'] . '/delete')) ?>" class="inline">
                                            <?= csrf_field() ?>
                                            <button class="btn" style="color:#b91c1c;" onclick="return confirm('Delete this unit?');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel">
                <h2>Payment Terms (<?= count($paymentTerms) ?>)</h2>
                <form method="post" action="<?= e(url('/settings/master/terms')) ?>" class="grid two">
                    <?= csrf_field() ?>
                    <label>Term Name *<input name="term_name" placeholder="e.g. Net 30" required></label>
                    <label>Net Days *<input type="number" min="0" name="net_days" value="30" required></label>
                    <label>Description<input name="description" placeholder="Payment due within 30 days"></label>
                    <div style="margin-top:1.35rem;"><button class="btn primary">Add Payment Term</button></div>
                </form>
                <hr>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Term</th>
                                <th>Days</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paymentTerms as $pt): ?>
                                <tr>
                                    <td><strong><?= e($pt['term_name']) ?></strong></td>
                                    <td><?= e((string)$pt['net_days']) ?></td>
                                    <td><?= e($pt['description'] ?? '—') ?></td>
                                    <td>
                                        <form method="post" action="<?= e(url('/settings/master/terms/' . $pt['id'] . '/delete')) ?>" class="inline">
                                            <?= csrf_field() ?>
                                            <button class="btn" style="color:#b91c1c;" onclick="return confirm('Delete this term?');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

<?php elseif ($tab === 'geo'): ?>
    <div class="grid two">
        <section class="panel">
            <h2>Countries (<?= count($countries) ?>)</h2>
            <form method="post" action="<?= e(url('/settings/geo/countries')) ?>">
                <?= csrf_field() ?>
                <label>Country Name *<input name="name" placeholder="e.g. Saudi Arabia" required></label>
                <div class="grid two">
                    <label>ISO-2 Code *<input name="iso2" maxlength="2" placeholder="e.g. SA" style="text-transform:uppercase;" required></label>
                    <label>ISO-3 Code *<input name="iso3" maxlength="3" placeholder="e.g. SAU" style="text-transform:uppercase;" required></label>
                </div>
                <button class="btn primary">Add Country</button>
            </form>
            <hr>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ISO-2</th>
                            <th>ISO-3</th>
                            <th>Country Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($countries as $ct): ?>
                            <tr>
                                <td><code><?= e($ct['iso2']) ?></code></td>
                                <td><code><?= e($ct['iso3']) ?></code></td>
                                <td><strong><?= e($ct['name']) ?></strong></td>
                                <td>
                                    <form method="post" action="<?= e(url('/settings/geo/countries/' . $ct['id'] . '/delete')) ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <button class="btn" style="color:#b91c1c;" onclick="return confirm('Delete <?= e($ct['name']) ?>?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <h2>Currencies (<?= count($allCurrencies) ?>)</h2>
            <form method="post" action="<?= e(url('/settings/geo/currencies')) ?>">
                <?= csrf_field() ?>
                <div class="grid three">
                    <label>Code (ISO) *<input name="code" maxlength="3" placeholder="e.g. SAR" style="text-transform:uppercase;" required></label>
                    <label>Symbol *<input name="symbol" maxlength="8" placeholder="e.g. ﷼" required></label>
                    <label>Decimals<input type="number" min="0" max="4" name="minor_unit" value="2"></label>
                </div>
                <label>Currency Name *<input name="name" placeholder="e.g. Saudi Riyal" required></label>
                <button class="btn primary">Save Currency</button>
            </form>
            <hr>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Symbol</th>
                            <th>Currency Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allCurrencies as $cur): ?>
                            <tr>
                                <td><code><?= e($cur['code']) ?></code></td>
                                <td><?= e($cur['symbol']) ?></td>
                                <td><?= e($cur['name']) ?></td>
                                <td><?= (int)$cur['is_active'] === 1 ? '<span class="badge success">Active</span>' : '<span class="badge">Inactive</span>' ?></td>
                                <td>
                                    <div class="inline">
                                        <form method="post" action="<?= e(url('/settings/geo/currencies/' . urlencode($cur['code']) . '/toggle')) ?>" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="is_active" value="<?= (int)$cur['is_active'] === 1 ? 0 : 1 ?>">
                                            <button class="btn"><?= (int)$cur['is_active'] === 1 ? 'Disable' : 'Enable' ?></button>
                                        </form>
                                        <form method="post" action="<?= e(url('/settings/geo/currencies/' . urlencode($cur['code']) . '/delete')) ?>" class="inline">
                                            <?= csrf_field() ?>
                                            <button class="btn" style="color:#b91c1c;" onclick="return confirm('Delete <?= e($cur['code']) ?>?');">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
<?php endif; ?>