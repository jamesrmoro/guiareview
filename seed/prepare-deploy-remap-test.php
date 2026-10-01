<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
$source=dirname(__DIR__).'/deploy-content.zip';$file=sys_get_temp_dir().'/guiareview-deploy-remap-test.zip';
copy($source,$file);$zip=new ZipArchive();$zip->open($file);$data=json_decode($zip->getFromName('manifest.json'),true,512,JSON_THROW_ON_ERROR);
$home=$data['source_home'];$fake='https://staging.guiareview.invalid';$shift=100000;
$rewrite=function($value)use(&$rewrite,$home,$fake){if(is_array($value)){return array_map($rewrite,$value);}return is_string($value)?str_replace($home,$fake,$value):$value;};
$data=$rewrite($data);$data['source_home']=$fake;
foreach($data['media'] as &$image){$image['source_id']+=$shift;}unset($image);
foreach($data['terms'] as &$term){$term['source_id']+=$shift;if($term['parent']){$term['parent']+=$shift;}if(is_numeric($term['image'])&&$term['image']){$term['image']+=$shift;}foreach(['wpseo_opengraph-image-id','wpseo_twitter-image-id'] as $key){if(!empty($term['seo'][$key])){$term['seo'][$key]+=$shift;}}}unset($term);
foreach($data['posts'] as &$post){$post['source_id']+=$shift;foreach(['_thumbnail_id','image','_yoast_wpseo_opengraph-image-id','_yoast_wpseo_twitter-image-id','_yoast_wpseo_primary_category'] as $key){if(!empty($post['meta'][$key])&&is_numeric($post['meta'][$key])){$post['meta'][$key]+=$shift;}}
    if(isset($post['meta']['gallery'])){$post['meta']['gallery']=array_map(fn($id)=>$id+$shift,$post['meta']['gallery']);}
    foreach($post['terms'] as &$ids){$ids=array_map(fn($id)=>$id+$shift,$ids);}unset($ids);
    $post['content']=preg_replace_callback('/data-grv-product="(\d+)"/',fn($match)=>'data-grv-product="'.((int)$match[1]+$shift).'"',$post['content']);
}unset($post);
$zip->addFromString('manifest.json',wp_json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));$zip->close();
$snapshot=['posts'=>[],'counts'=>[],'terms'=>[]];
foreach(get_posts(['post_type'=>['post','grv_ad'],'post_status'=>'publish','numberposts'=>-1]) as $post){$snapshot['posts'][$post->ID]=['title'=>$post->post_title,'content'=>$post->post_content,'excerpt'=>$post->post_excerpt,'slug'=>$post->post_name,'modified'=>$post->post_modified,'meta'=>get_post_meta($post->ID)];}
foreach(get_terms(['taxonomy'=>['category','post_tag'],'hide_empty'=>false]) as $term){$snapshot['terms'][$term->term_id]=['description'=>$term->description,'parent'=>$term->parent,'meta'=>get_term_meta($term->term_id)];}
global $wpdb;$snapshot['counts']=$wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE 'grv_affiliate_clicks_%' OR option_name LIKE 'grv_ad_clicks_%' OR option_name LIKE 'grv_ad_views_%' ORDER BY option_name",ARRAY_A);
file_put_contents(sys_get_temp_dir().'/guiareview-deploy-before-test.json',wp_json_encode($snapshot,JSON_PRETTY_PRINT));
echo $file."\n";
