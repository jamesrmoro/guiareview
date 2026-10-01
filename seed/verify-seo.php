<?php
if(PHP_SAPI!=='cli') {exit;}
require dirname(__DIR__,4).'/wp-load.php';
$report=[];
foreach(get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1]) as $p) {
    $report[]=['id'=>$p->ID,'seo'=>(int)WPSEO_Meta::get_value('linkdex',$p->ID),'readability'=>(int)WPSEO_Meta::get_value('content_score',$p->ID),'noindex'=>WPSEO_Meta::get_value('meta-robots-noindex',$p->ID)];
}
echo wp_json_encode(['posts'=>$report,'public'=>get_option('blog_public'),'sitemap'=>WPSEO_Options::get('enable_xml_sitemap'),'indexing_completed'=>WPSEO_Options::get('indexables_indexing_completed')],JSON_PRETTY_PRINT);
