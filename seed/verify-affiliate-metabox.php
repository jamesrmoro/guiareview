<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
$id=1390;$original=get_post_meta($id,'affiliate_url',true);$request=$_POST;
$expect=function($ok,$message){if(!$ok){throw new RuntimeException($message);}};
try{
    wp_set_current_user(1);
    $_POST=['grv_product_nonce'=>'invalid','grv_product'=>['affiliate_url'=>'https://example.org/invalid']];
    do_action('save_post_post',$id,get_post($id),true);
    $expect(get_post_meta($id,'affiliate_url',true)===$original,'Invalid nonce changed the affiliate URL.');
    $_POST['grv_product_nonce']=wp_create_nonce('grv_product_save');$_POST['grv_product']['affiliate_url']='https://example.org/affiliate-test?tag=123';
    do_action('save_post_post',$id,get_post($id),true);
    $expect(grv_get_field('url',$id)==='https://example.org/affiliate-test?tag=123','Native field did not save or take priority.');
    wp_set_current_user(0);$_POST['grv_product_nonce']=wp_create_nonce('grv_product_save');$_POST['grv_product']['affiliate_url']='https://example.org/unauthorized';
    do_action('save_post_post',$id,get_post($id),true);
    $expect(grv_get_field('url',$id)==='https://example.org/affiliate-test?tag=123','Unauthorized user changed the affiliate URL.');
    echo "Native affiliate field: save, precedence, nonce and capability checks passed.\n";
}finally{update_post_meta($id,'affiliate_url',$original);$_POST=$request;wp_set_current_user(0);}
