<?php
declare(strict_types=1);namespace App\Controllers;
final class RequisitionController extends ResourceController {protected function definition():array{return ['table'=>'requisitions','permission'=>'requisitions','path'=>'/requisitions','title'=>'Requisitions','order'=>'requisition_no','list'=>['id', 'requisition_no', 'purchase_request_id', 'prepared_by', 'branch_id', 'department_id', 'currency_code', 'status'],'fields'=>['requisition_no'=>['label'=>'Requisition number','type'=>'text','required'=>true,'nullable'=>false],
'purchase_request_id'=>['label'=>'Purchase request ID','type'=>'number','required'=>true,'nullable'=>false],
'prepared_by'=>['label'=>'Prepared by user ID','type'=>'number','required'=>true,'nullable'=>false], 'branch_id'=>['label'=>'Branch ID','type'=>'number','required'=>true,'nullable'=>false], 'department_id'=>['label'=>'Department ID','type'=>'number','required'=>true,'nullable'=>false],
'currency_code'=>['label'=>'Currency code','type'=>'text','required'=>true,'nullable'=>false],
'status'=>['label'=>'Status','type'=>'text','required'=>true,'nullable'=>false]]];}}
