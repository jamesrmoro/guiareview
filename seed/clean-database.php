<?php
/** CLI-only maintenance with a complete compressed SQL backup before any deletion. */
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
wp_set_current_user(1);global $wpdb;
if(!in_array('--apply',$argv,true)){throw new RuntimeException('Use --apply after auditing the database.');}
$tables=array_values(array_filter($wpdb->get_col('SHOW TABLES'),fn($name)=>str_starts_with($name,$wpdb->prefix)));
foreach($tables as $table){if(!preg_match('/^[A-Za-z0-9_]+$/',$table)){throw new RuntimeException('Unexpected table name.');}}
$obsolete_tables=array_values(array_filter($tables,function($table)use($wpdb){$suffix=substr($table,strlen($wpdb->prefix));return preg_match('/^(aal_|rank_math_|wf|podsrel$|actionscheduler_)/',$suffix); }));
if(class_exists('ActionScheduler')||is_plugin_active('wordfence/wordfence.php')||is_plugin_active('seo-by-rank-math/rank-math.php')||is_plugin_active('amazon-auto-links/amazon-auto-links.php')||is_plugin_active('pods/init.php')||function_exists('acf')){throw new RuntimeException('An obsolete plugin is active; aborting cleanup.');}
$protected_snapshot=function()use($wpdb){
    $ids=$wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_status NOT IN ('trash','auto-draft') AND post_type NOT IN ('revision','acf-field','acf-field-group','aal_button','rank_math_schema','rm_content_editor','oembed_cache') ORDER BY ID");
    $records=[];
    foreach($ids as $id){
        $p=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE ID=%d",$id),ARRAY_A);if($p['post_type']==='attachment'){unset($p['post_parent']);}
        $meta=[];foreach($wpdb->get_results($wpdb->prepare("SELECT meta_key,meta_value FROM {$wpdb->postmeta} WHERE post_id=%d",$id),ARRAY_A) as $row){if(str_starts_with($row['meta_key'],'_')&&str_starts_with($row['meta_value'],'field_')){continue;}$meta[$row['meta_key']][]=$row['meta_value'];}
        foreach($meta as &$values){sort($values);}unset($values);ksort($meta);
        $terms=$wpdb->get_col($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_relationships} WHERE object_id=%d ORDER BY term_taxonomy_id",$id));
        $records[$id]=['post'=>$p,'meta'=>$meta,'terms'=>$terms];
    }
    return hash('sha256',serialize($records));
};
$before_hash=$protected_snapshot();
$before_size=(int)$wpdb->get_var($wpdb->prepare('SELECT SUM(data_length+index_length) FROM information_schema.tables WHERE table_schema=DATABASE() AND LEFT(table_name,%d)=%s',strlen($wpdb->prefix),$wpdb->prefix));
$backup_path=sys_get_temp_dir().'/guiareview-before-database-cleanup-'.date('Ymd-His').'.sql.gz';
$stream=gzopen($backup_path,'wb6');if(!$stream){throw new RuntimeException('Cannot create database backup.');}
$bytes=0;$rows=0;
$write=function($sql)use($stream,&$bytes){$written=gzwrite($stream,$sql);if($written!==strlen($sql)){throw new RuntimeException('Incomplete backup write.');}$bytes+=$written;};
$write("-- Guia Review: full WordPress table backup\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
foreach($tables as $table){
    $create=$wpdb->get_row('SHOW CREATE TABLE `'.$table.'`',ARRAY_N);
    if(!$create||empty($create[1])){throw new RuntimeException('Cannot back up schema '.$table);}
    $write('DROP TABLE IF EXISTS `'.$table.'`;'."\n".$create[1].";\n");
    for($offset=0;;$offset+=100){
        $records=$wpdb->get_results('SELECT * FROM `'.$table.'` LIMIT '.$offset.',100',ARRAY_A);
        if($wpdb->last_error){throw new RuntimeException('Backup query failed.');}
        if(!$records){break;}
        foreach($records as $record){
            $columns=implode(',',array_map(fn($key)=>'`'.$key.'`',array_keys($record)));
            $values=implode(',',array_map(fn($value)=>$value===null?'NULL':"X'".bin2hex((string)$value)."'",array_values($record)));
            $write('INSERT INTO `'.$table.'` ('.$columns.') VALUES ('.$values.");\n");$rows++;
        }
    }
}
$write("SET FOREIGN_KEY_CHECKS=1;\n");gzclose($stream);
$reader=gzopen($backup_path,'rb');$verified=0;while(!gzeof($reader)){$chunk=gzread($reader,1048576);if($chunk===false){throw new RuntimeException('Backup verification failed.');}$verified+=strlen($chunk);}gzclose($reader);
if($verified!==$bytes){throw new RuntimeException('Backup size mismatch.');}
echo 'Backup verified: '.$backup_path.' ('.$rows." rows)\n";
$removed=[];
$remove_ids=$wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE (post_status='trash' AND post_type<>'attachment') OR post_status='auto-draft' OR post_type IN ('revision','acf-field','acf-field-group','aal_button','rank_math_schema','rm_content_editor','oembed_cache') ORDER BY ID");
$removed['posts_by_type']=[];
foreach($remove_ids as $id){$post=get_post($id);if(!$post){continue;}$type=$post->post_type;if(!wp_delete_post($id,true)){throw new RuntimeException('Post deletion failed: '.$id);}$removed['posts_by_type'][$type]=($removed['posts_by_type'][$type]??0)+1;}
$query=function($sql)use($wpdb){$result=$wpdb->query($sql);if($result===false){throw new RuntimeException('Maintenance SQL failed: '.$wpdb->last_error);}return $result;};
$removed['orphan_postmeta']=$query("DELETE m FROM {$wpdb->postmeta} m LEFT JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE p.ID IS NULL");
$removed['acf_reference_meta']=$query("DELETE FROM {$wpdb->postmeta} WHERE LEFT(meta_key,1)='_' AND meta_value LIKE 'field\\_%'");
$comment_ids=$wpdb->get_col("SELECT c.comment_ID FROM {$wpdb->comments} c LEFT JOIN {$wpdb->posts} p ON p.ID=c.comment_post_ID WHERE c.comment_approved IN ('spam','trash','post-trashed') OR (c.comment_post_ID>0 AND p.ID IS NULL)");
foreach($comment_ids as $id){wp_delete_comment($id,true);}
$removed['remaining_discarded_comments']=count($comment_ids);
$removed['orphan_commentmeta']=$query("DELETE m FROM {$wpdb->commentmeta} m LEFT JOIN {$wpdb->comments} c ON c.comment_ID=m.comment_id WHERE c.comment_ID IS NULL");
$removed['orphan_termmeta']=$query("DELETE m FROM {$wpdb->termmeta} m LEFT JOIN {$wpdb->terms} t ON t.term_id=m.term_id WHERE t.term_id IS NULL");
$removed['orphan_relationships']=$query("DELETE r FROM {$wpdb->term_relationships} r LEFT JOIN {$wpdb->posts} p ON p.ID=r.object_id LEFT JOIN {$wpdb->term_taxonomy} t ON t.term_taxonomy_id=r.term_taxonomy_id WHERE p.ID IS NULL OR t.term_taxonomy_id IS NULL");
$removed['acf_term_references']=$query("DELETE FROM {$wpdb->termmeta} WHERE LEFT(meta_key,1)='_' AND meta_value LIKE 'field\\_%'");
delete_expired_transients(true);
$option_names=$wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name REGEXP '^(rank_math|rank-math|wordfence|wf[A-Z]|wfls_|aal_|amazon_auto_links|pods_|acf_)'");
foreach($option_names as $name){delete_option($name);}$removed['obsolete_plugin_options']=count($option_names);
foreach($obsolete_tables as $table){$query('DROP TABLE `'.$table.'`');}$removed['obsolete_tables']=$obsolete_tables;
// These four Yoast tables are derived caches. Rebuild them to remove duplicate and obsolete indexables.
foreach(['yoast_indexable_hierarchy','yoast_indexable','yoast_primary_term','yoast_seo_links'] as $suffix){$query('TRUNCATE TABLE `'.$wpdb->prefix.$suffix.'`');}
WPSEO_Options::set('indexables_indexing_completed',false);
wp_cache_flush();
if($before_hash!==$protected_snapshot()){throw new RuntimeException('Protected content changed; stop and restore from backup.');}
$remaining=array_values(array_filter($wpdb->get_col('SHOW TABLES'),fn($name)=>str_starts_with($name,$wpdb->prefix)));
foreach($remaining as $table){$wpdb->get_results('OPTIMIZE TABLE `'.$table.'`');}
$after_size=(int)$wpdb->get_var($wpdb->prepare('SELECT SUM(data_length+index_length) FROM information_schema.tables WHERE table_schema=DATABASE() AND LEFT(table_name,%d)=%s',strlen($wpdb->prefix),$wpdb->prefix));
$report=['backup'=>$backup_path,'backup_sha256'=>hash_file('sha256',$backup_path),'removed'=>$removed,'bytes_before'=>$before_size,'bytes_after'=>$after_size,'protected_content_unchanged'=>true];
file_put_contents(sys_get_temp_dir().'/guiareview-database-cleanup-report.json',wp_json_encode($report,JSON_PRETTY_PRINT));
echo wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
