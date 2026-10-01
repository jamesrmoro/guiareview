<?php
add_action('wp_enqueue_scripts', 'sprintcodes_enqueue_scripts_input');
function sprintcodes_enqueue_scripts_input(){
	$postfix = ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ? '' : '.min';
	$js = array(
		'js_global' => [
			'jquery-3.6.0.min',
		],
	);


	foreach ($js['js_global'] as $item) {
		wp_enqueue_script( $item, get_template_directory_uri() . "/js/" . "$item.js", array(), sprintcodes_VERSION );
	}

	wp_enqueue_style( 'blog-wp', get_template_directory_uri() . "/css/blog.css", array(), sprintcodes_VERSION );
	wp_enqueue_style( 'search-wp', get_template_directory_uri() . "/css/search.css", array(), sprintcodes_VERSION );
	wp_enqueue_style( 'style-wp', get_template_directory_uri() . "/css/style.css", array(), sprintcodes_VERSION );

	// Redesenho: home, categorias, tags e produtos usam a nova folha única,
	// mais leve, no lugar de single.css/index.css/swiper (não usados mais).
	wp_enqueue_style( 'guiareview-wp', get_template_directory_uri() . "/css/guiareview.css", array( 'style-wp' ), filemtime( get_template_directory() . '/css/guiareview.css' ) );

	$translation_array = array(
     	'siteURL' => get_site_url(),
     	'siteUrlTemplate' => get_bloginfo('template_url'),
  	);

  	wp_localize_script( 'jquery-3.6.0.min', 'sprintcodesData', $translation_array );
}

