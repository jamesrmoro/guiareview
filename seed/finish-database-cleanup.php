<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
global $wpdb;
$baseline=json_decode(file_get_contents(sys_get_temp_dir().'/guiareview-database-audit.json'),true,512,JSON_THROW_ON_ERROR);
$backup=sys_get_temp_dir().'/guiareview-before-database-cleanup-20261001-030645.sql.gz';
if(!is_file($backup)){throw new RuntimeException('Original backup missing.');}
$tables=array_values(array_filter($wpdb->get_col('SHOW TABLES'),fn($t)=>str_starts_with($t,$wpdb->prefix)));
foreach($tables as $table){
    if(!preg_match('/^[A-Za-z0-9_]+$/',$table)){throw new RuntimeException('Invalid table.');}
    $result=$wpdb->get_results('OPTIMIZE TABLE `'.$table.'`',ARRAY_A);
    if($wpdb->last_error){throw new RuntimeException($wpdb->last_error);}
    foreach($result as $row){if($row['Msg_type']==='error'){throw new RuntimeException($row['Msg_text']);}}
    $check=$wpdb->get_results('CHECK TABLE `'.$table.'`',ARRAY_A);
    foreach($check as $row){if($row['Msg_type']==='error'){throw new RuntimeException($row['Msg_text']);}}
}
$after=array_values(array_filter($wpdb->get_results('SHOW TABLE STATUS',ARRAY_A),fn($t)=>str_starts_with($t['Name'],$wpdb->prefix)));
$report=['backup'=>$backup,'backup_sha256'=>hash_file('sha256',$backup),'tables_before'=>count($baseline['tables']),'tables_after'=>count($after),'removed_tables'=>array_values(array_diff(array_column($baseline['tables'],'name'),$tables)),'bytes_before'=>array_sum(array_column($baseline['tables'],'bytes')),'bytes_after'=>array_sum(array_map(fn($t)=>$t['Data_length']+$t['Index_length'],$after)),'posts_before'=>$baseline['posts'],'posts_after'=>$wpdb->get_results("SELECT post_type,post_status,COUNT(*) total FROM {$wpdb->posts} GROUP BY post_type,post_status",ARRAY_A),'comments_before'=>$baseline['comments'],'comments_after'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments}"),'active_plugins'=>get_option('active_plugins'),'table_checks'=>'passed'];
file_put_contents(sys_get_temp_dir().'/guiareview-database-cleanup-report.json',wp_json_encode($report,JSON_PRETTY_PRINT));
echo wp_json_encode($report,JSON_PRETTY_PRINT);
