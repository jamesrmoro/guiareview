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

	$url          = grv_get_field( 'url' );
	$image        = grv_get_field( 'image' );
	$gallery      = grv_get_field( 'gallery' );
	$price        = grv_get_field( 'price' );
	$old_price    = grv_get_field( 'old_price' );
	$brand        = grv_get_field( 'brand' );
	$color        = grv_get_field( 'color' );
	$rating       = grv_get_field( 'rating' );
	$review_count = grv_get_field( 'review_count' );
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

	// Galeria: campo gallery > campo image (legado) > imagem destacada > placeholder.
	// Alguns posts antigos já tinham um meta "gallery" próprio (lista de IDs de
	// anexo) antes deste campo ACF existir — por isso tratamos os dois formatos.
	$images = array();
	if ( $gallery && is_array( $gallery ) ) {
		foreach ( $gallery as $g ) {
			if ( is_array( $g ) && ! empty( $g['url'] ) ) {
				$images[] = array( 'url' => $g['url'], 'alt' => $g['alt'] ?: get_the_title() );
			} elseif ( is_numeric( $g ) ) {
				$gallery_url = wp_get_attachment_image_url( (int) $g, 'large' );
				if ( $gallery_url ) {
					$images[] = array( 'url' => $gallery_url, 'alt' => get_post_meta( (int) $g, '_wp_attachment_image_alt', true ) ?: get_the_title() );
				}
			}
		}
	}
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
	$primary_cat  = $categories ? $categories[0] : null;
	$trail        = $primary_cat ? grv_category_ancestors( $primary_cat ) : array();
	if ( $primary_cat ) {
		$trail[] = array( 'label' => $primary_cat->name, 'url' => get_category_link( $primary_cat->term_id ) );
	}
	$trail[] = array( 'label' => get_the_title(), 'url' => null );
	?>

	<?php grv_breadcrumbs( $trail ); ?>

	<article class="container grv-product">
		<div class="grv-gallery" id="grvGallery">
			<?php if ( count( $images ) > 1 ) : ?>
			<ul class="thumbs">
				<?php foreach ( $images as $i => $img ) : ?>
					<li><button class="grv-thumb<?php echo $i === 0 ? ' is-active' : ''; ?>" data-full="<?php echo esc_url( $img['url'] ); ?>" aria-label="Imagem <?php echo $i + 1; ?>">
						<img src="<?php echo esc_url( $img['url'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ); ?>" width="60" height="60" <?php echo $i > 0 ? 'loading="lazy"' : ''; ?>>
					</button></li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
			<figure class="grv-stage" id="grvStage">
				<img id="grvMainImg" src="<?php echo esc_url( $images[0]['url'] ); ?>" alt="<?php echo esc_attr( $images[0]['alt'] ); ?>" width="600" height="600" fetchpriority="high">
			</figure>
		</div>

		<div class="grv-info">
			<h1><?php the_title(); ?></h1>
			<?php if ( $brand ) : ?><p class="brand-link"><?php echo esc_html( $brand ); ?></p><?php endif; ?>

			<?php if ( $rating ) : ?>
				<p class="grv-rating" aria-label="Avaliação: <?php echo esc_attr( number_format_i18n( $rating, 1 ) ); ?> de 5 estrelas<?php echo $review_count ? ', ' . esc_attr( $review_count ) . ' avaliações' : ''; ?>">
					<span class="stars" aria-hidden="true"><?php echo str_repeat( '★', (int) round( $rating ) ) . str_repeat( '☆', 5 - (int) round( $rating ) ); ?></span>
					<span><?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?></span>
					<?php if ( $review_count ) : ?><?php if ( $url ) : ?><a href="<?php echo esc_url( $url ); ?>" <?php echo grv_offer_attributes( $post_id ); ?> target="_blank" rel="nofollow sponsored noopener noreferrer" aria-label="Ver avaliações de <?php echo esc_attr( get_the_title() ); ?> na loja"><?php echo esc_html( number_format_i18n( $review_count ) ); ?> avaliações</a><?php else : ?><span><?php echo esc_html( number_format_i18n( $review_count ) ); ?> avaliações</span><?php endif; ?><?php endif; ?>
				</p>
			<?php endif; ?>

			<?php if ( $brand || $color || $price ) : ?>
				<table class="grv-quick-specs">
					<?php if ( $brand ) : ?><tr><th>Marca</th><td><?php echo esc_html( $brand ); ?></td></tr><?php endif; ?>
					<?php if ( $color ) : ?><tr><th>Cor</th><td><?php echo esc_html( $color ); ?></td></tr><?php endif; ?>
					<?php if ( $price ) : ?><tr><th>Preço</th><td>R$ <?php echo esc_html( number_format( $price, 2, ',', '.' ) ); ?></td></tr><?php endif; ?>
				</table>
			<?php endif; ?>

			<?php the_content(); ?>

			<?php if ( $bullets ) : ?>
				<h2 class="sr-only">Destaques do produto</h2>
				<ul class="grv-bullets">
					<?php foreach ( $bullets as $b ) : ?>
						<li><?php if ( $b['label'] ) : ?><strong><?php echo esc_html( $b['label'] ); ?>:</strong> <?php endif; echo esc_html( $b['text'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
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
		</aside>
	</article>
	<?php if ( $url ) : ?>
	<div class="grv-mobile-buy"><a href="<?php echo esc_url( $url ); ?>" <?php echo grv_offer_attributes( $post_id ); ?> target="_blank" rel="nofollow sponsored noopener noreferrer">Comprar agora <span aria-hidden="true">↗</span></a></div>
	<?php endif; ?>

	<?php if ( $specs || $legacy ) : ?>
	<section class="container grv-block grv-accordion" id="grvReviewsInfo">
		<h2>Informações do produto</h2>
		<?php if ( $specs ) : ?>
			<details open>
				<summary>Especificações técnicas</summary>
				<?php echo grv_product_specs_html( $specs ); ?>
			</details>
		<?php endif; ?>
		<?php if ( $legacy ) : ?>
			<details<?php echo $specs ? '' : ' open'; ?>>
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

	<?php
	if ( $primary_cat ) {
		$related = new WP_Query( array(
			'cat'            => $primary_cat->term_id,
			'posts_per_page' => 8,
			'post__not_in'   => array( $post_id ),
			'ignore_sticky_posts' => true,
		) );
		if ( $related->have_posts() ) :
			?>
			<section class="container grv-block grv-related">
				<h2><?php echo esc_html( 'Mais em ' . $primary_cat->name ); ?></h2>
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
	// Store ratings are displayed with their source link, not as reviews collected here.
	?>
	<script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>

	<script>
	(function () {
		var gallery = document.getElementById('grvGallery');
		if (!gallery) return;
		var mainImg = document.getElementById('grvMainImg');
		var thumbs = gallery.querySelectorAll('.grv-thumb');
		thumbs.forEach(function (t) {
			t.addEventListener('click', function () {
				mainImg.src = t.dataset.full;
				mainImg.alt = t.querySelector('img').alt;
				thumbs.forEach(function (x) { x.classList.toggle('is-active', x === t); });
			});
		});
	})();
	</script>

<?php endwhile; ?>
</main>
<?php get_footer(); ?>
