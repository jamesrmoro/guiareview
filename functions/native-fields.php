<?php
/** Native fields using the existing metadata keys, including former ACF data. */
defined( 'ABSPATH' ) || exit;

function grv_get_field( $name, $object_id = false ) {
    if ( $object_id === 'option' || $object_id === 'options' ) {
        return get_option( 'options_' . $name, '' );
    }
    if ( is_string( $object_id ) && preg_match( '/^category_(\d+)$/', $object_id, $match ) ) {
        $value = get_term_meta( (int) $match[1], $name, true );
    } else {
        $value = get_post_meta( $object_id ?: get_the_ID(), $name, true );
        if ( $name === 'url' ) { $value = get_post_meta( $object_id ?: get_the_ID(), 'affiliate_url', true ) ?: $value; }
    }
    if ( $name === 'image' ) {
        if ( is_array( $value ) ) {
            return $value['url'] ?? '';
        }
        return is_numeric( $value ) ? ( wp_get_attachment_image_url( (int) $value, 'large' ) ?: '' ) : $value;
    }
    return $value;
}

function grv_update_field( $name, $value, $object_id ) {
    if ( $object_id === 'option' || $object_id === 'options' ) {
        return update_option( 'options_' . $name, $value );
    }
    return update_post_meta( $object_id, $name, $value );
}

function grv_product_fields() {
    return array(
        'url' => array( 'Link original do produto (opcional)', 'url' ),
        'affiliate_url' => array( 'Link de afiliado', 'url' ),
        'image' => array( 'Imagem principal', 'image' ),
        'gallery' => array( 'Galeria de imagens', 'gallery' ),
        'brand' => array( 'Marca', 'text' ),
        'color' => array( 'Cor', 'text' ),
        'price' => array( 'Preço (R$)', 'number' ),
        'old_price' => array( 'Preço anterior (R$)', 'number' ),
        'rating' => array( 'Nota (0 a 5)', 'number' ),
        'review_count' => array( 'Quantidade de avaliações', 'number' ),
        'bullets' => array( 'Destaques (um por linha)', 'textarea' ),
        'specs' => array( 'Especificações (Rótulo: valor, um por linha)', 'textarea' ),
        'author' => array( 'Autor', 'text' ),
        'company' => array( 'Editora', 'text' ),
        'pages' => array( 'Páginas', 'number' ),
        'language' => array( 'Idioma', 'text' ),
        'isbn' => array( 'ISBN', 'text' ),
        'isbn_13' => array( 'ISBN-13', 'text' ),
        'measurements' => array( 'Medidas', 'text' ),
        'date_published' => array( 'Data de publicação', 'text' ),
        'file_size' => array( 'Tamanho do arquivo', 'text' ),
        'page_flip' => array( 'Page Flip', 'text' ),
        'vocabulary_tips' => array( 'Dicas de vocabulário', 'text' ),
        'font_configuration' => array( 'Configuração de fonte', 'text' ),
    );
}

add_action( 'add_meta_boxes_post', function () {
    add_meta_box( 'grv-product', 'Informações do produto', 'grv_render_product_fields', 'post', 'normal', 'high' );
} );

function grv_render_product_fields( $post ) {
    wp_nonce_field( 'grv_product_save', 'grv_product_nonce' );
    foreach ( grv_product_fields() as $name => $field ) {
        if ( $name === 'affiliate_url' ) { continue; }
        list( $label, $type ) = $field;
        $value = get_post_meta( $post->ID, $name, true );
        if ( $type === 'gallery' ) {
            $value = is_array( $value ) ? implode( ',', array_map( 'absint', $value ) ) : $value;
        }
        if ( ! is_scalar( $value ) ) { $value = ''; }
        echo '<p><label for="grv-' . esc_attr( $name ) . '"><strong>' . esc_html( $label ) . '</strong></label><br>';
        if ( $type === 'textarea' ) {
            echo '<textarea class="widefat" rows="5" id="grv-' . esc_attr( $name ) . '" name="grv_product[' . esc_attr( $name ) . ']">' . esc_textarea( $value ) . '</textarea>';
        } else {
            $input_type = in_array( $type, array( 'image', 'gallery' ), true ) ? 'text' : $type;
            $limits = $type === 'number' ? ' min="0" step="' . ( in_array( $name, array( 'review_count', 'pages' ), true ) ? '1' : '0.01' ) . '"' : '';
            if ( $name === 'rating' ) { $limits .= ' max="5"'; }
            echo '<input class="widefat" id="grv-' . esc_attr( $name ) . '" name="grv_product[' . esc_attr( $name ) . ']" type="' . esc_attr( $input_type ) . '" value="' . esc_attr( $value ) . '"' . $limits . '>';
            if ( in_array( $type, array( 'image', 'gallery' ), true ) ) {
                echo '<button type="button" class="button grv-media" data-target="grv-' . esc_attr( $name ) . '" data-multiple="' . ( $type === 'gallery' ? '1' : '0' ) . '">Selecionar imagens</button> <button type="button" class="button grv-media-clear" data-target="grv-' . esc_attr( $name ) . '">Limpar</button><div id="grv-' . esc_attr( $name ) . '-preview">';
                $ids = $type === 'gallery' ? explode( ',', $value ) : array( $value );
                foreach ( $ids as $id ) { echo wp_get_attachment_image( absint( $id ), 'thumbnail' ); }
                echo '</div>';
            }
        }
        echo '</p>';
    }
}

add_action( 'admin_enqueue_scripts', function () {
    $screen = get_current_screen();
    if ( ! $screen || $screen->base !== 'post' || $screen->post_type !== 'post' ) { return; }
    wp_enqueue_media();
    wp_enqueue_script( 'grv-native-fields', get_template_directory_uri() . '/js/native-fields.js', array( 'jquery', 'media-views' ), filemtime( get_template_directory() . '/js/native-fields.js' ), true );
} );

add_action( 'save_post_post', function ( $post_id ) {
    if ( ! isset( $_POST['grv_product_nonce'] ) || ! is_string( $_POST['grv_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['grv_product_nonce'] ) ), 'grv_product_save' ) ) { return; }
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
    $values = isset( $_POST['grv_product'] ) && is_array( $_POST['grv_product'] ) ? wp_unslash( $_POST['grv_product'] ) : array();
    foreach ( grv_product_fields() as $name => $field ) {
        if ( ! array_key_exists( $name, $values ) || ! is_scalar( $values[$name] ) ) { continue; }
        $value = $values[$name];
        switch ( $field[1] ) {
            case 'url': $value = esc_url_raw( $value, array( 'http', 'https' ) ); break;
            case 'textarea': $value = sanitize_textarea_field( $value ); break;
            case 'image':
                $value = is_numeric( $value ) ? absint( $value ) : esc_url_raw( $value );
                if ( is_int( $value ) && $value && ! wp_attachment_is_image( $value ) ) { continue 2; }
                break;
            case 'gallery':
                $value = array_values( array_unique( array_filter( array_map( 'absint', explode( ',', $value ) ), 'wp_attachment_is_image' ) ) );
                break;
            case 'number':
                $value = str_replace( ',', '.', $value );
                if ( $value !== '' && ! is_numeric( $value ) ) { continue 2; }
                if ( $value !== '' ) {
                    $value = max( 0, (float) $value );
                    if ( $name === 'rating' ) { $value = min( 5, $value ); }
                    if ( in_array( $name, array( 'review_count', 'pages' ), true ) ) { $value = (int) $value; }
                }
                break;
            default: $value = sanitize_text_field( $value );
        }
        update_post_meta( $post_id, $name, $value );
    }
} );

// Category metadata uses the same keys as the former category fields.
function grv_category_fields( $term = null ) {
    $editing = $term instanceof WP_Term;
    wp_nonce_field( 'grv_category_save', 'grv_category_nonce' );
    foreach ( array( 'image' => 'Imagem (ID da mídia ou URL)', 'flag' => 'Destaque da categoria' ) as $name => $label ) {
        $value = $editing ? get_term_meta( $term->term_id, $name, true ) : '';
        echo $editing ? '<tr class="form-field"><th><label for="grv-cat-' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th><td>' : '<div class="form-field"><label for="grv-cat-' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>';
        echo '<input id="grv-cat-' . esc_attr( $name ) . '" type="' . ( $name === 'flag' ? 'checkbox' : 'text' ) . '" name="grv_category[' . esc_attr( $name ) . ']" value="' . esc_attr( $name === 'flag' ? '1' : $value ) . '"' . ( $name === 'flag' ? checked( (bool) $value, true, false ) : '' ) . '>';
        echo $editing ? '</td></tr>' : '</div>';
    }
}
add_action( 'category_add_form_fields', 'grv_category_fields' );
add_action( 'category_edit_form_fields', 'grv_category_fields' );
function grv_save_category_fields( $term_id ) {
    if ( ! current_user_can( 'manage_categories' ) || ! isset( $_POST['grv_category_nonce'] ) || ! is_string( $_POST['grv_category_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['grv_category_nonce'] ) ), 'grv_category_save' ) ) { return; }
    $values = isset( $_POST['grv_category'] ) && is_array( $_POST['grv_category'] ) ? wp_unslash( $_POST['grv_category'] ) : array();
    if ( isset( $values['image'] ) && is_scalar( $values['image'] ) ) {
        update_term_meta( $term_id, 'image', is_numeric( $values['image'] ) ? absint( $values['image'] ) : esc_url_raw( $values['image'] ) );
    }
    update_term_meta( $term_id, 'flag', empty( $values['flag'] ) ? 0 : 1 );
}
add_action( 'created_category', 'grv_save_category_fields' );
add_action( 'edited_category', 'grv_save_category_fields' );

add_action( 'admin_menu', function () {
    add_options_page( 'Configurações do site', 'Configurações do site', 'manage_options', 'grv-site-settings', 'grv_site_settings' );
} );
add_action( 'admin_init', function () {
    foreach ( array( 'script_header', 'script_footer' ) as $name ) {
        register_setting( 'grv_site_settings', 'options_' . $name, array( 'type' => 'string', 'sanitize_callback' => function ( $value ) {
            return current_user_can( 'unfiltered_html' ) && is_string( $value ) ? $value : '';
        } ) );
    }
} );
function grv_site_settings() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    echo '<div class="wrap"><h1>Configurações do site</h1>';
    if ( current_user_can( 'unfiltered_html' ) ) {
        echo '<form action="options.php" method="post">';
        settings_fields( 'grv_site_settings' );
        foreach ( array( 'script_header' => 'Script header', 'script_footer' => 'Script footer' ) as $name => $label ) {
            echo '<p><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></p><textarea class="large-text code" rows="8" id="' . esc_attr( $name ) . '" name="options_' . esc_attr( $name ) . '">' . esc_textarea( get_option( 'options_' . $name, '' ) ) . '</textarea>';
        }
        submit_button();
        echo '</form>';
    }
    echo '</div>';
}
