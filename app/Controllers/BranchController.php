<?php
declare(strict_types=1);namespace App\Controllers;
final class BranchController extends ResourceController {protected function definition():array{return ['table'=>'branches','permission'=>'organization','path'=>'/organization','title'=>'Branches','order'=>'name','list'=>['id', 'branch_code', 'name', 'city', 'default_currency', 'is_active'],'fields'=>['country_id'=>['label'=>'Country ID','type'=>'number','required'=>true,'nullable'=>false],
'branch_code'=>['label'=>'Branch code','type'=>'text','required'=>true,'nullable'=>false],
'name'=>['label'=>'Branch name','type'=>'text','required'=>true,'nullable'=>false],
'city'=>['label'=>'City','type'=>'text','required'=>false,'nullable'=>true],
'region'=>['label'=>'Region/state','type'=>'text','required'=>false,'nullable'=>true],
'default_currency'=>['label'=>'Default currency','type'=>'text','required'=>true,'nullable'=>false],
'timezone'=>['label'=>'Timezone','type'=>'text','required'=>true,'nullable'=>false],
'is_active'=>['label'=>'Active (1/0)','type'=>'number','required'=>true,'nullable'=>false]]];}}
