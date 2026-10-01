<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
global $wpdb;
$checks=[];
foreach(['postmeta'=>['post_id','posts','ID'],'commentmeta'=>['comment_id','comments','comment_ID'],'termmeta'=>['term_id','terms','term_id']] as $table=>$join){
    [$fk,$parent,$pk]=$join;$child=$wpdb->$table;$target=$wpdb->$parent;
    $checks['orphan_'.$table]=(int)$wpdb->get_var("SELECT COUNT(*) FROM $child c LEFT JOIN $target p ON c.$fk=p.$pk WHERE p.$pk IS NULL");
    if($checks['orphan_'.$table]){throw new RuntimeException('Orphan records remain.');}
}
$checks['orphan_relationships']=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->term_relationships} r LEFT JOIN {$wpdb->posts} p ON p.ID=r.object_id LEFT JOIN {$wpdb->term_taxonomy} t ON t.term_taxonomy_id=r.term_taxonomy_id WHERE p.ID IS NULL OR t.term_taxonomy_id IS NULL");
if($checks['orphan_relationships']){throw new RuntimeException('Orphan relationships remain.');}
$checks['missing_media_files']=[];
foreach($wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type='attachment'") as $id){if(!is_file(get_attached_file($id))){$checks['missing_media_files'][]=['id'=>(int)$id,'file'=>get_attached_file($id)];}}
$urls=[home_url('/'),home_url('/contato/'),home_url('/sitemap_index.xml')];
foreach(get_posts(['post_type'=>'post','numberposts'=>-1,'post_status'=>'publish']) as $post){$urls[]=get_permalink($post);}
foreach($urls as $url){$response=wp_remote_get($url,['timeout'=>20]);if(is_wp_error($response)||wp_remote_retrieve_response_code($response)!==200){throw new RuntimeException('Page failed: '.$url);}if(preg_match('/(?:Fatal error|Warning):/i',wp_remote_retrieve_body($response))){throw new RuntimeException('PHP page error: '.$url);}}
$checks['pages_checked']=count($urls);
$checks['yoast_indexing_complete']=WPSEO_Options::get('indexables_indexing_completed');
file_put_contents(sys_get_temp_dir().'/guiareview-cleanup-verification.json',wp_json_encode($checks,JSON_PRETTY_PRINT));
$checks['legacy_missing_media_count']=count($checks['missing_media_files']);unset($checks['missing_media_files']);
echo wp_json_encode($checks,JSON_PRETTY_PRINT);
