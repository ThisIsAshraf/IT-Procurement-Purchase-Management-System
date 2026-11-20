<?php
declare(strict_types=1);namespace App\Controllers;
final class PurchaseRequestController extends ResourceController {protected function definition():array{return ['table'=>'purchase_requests','permission'=>'purchase_requests','path'=>'/purchase_requests','title'=>'Purchase requests','order'=>'request_no','list'=>['id', 'request_no', 'requester_user_id', 'branch_id', 'department_id', 'currency_code', 'status'],'fields'=>['request_no'=>['label'=>'Request number','type'=>'text','required'=>true,'nullable'=>false],
'requester_user_id'=>['label'=>'Requester user ID','type'=>'number','required'=>true,'nullable'=>false],
'branch_id'=>['label'=>'Branch ID','type'=>'number','required'=>true,'nullable'=>false],
'department_id'=>['label'=>'Department ID','type'=>'number','required'=>true,'nullable'=>false],
'currency_code'=>['label'=>'Currency code','type'=>'text','required'=>true,'nullable'=>false],
'required_by'=>['label'=>'Required by','type'=>'date','required'=>false,'nullable'=>true],
'business_justification'=>['label'=>'Business justification','type'=>'textarea','required'=>true,'nullable'=>false],
'status'=>['label'=>'Status','type'=>'text','required'=>true,'nullable'=>false]]];}}
