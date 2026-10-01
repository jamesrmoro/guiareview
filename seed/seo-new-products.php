<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
wp_set_current_user(1);global $wpdb;
$items=require __DIR__.'/products-7-8.php';
$builder=YoastSEO()->classes->get(\Yoast\WP\SEO\Builders\Indexable_Builder::class);
$repository=YoastSEO()->classes->get(\Yoast\WP\SEO\Repositories\Indexable_Repository::class);
$report=[];
foreach($items as $number=>$item){
    $id=(int)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_title=%s AND post_type='post' AND post_status='publish' LIMIT 1",$item['title']));
    if(!$id){throw new RuntimeException('Product not imported: '.$number);}
    $content=str_replace('{{home}}',untrailingslashit(home_url()),$item['content']);
    $updated=wp_update_post(wp_slash(['ID'=>$id,'post_content'=>$content,'post_excerpt'=>$item['metadesc']]),true);
    if(is_wp_error($updated)){throw new RuntimeException($updated->get_error_message());}
    foreach(['focuskw'=>$item['key'],'title'=>$item['seo_title'].' %%sep%% %%sitename%%','metadesc'=>$item['metadesc'],'meta-robots-noindex'=>'2','meta-robots-nofollow'=>'0','opengraph-title'=>$item['seo_title'],'opengraph-description'=>$item['metadesc'],'twitter-title'=>$item['seo_title'],'twitter-description'=>$item['metadesc']] as $key=>$value){WPSEO_Meta::set_value($key,$value,$id);}
    foreach(get_children(['post_parent'=>$id,'post_type'=>'attachment','post_mime_type'=>'image']) as $image){update_post_meta($image->ID,'_wp_attachment_image_alt',$item['key'].' — imagem do produto');}
    $categories=wp_get_post_categories($id);
    if($categories){update_post_meta($id,'_yoast_wpseo_primary_category',$categories[0]);}
    $builder->build_for_id_and_type($id,'post',$repository->find_by_id_and_type($id,'post',false));
    $report[$number]=['id'=>$id,'url'=>get_permalink($id),'images'=>count(grv_get_field('gallery',$id)?:[])];
}
$descriptions=[
'category'=>[
'acessorios'=>['Acessórios de informática: conheça teclados e periféricos cadastrados no Guia Review. Consulte conexões, formato, compatibilidade e características para comparar os equipamentos antes da compra. A seleção inclui o teclado Razer Huntsman V3 Pro Mini; confira layout e apresentação na página do produto.','Acessórios de informática: conheça teclados Razer e periféricos. Confira formato, conexões, layout e características dos equipamentos cadastrados.'],
'teclados-mouses-e-perifericos'=>['Conheça os teclados, mouses e periféricos cadastrados no Guia Review. A seleção atual inclui o Razer Huntsman V3 Pro Mini na cor preta. Veja informações de switches, iluminação, layout e conexão. Antes de escolher um equipamento, verifique a compatibilidade e os acessórios incluídos na oferta.','Teclados, Mouses e Periféricos: consulte o Razer Huntsman V3 Pro Mini, switches, RGB e conexão USB-C. Veja imagens e detalhes antes de comprar.'],
'teclados'=>['Explore os teclados cadastrados e conheça o Razer Huntsman V3 Pro Mini. Consulte formato compacto, switches ópticos analógicos, Rapid Trigger e iluminação RGB. Compare o layout e a conexão com suas necessidades e confirme a apresentação anunciada na loja antes da compra.','Teclados: conheça o Razer Huntsman V3 Pro Mini compacto, switches ópticos analógicos, Rapid Trigger e RGB. Confira layout, conexões e imagens.'],
'monitores'=>['Conheça os monitores cadastrados no Guia Review. Consulte o KTC H27E6 de 27 polegadas, com painel Fast IPS QHD e atualização de 300 Hz. Compare resolução, conexões e ajustes da base e confirme os requisitos para usar as taxas máximas. Veja imagens e características na página do equipamento.','Monitores: conheça o KTC H27E6 de 27 polegadas, Fast IPS QHD e 300 Hz. Confira resolução, conexões, ajustes e características antes de comprar.'],
],
'post_tag'=>[
'razer'=>['Conheça os produtos Razer cadastrados, incluindo o teclado Huntsman V3 Pro Mini preto. Consulte formato, switches, iluminação RGB e conexões e confira a apresentação antes da compra.','Razer: conheça o teclado Huntsman V3 Pro Mini preto. Veja switches ópticos analógicos, formato compacto, iluminação RGB e conexão USB-C.'],
'huntsman-v3-pro-mini'=>['Confira o Huntsman V3 Pro Mini da Razer e consulte as características do teclado compacto anunciado. Veja switches, recursos de configuração, layout e conexões na página do produto.','Huntsman V3 Pro Mini: confira o teclado Razer compacto com switches ópticos analógicos, Rapid Trigger e RGB. Veja layout, conexões e imagens.'],
'teclado-gamer'=>['Explore o teclado gamer cadastrado no Guia Review: Razer Huntsman V3 Pro Mini. Confira switches, iluminação, formato e layout e consulte os requisitos de configuração no fabricante.','Teclado gamer: conheça o Razer Huntsman V3 Pro Mini, formato compacto, switches ópticos analógicos e RGB. Confira características e imagens.'],
'ktc'=>['Conheça os produtos KTC cadastrados no Guia Review. Consulte o monitor H27E6 de 27 polegadas com painel Fast IPS QHD e atualização de 300 Hz. Veja imagens e características da apresentação anunciada.','KTC: conheça o monitor H27E6 de 27 polegadas com painel Fast IPS QHD e 300 Hz. Confira imagens, conexões e ajustes da base antes da compra.'],
'h27e6'=>['Explore o H27E6 da KTC, monitor de 27 polegadas com resolução de 2560 x 1440 e painel Fast IPS. Consulte atualização, conexões e ajustes na página do equipamento e confirme a variante vendida.','H27E6: consulte o monitor KTC de 27 polegadas, Fast IPS QHD e 300 Hz, com até 320 Hz em overclock. Veja imagens e detalhes da apresentação.'],
'monitor-gamer'=>['Consulte o monitor gamer KTC H27E6 cadastrado no Guia Review. Confira painel, resolução, atualização e conectividade. A taxa máxima depende da configuração; consulte o fabricante antes de ativar o overclock.','Monitor gamer: conheça o KTC H27E6, Fast IPS QHD de 27 polegadas e 300 Hz. Confira atualização máxima, conexões, imagens e ajustes da base.'],
]];
foreach($descriptions as $taxonomy=>$terms){foreach($terms as $slug=>$data){
    $term=get_term_by('slug',$slug,$taxonomy);if(!$term){throw new RuntimeException('Missing taxonomy '.$slug);}
    wp_update_term($term->term_id,$taxonomy,['description'=>$data[0]]);
    $values=WPSEO_Taxonomy_Meta::get_term_meta($term,$taxonomy);
    foreach(['title'=>$term->name.': produtos %%sep%% %%sitename%%','desc'=>$data[1],'focuskw'=>$term->name,'opengraph-title'=>$term->name.' | Guia Review','opengraph-description'=>$data[1],'twitter-description'=>$data[1]] as $key=>$value){$values['wpseo_'.$key]=$value;}
    WPSEO_Taxonomy_Meta::set_values($term->term_id,$taxonomy,$values);
    if($taxonomy==='category'){$builder->build_for_id_and_type($term->term_id,'term',$repository->find_by_id_and_type($term->term_id,'term',false));}
}}
// Refresh the parent descriptions to reflect the enlarged catalogue.
$term=get_term_by('slug','computadores-e-informatica','category');
$description='Conheça os produtos de computadores e informática cadastrados no Guia Review: notebooks Dell e Apple, teclado Razer e monitor KTC. Compare processador, memória, armazenamento, tela e conexões nas páginas dos equipamentos. Explore as subcategorias, confira imagens e identifique a apresentação anunciada antes de acessar a oferta na loja.';
wp_update_term($term->term_id,'category',['description'=>$description]);
$values=WPSEO_Taxonomy_Meta::get_term_meta($term,'category');$values['wpseo_desc']='Computadores e Informática: compare notebooks Dell e Apple, teclado Razer e monitor KTC. Consulte configurações, imagens e características no Guia Review.';
$values['wpseo_opengraph-description']=$values['wpseo_desc'];$values['wpseo_twitter-description']=$values['wpseo_desc'];
WPSEO_Taxonomy_Meta::set_values($term->term_id,'category',$values);
$builder->build_for_id_and_type($term->term_id,'term',$repository->find_by_id_and_type($term->term_id,'term',false));
file_put_contents(__DIR__.'/products-7-8-result.json',wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
echo wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
