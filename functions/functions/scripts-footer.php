<?php
/** Load only the current catalog stylesheet. Feature scripts declare their own dependencies. */
defined( 'ABSPATH' ) || exit;
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'guiareview-wp', get_template_directory_uri() . '/css/guiareview.css', array(), filemtime( get_template_directory() . '/css/guiareview.css' ) );
} );

