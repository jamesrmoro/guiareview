<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
$input=[];
foreach(get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC']) as $post){
    $title=wpseo_replace_vars(WPSEO_Meta::get_value('title',$post->ID),$post);
    $bbox=imagettfbbox(15,0,'C:/Windows/Fonts/arial.ttf',$title);
    $input[]=['id'=>$post->ID,'text'=>$post->post_content.grv_yoast_native_content($post->ID),'keyword'=>WPSEO_Meta::get_value('focuskw',$post->ID),'title'=>$title,'titleWidth'=>$bbox[2]-$bbox[0],'description'=>WPSEO_Meta::get_value('metadesc',$post->ID),'slug'=>$post->post_name,'locale'=>'pt_BR','permalink'=>get_permalink($post)];
}
file_put_contents(__DIR__.'/seo-analysis-input.json',wp_json_encode($input,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
echo count($input)." products exported\n";
