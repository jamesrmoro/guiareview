<?php
/** Product registration progress in the native WordPress dashboard. */
defined( 'ABSPATH' ) || exit;

function grv_registration_stats( $days = 30 ) {
    global $wpdb;
    $days = max( 7, min( 90, (int) $days ) );
    $today = current_datetime()->setTime( 0, 0 );
    $start = $today->modify( '-' . ( $days - 1 ) . ' days' );
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT DATE(post_date) AS day, COUNT(*) AS registrations FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND post_date >= %s AND post_date < %s GROUP BY DATE(post_date) ORDER BY day DESC",
        $start->format( 'Y-m-d H:i:s' ), $today->modify( '+1 day' )->format( 'Y-m-d H:i:s' )
    ) );
    $daily = array();
    for ( $i = 0; $i < $days; $i++ ) { $daily[$today->modify( '-' . $i . ' days' )->format( 'Y-m-d' )] = 0; }
    foreach ( $rows as $row ) { $daily[$row->day] = (int) $row->registrations; }
    $total = (int) wp_count_posts( 'post' )->publish;
    $goal = 1000;
    return array(
        'total' => $total, 'goal' => $goal, 'remaining' => max( 0, $goal - $total ),
        'percent' => min( 100, $total / $goal * 100 ), 'daily' => $daily,
        'today' => $daily[$today->format( 'Y-m-d' )],
        'week' => array_sum( array_slice( $daily, 0, 7, true ) ),
        'period_total' => array_sum( $daily ), 'days' => $days,
    );
}

add_action( 'wp_dashboard_setup', function () {
    if ( current_user_can( 'edit_posts' ) ) {
        wp_add_dashboard_widget( 'grv_product_progress', 'Guia Review — Meta de cadastro de produtos', 'grv_render_registration_progress', null, null, 'normal', 'high' );
    }
} );
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'index.php' || ! current_user_can( 'edit_posts' ) ) { return; }
    wp_enqueue_style( 'grv-dashboard-progress', get_template_directory_uri() . '/css/dashboard-progress.css', array(), filemtime( get_template_directory() . '/css/dashboard-progress.css' ) );
} );

function grv_render_registration_progress() {
    if ( ! current_user_can( 'edit_posts' ) ) { return; }
    $stats = grv_registration_stats();
    $peak = max( 1, max( $stats['daily'] ) );
    ?>
    <div class="grv-progress-dashboard">
        <div class="grv-progress-heading">
            <strong><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?> <span>de <?php echo esc_html( number_format_i18n( $stats['goal'] ) ); ?> produtos</span></strong>
            <span class="grv-progress-percentage"><?php echo esc_html( number_format_i18n( $stats['percent'], 1 ) ); ?>%</span>
        </div>
        <progress class="grv-progress-meter" max="<?php echo esc_attr( $stats['goal'] ); ?>" value="<?php echo esc_attr( min( $stats['goal'], $stats['total'] ) ); ?>" aria-label="Progresso da meta de mil produtos"><?php echo esc_html( number_format_i18n( $stats['percent'], 1 ) ); ?>%</progress>
        <p class="grv-progress-caption"><?php echo $stats['remaining'] ? esc_html( sprintf( 'Faltam %s produtos para alcançar a meta.', number_format_i18n( $stats['remaining'] ) ) ) : 'Meta alcançada! Continue ampliando seu catálogo.'; ?></p>
        <div class="grv-progress-metrics">
            <div><strong><?php echo esc_html( number_format_i18n( $stats['today'] ) ); ?></strong><span>Hoje</span></div>
            <div><strong><?php echo esc_html( number_format_i18n( $stats['week'] ) ); ?></strong><span>Últimos 7 dias</span></div>
            <div><strong><?php echo esc_html( number_format_i18n( $stats['period_total'] ) ); ?></strong><span>Últimos 30 dias</span></div>
            <div><strong><?php echo esc_html( number_format_i18n( $stats['period_total'] / $stats['days'], 1 ) ); ?></strong><span>Média por dia (30 dias)</span></div>
        </div>
        <h3>Cadastros por dia</h3>
        <div class="grv-progress-history" tabindex="0" role="region" aria-label="Histórico de cadastros dos últimos 30 dias">
            <table class="widefat striped">
                <caption class="screen-reader-text">Produtos publicados por dia nos últimos 30 dias, incluindo dias sem cadastros</caption>
                <thead><tr><th scope="col">Data</th><th scope="col">Produtos</th><th scope="col">Volume diário</th></tr></thead>
                <tbody>
                <?php foreach ( $stats['daily'] as $day => $count ) : ?>
                    <tr>
                        <th scope="row"><?php echo esc_html( DateTimeImmutable::createFromFormat( '!Y-m-d', $day, wp_timezone() )->format( 'd/m/Y' ) ); ?></th>
                        <td><?php echo esc_html( number_format_i18n( $count ) ); ?></td>
                        <td><span class="grv-daily-track" aria-hidden="true"><span style="width:<?php echo esc_attr( round( $count / $peak * 100, 2 ) ); ?>%"></span></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="grv-progress-note">Contagem de produtos publicados (Posts), sem rascunhos, lixeira, páginas ou imagens. O histórico usa a data de publicação do WordPress e o fuso horário do site. Atualiza ao abrir ou recarregar o painel.</p>
        <a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>">Cadastrar produto</a>
        <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_status=publish&post_type=post' ) ); ?>">Ver produtos publicados</a>
    </div>
    <?php
}
