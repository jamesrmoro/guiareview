<?php
/** Shared category navigation and product sharing controls. */

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_script( 'grv-catalog-ui', get_template_directory_uri() . '/js/catalog-ui.js', array(), filemtime( __DIR__ . '/../js/catalog-ui.js' ), true );
} );

function grv_catalog_categories() {
    static $catalog;
    if ( ! isset( $catalog ) ) {
        $catalog = array(
            'terms' => get_categories( array( 'hide_empty' => false, 'orderby' => 'name' ) ),
            'counts' => grv_category_product_counts(),
        );
    }
    return $catalog;
}

function grv_category_navigation() {
    $catalog = grv_catalog_categories();
    // Preserve the configured main menu order and add the other catalog roots.
    $entries = array();
    $locations = get_nav_menu_locations();
    $items = empty( $locations['categorias'] ) ? array() : wp_get_nav_menu_items( $locations['categorias'] );
    foreach ( $items ?: array() as $item ) {
        if ( $item->menu_item_parent ) { continue; }
        $key = $item->object === 'category' ? 'cat-' . $item->object_id : 'menu-' . $item->ID;
        $entries[ $key ] = array( 'label' => $item->title, 'url' => $item->url, 'term' => $item->object === 'category' ? (int) $item->object_id : 0 );
    }
    foreach ( $catalog['terms'] as $term ) {
        if ( $term->parent ) { continue; }
        $key = 'cat-' . $term->term_id;
        if ( ! isset( $entries[ $key ] ) ) {
            $entries[ $key ] = array( 'label' => $term->name, 'url' => get_category_link( $term ), 'term' => $term->term_id );
        }
    }
    echo '<ul class="grv-cat-list" id="grvCategoryNav">';
    foreach ( $entries as $key => $entry ) {
        echo '<li class="grv-nav-item" data-category-key="' . esc_attr( $key ) . '"><a href="' . esc_url( $entry['url'] ) . '">' . esc_html( $entry['label'] ) . '</a></li>';
    }
    echo '<li class="grv-nav-more"><details class="grv-nav-dropdown"><summary><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M3 6h18M3 12h18M3 18h18"/></svg><span>Outras categorias</span><svg class="grv-nav-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg></summary>';
    echo '<div class="grv-mega-panel"><div class="grv-mega-heading"><strong>OUTRAS CATEGORIAS</strong><span class="grv-category-count" id="grvMoreCount">' . count( $catalog['terms'] ) . ' categorias</span></div><ul class="grv-mega-grid" id="grvMoreCategories">';
    foreach ( $catalog['terms'] as $term ) {
        echo '<li class="grv-more-item" data-category-key="cat-' . (int) $term->term_id . '">';
        grv_category_menu_link( $term, $catalog['counts'] );
        echo '</li>';
    }
    foreach ( $entries as $key => $entry ) {
        if ( $entry['term'] ) { continue; }
        echo '<li class="grv-more-item" data-category-key="' . esc_attr( $key ) . '"><a class="grv-category-row" href="' . esc_url( $entry['url'] ) . '"><span class="grv-category-label">' . esc_html( $entry['label'] ) . '</span></a></li>';
    }
    echo '</ul><div class="grv-mega-footer"><button type="button" data-open-category-drawer><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>Ver todas as ' . count( $catalog['terms'] ) . ' categorias no menu lateral</button></div></div></details></li></ul>';
}

function grv_category_menu_link( $term, $counts ) {
    $image = grv_get_field( 'menu_icon', 'category_' . $term->term_id );
    if ( ! $image ) { $image = grv_get_field( 'image', 'category_' . $term->term_id ); }
    if ( ! $image ) {
        foreach ( get_ancestors( $term->term_id, 'category' ) as $ancestor ) {
            $image = grv_get_field( 'image', 'category_' . $ancestor );
            if ( $image ) { break; }
        }
    }
    echo '<a class="grv-category-row" href="' . esc_url( get_category_link( $term ) ) . '" data-category-name="' . esc_attr( $term->name ) . '">';
    echo '<span class="grv-category-icon" aria-hidden="true">';
    if ( $image ) {
        echo '<img src="' . esc_url( $image ) . '" alt="" width="36" height="36" loading="lazy">';
    } else {
        echo '<svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>';
    }
    $count = $counts[ $term->term_id ] ?? 0;
    echo '</span><span class="grv-category-label">' . esc_html( $term->name ) . '</span><span class="grv-category-count" aria-label="' . esc_attr( $count . ( $count === 1 ? ' produto' : ' produtos' ) ) . '">' . (int) $count . '</span></a>';
}

function grv_catalog_drawer() {
    $catalog = grv_catalog_categories();
    echo '<ul class="grv-drawer-categories" id="grvDrawerCategories">';
    foreach ( $catalog['terms'] as $term ) {
        echo '<li>';
        grv_category_menu_link( $term, $catalog['counts'] );
        echo '</li>';
    }
    echo '</ul><p class="grv-category-empty" id="grvCategoryEmpty" role="status" hidden>Nenhuma categoria encontrada.</p>';
}

function grv_product_share( $post_id, $image ) {
    $permalink = get_permalink( $post_id );
    $url = rawurlencode( $permalink );
    $title = rawurlencode( html_entity_decode( wp_strip_all_tags( get_the_title( $post_id ) ), ENT_QUOTES, 'UTF-8' ) );
    $networks = array(
        'whatsapp' => array( 'WhatsApp', 'https://api.whatsapp.com/send?text=' . $title . '%20' . $url, '<path d="M20.5 11.6a8.5 8.5 0 0 1-12.6 7.5L3 20.5l1.4-4.8a8.5 8.5 0 1 1 16.1-4.1Z"/><path d="M8.2 7.6c-.7.4-.8 1.6-.1 3 1 2.1 2.8 3.8 5 4.6 1.4.5 2.5.1 2.8-.7l.3-1-2.5-1.2-.9 1c-1.3-.5-2.5-1.6-3-2.8l.8-1-1.2-2.1Z"/>' ),
        'facebook' => array( 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $url, '<path fill="currentColor" stroke="none" d="M14 22v-9h3l.5-4H14V7c0-1.2.4-2 2-2h2V1.4A23 23 0 0 0 15 1c-3 0-5 1.8-5 5v3H7v4h3v9Z"/>' ),
        'x' => array( 'X', 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title, '<path d="m4 3 16 18M20 3 4 21M4 3h4l12 18h-4Z"/>' ),
        'telegram' => array( 'Telegram', 'https://t.me/share/url?url=' . $url . '&text=' . $title, '<path d="m3 10 18-7-4 18-6-6-4 3 1-6 9-6-6 9M3 10l5 2"/>' ),
        'pinterest' => array( 'Pinterest', 'https://pinterest.com/pin/create/button/?url=' . $url . '&media=' . rawurlencode( $image ) . '&description=' . $title, '<path d="m8 22 3-14M10 15c5 4 9-1 9-6a7 7 0 0 0-14 0c0 2 1 4 2 4M11 8c2-3 5-1 4 2l-1 4"/>' ),
    );
    echo '<div class="grv-share" role="group" aria-label="Compartilhar produto">';
    foreach ( $networks as $key => $network ) {
        echo '<a class="grv-share-button grv-share-' . esc_attr( $key ) . '" href="' . esc_url( $network[1] ) . '" target="_blank" rel="noopener noreferrer" aria-label="Compartilhar no ' . esc_attr( $network[0] ) . '" title="Compartilhar no ' . esc_attr( $network[0] ) . '"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $network[2] . '</svg></a>';
    }
    echo '<button type="button" class="grv-share-button grv-share-copy" data-copy-url="' . esc_url( $permalink ) . '" aria-label="Copiar link do produto" title="Copiar link do produto"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="8" y="8" width="12" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg></button>';
    echo '<span class="grv-share-status" role="status" aria-live="polite"></span></div>';
}
