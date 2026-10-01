<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
$before=json_decode(file_get_contents(sys_get_temp_dir().'/guiareview-deploy-before-test.json'),true,512,JSON_THROW_ON_ERROR);$changes=[];
foreach($before['posts'] as $id=>$item){$post=get_post($id);if(!$post){$changes[]='missing '.$id;continue;}
    foreach(['title'=>'post_title','content'=>'post_content','excerpt'=>'post_excerpt','slug'=>'post_name','modified'=>'post_modified'] as $key=>$field){if($item[$key]!==$post->$field){$changes[]=$id.' '.$key;}}
    $meta=get_post_meta($id);foreach($item['meta'] as $key=>$value){if(($meta[$key]??null)!==$value){$changes[]=$id.' meta '.$key;}}
}
foreach($before['terms'] as $id=>$item){$term=get_term($id);if(!$term||$term->description!==$item['description']||(int)$term->parent!==(int)$item['parent']||get_term_meta($id)!==$item['meta']){$changes[]='term '.$id;}}
global $wpdb;$counts=$wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE 'grv_affiliate_clicks_%' OR option_name LIKE 'grv_ad_clicks_%' OR option_name LIKE 'grv_ad_views_%' ORDER BY option_name",ARRAY_A);
if($counts!==$before['counts']){$changes[]='counts';}
echo wp_json_encode(['posts_checked'=>count($before['posts']),'terms_checked'=>count($before['terms']),'differences'=>$changes,'counts_preserved'=>$counts===$before['counts']],JSON_PRETTY_PRINT);
if($changes){exit(1);}
