<?php
declare(strict_types=1);namespace App\Controllers;
final class InvoiceController extends ResourceController {protected function definition():array{return ['table'=>'invoices','permission'=>'invoices','path'=>'/invoices','title'=>'Invoices','order'=>'invoice_no','list'=>['id', 'invoice_no', 'vendor_id', 'purchase_order_id', 'currency_code', 'invoice_date', 'total_amount', 'status'],'fields'=>['invoice_no'=>['label'=>'Invoice number','type'=>'text','required'=>true,'nullable'=>false],
'vendor_id'=>['label'=>'Vendor ID','type'=>'number','required'=>true,'nullable'=>false],
'purchase_order_id'=>['label'=>'Purchase order ID','type'=>'number','required'=>true,'nullable'=>false],
'currency_code'=>['label'=>'Currency code','type'=>'text','required'=>true,'nullable'=>false],
'invoice_date'=>['label'=>'Invoice date','type'=>'date','required'=>true,'nullable'=>false],
'due_date'=>['label'=>'Due date','type'=>'date','required'=>false,'nullable'=>true],
'subtotal'=>['label'=>'Subtotal','type'=>'number','required'=>true,'nullable'=>false],
'tax_total'=>['label'=>'Tax total','type'=>'number','required'=>true,'nullable'=>false],
'total_amount'=>['label'=>'Total amount','type'=>'number','required'=>true,'nullable'=>false],
'status'=>['label'=>'Status','type'=>'text','required'=>true,'nullable'=>false]]];}}
