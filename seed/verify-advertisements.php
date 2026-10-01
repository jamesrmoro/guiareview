<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
$expect=function($condition,$message){if(!$condition){throw new RuntimeException($message);}};
$ads=get_posts(['post_type'=>'grv_ad','post_status'=>'publish','numberposts'=>-1]);$expect(count($ads)===2,'Expected two A/B variants.');
$distribution=[];for($i=0;$i<300;$i++){$selected=grv_select_ads();$expect($selected['modal']&&$selected['footer'],'Selection missing.');$expect($selected['modal']['id']===$selected['footer']['id'],'Placement variants mismatch.');$distribution[$selected['modal']['variant']]=($distribution[$selected['modal']['variant']]??0)+1;}
$expect(count($distribution)===2,'Both variants were not selected.');
$id=$ads[0]->ID;$campaign=get_post_meta($id,'_grv_ad_campaign',true);$expires=get_post_meta($id,'_grv_ad_expires',true);
try{update_post_meta($id,'_grv_ad_expires',time()-1);$expect(!grv_ad_active($id),'Expired ad remained eligible.');for($i=0;$i<10;$i++){$expect(grv_select_ads([$campaign=>$id])['modal']['id']!==$id,'Expired preferred variant was selected.');}}
finally{update_post_meta($id,'_grv_ad_expires',$expires);}
$expect(grv_select_ads([$campaign=>$id])['modal']['id']===$id,'Session variant was not preserved.');
$positions=[];foreach($ads as $ad){$positions[$ad->ID]=get_post_meta($ad->ID,'_grv_ad_position',true);}
try{update_post_meta($ads[0]->ID,'_grv_ad_position','modal');update_post_meta($ads[1]->ID,'_grv_ad_position','footer');for($i=0;$i<10;$i++){$selection=grv_select_ads();$expect($selection['modal']['id']===$ads[0]->ID&&$selection['footer']['id']===$ads[1]->ID,'Position-specific selection failed.');}}
finally{foreach($positions as $ad=>$value){update_post_meta($ad,'_grv_ad_position',$value);}}
$saved=[];foreach(['views','clicks'] as $kind){$saved['grv_ad_'.$kind.'_'.$id]=get_option('grv_ad_'.$kind.'_'.$id,false);}
$event=function($kind,$token,$agent='Mozilla/5.0 GuiaReview-QA')use($id){return wp_remote_post(admin_url('admin-ajax.php'),['timeout'=>20,'user-agent'=>$agent,'body'=>['action'=>'grv_ad_event','ad'=>$id,'event'=>$kind,'token'=>$token]]);};
try{
    foreach(['views','clicks'] as $kind){$key='grv_ad_'.$kind.'_'.$id;$before=grv_ad_count($id,$kind);$response=$event($kind,wp_hash('grv-ad|'.$id));wp_cache_delete($key,'options');wp_cache_delete('notoptions','options');$expect(wp_remote_retrieve_response_code($response)===200&&grv_ad_count($id,$kind)===$before+1,'Ad event failed.');
        $response=$event($kind,'invalid');$expect(wp_remote_retrieve_response_code($response)===403,'Invalid event signature accepted.');
        $before=grv_ad_count($id,$kind);$response=$event($kind,wp_hash('grv-ad|'.$id),'Googlebot');wp_cache_delete($key,'options');$expect(grv_ad_count($id,$kind)===$before,'Bot ad event counted.');}
}finally{global $wpdb;foreach($saved as $key=>$value){if($value===false){$wpdb->delete($wpdb->options,['option_name'=>$key]);}else{$wpdb->update($wpdb->options,['option_value'=>$value],['option_name'=>$key]);}wp_cache_delete($key,'options');wp_cache_delete('notoptions','options');}}
$response=wp_remote_post(admin_url('admin-ajax.php'),['timeout'=>20,'body'=>['action'=>'grv_get_ads','variants'=>'{}']]);$json=json_decode(wp_remote_retrieve_body($response),true);$expect(!empty($json['success'])&&!empty($json['data']['modal']),'Public selection endpoint failed.');
foreach([home_url('/'),get_permalink(1403),home_url('/contato/')] as $url){$response=wp_remote_get($url,['timeout'=>20]);$body=wp_remote_retrieve_body($response);$expect(wp_remote_retrieve_response_code($response)===200&&!preg_match('/(?:Fatal error|Warning):/',$body),'Page failed.');$expect(str_contains($body,'grvAdModal')&&str_contains($body,'site-widgets.js'),'Widgets missing.');if($url===get_permalink(1403)){$expect(str_contains($body,'grv-mobile-buy')&&str_contains($body,'https://link.amazon/B0c0seFve'),'Mobile affiliate button missing.');}}
echo wp_json_encode(['ads'=>count($ads),'random_distribution'=>$distribution,'expiration'=>'passed','session_consistency'=>'passed','clicks_impressions_signatures'=>'passed','pages'=>'passed','test_counts_removed'=>true],JSON_PRETTY_PRINT);
