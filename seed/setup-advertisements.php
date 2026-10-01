<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';wp_set_current_user(1);
require_once ABSPATH.'wp-admin/includes/image.php';require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/media.php';
$source=dirname(__DIR__).'/src/images/ads-2.jpg';$hash=hash_file('sha256',$source);
$existing=get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>1,'meta_key'=>'_grv_ad_asset_hash','meta_value'=>$hash]);
if($existing){$image=$existing[0]->ID;}else{
    $temp=wp_tempnam('kindle-colorsoft-anuncio.jpg');copy($source,$temp);
    $image=media_handle_sideload(['name'=>'kindle-colorsoft-anuncio.jpg','tmp_name'=>$temp],0,'Kindle Colorsoft — anúncio');
    if(is_wp_error($image)){throw new RuntimeException($image->get_error_message());}
    update_post_meta($image,'_grv_ad_asset_hash',$hash);update_post_meta($image,'_wp_attachment_image_alt','Kindle Colorsoft — imagem do anúncio');
}
$result=[];
foreach(['A'=>'Ver oferta','B'=>'Conhecer o Kindle'] as $variant=>$cta){
    $slug='kindle-colorsoft-'.strtolower($variant);$post=get_page_by_path($slug,OBJECT,'grv_ad');
    $id=$post?$post->ID:wp_insert_post(['post_type'=>'grv_ad','post_status'=>'publish','post_title'=>'Conheça o Kindle Colorsoft','post_name'=>$slug,'post_author'=>1],true);
    if(is_wp_error($id)){throw new RuntimeException($id->get_error_message());}
    set_post_thumbnail($id,$image);
    foreach(['_grv_ad_url'=>'https://amzn.to/3ILMrU3','_grv_ad_cta'=>$cta,'_grv_ad_campaign'=>'Kindle Colorsoft','_grv_ad_variant'=>$variant,'_grv_ad_position'=>'both'] as $key=>$value){update_post_meta($id,$key,$value);}
    if(!metadata_exists('post',$id,'_grv_ad_expires')){update_post_meta($id,'_grv_ad_expires','');}
    $result[]=['id'=>$id,'variant'=>$variant,'cta'=>$cta,'image'=>$image];
}
echo wp_json_encode($result,JSON_PRETTY_PRINT);
