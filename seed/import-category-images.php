<?php
/** Import supplied category artwork without moving or deleting originals. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
$categories = array(
    'alimentos-e-bebidas.png' => 'alimentos-e-bebidas',
    'beleza.png' => 'beleza',
    'casa.png' => 'casa',
    'computadores-e-informatica.png' => 'computadores-e-informatica',
    'esportes-aventura-e-lazer.png' => 'esporte-aventura-e-lazer',
    'geladeiras.png' => 'melhores-geladeiras-side-by-side',
    'ofertas.png' => 'shopee',
    'pet-shop.png' => 'pet-shop',
    'placas-de-neon.png' => 'placas-de-neon',
);
foreach ( $categories as $filename => $slug ) {
    if(isset($argv[1]) && $slug!==$argv[1]){continue;}
    $term = get_term_by( 'slug', $slug, 'category' );
    if ( ! $term ) { throw new RuntimeException( 'Categoria não encontrada: ' . $slug ); }
    $source = dirname( __DIR__ ) . '/produtos-inserir/' . $filename;
    if ( ! is_file( $source ) || ! wp_getimagesize( $source ) ) { throw new RuntimeException( 'Imagem inválida: ' . $source ); }
    $hash = hash_file( 'sha256', $source );
    $existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'meta_key' => '_grv_category_source_hash', 'meta_value' => $hash ) );
    if ( $existing ) {
        $id = $existing[0]->ID;
    } else {
        $tmp = wp_tempnam( $filename );
        if ( ! $tmp || ! copy( $source, $tmp ) ) { throw new RuntimeException( 'Falha ao copiar imagem: ' . $filename ); }
        $id = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $tmp ), 0, $term->name );
        if ( is_wp_error( $id ) ) { @unlink( $tmp ); throw new RuntimeException( $id->get_error_message() ); }
        update_post_meta( $id, '_grv_category_source_hash', $hash );
        update_post_meta( $id, '_wp_attachment_image_alt', $term->name );
    }
    update_term_meta( $term->term_id, 'image', $id );
    echo wp_json_encode( array( 'category' => $term->name, 'term_id' => $term->term_id, 'attachment' => $id, 'url' => grv_get_field( 'image', 'category_' . $term->term_id ) ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
}
