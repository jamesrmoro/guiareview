<?php
/** Only proportional, responsive sizes used by the catalog. */
defined( 'ABSPATH' ) || exit;

add_image_size( 'product_card', 400, 400, false );
add_image_size( 'product_display', 640, 640, false );
add_filter( 'intermediate_image_sizes_advanced', function ( $sizes ) {
    $keep = array(
        'thumbnail' => array( 'width' => 176, 'height' => 176, 'crop' => false ),
        'product_card' => array( 'width' => 400, 'height' => 400, 'crop' => false ),
        'product_display' => array( 'width' => 640, 'height' => 640, 'crop' => false ),
    );
    // WordPress also needs its dedicated favicon sizes when generating a site icon.
    foreach ( $sizes as $name => $size ) {
        if ( str_starts_with( $name, 'site_icon-' ) ) { $keep[ $name ] = $size; }
    }
    return $keep;
} );
add_filter( 'big_image_size_threshold', fn() => 1280 );
add_filter( 'image_editor_output_format', function ( $formats ) {
    $formats['image/jpeg'] = 'image/webp';
    return $formats;
} );
add_filter( 'wp_handle_upload', function ( $upload ) {
    if ( ! empty( $upload['error'] ) || ( $upload['type'] ?? '' ) !== 'image/jpeg' ) { return $upload; }
    $root = wp_normalize_path( realpath( wp_upload_dir()['basedir'] ) );
    $source = wp_normalize_path( realpath( $upload['file'] ) );
    if ( ! $root || ! $source || ! str_starts_with( strtolower( $source ), strtolower( $root . '/' ) ) ) { return $upload; }
    $editor = wp_get_image_editor( $source );
    if ( is_wp_error( $editor ) ) { return $upload; }
    $editor->set_quality( 82 );
    $editor->resize( 1280, 1280, false );
    $name = wp_unique_filename( dirname( $source ), pathinfo( $source, PATHINFO_FILENAME ) . '.webp' );
    $saved = $editor->save( dirname( $source ) . '/' . $name, 'image/webp' );
    if ( is_wp_error( $saved ) || ! is_file( $saved['path'] ) ) { return $upload; }
    // Preserve WordPress's native Windows separator prefix for relative attachment paths.
    $upload['file'] = wp_upload_dir()['basedir'] . substr( wp_normalize_path( $saved['path'] ), strlen( $root ) );
    $upload['url'] = trailingslashit( dirname( $upload['url'] ) ) . $name;
    $upload['type'] = 'image/webp';
    wp_delete_file( $source );
    return $upload;
} );

/** Native attachment output provides dimensions and responsive srcset automatically. */
function grv_media_image( $value, $size, $attributes = array() ) {
    if ( is_numeric( $value ) ) {
        return wp_get_attachment_image( (int) $value, $size, false, $attributes );
    }
    if ( is_array( $value ) ) {
        return ! empty( $value['ID'] ) ? grv_media_image( $value['ID'], $size, $attributes ) : grv_media_image( $value['url'] ?? '', $size, $attributes );
    }
    if ( ! $value ) { return ''; }
    $html = '<img src="' . esc_url( $value ) . '"';
    foreach ( $attributes as $key => $attribute ) {
        $html .= ' ' . esc_attr( $key ) . '="' . esc_attr( $attribute ) . '"';
    }
    return $html . '>';
}
