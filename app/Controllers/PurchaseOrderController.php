<?php
declare(strict_types=1);namespace App\Controllers;
final class PurchaseOrderController extends ResourceController {protected function definition():array{return ['table'=>'purchase_orders','permission'=>'purchase_orders','path'=>'/purchase_orders','title'=>'Purchase orders','order'=>'po_number','list'=>['id', 'po_number', 'vendor_id', 'ship_to_branch_id', 'currency_code', 'order_date', 'total_amount', 'status'],'fields'=>['po_number'=>['label'=>'PO number','type'=>'text','required'=>true,'nullable'=>false],
'vendor_id'=>['label'=>'Vendor ID','type'=>'number','required'=>true,'nullable'=>false],
'requisition_id'=>['label'=>'Requisition ID','type'=>'number','required'=>true,'nullable'=>false],
'quotation_id'=>['label'=>'Quotation ID','type'=>'number','required'=>false,'nullable'=>true], 'ship_to_branch_id'=>['label'=>'Ship-to branch ID','type'=>'number','required'=>true,'nullable'=>false],
'currency_code'=>['label'=>'Currency code','type'=>'text','required'=>true,'nullable'=>false], 'base_currency_code'=>['label'=>'Base currency code','type'=>'text','required'=>true,'nullable'=>false], 'exchange_rate'=>['label'=>'Exchange rate','type'=>'number','required'=>true,'nullable'=>false],
'order_date'=>['label'=>'Order date','type'=>'date','required'=>true,'nullable'=>false],
'expected_delivery_date'=>['label'=>'Expected delivery date','type'=>'date','required'=>false,'nullable'=>true],
'subtotal'=>['label'=>'Subtotal','type'=>'number','required'=>true,'nullable'=>false],
'tax_total'=>['label'=>'Tax total','type'=>'number','required'=>true,'nullable'=>false],
'total_amount'=>['label'=>'Total amount','type'=>'number','required'=>true,'nullable'=>false],
'status'=>['label'=>'Status','type'=>'text','required'=>true,'nullable'=>false], 'created_by'=>['label'=>'Created by user ID','type'=>'number','required'=>true,'nullable'=>false]]];}}
