<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';global $wpdb;
echo wp_json_encode(['options'=>$wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name REGEXP '^(rank_math|rank-math|wordfence|wf[A-Z]|wfls_|aal_|amazon_auto_links|pods_|acf_)'"),'scheduler'=>$wpdb->get_results("SELECT status,COUNT(*) total FROM {$wpdb->prefix}actionscheduler_actions GROUP BY status",ARRAY_A),'rank_redirects'=>$wpdb->get_results("SELECT id,status FROM {$wpdb->prefix}rank_math_redirections",ARRAY_A),'acf_pointers'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE LEFT(meta_key,1)='_' AND meta_value LIKE 'field\\_%'")],JSON_PRETTY_PRINT);
