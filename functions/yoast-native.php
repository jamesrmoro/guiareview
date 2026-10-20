<?php
/** Include the visible native product data in Yoast's content analysis. */
defined( 'ABSPATH' ) || exit;
function grv_product_specs_html( $rows ) {
    $groups = ['' => $rows];
    if ( count( $rows ) > 25 ) {
        $groups = [];
        foreach ( $rows as $row ) {
            $label = remove_accents( strtolower( $row['label'] ) );
            if ( preg_match( '/tela|resolucao|exibicao/', $label ) ) { $group = 'Tela e imagem'; }
            elseif ( preg_match( '/memoria|ram|disco|armazenamento/', $label ) ) { $group = 'Memória e armazenamento'; }
            elseif ( preg_match( '/cpu|processador|grafico|video/', $label ) ) { $group = 'Processador e gráficos'; }
            elseif ( preg_match( '/conect|comunicacao|bateria|pilha|energia|celula/', $label ) ) { $group = 'Conexões e energia'; }
            else { $group = 'Características gerais'; }
            $groups[$group][] = $row;
        }
    }
    $html = '';
    foreach ( $groups as $label => $items ) {
        if ( $label ) { $html .= '<h3>' . esc_html( $label ) . '</h3>'; }
        $html .= '<table class="specs"><tbody>';
        foreach ( $items as $row ) { $html .= '<tr><th>' . esc_html( $row['label'] ?: '—' ) . '</th><td>' . esc_html( $row['text'] ) . '</td></tr>'; }
        $html .= '</tbody></table>';
    }
    return $html;
}
function grv_yoast_native_content( $post_id ) {
    $html = '';
    $image = grv_get_field( 'image', $post_id ) ?: get_the_post_thumbnail_url( $post_id, 'large' );
    if ( $image ) {
        $id = get_post_thumbnail_id( $post_id );
        $alt = get_post_meta( $id, '_wp_attachment_image_alt', true ) ?: get_the_title( $post_id );
        $html .= '<img src="' . esc_url( $image ) . '" alt="' . esc_attr( $alt ) . '">';
    }
    foreach ( ['bullets', 'specs'] as $field ) {
        $rows = grv_parse_lines( grv_get_field( $field, $post_id ) );
        if ( ! $rows ) { continue; }
        if ( $field === 'specs' ) { $html .= '<div data-grv-analysis="specs"><h2>Informações do produto</h2>' . grv_product_specs_html( $rows ) . '</div>'; continue; }
        $html .= '<div data-grv-analysis="bullets"><ul>';
        foreach ( $rows as $row ) {
            if ( $field === 'specs' ) {
                $html .= '<tr><th>' . esc_html( $row['label'] ) . '</th><td>' . esc_html( $row['text'] ) . '</td></tr>';
            } else {
                $html .= '<li>' . esc_html( trim( $row['label'] . ': ' . $row['text'], ': ' ) ) . '</li>';
            }
        }
        $html .= '</ul></div>';
    }
    $url = grv_get_field( 'url', $post_id );
    if ( $url ) { $html .= '<a href="' . esc_url( $url ) . '" rel="nofollow sponsored noopener noreferrer">Ver oferta na loja</a>'; }
    return $html;
}
/** Analyze the actual homepage rendered by front-page.php, rather than an empty page editor. */
function grv_yoast_homepage_content() {
    $response = wp_remote_get( home_url( '/' ), array( 'timeout' => 15 ) );
    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) { return ''; }
    $dom = new DOMDocument();
    @$dom->loadHTML( '<?xml encoding="UTF-8">' . wp_remote_retrieve_body( $response ) );
    $main = $dom->getElementsByTagName( 'main' )->item( 0 );
    if ( ! $main ) { return ''; }
    $html = '';
    foreach ( $main->childNodes as $child ) { $html .= $dom->saveHTML( $child ); }
    return $html;
}
add_action( 'admin_enqueue_scripts', function () {
    $screen = get_current_screen();
    if ( ! defined( 'WPSEO_VERSION' ) || ! $screen || $screen->base !== 'post' ) { return; }
    $homepage = $screen->post_type === 'page' && get_the_ID() === (int) get_option( 'page_on_front' );
    if ( $screen->post_type !== 'post' && ! $homepage ) { return; }
    $content = $homepage ? grv_yoast_homepage_content() : grv_yoast_native_content( get_the_ID() );
    if ( $homepage && ! $content ) { return; }
    wp_enqueue_script( 'grv-yoast-native', get_template_directory_uri() . '/js/yoast-native.js', ['jquery'], filemtime( __DIR__ . '/../js/yoast-native.js' ), true );
    wp_localize_script( 'grv-yoast-native', 'grvYoastNative', ['content' => $content, 'replaceContent' => $homepage] );
} );
// Empty category archives are useful for navigation but have no indexable products yet.
add_filter( 'wpseo_robots', function ( $robots ) {
    global $wp_query;
    if ( is_category() && $wp_query && ! $wp_query->found_posts ) { return 'noindex, follow'; }
    return $robots;
} );
