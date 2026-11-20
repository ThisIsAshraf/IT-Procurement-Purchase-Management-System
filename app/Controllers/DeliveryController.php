<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\DeliveryModel;
use App\Models\PurchaseOrderModel;
use App\Services\DeliveryService;

final class DeliveryController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('deliveries.view');

        $poId = (int)$r->input('purchase_order_id', 0);
        $delModel = new DeliveryModel();
        $poModel = new PurchaseOrderModel();

        $selectedPo = $poId > 0 ? $poModel->find($poId) : null;
        $selectedPoItems = $poId > 0 ? $poModel->items($poId) : [];

        $this->view('deliveries/index', [
            'deliveries'      => $delModel->all($poId > 0 ? $poId : null),
            'receivablePos'   => $delModel->receivablePurchaseOrders(),
            'selectedPo'      => $selectedPo,
            'selectedPoItems' => $selectedPoItems,
            'flash'           => flash('success'),
            'error'           => flash('error'),
        ]);
    }

    public function store(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('deliveries.manage');

        $poId = (int)$r->input('purchase_order_id', 0);
        $vendorRef = trim((string)$r->input('vendor_delivery_ref', ''));
        $receivedAt = trim((string)$r->input('received_at', date('Y-m-d\TH:i')));
        $notes = trim((string)$r->input('notes', ''));
        $items = (array)$r->input('items', []);

        try {
            (new DeliveryService())->recordDelivery(
                $poId,
                $vendorRef,
                $receivedAt,
                $notes,
                $items,
                (int)current_user()['id']
            );
            flash('success', 'Goods receipt recorded and Purchase Order quantities updated.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/deliveries?purchase_order_id=' . $poId);
    }
}
