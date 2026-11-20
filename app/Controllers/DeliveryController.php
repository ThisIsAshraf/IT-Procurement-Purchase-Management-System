<?php
declare(strict_types=1);namespace App\Controllers;
final class DeliveryController extends ResourceController {protected function definition():array{return ['table'=>'deliveries','permission'=>'deliveries','path'=>'/deliveries','title'=>'Deliveries','order'=>'delivery_no','list'=>['id', 'delivery_no', 'purchase_order_id', 'received_at', 'delivery_status'],'fields'=>['delivery_no'=>['label'=>'Delivery number','type'=>'text','required'=>true,'nullable'=>false],
'purchase_order_id'=>['label'=>'Purchase order ID','type'=>'number','required'=>true,'nullable'=>false],
'vendor_delivery_ref'=>['label'=>'Vendor delivery reference','type'=>'text','required'=>false,'nullable'=>true],
'received_at'=>['label'=>'Received at','type'=>'text','required'=>true,'nullable'=>false],
'received_by'=>['label'=>'Received by user ID','type'=>'number','required'=>true,'nullable'=>false],
'delivery_status'=>['label'=>'Delivery status','type'=>'text','required'=>true,'nullable'=>false],
'notes'=>['label'=>'Notes','type'=>'textarea','required'=>false,'nullable'=>true]]];}}
