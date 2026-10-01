<?php
/** Versioned theme icons for browsers and installable shortcuts. */
defined( 'ABSPATH' ) || exit;
function grv_favicon_tags() {
    $base = get_template_directory_uri() . '/favicon/';
    $asset = function ( $file ) use ( $base ) {
        $path = get_template_directory() . '/favicon/' . $file;
        return $base . $file . '?v=' . ( is_file( $path ) ? substr( hash_file( 'sha256', $path ), 0, 12 ) : '1' );
    };
    foreach ( array( 16, 32, 48, 96 ) as $size ) {
        echo '<link rel="icon" type="image/png" sizes="' . $size . 'x' . $size . '" href="' . esc_url( $asset( 'images/favicon-' . $size . 'x' . $size . '.png' ) ) . '">' . "\n";
    }
    echo '<link rel="icon" type="image/x-icon" href="' . esc_url( $asset( 'images/favicon.ico' ) ) . '">' . "\n";
    echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url( $asset( 'images/apple-touch-icon.png' ) ) . '">' . "\n";
    echo '<link rel="mask-icon" href="' . esc_url( $asset( 'images/safari-pinned-tab.svg' ) ) . '" color="#ff1637">' . "\n";
    echo '<link rel="manifest" href="' . esc_url( $asset( 'site.webmanifest' ) ) . '">' . "\n";
    echo '<meta name="application-name" content="Guia Review"><meta name="apple-mobile-web-app-title" content="Guia Review">' . "\n";
    echo '<meta name="msapplication-TileColor" content="#131921"><meta name="msapplication-TileImage" content="' . esc_url( $asset( 'images/mstile-144x144.png' ) ) . '">' . "\n";
    echo '<meta name="msapplication-config" content="' . esc_url( $asset( 'browserconfig.xml' ) ) . '">' . "\n";
}
add_action( 'wp_head', 'grv_favicon_tags' );
add_action( 'admin_head', 'grv_favicon_tags' );
add_action( 'login_head', 'grv_favicon_tags' );
// The theme supplies the same artwork on frontend, dashboard and login.
add_filter( 'site_icon_meta_tags', '__return_empty_array' );
