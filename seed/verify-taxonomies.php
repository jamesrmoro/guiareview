<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
$failures=[];$counts=[];$urls=[];
foreach(['category','post_tag'] as $taxonomy){
    $terms=get_terms(['taxonomy'=>$taxonomy,'hide_empty'=>false]);$counts[$taxonomy]=count($terms);
    foreach($terms as $term){
        $seo=WPSEO_Taxonomy_Meta::get_term_meta($term->term_id,$taxonomy);
        foreach(['wpseo_title','wpseo_desc','wpseo_focuskw','wpseo_opengraph-title','wpseo_opengraph-description'] as $key){if(empty($seo[$key])){$failures[]=$term->slug.' missing '.$key;}}
        if(!$term->description){$failures[]=$term->slug.' missing description';}
        if($term->parent&&!term_exists($term->parent,'category')){$failures[]=$term->slug.' missing parent';}
        $url=get_term_link($term);$urls[]=$url;
        $response=wp_remote_get($url,['timeout'=>15]);
        if(is_wp_error($response)){ $failures[]=$term->slug.' HTTP failed'; continue; }
        $html=wp_remote_retrieve_body($response);
        if(wp_remote_retrieve_response_code($response)!==200){$failures[]=$term->slug.' HTTP not 200';}
        $noindex=$taxonomy==='post_tag'&&WPSEO_Options::get('noindex-tax-post_tag');
        if(!$noindex&&substr_count($html,'<meta name="description"')!==1){$failures[]=$term->slug.' invalid description count';}
        if(!$noindex&&substr_count($html,'<link rel="canonical"')!==1){$failures[]=$term->slug.' invalid canonical count';}
        if($noindex&&!preg_match('/<meta[^>]*robots[^>]*noindex/',$html)){$failures[]=$term->slug.' missing noindex';}
        if(!str_contains(mb_strtolower($html),mb_strtolower(esc_html($term->name)).': produtos')){$failures[]=$term->slug.' title not updated';}
        if(!str_contains($html,esc_html($term->description))){$failures[]=$term->slug.' description not visible';}
        if(preg_match('/Fatal error|Warning:|Notice:/',$html)){$failures[]=$term->slug.' PHP error';}
    }
}
$products=[];
foreach(get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1]) as $post){$products[]=['id'=>$post->ID,'categories'=>wp_get_post_categories($post->ID),'tags'=>wp_get_post_tags($post->ID,['fields'=>'ids'])];}
echo wp_json_encode(['counts'=>$counts,'published_products'=>count($products),'tags_noindex'=>WPSEO_Options::get('noindex-tax-post_tag'),'failures'=>$failures],JSON_PRETTY_PRINT);
exit($failures?1:0);
