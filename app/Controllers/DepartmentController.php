<?php
declare(strict_types=1);namespace App\Controllers;
final class DepartmentController extends ResourceController {protected function definition():array{return ['table'=>'departments','permission'=>'organization','path'=>'/departments','title'=>'Departments','order'=>'name','list'=>['id', 'department_code', 'name', 'is_active'],'fields'=>['name'=>['label'=>'Department name','type'=>'text','required'=>true,'nullable'=>false],
'department_code'=>['label'=>'Department code','type'=>'text','required'=>true,'nullable'=>false],
'is_active'=>['label'=>'Active (1/0)','type'=>'number','required'=>true,'nullable'=>false]]];}}
