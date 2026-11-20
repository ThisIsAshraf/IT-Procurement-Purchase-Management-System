<?php
declare(strict_types=1);namespace App\Controllers;
final class CatalogController extends ResourceController {protected function definition():array{return ['table'=>'catalog_items','permission'=>'catalog','path'=>'/catalog','title'=>'Catalog items','order'=>'item_name','list'=>['id', 'category_id', 'unit_id', 'sku', 'item_name', 'is_active'],'fields'=>['category_id'=>['label'=>'Category ID','type'=>'number','required'=>true,'nullable'=>false],
'unit_id'=>['label'=>'Unit ID','type'=>'number','required'=>true,'nullable'=>false],
'sku'=>['label'=>'SKU','type'=>'text','required'=>false,'nullable'=>true],
'item_name'=>['label'=>'Item name','type'=>'text','required'=>true,'nullable'=>false],
'description'=>['label'=>'Description','type'=>'textarea','required'=>false,'nullable'=>true],
'is_active'=>['label'=>'Active (1/0)','type'=>'number','required'=>true,'nullable'=>false]]];}}
