<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class VendorModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function search(?string $q = null, ?string $status = null): array
    {
        $params = [];
        $sql = "SELECT v.*,
                       (SELECT vc.contact_name FROM vendor_contacts vc WHERE vc.vendor_id = v.id ORDER BY vc.is_primary DESC, vc.id ASC LIMIT 1) AS primary_contact,
                       (SELECT vc.email FROM vendor_contacts vc WHERE vc.vendor_id = v.id ORDER BY vc.is_primary DESC, vc.id ASC LIMIT 1) AS primary_email,
                       (SELECT COUNT(*) FROM purchase_orders po WHERE po.vendor_id = v.id AND po.status <> 'cancelled') AS po_count,
                       (SELECT ROUND(AVG((delivery_score + quality_score + pricing_score + communication_score) / 4), 2)
                        FROM vendor_performance_reviews vpr WHERE vpr.vendor_id = v.id) AS avg_rating
                FROM vendors v
                WHERE 1=1";

        if ($status !== null && $status !== '') {
            $sql .= ' AND v.status = ?';
            $params[] = $status;
        }
        if ($q !== null && trim($q) !== '') {
            $like = '%' . trim($q) . '%';
            $sql .= ' AND (v.vendor_code LIKE ? OR v.legal_name LIKE ? OR v.tax_registration_no LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY v.legal_name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM vendors WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data, ?array $primaryContact = null): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO vendors (vendor_code, legal_name, tax_registration_no, website, status, notes)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                strtoupper($data['vendor_code']),
                $data['legal_name'],
                $data['tax_registration_no'] !== '' ? $data['tax_registration_no'] : null,
                $data['website'] !== '' ? $data['website'] : null,
                $data['status'],
                $data['notes'] !== '' ? $data['notes'] : null,
            ]);
            $vendorId = (int)$this->db->lastInsertId();

            if ($primaryContact && trim((string)($primaryContact['contact_name'] ?? '')) !== '') {
                $this->addContact($vendorId, [
                    'contact_name' => trim((string)$primaryContact['contact_name']),
                    'email'        => trim((string)($primaryContact['email'] ?? '')),
                    'phone'        => trim((string)($primaryContact['phone'] ?? '')),
                    'job_title'    => trim((string)($primaryContact['job_title'] ?? '')),
                    'is_primary'   => 1,
                ]);
            }

            $this->db->commit();
            return $vendorId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE vendors
             SET vendor_code = ?, legal_name = ?, tax_registration_no = ?, website = ?, status = ?, notes = ?
             WHERE id = ?'
        );
        $stmt->execute([
            strtoupper($data['vendor_code']),
            $data['legal_name'],
            $data['tax_registration_no'] !== '' ? $data['tax_registration_no'] : null,
            $data['website'] !== '' ? $data['website'] : null,
            $data['status'],
            $data['notes'] !== '' ? $data['notes'] : null,
            $id,
        ]);
    }

    public function contacts(int $vendorId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM vendor_contacts WHERE vendor_id = ? ORDER BY is_primary DESC, contact_name ASC'
        );
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll();
    }

    public function addContact(int $vendorId, array $data): int
    {
        if (!empty($data['is_primary'])) {
            $this->db->prepare('UPDATE vendor_contacts SET is_primary = 0 WHERE vendor_id = ?')->execute([$vendorId]);
        }
        $stmt = $this->db->prepare(
            'INSERT INTO vendor_contacts (vendor_id, contact_name, email, phone, job_title, is_primary)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $vendorId,
            $data['contact_name'],
            $data['email'] !== '' ? $data['email'] : null,
            $data['phone'] !== '' ? $data['phone'] : null,
            $data['job_title'] !== '' ? $data['job_title'] : null,
            !empty($data['is_primary']) ? 1 : 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteContact(int $vendorId, int $contactId): void
    {
        $stmt = $this->db->prepare('DELETE FROM vendor_contacts WHERE id = ? AND vendor_id = ?');
        $stmt->execute([$contactId, $vendorId]);
    }

    public function addresses(int $vendorId): array
    {
        $stmt = $this->db->prepare(
            'SELECT va.*, c.name AS country_name, c.iso2
             FROM vendor_addresses va
             JOIN countries c ON c.id = va.country_id
             WHERE va.vendor_id = ?
             ORDER BY va.address_type ASC, va.id ASC'
        );
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll();
    }

    public function addAddress(int $vendorId, array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vendor_addresses (vendor_id, country_id, address_type, address_line1, address_line2, city, region, postal_code)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $vendorId,
            $data['country_id'],
            $data['address_type'],
            $data['address_line1'],
            $data['address_line2'] !== '' ? $data['address_line2'] : null,
            $data['city'] !== '' ? $data['city'] : null,
            $data['region'] !== '' ? $data['region'] : null,
            $data['postal_code'] !== '' ? $data['postal_code'] : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteAddress(int $vendorId, int $addressId): void
    {
        $stmt = $this->db->prepare('DELETE FROM vendor_addresses WHERE id = ? AND vendor_id = ?');
        $stmt->execute([$addressId, $vendorId]);
    }

    public function performanceSummary(int $vendorId): array
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS review_count,
                    ROUND(AVG(delivery_score), 2) AS avg_delivery,
                    ROUND(AVG(quality_score), 2) AS avg_quality,
                    ROUND(AVG(pricing_score), 2) AS avg_pricing,
                    ROUND(AVG(communication_score), 2) AS avg_communication,
                    ROUND(AVG((delivery_score + quality_score + pricing_score + communication_score) / 4), 2) AS overall_score
             FROM vendor_performance_reviews
             WHERE vendor_id = ?'
        );
        $stmt->execute([$vendorId]);
        return $stmt->fetch() ?: [];
    }

    public function reviews(int $vendorId): array
    {
        $stmt = $this->db->prepare(
            'SELECT vpr.*, po.po_number, u.full_name AS reviewer_name
             FROM vendor_performance_reviews vpr
             LEFT JOIN purchase_orders po ON po.id = vpr.purchase_order_id
             JOIN users u ON u.id = vpr.reviewed_by
             WHERE vpr.vendor_id = ?
             ORDER BY vpr.created_at DESC'
        );
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll();
    }

    public function addReview(int $vendorId, array $data, int $userId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vendor_performance_reviews
             (vendor_id, purchase_order_id, delivery_score, quality_score, pricing_score, communication_score, comments, reviewed_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $vendorId,
            $data['purchase_order_id'] ?: null,
            $data['delivery_score'],
            $data['quality_score'],
            $data['pricing_score'],
            $data['communication_score'],
            $data['comments'] !== '' ? $data['comments'] : null,
            $userId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function purchaseOrders(int $vendorId): array
    {
        $stmt = $this->db->prepare(
            'SELECT po.id, po.po_number, po.order_date, po.expected_delivery_date, po.status, po.currency_code, po.total_amount, b.name AS branch_name
             FROM purchase_orders po
             JOIN branches b ON b.id = po.ship_to_branch_id
             WHERE po.vendor_id = ?
             ORDER BY po.order_date DESC, po.id DESC
             LIMIT 50'
        );
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll();
    }

    public function quotations(int $vendorId): array
    {
        $stmt = $this->db->prepare(
            'SELECT q.id, q.quotation_no, q.quote_date, q.valid_until, q.status, q.currency_code, r.requisition_no
             FROM quotations q
             JOIN requisitions r ON r.id = q.requisition_id
             WHERE q.vendor_id = ?
             ORDER BY q.quote_date DESC, q.id DESC
             LIMIT 50'
        );
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll();
    }
}
