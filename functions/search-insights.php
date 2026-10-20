<?php
/** Anonymous daily totals for searches submitted through the site's forms. */
defined( 'ABSPATH' ) || exit;

function grv_search_table() {
    global $wpdb;
    return $wpdb->prefix . 'grv_search_daily';
}

add_action( 'init', function () {
    if ( get_option( 'grv_search_schema' ) === '1' ) { return; }
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = grv_search_table();
    dbDelta( "CREATE TABLE $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        search_day date NOT NULL,
        term varchar(200) NOT NULL,
        term_hash char(64) NOT NULL,
        searches bigint(20) unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        UNIQUE KEY day_term (search_day,term_hash)
    ) " . $wpdb->get_charset_collate() . ';' );
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table ) {
        update_option( 'grv_search_schema', '1', false );
    }
} );

function grv_record_search() {
    if ( ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' ) { wp_send_json_error( null, 405 ); }
    $raw = $_POST['term'] ?? '';
    if ( ! is_string( $raw ) ) { wp_send_json_error( null, 400 ); }
    $term = trim( preg_replace( '/\s+/u', ' ', sanitize_text_field( wp_unslash( $raw ) ) ) );
    $term = function_exists('mb_substr') ? mb_substr($term,0,200) : substr($term,0,200);
    $term = function_exists('mb_strtolower') ? mb_strtolower($term,'UTF-8') : strtolower($term);
    if ( $term === '' ) { wp_send_json_error( null, 400 ); }
    global $wpdb;
    $ok = $wpdb->query( $wpdb->prepare(
        'INSERT INTO ' . grv_search_table() . ' (search_day,term,term_hash,searches) VALUES (%s,%s,%s,1) ON DUPLICATE KEY UPDATE searches=searches+1',
        current_datetime()->format('Y-m-d'), $term, hash('sha256',$term)
    ) );
    if ( $ok === false ) { wp_send_json_error( null, 500 ); }
    wp_send_json_success();
}
add_action('wp_ajax_grv_record_search','grv_record_search');
add_action('wp_ajax_nopriv_grv_record_search','grv_record_search');

add_action('wp_enqueue_scripts',function(){
    wp_enqueue_script('grv-search-track',get_template_directory_uri().'/js/search-track.js',[],filemtime(dirname(__DIR__).'/js/search-track.js'),true);
    wp_localize_script('grv-search-track','grvSearchTrack',['endpoint'=>admin_url('admin-ajax.php')]);
});

add_action('admin_menu',function(){
    add_menu_page('Buscas de produtos','Buscas de produtos','manage_options','grv-search-insights','grv_render_search_insights','dashicons-search',26);
});
add_action('admin_enqueue_scripts',function($hook){
    if($hook!=='toplevel_page_grv-search-insights'){return;}
    wp_enqueue_style('grv-search-insights',get_template_directory_uri().'/css/search-insights.css',[],filemtime(dirname(__DIR__).'/css/search-insights.css'));
    wp_enqueue_script('grv-search-insights',get_template_directory_uri().'/js/search-insights.js',[],filemtime(dirname(__DIR__).'/js/search-insights.js'),true);
});

function grv_search_date($key,$fallback) {
    $value = isset($_GET[$key]) && is_string($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : '';
    $date = DateTimeImmutable::createFromFormat('!Y-m-d',$value,wp_timezone());
    return $date && $date->format('Y-m-d')===$value ? $value : $fallback;
}
function grv_search_period() {
    $today=current_datetime()->setTime(0,0);
    $from=grv_search_date('from',$today->modify('-29 days')->format('Y-m-d'));
    $to=grv_search_date('to',$today->format('Y-m-d'));
    if($from>$to){[$from,$to]=[$to,$from];}
    return [$from,$to];
}
function grv_search_rows($from,$to,$limit=0,$offset=0) {
    global $wpdb;
    $sql=$wpdb->prepare('SELECT search_day,term,searches FROM '.grv_search_table().' WHERE search_day BETWEEN %s AND %s ORDER BY search_day DESC,searches DESC,term ASC',$from,$to);
    if($limit){$sql.=$wpdb->prepare(' LIMIT %d OFFSET %d',$limit,$offset);}
    return $wpdb->get_results($sql);
}
function grv_search_export_url($from,$to,$format='csv') {
    return wp_nonce_url(add_query_arg(['action'=>'grv_export_searches','from'=>$from,'to'=>$to,'format'=>$format],admin_url('admin-post.php')),'grv_export_searches');
}
function grv_search_export_cell($value) {
    // Prevent spreadsheet programs from interpreting a searched term as a formula.
    return preg_match('/^[=+@\-\t\r\n]/u',$value) ? "'".$value : $value;
}
add_action('admin_post_grv_export_searches',function(){
    if(!current_user_can('manage_options')){wp_die('Acesso negado.','',['response'=>403]);}
    check_admin_referer('grv_export_searches');
    [$from,$to]=grv_search_period();
    $tsv=isset($_GET['format']) && $_GET['format']==='tsv';
    nocache_headers();
    header('Content-Type: '.($tsv?'text/tab-separated-values':'text/csv').'; charset=utf-8');
    if(!$tsv){header('Content-Disposition: attachment; filename="buscas-'.$from.'-'.$to.'.csv"');}
    $stream=fopen('php://output','w');
    if(!$tsv){fwrite($stream,"\xEF\xBB\xBF");}
    fputcsv($stream,['Data','Termo pesquisado','Buscas'],$tsv?"\t":';','"','');
    $offset=0;
    do {
        $rows=grv_search_rows($from,$to,1000,$offset);
        foreach($rows as $row){fputcsv($stream,[$row->search_day,grv_search_export_cell($row->term),(int)$row->searches],$tsv?"\t":';','"','');}
        $offset+=1000;
    } while(count($rows)===1000);
    fclose($stream);exit;
});

function grv_render_search_insights() {
    if(!current_user_can('manage_options')){return;}
    global $wpdb;
    [$from,$to]=grv_search_period();
    $where=$wpdb->prepare(' WHERE search_day BETWEEN %s AND %s',$from,$to);
    $table=grv_search_table();
    $total=(int)$wpdb->get_var("SELECT COALESCE(SUM(searches),0) FROM $table $where");
    $unique=(int)$wpdb->get_var("SELECT COUNT(DISTINCT term_hash) FROM $table $where");
    $count=(int)$wpdb->get_var("SELECT COUNT(*) FROM $table $where");
    $pages=max(1,(int)ceil($count/50));
    $page=min($pages,max(1,absint($_GET['report_page']??1)));
    $rows=grv_search_rows($from,$to,50,($page-1)*50);
    $ranking=$wpdb->get_results("SELECT term,SUM(searches) AS searches FROM $table $where GROUP BY term_hash,term ORDER BY searches DESC,term ASC LIMIT 10");
    $daily=$wpdb->get_results("SELECT search_day,SUM(searches) AS searches FROM $table $where GROUP BY search_day ORDER BY search_day");
    $day_map=[];foreach($daily as $day){$day_map[$day->search_day]=(int)$day->searches;}
    // Bound the chart's number of bars for large date ranges; tables retain all data.
    $start=new DateTimeImmutable($from,wp_timezone());$end=new DateTimeImmutable($to,wp_timezone());
    $days=(int)$start->diff($end)->days+1;
    $step=max(1,(int)ceil($days/90));$bars=[];
    for($i=0;$i<$days;$i+=$step){
        $a=$start->modify('+'.$i.' days');$b=$start->modify('+'.min($days-1,$i+$step-1).' days');$sum=0;
        foreach($day_map as $date=>$value){if($date>=$a->format('Y-m-d') && $date<=$b->format('Y-m-d')){$sum+=$value;}}
        $bars[]=['label'=>$a->format('d/m/Y').($step>1?' a '.$b->format('d/m/Y'):''),'count'=>$sum];
    }
    $peak=max(1,max(array_column($bars,'count')));
    ?>
    <div class="wrap grv-search-report">
        <h1>Buscas de produtos</h1>
        <p>Veja o que os visitantes procuram para planejar novos produtos. Registro iniciado em <?php echo esc_html(get_option('grv_search_started','')); ?>. As buscas anteriores não são recuperadas.</p>
        <form method="get" class="grv-search-filters">
            <input type="hidden" name="page" value="grv-search-insights">
            <label>De <input type="date" name="from" value="<?php echo esc_attr($from); ?>" required></label>
            <label>Até <input type="date" name="to" value="<?php echo esc_attr($to); ?>" required></label>
            <button class="button button-primary">Filtrar</button>
            <a class="button" href="<?php echo esc_url(grv_search_export_url($from,$to)); ?>">Exportar CSV</a>
            <button type="button" class="button" id="grv-copy-searches" data-url="<?php echo esc_url(grv_search_export_url($from,$to,'tsv')); ?>">Copiar tabela completa</button>
            <span id="grv-copy-search-status" role="status" aria-live="polite"></span>
        </form>
        <div class="grv-search-metrics"><div><strong><?php echo esc_html(number_format_i18n($total)); ?></strong>Buscas no período</div><div><strong><?php echo esc_html(number_format_i18n($unique)); ?></strong>Termos diferentes</div></div>
        <section class="grv-search-panel"><h2>Buscas por dia<?php echo $step>1?' (agrupadas a cada '.(int)$step.' dias)':''; ?></h2>
            <div class="grv-search-chart" role="img" aria-label="Volume de buscas no período. Total: <?php echo esc_attr($total); ?>. Os valores de cada intervalo estão na tabela abaixo.">
                <?php foreach($bars as $bar): ?><div class="grv-search-bar" title="<?php echo esc_attr($bar['label'].': '.$bar['count'].' buscas'); ?>"><span style="height:<?php echo esc_attr(100*$bar['count']/$peak); ?>%"></span></div><?php endforeach; ?>
            </div>
            <p class="grv-chart-axis"><span><?php echo esc_html($from); ?></span><span>Pico: <?php echo esc_html(number_format_i18n(max(array_column($bars,'count')))); ?> buscas</span><span><?php echo esc_html($to); ?></span></p>
            <details><summary>Ver valores do gráfico</summary><table class="widefat striped"><thead><tr><th>Dia / intervalo</th><th>Buscas</th></tr></thead><tbody><?php foreach($bars as $bar): ?><tr><td><?php echo esc_html($bar['label']); ?></td><td><?php echo (int)$bar['count']; ?></td></tr><?php endforeach; ?></tbody></table></details>
        </section>
        <section class="grv-search-panel"><h2>10 termos mais buscados</h2><table class="widefat striped"><thead><tr><th>Termo pesquisado</th><th>Buscas</th></tr></thead><tbody>
            <?php foreach($ranking as $row): ?><tr><td><?php echo esc_html($row->term); ?></td><td><?php echo (int)$row->searches; ?></td></tr><?php endforeach; ?>
            <?php if(!$ranking): ?><tr><td colspan="2">Nenhuma busca neste período.</td></tr><?php endif; ?>
        </tbody></table></section>
        <section class="grv-search-panel"><h2>Termos pesquisados por dia</h2><p>Exportar e copiar incluem todas as linhas do período filtrado.</p>
            <table class="widefat striped"><thead><tr><th>Data</th><th>Termo pesquisado</th><th>Buscas</th></tr></thead><tbody>
                <?php foreach($rows as $row): ?><tr><td><?php echo esc_html($row->search_day); ?></td><td><?php echo esc_html($row->term); ?></td><td><?php echo (int)$row->searches; ?></td></tr><?php endforeach; ?>
                <?php if(!$rows): ?><tr><td colspan="3">Nenhuma busca neste período.</td></tr><?php endif; ?>
            </tbody></table>
            <?php if($pages>1): ?><nav class="grv-search-pagination" aria-label="Páginas do relatório"><?php echo wp_kses_post(paginate_links(['base'=>add_query_arg(['page'=>'grv-search-insights','from'=>$from,'to'=>$to,'report_page'=>'%#%'],admin_url('admin.php')),'format'=>'','current'=>$page,'total'=>$pages])); ?></nav><?php endif; ?>
        </section>
        <p>Conta cada envio do formulário, inclusive buscas sem resultados e de usuários logados. Recarregar resultados ou avançar páginas não acrescenta buscas. Termos iguais são agrupados sem diferenciar maiúsculas e minúsculas. Nenhum IP ou identificação do visitante é armazenado.</p>
    </div>
    <?php
}
add_action('init',function(){if(!get_option('grv_search_started')){add_option('grv_search_started',current_datetime()->format('d/m/Y'),'','no');}});
