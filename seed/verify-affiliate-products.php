<?php
if(PHP_SAPI!=='cli'){exit;}
define('WP_ADMIN',true);
require dirname(__DIR__,4).'/wp-load.php';
$items=json_decode(file_get_contents(__DIR__.'/affiliate-products-result.json'),true,512,JSON_THROW_ON_ERROR);
$items[10]=['id'=>1403,'affiliate'=>grv_get_field('url',1403)];
$checks=[];
$expect=function($condition,$message){if(!$condition){throw new RuntimeException($message);}};
foreach($items as $item){
    $id=$item['id'];$expect(grv_get_field('url',$id)===$item['affiliate'],'Affiliate precedence failed.');
    $response=wp_remote_get(get_permalink($id),['timeout'=>20]);$body=wp_remote_retrieve_body($response);
    $expect(!is_wp_error($response)&&wp_remote_retrieve_response_code($response)===200,'Product page failed.');
    $expect(!preg_match('/(?:Fatal error|Warning):/i',$body),'PHP page error.');
    $dom=new DOMDocument();libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="UTF-8">'.$body);$xp=new DOMXPath($dom);
    foreach($xp->query('//main//a[contains(concat(" ",normalize-space(@class)," ")," btn-buy ") or contains(concat(" ",normalize-space(@class)," ")," btn-buy-2 ") or starts-with(@aria-label,"Ver avaliações de")]') as $link){
        $linked_id=(int)$link->getAttribute('data-grv-product');
        $expect($linked_id&&$link->getAttribute('href')===grv_get_field('url',$linked_id)&&$link->getAttribute('data-grv-click-token')===wp_hash('grv-offer|'.$linked_id),'Incorrect affiliate link for '.$id.': '.$link->getAttribute('href'));
    }
    $response=wp_remote_request(grv_offer_url($id),['method'=>'HEAD','redirection'=>0,'timeout'=>20]);
    $expect(wp_remote_retrieve_response_code($response)===302,'Offer redirect failed.');
    $expect(wp_remote_retrieve_header($response,'location')===$item['affiliate'],'Wrong affiliate destination.');
    $checks['products_checked']=($checks['products_checked']??0)+1;
}
$id=1390;$key='grv_affiliate_clicks_'.$id;$original=get_option($key,'0');$other='grv_affiliate_clicks_1291';$other_original=get_option($other,'0');
$count=function()use($id,$key){wp_cache_delete($key,'options');return grv_affiliate_click_count($id);};
try{
    $before=$count();
    wp_remote_get(grv_offer_url($id),['redirection'=>0,'user-agent'=>'Mozilla/5.0 GuiaReview-QA','timeout'=>20]);
    $expect($count()===$before+1,'Visitor click was not counted.');
    $before=$count();
    foreach([
        ['method'=>'HEAD','user-agent'=>'Mozilla/5.0'],
        ['method'=>'GET','user-agent'=>'Googlebot'],
        ['method'=>'GET','user-agent'=>'Mozilla/5.0','headers'=>['Sec-Purpose'=>'prefetch']]
    ] as $request){$request+=['redirection'=>0,'timeout'=>20];$response=wp_remote_request(grv_offer_url($id),$request);$expect(wp_remote_retrieve_response_code($response)===302,'Excluded click redirect failed.');}
    $expect($count()===$before,'Excluded clicks were counted.');
    $response=wp_remote_post(grv_offer_url($id),['redirection'=>0,'timeout'=>20]);$expect(wp_remote_retrieve_response_code($response)===405,'POST was accepted.');
    $response=wp_remote_get(add_query_arg('grv_offer','999999999',home_url('/')),['redirection'=>0,'timeout'=>20]);$expect(wp_remote_retrieve_response_code($response)===404,'Invalid product was accepted.');
    update_option($key,'99',false);update_option($other,'100',false);
    $sorted=get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1,'orderby'=>'grv_clicks','order'=>'DESC','suppress_filters'=>false]);
    $expect($sorted[0]->ID===1291&&$sorted[1]->ID===$id&&count($sorted)===count($items),'Numeric descending order failed or zero-click products missing.');
    $sorted=get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1,'orderby'=>'grv_clicks','order'=>'ASC','suppress_filters'=>false]);
    $expect(end($sorted)->ID===1291,'Ascending order failed.');
    $checks['visitor_count_and_exclusions']='passed';$checks['numeric_sorting']='passed';
    $click=function($product,$token,$extra=[]) {
        return wp_remote_post(admin_url('admin-ajax.php'),$extra+['timeout'=>20,'user-agent'=>'Mozilla/5.0 GuiaReview-QA','body'=>['action'=>'grv_affiliate_click','product'=>$product,'token'=>$token]]);
    };
    $before=$count();$response=$click($id,wp_hash('grv-offer|'.$id));
    $expect(wp_remote_retrieve_response_code($response)===200&&$count()===$before+1,'Background click failed.');
    $before=$count();$response=$click($id,'invalid');$expect(wp_remote_retrieve_response_code($response)===403&&$count()===$before,'Invalid token accepted.');
    $response=$click($id,wp_hash('grv-offer|'.$id),['user-agent'=>'Googlebot']);$expect(wp_remote_retrieve_response_code($response)===200&&$count()===$before,'Bot background click counted.');
    $sessions=WP_Session_Tokens::get_instance(1);$expires=time()+120;$session=$sessions->create($expires);
    try{
        $cookie=wp_generate_auth_cookie(1,$expires,'logged_in',$session);
        $response=$click($id,wp_hash('grv-offer|'.$id),['headers'=>['Cookie'=>LOGGED_IN_COOKIE.'='.$cookie]]);
        $expect(wp_remote_retrieve_response_code($response)===200&&$count()===$before+1,'Admin background click was not counted.');
    }finally{$sessions->destroy($session);}
    $checks['background_tracking_including_admin']='passed';
}finally{
    global $wpdb;
    foreach([$key=>$original,$other=>$other_original] as $name=>$value){$wpdb->update($wpdb->options,['option_value'=>(string)$value],['option_name'=>$name]);wp_cache_delete($name,'options');}
}
foreach(grv_get_field('gallery',$id) as $image){$expect(is_file(get_attached_file($image))&&getimagesize(get_attached_file($image)),'Product 9 image missing.');$expect((bool)get_post_meta($image,'_wp_attachment_image_alt',true),'Product 9 image missing alt.');}
$checks['product_9_images']=count(grv_get_field('gallery',$id));
foreach(grv_get_field('gallery',1403) as $image){$expect(is_file(get_attached_file($image))&&getimagesize(get_attached_file($image)),'Product 10 image missing.');$expect((bool)get_post_meta($image,'_wp_attachment_image_alt',true),'Product 10 image missing alt.');}
$checks['product_10_images']=count(grv_get_field('gallery',1403));
$columns=apply_filters('manage_post_posts_columns',[]);$expect(isset($columns['grv_affiliate'],$columns['grv_clicks']),'Admin columns missing.');
ob_start();do_action('manage_post_posts_custom_column','grv_affiliate',$id);$column=ob_get_clean();$expect(str_contains($column,'https://meli.la/1Gr5Mpy')&&str_contains($column,'Copiar link'),'Copy column failed.');
$checks['copy_column']='passed';$checks['test_clicks_removed']=grv_affiliate_click_count($id)===(int)$original;
$columns=apply_filters('manage_post_posts_columns',['cb'=>'','title'=>'Título']);$expect(isset($columns['grv_product_number']),'Product number column missing.');
$ordered=get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1,'orderby'=>'grv_product_number','order'=>'ASC','suppress_filters'=>false]);
$numbers=array_map(fn($post)=>(int)get_post_meta($post->ID,'grv_product_number',true),$ordered);$expect($numbers===range(1,10),'Product number order is incorrect.');
$checks['product_numbers']=$numbers;
echo wp_json_encode($checks,JSON_PRETTY_PRINT);
