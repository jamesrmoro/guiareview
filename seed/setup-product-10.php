<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';wp_set_current_user(1);
$id=1403;$item=require __DIR__.'/product-10.php';
$links=file_get_contents(__DIR__.'/../produtos-inserir/link-produtos.txt');
if(preg_match('/Produto\s+10\s*-\s*(https?:\/\/\S+)/iu',$links,$match)){update_post_meta($id,'affiliate_url',esc_url_raw(trim($match[1]),['http','https']));}
$content=str_replace('{{home}}',untrailingslashit(home_url()),$item['content']);
if(get_post_field('post_content',$id)!==$content){$result=wp_update_post(wp_slash(['ID'=>$id,'post_content'=>$content,'post_excerpt'=>$item['metadesc']]),true);if(is_wp_error($result)){throw new RuntimeException($result->get_error_message());}}
foreach(['focuskw'=>$item['key'],'title'=>$item['seo_title'].' %%sep%% %%sitename%%','metadesc'=>$item['metadesc'],'meta-robots-noindex'=>'2','meta-robots-nofollow'=>'0','opengraph-title'=>$item['seo_title'],'opengraph-description'=>$item['metadesc'],'twitter-title'=>$item['seo_title'],'twitter-description'=>$item['metadesc']] as $key=>$value){WPSEO_Meta::set_value($key,$value,$id);}
foreach(grv_get_field('gallery',$id) as $index=>$image){update_post_meta($image,'_wp_attachment_image_alt',$item['key'].' Cheunyar — imagem '.($index+1));}
update_post_meta($id,'grv_product_number',10);add_option('grv_affiliate_clicks_'.$id,'0','','no');
$cats=wp_get_post_categories($id);update_post_meta($id,'_yoast_wpseo_primary_category',$cats[0]);
foreach(['cheunyar'=>'Conheça os produtos Cheunyar cadastrados, incluindo a placa LED decorativa Shy Guy. Consulte iluminação, controles de brilho e informações de montagem. Veja as imagens e confirme a apresentação anunciada antes da compra.',
'shy-guy'=>'Confira o letreiro decorativo Shy Guy da Cheunyar. A apresentação anunciada possui iluminação LED colorida e controle de brilho. Consulte as imagens, os materiais e as medidas divulgadas. Confirme os acessórios incluídos e a variante com o vendedor.',
'decoracao-gamer'=>'Explore os produtos de decoração gamer cadastrados no Guia Review. Conheça a placa de neon LED Shy Guy e consulte desenho, iluminação e opções de instalação. Compare as medidas com o espaço disponível e confirme os detalhes da oferta.'] as $slug=>$description){
    $term=get_term_by('slug',$slug,'post_tag');if(!$term){throw new RuntimeException('Missing tag: '.$slug);}
    wp_update_term($term->term_id,'post_tag',['description'=>$description]);
    $desc=wp_trim_words($description,25,'');$values=WPSEO_Taxonomy_Meta::get_term_meta($term->term_id,'post_tag');
    WPSEO_Taxonomy_Meta::set_values($term->term_id,'post_tag',array_merge(is_array($values)?$values:[],['wpseo_title'=>$term->name.': produtos e características %%sep%% %%sitename%%','wpseo_desc'=>$desc,'wpseo_focuskw'=>$term->name,'wpseo_opengraph-title'=>$term->name.' | Guia Review','wpseo_opengraph-description'=>$desc,'wpseo_twitter-title'=>$term->name.' | Guia Review','wpseo_twitter-description'=>$desc]));
}
echo wp_json_encode(['id'=>$id,'url'=>get_permalink($id),'images'=>count(grv_get_field('gallery',$id)),'affiliate'=>get_post_meta($id,'affiliate_url',true)],JSON_PRETTY_PRINT);
