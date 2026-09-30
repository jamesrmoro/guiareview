<?php
/**
 * SEO básico do tema (sem plugin de SEO ativo no site):
 * meta description, canonical, Open Graph e JSON-LD Organization/WebSite.
 * JSON-LD de Product fica em single.php (precisa dos campos ACF do post).
 */

function grv_meta_description() {
	if ( is_singular( 'post' ) ) {
		global $post;
		$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_strip_all_tags( $post->post_content );
		$excerpt = wp_trim_words( $excerpt, 30, '…' );
		return $excerpt ?: get_bloginfo( 'description' );
	}

	if ( is_category() ) {
		$term = get_queried_object();
		if ( $term && ! empty( $term->description ) ) {
			return wp_trim_words( wp_strip_all_tags( $term->description ), 30, '…' );
		}
		return 'Comparativos, análises e as melhores opções em ' . single_cat_title( '', false ) . ' — Guia Review.';
	}

	if ( is_tag() ) {
		return 'Produtos relacionados a ' . single_tag_title( '', false ) . ' — Guia Review.';
	}

	if ( is_front_page() ) {
		return 'Guia Review: análises, comparativos e as melhores recomendações de produtos para você comprar com confiança.';
	}

	return get_bloginfo( 'description' );
}

function grv_canonical_url() {
	if ( is_singular() ) {
		return get_permalink();
	}
	if ( is_category() || is_tag() ) {
		return get_term_link( get_queried_object() );
	}
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	global $wp;
	return home_url( add_query_arg( array(), $wp->request ) );
}

add_action( 'wp_head', 'grv_seo_head_tags', 1 );
function grv_seo_head_tags() {
	$description = grv_meta_description();
	$canonical   = grv_canonical_url();
	$title       = wp_get_document_title();
	$image       = get_template_directory_uri() . '/src/images/logo-guia-review.png';

	if ( is_singular( 'post' ) && has_post_thumbnail() ) {
		$image = get_the_post_thumbnail_url( get_queried_object_id(), 'large' );
	}

	echo "\n<!-- Guia Review SEO -->\n";
	printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	if ( ! is_wp_error( $canonical ) && $canonical ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canonical ) );
	}
	printf( '<meta property="og:type" content="%s">' . "\n", is_singular( 'post' ) ? 'product' : 'website' );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
	printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
	printf( '<meta property="og:locale" content="pt_BR">' . "\n" );
	printf( '<meta name="twitter:card" content="summary_large_image">' . "\n" );

	if ( is_front_page() ) {
		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'WebSite',
			'name'     => get_bloginfo( 'name' ),
			'url'      => home_url( '/' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => home_url( '/?s={search_term}' ),
				'query-input' => 'required name=search_term',
			),
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
	echo "<!-- /Guia Review SEO -->\n";
}
