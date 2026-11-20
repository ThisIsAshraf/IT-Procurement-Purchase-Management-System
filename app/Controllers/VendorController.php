<?php
declare(strict_types=1);namespace App\Controllers;
final class VendorController extends ResourceController {protected function definition():array{return ['table'=>'vendors','permission'=>'vendors','path'=>'/vendors','title'=>'Vendors','order'=>'vendor_code','list'=>['id', 'vendor_code', 'legal_name', 'status'],'fields'=>['vendor_code'=>['label'=>'Vendor code','type'=>'text','required'=>true,'nullable'=>false],
'legal_name'=>['label'=>'Legal name','type'=>'text','required'=>true,'nullable'=>false],
'tax_registration_no'=>['label'=>'Tax registration no.','type'=>'text','required'=>false,'nullable'=>true],
'website'=>['label'=>'Website','type'=>'url','required'=>false,'nullable'=>true],
'status'=>['label'=>'Status: pending/approved/blocked/inactive','type'=>'text','required'=>true,'nullable'=>false],
'notes'=>['label'=>'Notes','type'=>'textarea','required'=>false,'nullable'=>true]]];}}
