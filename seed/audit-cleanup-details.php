<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';global $wpdb;
echo wp_json_encode(['scheduler_loaded'=>class_exists('ActionScheduler'),'scheduler_hooks'=>$wpdb->get_results("SELECT hook,status,COUNT(*) total FROM {$wpdb->prefix}actionscheduler_actions GROUP BY hook,status",ARRAY_A),'cron_hooks'=>array_values(array_unique(array_merge(...array_map('array_keys',_get_cron_array()?:[])))),'yoast_duplicate_groups'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM (SELECT object_type,object_id,object_sub_type,COUNT(*) c FROM {$wpdb->prefix}yoast_indexable WHERE object_id IS NOT NULL GROUP BY object_type,object_id,object_sub_type HAVING c>1) d")],JSON_PRETTY_PRINT);
