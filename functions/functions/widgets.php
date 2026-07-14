<?php

add_action( 'widgets_init', 'imob9_widgets_init' );


 function imob9_widgets_init() {

    register_sidebar ( array (
        'name' => __( 'Coluna 1 - Rodapé', 'imob9' ),
        'id' => 'footer_1',
        'description' => __( 'Coluna 1 do rodapé', 'imob9' ),
        'before_widget' => '',
        'after_widget' => '',
        'before_title' => '',
        'after_title' => '',
    ));

    register_sidebar ( array (
        'name' => __( 'Coluna 2 - Rodapé', 'imob9' ),
        'id' => 'footer_2',
        'description' => __( 'Coluna 2 do rodapé', 'imob9' ),
        'before_widget' => '',
        'after_widget' => '',
        'before_title' => '',
        'after_title' => '',
    ));

    register_sidebar ( array (
        'name' => __( 'Coluna 3 - Rodapé', 'imob9' ),
        'id' => 'footer_3',
        'description' => __( 'Coluna 3 do rodapé', 'imob9' ),
        'before_widget' => '',
        'after_widget' => '',
        'before_title' => '',
        'after_title' => '',
    ));

}