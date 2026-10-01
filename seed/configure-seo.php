<?php
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname(__DIR__, 4) . '/wp-load.php';
if ( ! defined('WPSEO_VERSION') ) { throw new RuntimeException('Yoast SEO is not active.'); }
wp_set_current_user(1);
$posts = get_posts(['post_type'=>['post','page'],'post_status'=>'publish','numberposts'=>-1]);
$backup = ['options'=>['wpseo'=>get_option('wpseo'),'wpseo_titles'=>get_option('wpseo_titles')],'posts'=>[],'terms'=>get_option('wpseo_taxonomy_meta')];
foreach($posts as $post) { $backup['posts'][$post->ID]=['post'=>(array)$post,'meta'=>get_post_meta($post->ID)]; }
$backup_path = sys_get_temp_dir().'/guiareview-seo-before-'.date('Ymd-His').'.json';
file_put_contents($backup_path,wp_json_encode($backup,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
$settings = [
'enable_xml_sitemap'=>true,'content_analysis_active'=>true,'keyword_analysis_active'=>true,
'website_name'=>'Guia Review','company_name'=>'Guia Review','company_or_person'=>'company',
'company_logo'=>get_template_directory_uri().'/favicon/images/android-chrome-512x512.png',
'title-home-wpseo'=>'Guia Review: produtos, características e comparações',
'metadesc-home-wpseo'=>'Encontre produtos de informática, casa, esporte e Pet Shop no Guia Review. Compare características, veja imagens e consulte as ofertas nas lojas.',
'open_graph_frontpage_title'=>'Guia Review: conheça e compare produtos',
'open_graph_frontpage_desc'=>'Características, imagens e informações para comparar produtos antes de comprar.',
'open_graph_frontpage_image'=>get_template_directory_uri().'/favicon/images/android-chrome-512x512.png',
'title-post'=>'%%title%% %%page%% %%sep%% %%sitename%%','metadesc-post'=>'%%excerpt%%',
'title-page'=>'%%title%% %%page%% %%sep%% %%sitename%%','metadesc-page'=>'%%excerpt%%',
'title-tax-category'=>'%%term_title%%: produtos %%page%% %%sep%% %%sitename%%',
'metadesc-tax-category'=>'%%term_description%%',
'title-search-wpseo'=>'Busca por %%searchphrase%% %%page%% %%sep%% %%sitename%%',
'title-404-wpseo'=>'Página não encontrada %%sep%% %%sitename%%',
'breadcrumbs-home'=>'Início','breadcrumbs-searchprefix'=>'Busca por','breadcrumbs-404crumb'=>'Página não encontrada',
'noindex-post'=>false,'noindex-page'=>false,'noindex-tax-category'=>false,
'noindex-tax-post_tag'=>true,'noindex-author-wpseo'=>true,'noindex-archive-wpseo'=>true,
'schema-article-type-post'=>'None','publishing_principles_id'=>134,
];
foreach($settings as $key=>$value) { WPSEO_Options::set($key,$value); }
update_option('blog_public','1');
$products = require __DIR__.'/seo-products.php';
$concise_bullets = [
1291 => ['Processador: Intel Core i7-1355U de 13ª geração, com 10 núcleos.','Memória e armazenamento: 16 GB DDR5 e SSD de 1 TB.','Tela: 15,6 polegadas Full HD, antirreflexo e 120 Hz.','Conexões: USB 2.0, USB 3.2 Type-A, USB-C somente para dados, HDMI 1.4 e leitor SD.','Sistema: Windows 11 Home e gráficos integrados Intel UHD.','Garantia anunciada: um ano de assistência básica no local. Confirme as condições com a Dell.'],
1302 => ['Chip: Apple M5, com CPU e GPU de 10 núcleos.','Memória e armazenamento: 16 GB de memória unificada e SSD de 512 GB.','Tela: Liquid Retina de 15,3 polegadas.','Câmera e áudio: Center Stage de 12 MP, três microfones e seis alto-falantes.','Conexões: duas portas Thunderbolt 4, MagSafe e entrada para fones.','Conectividade: Wi-Fi 7 e Bluetooth 6, conforme o anúncio.','Autonomia anunciada: até 18 horas. O tempo real depende do uso.','Acabamento: meia-noite.'],
];
$builder = YoastSEO()->classes->get(\Yoast\WP\SEO\Builders\Indexable_Builder::class);
$analysis=[];
foreach($products as $id=>$data) {
    if(get_post_status($id)!=='publish') {continue;}
    $content=str_replace('{{home}}',untrailingslashit(home_url()),$data['content']);
    if(isset($concise_bullets[$id])) {grv_update_field('bullets',implode("\n",$concise_bullets[$id]),$id);}
    $result=wp_update_post(wp_slash(['ID'=>$id,'post_content'=>$content,'post_excerpt'=>$data['description']]),true);
    if(is_wp_error($result)) {throw new RuntimeException($result->get_error_message());}
    foreach(['focuskw'=>$data['key'],'title'=>$data['title'].' %%sep%% %%sitename%%','metadesc'=>$data['description'],'meta-robots-noindex'=>'2','meta-robots-nofollow'=>'0'] as $key=>$value) {WPSEO_Meta::set_value($key,$value,$id);}
    $terms=wp_get_post_categories($id);
    usort($terms,fn($a,$b)=>count(get_ancestors($b,'category'))<=>count(get_ancestors($a,'category')));
    if($terms) {update_post_meta($id,'_yoast_wpseo_primary_category',$terms[0]);}
    $images=get_children(['post_parent'=>$id,'post_type'=>'attachment','post_mime_type'=>'image']);
    foreach($images as $image) {
        $existing=get_post_meta($image->ID,'_wp_attachment_image_alt',true);
        if(!$existing) {update_post_meta($image->ID,'_wp_attachment_image_alt',$data['key'].' — imagem do produto');}
    }
    $title=wpseo_replace_vars($data['title'].' %%sep%% %%sitename%%',get_post($id));
    $bbox=imagettfbbox(15,0,'C:/Windows/Fonts/arial.ttf',$title);
    $analysis[]=['id'=>$id,'text'=>$content.grv_yoast_native_content($id),'keyword'=>$data['key'],'title'=>$title,'titleWidth'=>$bbox[2]-$bbox[0],'description'=>$data['description'],'slug'=>get_post_field('post_name',$id),'locale'=>'pt_BR','permalink'=>get_permalink($id)];
    $builder->build_for_id_and_type($id,'post');
}
$pages=[
3=>['Política de Privacidade','Conheça a Política de Privacidade do Guia Review: dados de contato, cookies, serviços externos e informações sobre o tratamento de dados pessoais.'],
134=>['Sobre o Guia Review','Conheça o Guia Review, nossas fontes de informação e a transparência sobre avaliações, ofertas e links de afiliados nas páginas de produtos.'],
136=>['Contato Guia Review','Entre em contato com o Guia Review para enviar dúvidas, sugestões, correções de informações ou propostas de parceria pelo formulário de contato.'],
2=>['Guia Review: produtos, características e comparações','Encontre produtos e compare características, imagens e informações no Guia Review. Explore informática, casa, esporte e Pet Shop e consulte as ofertas.'],
];
foreach($pages as $id=>$data) {
    WPSEO_Meta::set_value('title',$data[0].' %%sep%% %%sitename%%',$id);
    WPSEO_Meta::set_value('metadesc',$data[1],$id);
    WPSEO_Meta::set_value('focuskw',$data[0],$id);
    WPSEO_Meta::set_value('meta-robots-noindex','2',$id);
    if($id===134) {WPSEO_Meta::set_value('schema_page_type','AboutPage',$id);}
    if($id===136) {WPSEO_Meta::set_value('schema_page_type','ContactPage',$id);}
    $builder->build_for_id_and_type($id,'post');
}
foreach(get_categories(['hide_empty'=>false]) as $term) {
    $description='Confira os produtos de '.$term->name.' no Guia Review. Compare características, veja imagens e consulte informações e ofertas nas lojas de destino.';
    if(!$term->description) {wp_update_term($term->term_id,'category',['description'=>$description]);}
    $seo_values=WPSEO_Taxonomy_Meta::get_term_meta($term,'category');
    $seo_values['wpseo_title']=$term->name.': produtos %%sep%% %%sitename%%';
    $seo_values['wpseo_desc']=$description;
    WPSEO_Taxonomy_Meta::set_values($term->term_id,'category',$seo_values);
    $builder->build_for_id_and_type($term->term_id,'term');
}
$builder->build_for_home_page();
$builder->build_for_system_page('search-result');
$builder->build_for_system_page('404');
flush_rewrite_rules(false);
file_put_contents(__DIR__.'/seo-analysis-input.json',wp_json_encode($analysis,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
echo wp_json_encode(['products'=>count($analysis),'pages'=>count($pages),'backup'=>$backup_path,'sitemap'=>home_url('/sitemap_index.xml')],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
