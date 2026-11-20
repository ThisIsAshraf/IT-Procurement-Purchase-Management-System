<?php
declare(strict_types=1);namespace App\Controllers;
final class PaymentController extends ResourceController {protected function definition():array{return ['table'=>'payments','permission'=>'payments','path'=>'/payments','title'=>'Payments','order'=>'payment_reference','list'=>['id', 'payment_reference', 'vendor_id', 'currency_code', 'payment_date', 'amount', 'status'],'fields'=>['payment_reference'=>['label'=>'Payment reference','type'=>'text','required'=>true,'nullable'=>false],
'vendor_id'=>['label'=>'Vendor ID','type'=>'number','required'=>true,'nullable'=>false],
'currency_code'=>['label'=>'Currency code','type'=>'text','required'=>true,'nullable'=>false],
'payment_date'=>['label'=>'Payment date','type'=>'date','required'=>true,'nullable'=>false],
'payment_method'=>['label'=>'Payment method','type'=>'text','required'=>true,'nullable'=>false],
'amount'=>['label'=>'Amount','type'=>'number','required'=>true,'nullable'=>false],
'transaction_reference'=>['label'=>'Transaction reference','type'=>'text','required'=>false,'nullable'=>true],
'status'=>['label'=>'Status','type'=>'text','required'=>true,'nullable'=>false],
'recorded_by'=>['label'=>'Recorded by user ID','type'=>'number','required'=>true,'nullable'=>false],
'notes'=>['label'=>'Notes','type'=>'textarea','required'=>false,'nullable'=>true]]];}}
