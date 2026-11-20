<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class PaymentModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        return $this->db->query(
            "SELECT p.*,
                    v.vendor_code, v.legal_name AS vendor_name,
                    u.full_name AS recorded_by_name,
                    COALESCE((SELECT SUM(pa.allocated_amount) FROM payment_allocations pa WHERE pa.payment_id = p.id), 0) AS allocated_total,
                    (SELECT GROUP_CONCAT(inv.invoice_no SEPARATOR ', ')
                     FROM payment_allocations pa
                     JOIN invoices inv ON inv.id = pa.invoice_id
                     WHERE pa.payment_id = p.id) AS invoice_numbers
             FROM payments p
             JOIN vendors v ON v.id = p.vendor_id
             JOIN users u ON u.id = p.recorded_by
             ORDER BY p.payment_date DESC, p.id DESC
             LIMIT 250"
        )->fetchAll();
    }
}
