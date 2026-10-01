<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
global $wpdb;
$report=[];
foreach(['category','post_tag'] as $taxonomy){
foreach(get_terms(['taxonomy'=>$taxonomy,'hide_empty'=>false]) as $term){
$relationships=$wpdb->get_results($wpdb->prepare("SELECT p.ID,p.post_type,p.post_status,p.post_title FROM {$wpdb->posts} p JOIN {$wpdb->term_relationships} r ON r.object_id=p.ID WHERE r.term_taxonomy_id=%d",$term->term_taxonomy_id),ARRAY_A);
$report[]=['id'=>$term->term_id,'taxonomy'=>$taxonomy,'name'=>$term->name,'slug'=>$term->slug,'parent'=>$term->parent,'count'=>$term->count,'description'=>$term->description,'posts'=>$relationships,'image'=>get_term_meta($term->term_id,'image',true)];
}}
file_put_contents(sys_get_temp_dir().'/guiareview-taxonomy-audit.json',wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
echo wp_json_encode(array_map(function($row){$row['statuses']=array_count_values(array_column($row['posts'],'post_status'));$row['active_posts']=array_values(array_filter($row['posts'],fn($p)=>$p['post_type']==='post'&&!in_array($p['post_status'],['trash','auto-draft'],true)));unset($row['posts'],$row['description']);return $row;},$report),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
