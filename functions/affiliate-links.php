<?php
defined('ABSPATH') || exit;

function grv_offer_url($post_id) {
    return grv_get_field('url', $post_id) ? add_query_arg('grv_offer', absint($post_id), home_url('/')) : '';
}

function grv_offer_attributes($post_id) {
    $id=absint($post_id);
    return 'data-grv-product="'.$id.'" data-grv-click-token="'.esc_attr(wp_hash('grv-offer|'.$id)).'"';
}

function grv_should_count_affiliate_click($id) {
    $agent=sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']??''));
    $purpose=sanitize_text_field(wp_unslash($_SERVER['HTTP_SEC_PURPOSE']??$_SERVER['HTTP_PURPOSE']??''));
    return !str_contains(strtolower($purpose),'prefetch') && !preg_match('/bot|crawler|spider|slurp|preview|facebookexternalhit|WhatsApp/i',$agent);
}

function grv_record_affiliate_click() {
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){wp_send_json_error(null,405);}
    $id=isset($_POST['product'])&&is_scalar($_POST['product'])?absint($_POST['product']):0;
    $token=isset($_POST['token'])&&is_string($_POST['token'])?sanitize_text_field(wp_unslash($_POST['token'])):'';
    if(!$id||!hash_equals(wp_hash('grv-offer|'.$id),$token)){wp_send_json_error(null,403);}
    if(get_post_type($id)!=='post'||get_post_status($id)!=='publish'||!grv_get_field('url',$id)){wp_send_json_error(null,404);}
    $counted=grv_should_count_affiliate_click($id);
    if($counted&&!grv_increment_affiliate_clicks($id)){wp_send_json_error(null,500);}
    wp_send_json_success(['counted'=>$counted,'total'=>grv_affiliate_click_count($id)]);
}
add_action('wp_ajax_grv_affiliate_click','grv_record_affiliate_click');
add_action('wp_ajax_nopriv_grv_affiliate_click','grv_record_affiliate_click');
function grv_enqueue_affiliate_tracking(){
    wp_enqueue_script('grv-affiliate-clicks',get_template_directory_uri().'/js/affiliate-clicks.js',[],filemtime(get_template_directory().'/js/affiliate-clicks.js'),true);
    wp_localize_script('grv-affiliate-clicks','grvAffiliateTracking',['endpoint'=>admin_url('admin-ajax.php')]);
}
add_action('wp_enqueue_scripts','grv_enqueue_affiliate_tracking');

function grv_affiliate_click_count($post_id) {
    return max(0, (int)get_option('grv_affiliate_clicks_'.absint($post_id), 0));
}

// One uniquely keyed, non-autoloaded option per product makes increments atomic.
function grv_increment_affiliate_clicks($post_id) {
    global $wpdb;
    $key='grv_affiliate_clicks_'.absint($post_id);
    $result=$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,'1','no') ON DUPLICATE KEY UPDATE option_value=CAST(option_value AS UNSIGNED)+1",$key));
    wp_cache_delete($key,'options');wp_cache_delete('notoptions','options');
    return $result !== false;
}

add_action('template_redirect', function() {
    if(!isset($_GET['grv_offer'])) {return;}
    $input=wp_unslash($_GET['grv_offer']);
    $id=is_string($input)&&ctype_digit($input)?(int)$input:0;
    $post=get_post($id);
    $url=$post?grv_get_field('url',$id):'';
    if(!$post || $post->post_type!=='post' || $post->post_status!=='publish' || !is_string($url) || !preg_match('#^https?://#i',$url) || !wp_http_validate_url($url)) {
        status_header(404);nocache_headers();wp_die('Oferta indisponível.','Oferta indisponível',['response'=>404]);
    }
    $method=$_SERVER['REQUEST_METHOD']??'GET';
    if(!in_array($method,['GET','HEAD'],true)){status_header(405);header('Allow: GET, HEAD');exit;}
    if($method==='GET' && grv_should_count_affiliate_click($id)) {
        grv_increment_affiliate_clicks($id);
    }
    nocache_headers();header('X-Robots-Tag: noindex, nofollow');header('Referrer-Policy: strict-origin-when-cross-origin');
    // The destination is stored by an editor; request parameters cannot supply a URL.
    wp_redirect(esc_url_raw($url,['http','https']),302,'GuiaReview');exit;
}, 0);

add_action('add_meta_boxes_post',function(){
    add_meta_box('grv-affiliate','Link de afiliado',function($post){
        wp_nonce_field('grv_product_save','grv_product_nonce');
        echo '<p><label for="grv-affiliate_url">URL de afiliado da oferta</label></p><input class="widefat" type="url" id="grv-affiliate_url" name="grv_product[affiliate_url]" value="'.esc_attr(get_post_meta($post->ID,'affiliate_url',true)).'" placeholder="https://…">';
        echo '<p class="description">Este link será usado nos botões de compra e nas avaliações.</p><p><strong>Cliques na oferta: '.esc_html(number_format_i18n(grv_affiliate_click_count($post->ID))).'</strong></p>';
    },'post','side','high');
});

add_filter('manage_post_posts_columns',function($columns){
    $numbered=[];foreach($columns as $key=>$label){$numbered[$key]=$label;if($key==='cb'){$numbered['grv_product_number']='Produto nº';}}
    $columns=$numbered;
    $columns['grv_affiliate']='Link de afiliado';$columns['grv_clicks']='Cliques na oferta';return $columns;
});
add_action('manage_post_posts_custom_column',function($column,$id){
    if($column==='grv_product_number'){echo esc_html(get_post_meta($id,'grv_product_number',true)?:'—');}
    if($column==='grv_clicks'){echo esc_html(number_format_i18n(grv_affiliate_click_count($id)));}
    if($column==='grv_affiliate'){
        $url=get_post_meta($id,'affiliate_url',true);
        if(!$url){echo 'Afiliado não cadastrado';return;}
        echo '<input class="widefat grv-affiliate-copy-value" type="text" readonly aria-label="Link de afiliado de '.esc_attr(get_the_title($id)).'" value="'.esc_attr($url).'"><button class="button button-small grv-affiliate-copy" type="button">Copiar link</button> <a href="'.esc_url($url).'" '.grv_offer_attributes($id).' target="_blank" rel="nofollow sponsored noopener noreferrer">Abrir oferta</a><span class="grv-copy-status" role="status" aria-live="polite"></span>';
    }
},10,2);
add_filter('manage_edit-post_sortable_columns',function($columns){$columns['grv_clicks']='grv_clicks';$columns['grv_product_number']='grv_product_number';return $columns;});
add_action('restrict_manage_posts',function($type){
    if($type!=='post'){return;}
    $selected=isset($_GET['grv_click_order'])&&is_string($_GET['grv_click_order'])?sanitize_key($_GET['grv_click_order']):'';
    echo '<select name="grv_click_order" aria-label="Ordenar por cliques"><option value="">Ordenação padrão</option><option value="most" '.selected($selected,'most',false).'>Mais clicados primeiro</option><option value="least" '.selected($selected,'least',false).'>Menos clicados primeiro</option></select>';
});
add_action('pre_get_posts',function($query){
    if(!is_admin()||!$query->is_main_query()||$query->get('post_type')!=='post'){return;}
    $order=isset($_GET['grv_click_order'])&&is_string($_GET['grv_click_order'])?sanitize_key($_GET['grv_click_order']):'';
    if(in_array($order,['most','least'],true)){$query->set('orderby','grv_clicks');$query->set('order',$order==='most'?'DESC':'ASC');}
});
add_filter('posts_clauses',function($clauses,$query){
    if(is_admin()&&$query->get('post_type')==='post'&&$query->get('orderby')==='grv_product_number'){
        global $wpdb;
        $clauses['join'].=" LEFT JOIN {$wpdb->postmeta} grv_numbers ON grv_numbers.post_id={$wpdb->posts}.ID AND grv_numbers.meta_key='grv_product_number' ";
        $direction=strtoupper($query->get('order'))==='DESC'?'DESC':'ASC';
        $clauses['orderby']="CAST(COALESCE(grv_numbers.meta_value,'0') AS UNSIGNED) $direction, {$wpdb->posts}.ID ASC";
        return $clauses;
    }
    if(!is_admin()||$query->get('post_type')!=='post'||$query->get('orderby')!=='grv_clicks'){return $clauses;}
    global $wpdb;
    $clauses['join'].=" LEFT JOIN {$wpdb->options} grv_click_totals ON grv_click_totals.option_name=CONCAT('grv_affiliate_clicks_',{$wpdb->posts}.ID) ";
    $direction=strtoupper($query->get('order'))==='ASC'?'ASC':'DESC';
    $clauses['orderby']="CAST(COALESCE(grv_click_totals.option_value,'0') AS UNSIGNED) $direction, {$wpdb->posts}.ID DESC";
    return $clauses;
},10,2);
add_action('before_delete_post',function($id,$post){if($post->post_type==='post'){delete_option('grv_affiliate_clicks_'.$id);}},10,2);
add_action('admin_enqueue_scripts',function(){
    $screen=get_current_screen();if(!$screen||$screen->id!=='edit-post'){return;}
    grv_enqueue_affiliate_tracking();
    wp_enqueue_script('grv-affiliate-admin',get_template_directory_uri().'/js/affiliate-admin.js',[],filemtime(get_template_directory().'/js/affiliate-admin.js'),true);
    wp_add_inline_style('common','.column-grv_product_number{width:85px}.column-grv_affiliate{width:240px}.column-grv_clicks{width:110px}.grv-affiliate-copy{margin-top:5px!important}.grv-copy-status{display:block;font-size:12px;margin-top:4px}');
});

function grv_assign_product_number($id,$post){
    if($post->post_type!=='post'||in_array($post->post_status,['auto-draft','trash'],true)||get_post_meta($id,'grv_product_number',true)){return;}
    global $wpdb;
    $maximum=(int)$wpdb->get_var("SELECT MAX(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key='grv_product_number'");
    add_option('grv_product_sequence',(string)$maximum,'','no');
    $result=$wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value=LAST_INSERT_ID(GREATEST(CAST(option_value AS UNSIGNED),%d)+1) WHERE option_name='grv_product_sequence'",$maximum));
    if($result){$number=(int)$wpdb->get_var('SELECT LAST_INSERT_ID()');add_post_meta($id,'grv_product_number',$number,true);wp_cache_delete('grv_product_sequence','options');}
}
add_action('save_post_post','grv_assign_product_number',20,2);
