<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';wp_set_current_user(1);global $wpdb;
$ids=[1=>1291,2=>1302,3=>1325,4=>1327,5=>1337,6=>1348,7=>1373,8=>1378,9=>1390];
$text=file_get_contents(__DIR__.'/../produtos-inserir/link-produtos.txt');
preg_match_all('/Produto\s+(\d+)\s*-\s*(https?:\/\/\S+)/iu',$text,$matches,PREG_SET_ORDER);
$links=[];foreach($matches as $match){$links[(int)$match[1]]=trim($match[2]);}
if(array_diff(array_keys($ids),array_keys($links))){throw new RuntimeException('Missing affiliate links.');}
$backup=[];
foreach($ids as $number=>$id){if(get_post_status($id)!=='publish'||!wp_http_validate_url($links[$number])){throw new RuntimeException('Invalid product or affiliate URL.');}$backup[$id]=['url'=>get_post_meta($id,'url',true),'affiliate_url'=>get_post_meta($id,'affiliate_url',true)];}
file_put_contents(sys_get_temp_dir().'/guiareview-affiliate-links-before-'.date('Ymd-His').'.json',wp_json_encode($backup,JSON_PRETTY_PRINT));
foreach($ids as $number=>$id){update_post_meta($id,'affiliate_url',esc_url_raw($links[$number],['http','https']));add_option('grv_affiliate_clicks_'.$id,'0','','no');update_post_meta($id,'grv_product_number',$number);}
$item=require __DIR__.'/product-9.php';$id=$ids[9];
$content=str_replace('{{home}}',untrailingslashit(home_url()),$item['content']);
$content=str_replace('href="'.$item['url'].'"','href="'.grv_get_field('url',$id).'" '.grv_offer_attributes($id),$content);
$content=str_replace('rel="nofollow"','rel="nofollow sponsored noopener"',$content);
$result=wp_update_post(wp_slash(['ID'=>$id,'post_content'=>$content,'post_excerpt'=>$item['metadesc']]),true);
if(is_wp_error($result)){throw new RuntimeException($result->get_error_message());}
foreach(['focuskw'=>$item['key'],'title'=>$item['seo_title'].' %%sep%% %%sitename%%','metadesc'=>$item['metadesc'],'meta-robots-noindex'=>'2','meta-robots-nofollow'=>'0','opengraph-title'=>$item['seo_title'],'opengraph-description'=>$item['metadesc'],'twitter-title'=>$item['seo_title'],'twitter-description'=>$item['metadesc']] as $key=>$value){WPSEO_Meta::set_value($key,$value,$id);}
foreach(grv_get_field('gallery',$id) as $index=>$image){update_post_meta($image,'_wp_attachment_image_alt',$item['key'].' — número decorativo, imagem '.($index+1));}
$cats=wp_get_post_categories($id);update_post_meta($id,'_yoast_wpseo_primary_category',$cats[0]);
$descriptions=[
'category'=>[
'casa'=>'Conheça os produtos para casa cadastrados no Guia Review. A seleção inclui o painel neon LED de 50 cm para decoração de festas e ambientes internos. Consulte imagens, medidas, materiais e opções anunciadas. Confirme a apresentação escolhida e os acessórios incluídos na loja antes da compra.',
'decoracao'=>'Explore os itens de decoração cadastrados no Guia Review. Conheça o painel de números em LED neon com altura anunciada de 50 cm e estrutura em acrílico. Veja opções de iluminação e instalação para comparar a apresentação com o espaço disponível. Confirme as medidas e a variante na loja.',
'placas-de-neon'=>'Consulte as placas de neon cadastradas e conheça o painel LED decorativo com opções de números de 0 a 9. A apresentação anunciada tem 50 cm de altura e acrílico transparente. Veja as fotos, a cor da luz, a alimentação e as orientações de instalação. Confirme o número e a quantidade de peças antes da compra.'
],
'post_tag'=>[
'multi-neon-led'=>'Conheça os produtos Multi Neon Led cadastrados, incluindo o painel de números em LED neon para festas. Veja imagens, estrutura em acrílico e opções de iluminação. Confira a variante anunciada e as características com o vendedor.',
'numeros-em-neon'=>'Confira os números em neon cadastrados para decoração de festas e ambientes internos. Consulte o painel LED de 50 cm, as opções de números e as medidas divulgadas. Confirme a quantidade de peças e a apresentação escolhida antes da compra.',
'decoracao-de-festas'=>'Explore os produtos para decoração de festas cadastrados no Guia Review. Conheça o painel decorativo de números em LED neon e consulte materiais, iluminação e instalação. Veja as fotos e confirme as opções disponíveis diretamente na oferta.'
]];
foreach($descriptions as $taxonomy=>$entries){foreach($entries as $slug=>$description){
    $term=get_term_by('slug',$slug,$taxonomy);if(!$term){throw new RuntimeException('Missing term: '.$slug);}
    wp_update_term($term->term_id,$taxonomy,['description'=>$description]);
    $desc=wp_trim_words($description,26,'');
    $values=WPSEO_Taxonomy_Meta::get_term_meta($term->term_id,$taxonomy);
    $values=array_merge(is_array($values)?$values:[],['wpseo_title'=>$term->name.': produtos e características %%sep%% %%sitename%%','wpseo_desc'=>$desc,'wpseo_focuskw'=>$term->name,'wpseo_opengraph-title'=>$term->name.' | Guia Review','wpseo_opengraph-description'=>$desc,'wpseo_twitter-title'=>$term->name.' | Guia Review','wpseo_twitter-description'=>$desc]);
    WPSEO_Taxonomy_Meta::set_values($term->term_id,$taxonomy,$values);
    if($taxonomy==='category'){update_term_meta($term->term_id,'image',get_post_thumbnail_id($id));}
}}
$report=[];foreach($ids as $number=>$id){$report[$number]=['id'=>$id,'title'=>get_the_title($id),'affiliate'=>grv_get_field('url',$id),'offer'=>grv_offer_url($id),'clicks'=>grv_affiliate_click_count($id)];}
file_put_contents(__DIR__.'/affiliate-products-result.json',wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
echo wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
