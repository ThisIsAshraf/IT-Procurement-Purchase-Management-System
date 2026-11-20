<?php
declare(strict_types=1);namespace App\Controllers;
final class BudgetController extends ResourceController {protected function definition():array{return ['table'=>'budgets','permission'=>'budgets','path'=>'/budgets','title'=>'Budgets','order'=>'budget_name','list'=>['id', 'branch_id', 'department_id', 'fiscal_year', 'currency_code', 'budget_name', 'status'],'fields'=>['branch_id'=>['label'=>'Branch ID','type'=>'number','required'=>true,'nullable'=>false],
'department_id'=>['label'=>'Department ID','type'=>'number','required'=>true,'nullable'=>false],
'fiscal_year'=>['label'=>'Fiscal year','type'=>'number','required'=>true,'nullable'=>false],
'currency_code'=>['label'=>'Currency code','type'=>'text','required'=>true,'nullable'=>false],
'budget_name'=>['label'=>'Budget name','type'=>'text','required'=>true,'nullable'=>false],
'status'=>['label'=>'Status','type'=>'text','required'=>true,'nullable'=>false]]];}}
