<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
$errors=[];$checked=0;
foreach([1373=>4,1378=>9] as $id=>$expected){
    $gallery=grv_get_field('gallery',$id);
    if(!is_array($gallery)||count($gallery)!==$expected){$errors[]='Wrong gallery count for '.$id;continue;}
    foreach($gallery as $attachment){
        $file=get_attached_file($attachment);
        if(!file_exists($file)||!getimagesize($file)){$errors[]='Invalid image '.$attachment;}
        $response=wp_remote_head(wp_get_attachment_image_url($attachment,'large'),['timeout'=>15]);
        if(is_wp_error($response)||wp_remote_retrieve_response_code($response)!==200){$errors[]='Image HTTP failed '.$attachment;}
        if(!get_post_meta($attachment,'_wp_attachment_image_alt',true)){$errors[]='Missing alt '.$attachment;}
        $checked++;
    }
    if((int)get_post_thumbnail_id($id)!==(int)$gallery[0]){$errors[]='Invalid featured image '.$id;}
    if(get_post_meta($id,'price',true)){$errors[]='Unexpected fixed price '.$id;}
}
echo wp_json_encode(['images_checked'=>$checked,'errors'=>$errors],JSON_PRETTY_PRINT);
exit($errors?1:0);
