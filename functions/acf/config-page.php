<?php

add_action('acf/init', 'my_acf_op_init');
function my_acf_op_init() {

    // Check function exists.
    if( function_exists('acf_add_options_page') ) {

        // Add parent.
        $parent = acf_add_options_page(array(
            'page_title'  => __('Configurações do site'),
            'menu_title'  => __('Configurações do site'),
            'redirect'    => false,
        ));

        $child = acf_add_options_page(array(
            'page_title'  => __('Tutorial'),
            'menu_title'  => __('Tutorial'),
            'parent_slug' => $parent['menu_slug'],
        ));
    }
}