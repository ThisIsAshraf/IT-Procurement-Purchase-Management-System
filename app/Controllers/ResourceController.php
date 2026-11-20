<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Database;use App\Core\Request;
abstract class ResourceController extends BaseController {
 abstract protected function definition():array;
 public function index(Request $r):void{$this->auth();$d=$this->definition();$this->require($d['permission'].'.view');$pdo=Database::connection();$cols=implode(',',array_map(fn($c)=>'`'.$c.'`',$d['list']));$rows=$pdo->query('SELECT '.$cols.' FROM `'.$d['table'].'` ORDER BY '.$d['order'].' DESC LIMIT 200')->fetchAll();$this->view('resources/index',['title'=>$d['title'],'rows'=>$rows,'columns'=>$d['list'],'fields'=>$d['fields'],'action'=>$d['path'],'flash'=>flash('success'),'error'=>flash('error')]);}
 public function store(Request $r):void{$this->auth();$this->csrf();$d=$this->definition();if(!can($d['permission'].'.create')&&!can($d['permission'].'.manage'))$this->require($d['permission'].'.create');$input=$r->input();$data=[];foreach($d['fields'] as $field=>$meta){$v=trim((string)($input[$field]??''));if($v===''){if($meta['required']??false){flash('error',$meta['label'].' is required.');redirect($d['path']);}if($meta['nullable']??false){$data[$field]=null;}else{continue;}}else{$data[$field]=$v;}}
 try{$pdo=Database::connection();$names=array_keys($data);$sql='INSERT INTO `'.$d['table'].'` (`'.implode('`,`',$names).'`) VALUES ('.implode(',',array_fill(0,count($names),'?')).')';$pdo->prepare($sql)->execute(array_values($data));$id=(int)$pdo->lastInsertId();\audit_log('create',$d['table'],$id,null,$data);flash('success',$d['title'].' record created successfully.');}catch(\Throwable $e){flash('error','Unable to save record: '.$e->getMessage());}redirect($d['path']);}
}
