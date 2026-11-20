<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<string, mixed> $vendor
 * @var array<int, array<string, mixed>> $contacts
 * @var array<int, array<string, mixed>> $addresses
 * @var array<int, array<string, mixed>> $attachments
 * @var array<string, mixed> $performanceSummary
 * @var array<int, array<string, mixed>> $reviews
 * @var array<int, array<string, mixed>> $purchaseOrders
 * @var array<int, array<string, mixed>> $quotations
 * @var array<int, array<string, mixed>> $countries
 */
$flash = $flash ?? null;
$error = $error ?? null;
$vendor = $vendor ?? [];
$contacts = $contacts ?? [];
$addresses = $addresses ?? [];
$attachments = $attachments ?? [];
$performanceSummary = $performanceSummary ?? [];
$reviews = $reviews ?? [];
$purchaseOrders = $purchaseOrders ?? [];
$quotations = $quotations ?? [];
$countries = $countries ?? [];
$canEdit = can('vendors.edit') || can('vendors.manage');
?>
<p><a href="<?= e(url('/vendors')) ?>">&larr; Back to Vendors</a></p>
<h1><?= e($vendor['legal_name']) ?> <code>(<?= e($vendor['vendor_code']) ?>)</code></h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="grid three">
    <section class="panel">
        <h3>Status</h3>
        <p><strong><?= e(strtoupper((string)$vendor['status'])) ?></strong></p>
    </section>
    <section class="panel">
        <h3>Overall Performance</h3>
        <p>
            <strong><?= !empty($performanceSummary['overall_score']) ? e((string)$performanceSummary['overall_score']) . ' / 5.00' : 'No reviews yet' ?></strong>
            (<?= (int)($performanceSummary['review_count'] ?? 0) ?> reviews)
        </p>
    </section>
    <section class="panel">
        <h3>Activity</h3>
        <p><?= count($purchaseOrders) ?> Purchase Orders · <?= count($quotations) ?> Quotations</p>
    </section>
</div>

<div class="grid two">
    <section class="panel">
        <h2>Company Profile</h2>
        <form method="post" action="<?= e(url('/vendors/' . $vendor['id'] . '/update')) ?>">
            <?= csrf_field() ?>
            <label>Vendor Code<input name="vendor_code" value="<?= e($vendor['vendor_code']) ?>" <?= $canEdit ? 'required' : 'readonly' ?>></label>
            <label>Legal Name<input name="legal_name" value="<?= e($vendor['legal_name']) ?>" <?= $canEdit ? 'required' : 'readonly' ?>></label>
            <label>Tax Registration No.<input name="tax_registration_no" value="<?= e($vendor['tax_registration_no'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>></label>
            <label>Website<input type="url" name="website" value="<?= e($vendor['website'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>></label>
            <label>Status
                <select name="status" <?= $canEdit ? '' : 'disabled' ?>>
                    <?php foreach (['pending', 'approved', 'blocked', 'inactive'] as $st): ?>
                        <option value="<?= e($st) ?>" <?= $vendor['status'] === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Notes<textarea name="notes" rows="3" <?= $canEdit ? '' : 'readonly' ?>><?= e($vendor['notes'] ?? '') ?></textarea></label>
            <?php if ($canEdit): ?>
                <button class="btn primary">Update Vendor</button>
            <?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <h2>Compliance &amp; Vendor Documents</h2>
        <?php if ($canEdit): ?>
            <form method="post" action="<?= e(url('/vendors/' . $vendor['id'] . '/documents')) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <label>Document Type
                    <select name="document_kind">
                        <option value="Tax / W-9 Certificate">Tax / W-9 Certificate</option>
                        <option value="Business Registration">Business Registration</option>
                        <option value="Master Service Agreement / NDA">Master Service Agreement / NDA</option>
                        <option value="Bank / Wire Details">Bank / Wire Details</option>
                        <option value="Product Catalog / Price Sheet">Product Catalog / Price Sheet</option>
                    </select>
                </label>
                <label>File (PDF, Office, Image, CSV — max 15 MB)
                    <input type="file" name="document" required>
                </label>
                <button class="btn primary">Upload Document</button>
            </form>
            <hr>
        <?php endif; ?>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Kind</th>
                        <th>Uploaded</th>
                        <th>Download</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attachments as $att): ?>
                        <tr>
                            <td><?= e($att['original_name']) ?></td>
                            <td><?= e($att['document_kind'] ?? 'Document') ?></td>
                            <td><?= e($att['created_at']) ?></td>
                            <td><a class="btn" href="<?= e(url('/vendors/' . $vendor['id'] . '/documents/' . $att['id'])) ?>">Download</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="grid two">
    <section class="panel">
        <h2>Vendor Contacts (<?= count($contacts) ?>)</h2>
        <?php if ($canEdit): ?>
            <form method="post" action="<?= e(url('/vendors/' . $vendor['id'] . '/contacts')) ?>">
                <?= csrf_field() ?>
                <label>Name<input name="contact_name" required></label>
                <label>Email<input type="email" name="email"></label>
                <label>Phone<input name="phone"></label>
                <label>Job Title<input name="job_title"></label>
                <label class="inline"><input type="checkbox" name="is_primary" value="1"> Primary Contact</label><br>
                <button class="btn">Add Contact</button>
            </form>
            <hr>
        <?php endif; ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email / Phone</th>
                        <th>Role</th><?php if ($canEdit): ?><th>Action</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $c): ?>
                        <tr>
                            <td><strong><?= e($c['contact_name']) ?></strong> <?= (int)$c['is_primary'] === 1 ? '(Primary)' : '' ?></td>
                            <td><?= e($c['email'] ?? '—') ?><br><small><?= e($c['phone'] ?? '') ?></small></td>
                            <td><?= e($c['job_title'] ?? '—') ?></td>
                            <?php if ($canEdit): ?>
                                <td>
                                    <form method="post" action="<?= e(url('/vendors/' . $vendor['id'] . '/contacts/' . $c['id'] . '/delete')) ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn">Remove</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <h2>Vendor Addresses (<?= count($addresses) ?>)</h2>
        <?php if ($canEdit): ?>
            <form method="post" action="<?= e(url('/vendors/' . $vendor['id'] . '/addresses')) ?>">
                <?= csrf_field() ?>
                <label>Type
                    <select name="address_type">
                        <option value="office">Office</option>
                        <option value="billing">Billing</option>
                        <option value="shipping">Shipping</option>
                        <option value="other">Other</option>
                    </select>
                </label>
                <label>Country
                    <select name="country_id" required>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= e($country['id']) ?>"><?= e($country['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Address Line 1<input name="address_line1" required></label>
                <label>Address Line 2<input name="address_line2"></label>
                <label>City<input name="city"></label>
                <label>Region / State<input name="region"></label>
                <label>Postal Code<input name="postal_code"></label>
                <button class="btn">Add Address</button>
            </form>
            <hr>
        <?php endif; ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Address</th><?php if ($canEdit): ?><th>Action</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($addresses as $addr): ?>
                        <tr>
                            <td><strong><?= e(ucfirst((string)$addr['address_type'])) ?></strong></td>
                            <td>
                                <?= e($addr['address_line1']) ?>
                                <?= !empty($addr['address_line2']) ? ', ' . e($addr['address_line2']) : '' ?><br>
                                <small><?= e(trim(($addr['city'] ?? '') . ' ' . ($addr['region'] ?? '') . ' ' . ($addr['postal_code'] ?? ''))) ?> · <?= e($addr['country_name']) ?></small>
                            </td>
                            <?php if ($canEdit): ?>
                                <td>
                                    <form method="post" action="<?= e(url('/vendors/' . $vendor['id'] . '/addresses/' . $addr['id'] . '/delete')) ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn">Remove</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="grid two">
    <section class="panel">
        <h2>Performance Scorecard &amp; Reviews</h2>
        <p>
            Delivery: <strong><?= e((string)($performanceSummary['avg_delivery'] ?? '—')) ?></strong> ·
            Quality: <strong><?= e((string)($performanceSummary['avg_quality'] ?? '—')) ?></strong> ·
            Pricing: <strong><?= e((string)($performanceSummary['avg_pricing'] ?? '—')) ?></strong> ·
            Communication: <strong><?= e((string)($performanceSummary['avg_communication'] ?? '—')) ?></strong>
        </p>
        <?php if ($canEdit): ?>
            <form method="post" action="<?= e(url('/vendors/' . $vendor['id'] . '/reviews')) ?>">
                <?= csrf_field() ?>
                <label>Related Purchase Order (optional)
                    <select name="purchase_order_id">
                        <option value="">General Vendor Evaluation</option>
                        <?php foreach ($purchaseOrders as $po): ?>
                            <option value="<?= e($po['id']) ?>"><?= e($po['po_number']) ?> (<?= e($po['order_date']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="grid two">
                    <label>Delivery (1–5)<input type="number" name="delivery_score" min="1" max="5" value="5" required></label>
                    <label>Quality (1–5)<input type="number" name="quality_score" min="1" max="5" value="5" required></label>
                    <label>Pricing (1–5)<input type="number" name="pricing_score" min="1" max="5" value="5" required></label>
                    <label>Communication (1–5)<input type="number" name="communication_score" min="1" max="5" value="5" required></label>
                </div>
                <label>Review Comments<textarea name="comments" rows="2"></textarea></label>
                <button class="btn primary">Record Performance Review</button>
            </form>
            <hr>
        <?php endif; ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>PO</th>
                        <th>Scores (D/Q/P/C)</th>
                        <th>Reviewer</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $rev): ?>
                        <tr>
                            <td><?= e(substr((string)$rev['created_at'], 0, 10)) ?></td>
                            <td><?= e($rev['po_number'] ?? 'General') ?></td>
                            <td><?= e($rev['delivery_score'] . '/' . $rev['quality_score'] . '/' . $rev['pricing_score'] . '/' . $rev['communication_score']) ?></td>
                            <td><?= e($rev['reviewer_name']) ?></td>
                            <td><?= e($rev['comments'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <h2>Purchase Order &amp; Quotation History</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>PO #</th>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchaseOrders as $po): ?>
                        <tr>
                            <td><a href="<?= e(url('/purchase_orders/' . $po['id'])) ?>"><?= e($po['po_number']) ?></a></td>
                            <td><?= e($po['order_date']) ?></td>
                            <td><?= e($po['branch_name']) ?></td>
                            <td><?= money($po['total_amount'], (string)$po['currency_code']) ?></td>
                            <td><?= e($po['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>