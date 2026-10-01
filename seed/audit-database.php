<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
global $wpdb;
$tables=$wpdb->get_results('SHOW TABLE STATUS',ARRAY_A);
$report=['tables'=>array_map(fn($t)=>['name'=>$t['Name'],'rows'=>$t['Rows'],'bytes'=>$t['Data_length']+$t['Index_length']],array_filter($tables,fn($t)=>str_starts_with($t['Name'],$wpdb->prefix))),'posts'=>$wpdb->get_results("SELECT post_type,post_status,COUNT(*) total FROM {$wpdb->posts} GROUP BY post_type,post_status",ARRAY_A),'comments'=>$wpdb->get_results("SELECT comment_approved,COUNT(*) total FROM {$wpdb->comments} GROUP BY comment_approved",ARRAY_A),'plugins'=>get_option('active_plugins'),'counts'=>[]];
$queries=[
'orphan_postmeta'=>"SELECT COUNT(*) FROM {$wpdb->postmeta} m LEFT JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE p.ID IS NULL",
'orphan_commentmeta'=>"SELECT COUNT(*) FROM {$wpdb->commentmeta} m LEFT JOIN {$wpdb->comments} c ON c.comment_ID=m.comment_id WHERE c.comment_ID IS NULL",
'orphan_termmeta'=>"SELECT COUNT(*) FROM {$wpdb->termmeta} m LEFT JOIN {$wpdb->terms} t ON t.term_id=m.term_id WHERE t.term_id IS NULL",
'orphan_relationships'=>"SELECT COUNT(*) FROM {$wpdb->term_relationships} r LEFT JOIN {$wpdb->posts} p ON p.ID=r.object_id LEFT JOIN {$wpdb->term_taxonomy} t ON t.term_taxonomy_id=r.term_taxonomy_id WHERE p.ID IS NULL OR t.term_taxonomy_id IS NULL",
'expired_transients'=>"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_timeout\\_%' AND CAST(option_value AS UNSIGNED)<UNIX_TIMESTAMP()",
];
foreach($queries as $name=>$sql){$report['counts'][$name]=(int)$wpdb->get_var($sql);}
$report['acf_posts']=$wpdb->get_results("SELECT ID,post_type,post_status,post_title FROM {$wpdb->posts} WHERE post_type LIKE 'acf-%'",ARRAY_A);
file_put_contents(sys_get_temp_dir().'/guiareview-database-audit.json',wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
unset($report['acf_posts']);
echo wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
