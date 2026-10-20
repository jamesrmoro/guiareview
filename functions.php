<?php
require_once __DIR__ . '/functions/yoast-native.php';
require_once __DIR__ . '/functions/search-insights.php';
require_once __DIR__ . '/functions/catalog-ui.php';


add_theme_support( 'post-thumbnails' );
add_theme_support( 'title-tag' );

require_once __DIR__ . '/functions/media-optimization.php';

register_nav_menus( array(
  'categorias' => __( 'Menu de Categorias' ),
) );

/**
 * Breadcrumb simples. $items = [ ['label'=>'Casa','url'=>'...'], ['label'=>'Atual','url'=>null] ]
 */
function grv_breadcrumbs( $items ) {
  if ( empty( $items ) ) {
    return;
  }
  echo '<nav class="container grv-crumbs" aria-label="Trilha de navegação"><ol>';
  echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">Início</a></li>';
  $last = count( $items ) - 1;
  foreach ( $items as $i => $item ) {
    if ( ! empty( $item['url'] ) && $i !== $last ) {
      echo '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a></li>';
    } else {
      echo '<li aria-current="page">' . esc_html( $item['label'] ) . '</li>';
    }
  }
  echo '</ol></nav>';
}

/**
 * Categorias ancestrais de um termo, da mais antiga para a mais recente
 * (não inclui o próprio termo).
 */
function grv_category_ancestors( $term ) {
  $ancestors = array_reverse( get_ancestors( $term->term_id, 'category' ) );
  $trail = array();
  foreach ( $ancestors as $ancestor_id ) {
    $ancestor = get_term( $ancestor_id, 'category' );
    if ( $ancestor && ! is_wp_error( $ancestor ) ) {
      $trail[] = array( 'label' => $ancestor->name, 'url' => get_category_link( $ancestor->term_id ) );
    }
  }
  return $trail;
}

/**
 * Converte um campo textarea "Rótulo: texto" (uma linha por item) em array
 * de ['label'=>..,'text'=>..]. Linhas sem ":" viram texto simples (label vazio).
 */
function grv_parse_lines( $raw ) {
  $out = array();
  if ( empty( $raw ) ) {
    return $out;
  }
  foreach ( preg_split( "/\r\n|\r|\n/", $raw ) as $line ) {
    $line = trim( $line );
    if ( $line === '' ) {
      continue;
    }
    if ( strpos( $line, ':' ) !== false ) {
      list( $label, $text ) = explode( ':', $line, 2 );
      $out[] = array( 'label' => trim( $label ), 'text' => trim( $text ) );
    } else {
      $out[] = array( 'label' => '', 'text' => $line );
    }
  }
  return $out;
}

/**
 * Card de produto reaproveitado no grid (categoria, home, relacionados).
 */
/** Unique published products in each category and all its descendants. */
function grv_category_product_counts() {
  global $wpdb;
  $terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
  if ( is_wp_error( $terms ) ) { return array(); }
  $parents = array(); $products = array();
  foreach ( $terms as $term ) { $parents[$term->term_id] = (int) $term->parent; }
  $rows = $wpdb->get_results( "SELECT DISTINCT tt.term_id, p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE p.post_type = 'post' AND p.post_status = 'publish' AND tt.taxonomy = 'category'" );
  foreach ( $rows as $row ) {
    $id = (int) $row->term_id; $visited = array();
    while ( $id && ! isset( $visited[$id] ) ) {
      $visited[$id] = true; $products[$id][$row->ID] = true; $id = $parents[$id] ?? 0;
    }
  }
  return array_map( 'count', $products );
}
add_action( 'pre_get_posts', function ( $query ) {
  if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
    $query->set( 'post_type', 'post' ); $query->set( 'posts_per_page', 12 );
  }
} );

function grv_product_card( $post_id ) {
  $title    = get_the_title( $post_id );
  $url      = grv_get_field( 'url', $post_id );
  $image    = grv_get_field( 'image', $post_id );
  $price    = grv_get_field( 'price', $post_id );
  $old_price= grv_get_field( 'old_price', $post_id );

  if ( ! $image ) {
    $image = get_the_post_thumbnail_url( $post_id, 'product_card' );
  }
  if ( ! $image ) {
    $image = get_template_directory_uri() . '/src/images/placeholder-product.svg';
  }

  $link = get_permalink( $post_id );
  ?>
  <article class="grv-card">
    <a class="grv-card-main" href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $title ); ?>">
    <span class="thumb"><?php echo grv_media_image( get_post_meta( $post_id, 'image', true ) ?: ( get_post_thumbnail_id( $post_id ) ?: $image ), 'product_card', array( 'alt' => $title, 'loading' => 'lazy', 'sizes' => '(max-width: 460px) calc(100vw - 48px), (max-width: 760px) 45vw, (max-width: 1100px) 30vw, 260px' ) ); ?></span>
    <h3><?php echo esc_html( $title ); ?></h3>
    </a>
    <?php if ( $price ) : ?>
      <span class="price"><?php if ( $old_price && $old_price > $price ) : ?><span class="old">R$ <?php echo esc_html( number_format( $old_price, 2, ',', '.' ) ); ?></span><?php endif; ?>R$ <?php echo esc_html( number_format( $price, 2, ',', '.' ) ); ?></span>
    <?php endif; ?>
    <a class="btn-buy-sm" href="<?php echo esc_url( $link ); ?>"><?php echo $url ? 'Ver oferta' : 'Ver detalhes'; ?></a>
  </article>
  <?php
}

function grv_category_drawer_tree( $parent_id = 0 ) {
  $cats = get_categories( array(
    'parent'     => $parent_id,
    'hide_empty' => false,
    'orderby'    => 'name',
  ) );

  if ( empty( $cats ) ) {
    return;
  }

  foreach ( $cats as $cat ) {
    $has_children = get_categories( array( 'parent' => $cat->term_id, 'hide_empty' => false, 'number' => 1 ) );
    if ( $has_children ) {
      echo '<details><summary>' . esc_html( $cat->name ) . '</summary><ul class="grv-drawer-sub"><li><a href="' . esc_url( get_category_link( $cat->term_id ) ) . '">Ver tudo em ' . esc_html( $cat->name ) . '</a></li></ul>';
      grv_category_drawer_tree( $cat->term_id );
      echo '</details>';
    } else {
      echo '<a class="grv-drawer-flat-link" href="' . esc_url( get_category_link( $cat->term_id ) ) . '">' . esc_html( $cat->name ) . '</a>';
    }
  }
}

remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');

// Performance: remove desnecessários do <head> (site mais leve)
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
add_filter( 'xmlrpc_enabled', '__return_false' );
add_action( 'init', function () {
  remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
  remove_action( 'wp_head', 'wp_oembed_add_host_js' );
  wp_deregister_script( 'wp-embed' );
} );

// Functions
require(get_template_directory() . '/functions/functions/scripts-footer.php' );

require(get_template_directory() . '/functions/functions/login-style.php' );
require(get_template_directory() . '/functions/functions/pagination.php' );
require(get_template_directory() . '/functions/functions/seo.php' );

// Campos nativos do WordPress.
require get_template_directory() . '/functions/native-fields.php';
require get_template_directory() . '/functions/affiliate-links.php';
require get_template_directory() . '/functions/advertisements.php';
require get_template_directory() . '/functions/dashboard-progress.php';
require get_template_directory() . '/favicon/favicon.php';

add_filter( 'use_widgets_block_editor', '__return_false' );

function isMobile() {
  return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}
function add_additional_class_on_a($classes, $item, $args) {
    if (isset($args->add_a_class)) {
        $classes['class'] = $args->add_a_class;
    }
    return $classes;
}
add_filter('nav_menu_link_attributes', 'add_additional_class_on_a', 1, 3);

// Adiciona a coluna da imagem em miniatura na tela de administração
function custom_columns_head($defaults) {
    // Move a coluna da miniatura para a primeira posição
    $new_defaults = array_slice($defaults, 0, 1, true) + array('thumbnail' => 'Imagem em Miniatura') + array_slice($defaults, 1, null, true);
    return $new_defaults;
}

// Exibe a imagem em miniatura na coluna adicionada
function custom_columns_content($column_name, $post_ID) {
    if ($column_name == 'thumbnail') {
    	echo '<div style="display:flex;align-items: center;justify-content: center;">';
        echo get_the_post_thumbnail($post_ID, array(100, 100));
    	echo '</div>';
    }
}

// Adiciona as colunas personalizadas
add_filter('manage_posts_columns', 'custom_columns_head');
add_action('manage_posts_custom_column', 'custom_columns_content', 10, 2);

function remove_block_library_style() {
    wp_dequeue_style('wp-block-library');
}

add_action('wp_enqueue_scripts', 'remove_block_library_style');




// Altera o nome do remetente
add_filter('wp_mail_from_name', function($name) {
    return 'Guia Review';
});

// Altera o e-mail do remetente
add_filter('wp_mail_from', function($email) {
    return 'contato@guiareview.com.br';
});

// Preserve bookmarks for the former duplicate category and privacy URL.
add_action( 'template_redirect', function () {
    $path = trim( (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );
    $base = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
    if ( $base && strpos( $path, $base . '/' ) === 0 ) { $path = substr( $path, strlen( $base ) + 1 ); }
    if ( $path === 'privacy' ) {
        wp_safe_redirect( get_privacy_policy_url() ?: home_url( '/politica-de-privacidade/' ), 301 );
        exit;
    }
    $redirects = get_option( 'grv_category_redirects', array() );
    foreach ( $redirects as $slug => $term_id ) {
        if ( $path === 'category/' . $slug || ( isset( $_GET['cat'] ) && (int) $_GET['cat'] === 80 ) ) {
            $url = get_category_link( $term_id );
            if ( ! is_wp_error( $url ) ) { wp_safe_redirect( $url, 301 ); exit; }
        }
    }
} );
