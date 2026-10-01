<?php
if (PHP_SAPI !== 'cli') { exit; }
require dirname(__DIR__, 4) . '/wp-load.php';
echo json_encode(['url'=>home_url(),'public'=>get_option('blog_public'),'active'=>get_option('active_plugins'),'seo'=>get_option('wpseo'),'titles'=>get_option('wpseo_titles'),'posts'=>array_map(function($p){return ['id'=>$p->ID,'title'=>$p->post_title,'slug'=>$p->post_name,'content'=>$p->post_content,'excerpt'=>$p->post_excerpt,'seo'=>array_filter(get_post_meta($p->ID),fn($k)=>str_starts_with($k,'_yoast'),ARRAY_FILTER_USE_KEY)];},get_posts(['post_type'=>['post','page'],'post_status'=>'publish','numberposts'=>-1]))],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
