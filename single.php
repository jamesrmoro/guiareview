<?php
/**
 * @package Guia Review
 * @since 0.0.1
 */
get_header();
?>
<main>
<?php while ( have_posts() ) : the_post();
	$post_id = get_the_ID();
	if ( is_user_logged_in() ) {
		$product_number = get_post_meta( $post_id, 'grv_product_number', true );
		if ( $product_number !== '' ) {
			echo '<aside class="grv-product-number">Produto n&ordm; <strong>' . esc_html( $product_number ) . '</strong></aside>';
		}
	}

	$url          = grv_get_field( 'url' );
	$image        = grv_get_field( 'image' );
	$price        = grv_get_field( 'price' );
	$old_price    = grv_get_field( 'old_price' );
	$brand        = grv_get_field( 'brand' );
	$color        = grv_get_field( 'color' );
	$bullets      = grv_parse_lines( grv_get_field( 'bullets' ) );
	$specs        = grv_parse_lines( grv_get_field( 'specs' ) );

	// Campos legados (posts antigos, ex.: livros) — só aparecem se preenchidos.
	$legacy = array(
		'Autor'              => grv_get_field( 'author' ),
		'Editora'            => grv_get_field( 'company' ),
		'Páginas'            => grv_get_field( 'pages' ),
		'Idioma'             => grv_get_field( 'language' ),
		'ISBN'               => grv_get_field( 'isbn' ),
		'ISBN-13'            => grv_get_field( 'isbn_13' ),
		'Medidas'            => grv_get_field( 'measurements' ),
		'Data de publicação' => grv_get_field( 'date_published' ),
	);
	$legacy = array_filter( $legacy, function ( $v ) { return $v !== '' && $v !== false && $v !== null; } );

	// Uma única imagem: campo principal > imagem destacada > placeholder.
	$images = array();
	if ( empty( $images ) && $image ) {
		$images[] = array( 'url' => $image, 'alt' => get_the_title() );
	}
	if ( empty( $images ) && has_post_thumbnail() ) {
		$images[] = array( 'url' => get_the_post_thumbnail_url( $post_id, 'large' ), 'alt' => get_the_title() );
	}
	if ( empty( $images ) ) {
		$images[] = array( 'url' => get_template_directory_uri() . '/src/images/placeholder-product.svg', 'alt' => get_the_title() );
	}

	$categories   = get_the_category();
	$primary_cat  = null;
	foreach ( $categories as $category ) {
		if ( ! $primary_cat || count( get_ancestors( $category->term_id, 'category' ) ) > count( get_ancestors( $primary_cat->term_id, 'category' ) ) ) {
			$primary_cat = $category;
		}
	}
	$trail        = $primary_cat ? grv_category_ancestors( $primary_cat ) : array();
	if ( $primary_cat ) {
		$trail[] = array( 'label' => $primary_cat->name, 'url' => get_category_link( $primary_cat->term_id ) );
	}
	$trail[] = array( 'label' => get_the_title(), 'url' => null );
	?>

	<?php grv_breadcrumbs( $trail ); ?>

	<article class="container grv-product">
		<div class="grv-gallery" id="grvGallery">
			<figure class="grv-stage" id="grvStage">
				<?php if ( $url ) : ?>
					<a class="grv-stage-link" href="<?php echo esc_url( $url ); ?>" <?php echo grv_offer_attributes( $post_id ); ?> target="_blank" rel="nofollow sponsored noopener noreferrer" aria-label="<?php echo esc_attr( 'Ver oferta de ' . get_the_title() . ' (abre em nova aba)' ); ?>">
				<?php endif; ?>
				<?php echo grv_media_image( get_post_meta( $post_id, 'image', true ) ?: ( get_post_thumbnail_id( $post_id ) ?: $images[0]['url'] ), 'product_display', array( 'id' => 'grvMainImg', 'alt' => $images[0]['alt'], 'fetchpriority' => 'high', 'loading' => 'eager', 'sizes' => '(max-width: 680px) calc(100vw - 32px), (max-width: 1080px) 48vw, 520px' ) ); ?>
				<?php if ( $url ) : ?></a><?php endif; ?>
			</figure>
		</div>

		<div class="grv-info">
			<h1><?php the_title(); ?></h1>
			<?php if ( $brand ) : ?><p class="brand-link"><?php echo esc_html( $brand ); ?></p><?php endif; ?>

			<?php if ( $brand || $color || $price ) : ?>
				<table class="grv-quick-specs">
					<?php if ( $brand ) : ?><tr><th>Marca</th><td><?php echo esc_html( $brand ); ?></td></tr><?php endif; ?>
					<?php if ( $color ) : ?><tr><th>Cor</th><td><?php echo esc_html( $color ); ?></td></tr><?php endif; ?>
					<?php if ( $price ) : ?><tr><th>Preço</th><td>R$ <?php echo esc_html( number_format( $price, 2, ',', '.' ) ); ?></td></tr><?php endif; ?>
				</table>
			<?php endif; ?>

			<div class="grv-product-description" id="grvProductDetails">
			<?php the_content(); ?>

			<?php if ( $bullets ) : ?>
				<h2 class="sr-only">Destaques do produto</h2>
				<ul class="grv-bullets">
					<?php foreach ( $bullets as $b ) : ?>
						<li><?php if ( $b['label'] ) : ?><strong><?php echo esc_html( $b['label'] ); ?>:</strong> <?php endif; echo esc_html( $b['text'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $specs || $legacy ) : ?>
			<section class="grv-accordion grv-product-specifications" aria-label="Informações do produto">
				<h2>Informações do produto</h2>
				<?php if ( $specs ) : ?>
				<details open>
					<summary>Especificações técnicas</summary>
					<?php echo grv_product_specs_html( $specs ); ?>
				</details>
				<?php endif; ?>
				<?php if ( $legacy ) : ?>
				<details open>
					<summary>Detalhes adicionais</summary>
					<table class="specs">
						<?php foreach ( $legacy as $label => $value ) : ?>
						<tr><th><?php echo esc_html( $label ); ?></th><td><?php echo esc_html( $value ); ?></td></tr>
						<?php endforeach; ?>
					</table>
				</details>
				<?php endif; ?>
			</section>
			<?php endif; ?>
			</div>
			<button type="button" class="grv-description-toggle" aria-controls="grvProductDetails" aria-expanded="false" hidden><span>Ver mais informações</span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>
		</div>

		<aside class="grv-buybox" aria-label="Comprar">
			<h2 class="sr-only">Comprar</h2>
			<?php if ( $price ) : ?>
				<p class="price">
					<?php if ( $old_price && $old_price > $price ) : ?><span class="old">R$ <?php echo esc_html( number_format( $old_price, 2, ',', '.' ) ); ?></span><?php endif; ?>
					R$ <?php echo esc_html( number_format( $price, 2, ',', '.' ) ); ?>
				</p>
			<?php endif; ?>
			<p class="stock">Disponível</p>
			<p class="muted">Preço e disponibilidade podem mudar no site do vendedor.</p>
			<?php if ( $url ) : ?>
				<a class="btn-buy" href="<?php echo esc_url( $url ); ?>" <?php echo grv_offer_attributes( $post_id ); ?> target="_blank" rel="nofollow sponsored noopener" title="Ver oferta">Ver oferta</a>
				<a class="btn-buy-2" href="<?php echo esc_url( $url ); ?>" <?php echo grv_offer_attributes( $post_id ); ?> target="_blank" rel="nofollow sponsored noopener" title="Comprar">Comprar agora</a>
			<?php endif; ?>
			<?php grv_product_share( $post_id, $images[0]['url'] ); ?>
		</aside>
	</article>
	<?php if ( $url ) : ?>
	<div class="grv-mobile-buy"><a href="<?php echo esc_url( $url ); ?>" <?php echo grv_offer_attributes( $post_id ); ?> target="_blank" rel="nofollow sponsored noopener noreferrer">Comprar agora <span aria-hidden="true">↗</span></a></div>
	<?php endif; ?>

	<?php
	if ( $primary_cat ) {
		$related_cat = $primary_cat;
		do {
			$related = new WP_Query( array(
				'cat'                 => $related_cat->term_id,
				'posts_per_page'      => 3,
				'post__not_in'        => array( $post_id ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			) );
			if ( $related->have_posts() || ! $related_cat->parent ) { break; }
			$related_cat = get_category( $related_cat->parent );
		} while ( $related_cat && ! is_wp_error( $related_cat ) );
		if ( $related->have_posts() ) :
			?>
			<section class="container grv-block grv-related">
				<h2><?php echo esc_html( 'Mais em ' . $related_cat->name ); ?></h2>
				<div class="grv-grid">
					<?php while ( $related->have_posts() ) : $related->the_post(); grv_product_card( get_the_ID() ); endwhile; ?>
				</div>
			</section>
			<?php
		endif;
		wp_reset_postdata();
	}
	?>

	<?php
	// JSON-LD Product — só inclui offers/aggregateRating quando os dados existem de verdade.
	$schema = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Product',
		'name'     => get_the_title(),
		'image'    => wp_list_pluck( $images, 'url' ),
	);
	if ( has_excerpt() || get_the_excerpt() ) {
		$schema['description'] = wp_strip_all_tags( get_the_excerpt() );
	}
	if ( $brand ) {
		$schema['brand'] = array( '@type' => 'Brand', 'name' => $brand );
	}
	if ( $color ) {
		$schema['color'] = $color;
	}
	if ( $price ) {
		$schema['offers'] = array(
			'@type'         => 'Offer',
			'priceCurrency' => 'BRL',
			'price'         => (string) $price,
			'availability'  => 'https://schema.org/InStock',
			'url'           => $url ?: get_permalink(),
		);
	}
	?>
	<script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>

<?php endwhile; ?>
</main>
<?php get_footer(); ?>
