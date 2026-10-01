<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
wp_set_current_user(1);
global $wpdb;
$terms=get_terms(['taxonomy'=>['category','post_tag'],'hide_empty'=>false]);
$keep=[];$all=[];
foreach($terms as $term){
    $all[$term->term_id]=$term;
    $post_ids=$wpdb->get_col($wpdb->prepare("SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->term_relationships} r ON r.object_id=p.ID WHERE r.term_taxonomy_id=%d AND p.post_type='post' AND p.post_status NOT IN ('trash','auto-draft')",$term->term_taxonomy_id));
    if($post_ids){$keep[$term->term_id]=true;}
}
foreach(array_keys($keep) as $id){if($all[$id]->taxonomy==='category'){foreach(get_ancestors($id,'category') as $ancestor){$keep[$ancestor]=true;}}}
$keep[(int)get_option('default_category')]=true;
$remove=array_values(array_filter($terms,fn($term)=>!isset($keep[$term->term_id])));
$retained=array_values(array_filter($terms,fn($term)=>isset($keep[$term->term_id])));
$descriptions=require __DIR__.'/taxonomy-descriptions.php';
$tag_meta=[
'apple-m5'=>'Apple M5: conheça o MacBook Air de 15 polegadas, memória e SSD da versão cadastrada. Veja imagens e características no Guia Review.',
'macbook-air'=>'MacBook Air com M5, 16 GB de memória e SSD de 512 GB: confira a versão de 15 polegadas meia-noite, imagens e características no Guia Review.',
'notebook-apple'=>'Notebook Apple: conheça o MacBook Air M5 de 15 polegadas. Confira memória, SSD, tela, câmera e conexões da configuração anunciada.',
'notebook-dell'=>'Notebook Dell: confira o Dell 15 com Core i7-1355U, 16 GB DDR5 e SSD de 1 TB. Veja tela, conexões e imagens da versão no Guia Review.',
'dell-15'=>'Dell 15 DC15-I71355U-A100: consulte processador, memória, armazenamento e tela. Veja imagens e características da configuração cadastrada.',
'intel-core-i7'=>'Intel Core i7: conheça o Dell 15 com i7-1355U de 13ª geração. Confira também memória, armazenamento e gráficos da configuração cadastrada.',
'hipismo'=>'Hipismo: conheça acessórios Tough 1 para cavalos. Confira materiais, fechos, tamanhos e informações para comparar os produtos cadastrados.',
'protecao-para-cavalos'=>'Proteção para cavalos: confira as botas Tough 1 em neoprene perfurado, tamanho P e azul royal. Veja características e informações de ajuste.',
'tough-1'=>'Tough 1: conheça as botas de proteção para cavalos em neoprene perfurado. Confira fechos, tamanho P, cor e características no Guia Review.',
'virbac'=>'Virbac: consulte a apresentação do Cyclavance de 50 mL, imagens e informações do fabricante. Produto de uso veterinário; siga orientação profissional.',
'cyclavance'=>'Cyclavance Virbac 50 mL: veja imagens da apresentação e informações oficiais do fabricante. Consulte a bula e a orientação do médico veterinário.',
'saude-de-caes'=>'Saúde de cães: consulte apresentações, imagens e fontes dos produtos veterinários cadastrados. Siga a bula e a orientação do médico veterinário.',
'chocadeira-automatica'=>'Chocadeira automática: conheça a Juli 120 digital da Chocmaster, 220 V e com ovoscópio. Confira capacidade, dimensões e características anunciadas.',
'juli-120'=>'Juli 120: consulte a chocadeira automática digital Chocmaster de 220 V com ovoscópio. Veja capacidade, dimensões e características do equipamento.',
'chocmaster'=>'Chocmaster: conheça a chocadeira Juli 120 automática digital, 220 V e com ovoscópio. Consulte imagens, capacidade e dimensões no Guia Review.',
'feandrea'=>'Feandrea: conheça a árvore para gatos de 168 cm cinza fumê. Confira postes arranhadores, espaços de descanso, materiais e informações de montagem.',
'arranhador-para-gatos'=>'Arranhador para gatos: confira a torre Feandrea de 168 cm, postes, rampa, tocas e poleiros. Veja medidas, materiais e informações de montagem.',
'torre-para-gatos'=>'Torre para gatos: conheça a Feandrea de 168 cm cinza fumê, com postes, tocas, poleiros, cesta e rede. Confira imagens, medidas e materiais.',
];
foreach($retained as $term){if(!isset($descriptions[$term->taxonomy][$term->slug])){throw new RuntimeException('Missing description: '.$term->taxonomy.'/'.$term->slug);}}
$summary=['remove'=>array_map(fn($t)=>['id'=>$t->term_id,'taxonomy'=>$t->taxonomy,'name'=>$t->name],$remove),'keep'=>array_map(fn($t)=>['id'=>$t->term_id,'taxonomy'=>$t->taxonomy,'name'=>$t->name],$retained)];
if(!in_array('--apply',$argv,true)){echo wp_json_encode($summary,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);exit;}
$backup=['terms'=>[],'yoast_taxonomy'=>get_option('wpseo_taxonomy_meta'),'yoast_titles'=>get_option('wpseo_titles'),'menus'=>[]];
foreach($terms as $term){
    $backup['terms'][]=['term'=>(array)$term,'meta'=>get_term_meta($term->term_id),'relationships'=>$wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d",$term->term_taxonomy_id),ARRAY_A)];
}
foreach(wp_get_nav_menus() as $menu){foreach(wp_get_nav_menu_items($menu->term_id)?:[] as $item){$backup['menus'][]=['item'=>(array)$item,'meta'=>get_post_meta($item->ID)];}}
$backup_path=sys_get_temp_dir().'/guiareview-taxonomies-before-'.date('Ymd-His').'.json';
if(!file_put_contents($backup_path,wp_json_encode($backup,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))){throw new RuntimeException('Backup failed.');}
// Delete descendants before parents to avoid reparenting unused branches.
usort($remove,fn($a,$b)=>count(get_ancestors($b->term_id,$b->taxonomy))<=>count(get_ancestors($a->term_id,$a->taxonomy)));
$counts=['category'=>0,'post_tag'=>0];
foreach($remove as $term){
    $deleted=wp_delete_term($term->term_id,$term->taxonomy);
    if(is_wp_error($deleted)||!$deleted){throw new RuntimeException('Cannot delete term '.$term->term_id);}
    $counts[$term->taxonomy]++;
}
$builder=YoastSEO()->classes->get(\Yoast\WP\SEO\Builders\Indexable_Builder::class);
$repository=YoastSEO()->classes->get(\Yoast\WP\SEO\Repositories\Indexable_Repository::class);
foreach(['title-tax-post_tag'=>'%%term_title%%: produtos %%page%% %%sep%% %%sitename%%','metadesc-tax-post_tag'=>'%%term_description%%','social-title-tax-post_tag'=>'%%term_title%% | Guia Review','social-description-tax-post_tag'=>'%%term_description%%'] as $key=>$value){WPSEO_Options::set($key,$value);}
foreach($retained as $term){
    $data=$descriptions[$term->taxonomy][$term->slug];
    if($term->taxonomy==='category'){$description=$data[0];$meta=$data[1];$keyword=$term->name;}
    else{$keyword=$data[0];$description=$data[1];$meta=$tag_meta[$term->slug];}
    $updated=wp_update_term($term->term_id,$term->taxonomy,['description'=>$description]);
    if(is_wp_error($updated)){throw new RuntimeException($updated->get_error_message());}
    $seo_values=WPSEO_Taxonomy_Meta::get_term_meta($term,$term->taxonomy);
    foreach(['title'=>$keyword.': produtos %%sep%% %%sitename%%','desc'=>$meta,'focuskw'=>$keyword,'opengraph-title'=>$keyword.' | Guia Review','opengraph-description'=>$meta,'twitter-title'=>$keyword.' | Guia Review','twitter-description'=>$meta] as $key=>$value){$seo_values['wpseo_'.$key]=$value;}
    $image=get_term_meta($term->term_id,'image',true);
    if(is_numeric($image)&&$image){$seo_values['wpseo_opengraph-image-id']=(string)$image;$seo_values['wpseo_opengraph-image']=wp_get_attachment_image_url((int)$image,'large');}
    WPSEO_Taxonomy_Meta::set_values($term->term_id,$term->taxonomy,$seo_values);
    if($term->taxonomy==='category'||!WPSEO_Options::get('noindex-tax-post_tag')){$builder->build_for_id_and_type($term->term_id,'term',$repository->find_by_id_and_type($term->term_id,'term',false));}
}
// Remove obsolete taxonomy metadata as well as the deleted terms.
$seo=get_option('wpseo_taxonomy_meta',[]);
foreach($remove as $term){unset($seo[$term->taxonomy][$term->term_id]);}
update_option('wpseo_taxonomy_meta',$seo);
flush_rewrite_rules(false);
echo wp_json_encode(['removed'=>$counts,'retained'=>array_count_values(array_map(fn($t)=>$t->taxonomy,$retained)),'backup'=>$backup_path],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
