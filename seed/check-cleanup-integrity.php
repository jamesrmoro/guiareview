<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';global $wpdb;
$backup=$argv[1]??'';
$reader=gzopen($backup,'rb');if(!$reader){throw new RuntimeException('Missing backup.');}
$original=[];
while(!gzeof($reader)){
    $line=gzgets($reader);
    if(!preg_match('/^INSERT INTO `([^`]+)` \((.+)\) VALUES \((.+)\);/s',$line,$match)){continue;}
    if(!in_array($match[1],[$wpdb->posts,$wpdb->postmeta,$wpdb->term_relationships],true)){continue;}
    preg_match_all('/`([^`]+)`/',$match[2],$columns);preg_match_all("/NULL|X'([0-9a-f]*)'/",$match[3],$values,PREG_SET_ORDER);
    $row=[];foreach($columns[1] as $i=>$column){$row[$column]=$values[$i][0]==='NULL'?null:hex2bin($values[$i][1]);}
    $original[$match[1]][]=$row;
}
gzclose($reader);$differences=[];$protected=[];
foreach($original[$wpdb->posts] as $row){
    if(in_array($row['post_status'],['trash','auto-draft'],true)||in_array($row['post_type'],['revision','acf-field','acf-field-group','aal_button','rank_math_schema','rm_content_editor','oembed_cache'],true)){continue;}
    $id=(int)$row['ID'];$protected[$id]=$row['post_type'];
    $current=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE ID=%d",$id),ARRAY_A);
    if(!$current){$differences[]=['id'=>$id,'type'=>$row['post_type'],'change'=>'missing post'];continue;}
    foreach($row as $key=>$value){if($row['post_type']==='attachment'&&$key==='post_parent'){continue;}if((string)$value!==(string)$current[$key]){$differences[]=['id'=>$id,'type'=>$row['post_type'],'change'=>$key];}}
}
foreach($protected as $id=>$type){
    $before=[];foreach($original[$wpdb->postmeta] as $row){if((int)$row['post_id']===$id){if(str_starts_with($row['meta_key'],'_')&&str_starts_with($row['meta_value'],'field_')){continue;}$before[$row['meta_key']][]=$row['meta_value'];}}
    $after=[];foreach($wpdb->get_results($wpdb->prepare("SELECT meta_key,meta_value FROM {$wpdb->postmeta} WHERE post_id=%d",$id),ARRAY_A) as $row){$after[$row['meta_key']][]=$row['meta_value'];}
    foreach(array_unique(array_merge(array_keys($before),array_keys($after))) as $key){$a=$before[$key]??[];$b=$after[$key]??[];sort($a);sort($b);if($a!==$b){$differences[]=['id'=>$id,'type'=>$type,'change'=>'meta:'.$key];}}
    $before=[];foreach($original[$wpdb->term_relationships] as $row){if((int)$row['object_id']===$id){$before[]=(int)$row['term_taxonomy_id'];}}
    $after=array_map('intval',$wpdb->get_col($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_relationships} WHERE object_id=%d",$id)));sort($before);sort($after);
    if($before!==$after){$differences[]=['id'=>$id,'type'=>$type,'change'=>'term relationships'];}
}
echo wp_json_encode(['protected_posts'=>count($protected),'differences'=>$differences],JSON_PRETTY_PRINT);
if($differences){exit(1);}
