<?php
defined('ABSPATH') || exit;

add_action('init',function(){
    register_post_type('grv_ad',[
        'labels'=>['name'=>'Anúncios','singular_name'=>'Anúncio','add_new_item'=>'Novo anúncio','edit_item'=>'Editar anúncio','all_items'=>'Todos os anúncios','featured_image'=>'Imagem do anúncio','set_featured_image'=>'Selecionar imagem'],
        'public'=>false,'show_ui'=>true,'show_in_menu'=>true,'menu_icon'=>'dashicons-megaphone','supports'=>['title','thumbnail'],'capability_type'=>'post','map_meta_cap'=>true,
    ]);
    if(wp_next_scheduled('enviar_relatorio_diario_cliques')){wp_clear_scheduled_hook('enviar_relatorio_diario_cliques');}
});

function grv_ad_fields(){return [
    '_grv_ad_url'=>['Link da oferta','url'],
    '_grv_ad_cta'=>['Texto do botão','text'],
    '_grv_ad_campaign'=>['Campanha A/B','text'],
    '_grv_ad_variant'=>['Variante (A, B…)','text'],
    '_grv_ad_position'=>['Exibir em','select'],
    '_grv_ad_expires'=>['Expira em (opcional)','datetime-local'],
];}

add_action('add_meta_boxes_grv_ad',function(){
    add_meta_box('grv-ad-settings','Configuração do anúncio',function($post){
        wp_nonce_field('grv_save_ad','grv_ad_nonce');
        echo '<div class="grv-ad-fields">';
        foreach(grv_ad_fields() as $key=>[$label,$type]){
            $value=get_post_meta($post->ID,$key,true);
            if($key==='_grv_ad_expires'&&$value){$value=wp_date('Y-m-d\TH:i',(int)$value,wp_timezone());}
            echo '<label><span>'.esc_html($label).'</span>';
            if($type==='select'){
                echo '<select name="grv_ad['.esc_attr($key).']">';foreach(['both'=>'Modal e rodapé','modal'=>'Somente modal','footer'=>'Somente rodapé'] as $choice=>$name){echo '<option value="'.$choice.'" '.selected($value?:'both',$choice,false).'>'.$name.'</option>';}echo '</select>';
            }else{echo '<input type="'.esc_attr($type).'" name="grv_ad['.esc_attr($key).']" value="'.esc_attr($value).'">';}
            echo '</label>';
        }
        echo '</div><p class="description">Variantes da mesma campanha são distribuídas aleatoriamente, com chances iguais. Sem data, o anúncio não expira. Horário do site: '.esc_html(wp_timezone_string()).'.</p>';
        $views=grv_ad_count($post->ID,'views');$clicks=grv_ad_count($post->ID,'clicks');
        echo '<p><strong>'.number_format_i18n($views).' exibições · '.number_format_i18n($clicks).' cliques · '.esc_html($views?number_format_i18n(100*$clicks/$views,2):'0').' % CTR</strong></p>';
    },'grv_ad','normal','high');
});

add_action('save_post_grv_ad',function($id){
    if(wp_is_post_revision($id)||(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)||!current_user_can('edit_post',$id)||!isset($_POST['grv_ad_nonce'])||!is_string($_POST['grv_ad_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['grv_ad_nonce'])),'grv_save_ad')){return;}
    $values=isset($_POST['grv_ad'])&&is_array($_POST['grv_ad'])?wp_unslash($_POST['grv_ad']):[];
    foreach(grv_ad_fields() as $key=>[$label,$type]){
        if(!isset($values[$key])||!is_scalar($values[$key])){continue;}
        $value=$values[$key];
        if($type==='url'){$value=esc_url_raw($value,['http','https']);}
        elseif($type==='select'){$value=in_array($value,['both','modal','footer'],true)?$value:'both';}
        elseif($type==='datetime-local'){
            $date=$value!==''?DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$value,wp_timezone()):false;
            if($value!==''&&(!$date||$date->format('Y-m-d\TH:i')!==$value)){continue;}
            $value=$date?$date->getTimestamp():'';
        }else{$value=sanitize_text_field($value);}
        update_post_meta($id,$key,$value);
    }
});

function grv_ad_active($id){
    $expires=(int)get_post_meta($id,'_grv_ad_expires',true);$url=get_post_meta($id,'_grv_ad_url',true);
    return get_post_type($id)==='grv_ad'&&get_post_status($id)==='publish'&&(!$expires||$expires>time())&&preg_match('#^https?://#i',(string)$url)&&wp_get_attachment_image_url(get_post_thumbnail_id($id),'large');
}
function grv_ad_count($id,$event){return max(0,(int)get_option('grv_ad_'.$event.'_'.absint($id),0));}
function grv_ad_increment($id,$event){
    if(!in_array($event,['views','clicks'],true)){return false;}
    global $wpdb;$key='grv_ad_'.$event.'_'.absint($id);
    $ok=$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,'1','no') ON DUPLICATE KEY UPDATE option_value=CAST(option_value AS UNSIGNED)+1",$key));
    wp_cache_delete($key,'options');wp_cache_delete('notoptions','options');return $ok!==false;
}
function grv_ad_payload($id){return [
    'id'=>(int)$id,'title'=>get_the_title($id),'url'=>get_post_meta($id,'_grv_ad_url',true),'cta'=>get_post_meta($id,'_grv_ad_cta',true)?:'Ver oferta',
    'image'=>wp_get_attachment_image_url(get_post_thumbnail_id($id),'large'),'campaign'=>get_post_meta($id,'_grv_ad_campaign',true)?:'geral','variant'=>get_post_meta($id,'_grv_ad_variant',true),
    'token'=>wp_hash('grv-ad|'.$id),'expires'=>(int)get_post_meta($id,'_grv_ad_expires',true),
];}
function grv_select_ads($preferences=[]){
    $groups=[];foreach(get_posts(['post_type'=>'grv_ad','post_status'=>'publish','numberposts'=>-1]) as $post){if(grv_ad_active($post->ID)){$group=get_post_meta($post->ID,'_grv_ad_campaign',true)?:'geral';$groups[$group][]=$post->ID;}}
    $selected=[];
    foreach($groups as $group=>$ids){$preferred=isset($preferences[$group])&&is_scalar($preferences[$group])?absint($preferences[$group]):0;$selected[$group]=in_array($preferred,$ids,true)?$preferred:$ids[wp_rand(0,count($ids)-1)];}
    $result=['modal'=>null,'footer'=>null];
    foreach(array_keys($result) as $position){
        $candidates=[];foreach($groups as $group=>$ids){
            $eligible=array_values(array_filter($ids,fn($id)=>in_array(get_post_meta($id,'_grv_ad_position',true)?:'both',['both',$position],true)));
            if(!$eligible){continue;}
            $key=$group.'|'.$position;$preferred=isset($preferences[$key])&&is_scalar($preferences[$key])?absint($preferences[$key]):$selected[$group];
            $candidates[]=in_array($preferred,$eligible,true)?$preferred:$eligible[wp_rand(0,count($eligible)-1)];
        }
        if($candidates){$result[$position]=grv_ad_payload($candidates[wp_rand(0,count($candidates)-1)]);$result[$position]['position']=$position;}
    }
    return $result;
}
function grv_get_ads_ajax(){
    $preferences=[];if(isset($_POST['variants'])&&is_string($_POST['variants'])){$preferences=json_decode(wp_unslash($_POST['variants']),true);if(!is_array($preferences)){$preferences=[];}}
    nocache_headers();wp_send_json_success(grv_select_ads($preferences));
}
function grv_ad_event_ajax(){
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){wp_send_json_error(null,405);}
    $id=isset($_POST['ad'])&&is_scalar($_POST['ad'])?absint($_POST['ad']):0;
    $event=isset($_POST['event'])&&is_string($_POST['event'])?sanitize_key($_POST['event']):'';
    $token=isset($_POST['token'])&&is_string($_POST['token'])?sanitize_text_field(wp_unslash($_POST['token'])):'';
    if(!$id||!hash_equals(wp_hash('grv-ad|'.$id),$token)){wp_send_json_error(null,403);}
    if(!in_array($event,['views','clicks'],true)||!grv_ad_active($id)){wp_send_json_error(null,404);}
    $counted=grv_should_count_affiliate_click($id);
    if($counted&&!grv_ad_increment($id,$event)){wp_send_json_error(null,500);}
    wp_send_json_success(['counted'=>$counted]);
}
foreach(['grv_get_ads'=>'grv_get_ads_ajax','grv_ad_event'=>'grv_ad_event_ajax'] as $action=>$callback){add_action('wp_ajax_'.$action,$callback);add_action('wp_ajax_nopriv_'.$action,$callback);}

add_filter('manage_grv_ad_posts_columns',function($columns){return ['cb'=>$columns['cb'],'title'=>'Anúncio','grv_variant'=>'Campanha / variante','grv_ad_state'=>'Validade','grv_views'=>'Exibições','grv_ad_clicks'=>'Cliques','grv_ctr'=>'CTR','date'=>'Data'];});
add_action('manage_grv_ad_posts_custom_column',function($column,$id){
    if($column==='grv_variant'){echo esc_html((get_post_meta($id,'_grv_ad_campaign',true)?:'Geral').' / '.(get_post_meta($id,'_grv_ad_variant',true)?:'—'));}
    if($column==='grv_ad_state'){$expires=(int)get_post_meta($id,'_grv_ad_expires',true);echo esc_html($expires?($expires<=time()?'Expirado · ':'Até ').wp_date('d/m/Y H:i',$expires):'Sem expiração');}
    if($column==='grv_views'){echo number_format_i18n(grv_ad_count($id,'views'));}
    if($column==='grv_ad_clicks'){echo number_format_i18n(grv_ad_count($id,'clicks'));}
    if($column==='grv_ctr'){$views=grv_ad_count($id,'views');echo ($views?number_format_i18n(100*grv_ad_count($id,'clicks')/$views,2):'0').' %';}
},10,2);
add_filter('manage_edit-grv_ad_sortable_columns',function($columns){$columns['grv_ad_clicks']='grv_ad_clicks';$columns['grv_views']='grv_views';return $columns;});
add_filter('posts_clauses',function($clauses,$query){
    if(!is_admin()||$query->get('post_type')!=='grv_ad'||!in_array($query->get('orderby'),['grv_ad_clicks','grv_views'],true)){return $clauses;}
    global $wpdb;$prefix=$query->get('orderby')==='grv_views'?'grv_ad_views_':'grv_ad_clicks_';$direction=$query->get('order')==='ASC'?'ASC':'DESC';
    $clauses['join'].=$wpdb->prepare(" LEFT JOIN {$wpdb->options} grv_ad_totals ON grv_ad_totals.option_name=CONCAT(%s,{$wpdb->posts}.ID) ",$prefix);
    $clauses['orderby']="CAST(COALESCE(grv_ad_totals.option_value,'0') AS UNSIGNED) $direction, {$wpdb->posts}.ID DESC";return $clauses;
},10,2);
add_action('admin_enqueue_scripts',function(){if(get_current_screen()?->post_type==='grv_ad'){wp_add_inline_style('common','.grv-ad-fields{display:grid;grid-template-columns:1fr 1fr;gap:16px;max-width:800px}.grv-ad-fields label span{display:block;margin-bottom:6px;font-weight:600}.grv-ad-fields input,.grv-ad-fields select{width:100%;min-height:38px}@media(max-width:700px){.grv-ad-fields{grid-template-columns:1fr}}');}});
add_action('add_meta_boxes_grv_ad',function(){remove_meta_box('wpseo_meta','grv_ad','normal');remove_meta_box('slugdiv','grv_ad','normal');},100);
add_action('before_delete_post',function($id,$post){if($post->post_type==='grv_ad'){delete_option('grv_ad_views_'.$id);delete_option('grv_ad_clicks_'.$id);}},10,2);
add_action('wp_enqueue_scripts',function(){
    wp_enqueue_script('grv-site-widgets',get_template_directory_uri().'/js/site-widgets.js',[],filemtime(get_template_directory().'/js/site-widgets.js'),true);
    wp_localize_script('grv-site-widgets','grvWidgets',['endpoint'=>admin_url('admin-ajax.php'),'product'=>is_singular('post')]);
});
