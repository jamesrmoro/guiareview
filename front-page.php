<?php
/**
 * Home — foco em SEO: categorias em destaque + produtos recentes.
 *
 * @package Guia Review
 * @since 0.0.1
 */
get_header();

$top_cats = get_categories( array( 'parent' => 0, 'hide_empty' => false, 'orderby' => 'name', 'pad_counts' => true ) );
$category_counts = grv_category_product_counts();
?>
<main>
	<section class="grv-hero">
		<div class="container">
			<h1>Guia Review: compare, decida e compre com confiança</h1>
			<p>No Guia Review, consulte características, imagens e versões dos produtos. Explore as categorias e compare as informações antes de acessar a oferta na loja.</p>
		</div>
	</section>

	<?php if ( $top_cats ) : ?>
	<section class="grv-section container">
		<div class="section-head">
			<h2>Categorias</h2>
		</div>
		<div class="grv-cat-grid">
			<?php foreach ( $top_cats as $cat ) :
				$cat_image = grv_get_field( 'image', 'category_' . $cat->term_id );
				?>
				<a class="grv-cat-tile" href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>">
					<?php if ( $cat_image ) : ?>
						<?php echo grv_media_image( get_term_meta( $cat->term_id, 'image', true ) ?: $cat_image, 'product_card', array( 'alt' => $cat->name, 'loading' => 'lazy', 'sizes' => '(max-width: 408px) calc(100vw - 32px), (max-width: 680px) 45vw, 240px' ) ); ?>
					<?php endif; ?>
					<span><?php echo esc_html( $cat->name ); ?> <span class="muted">(<?php echo (int) ( $category_counts[$cat->term_id] ?? 0 ); ?>)</span></span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php
	$recent = new WP_Query( array( 'posts_per_page' => 12, 'ignore_sticky_posts' => true ) );
	if ( $recent->have_posts() ) :
		?>
		<section class="grv-section container">
			<div class="section-head">
				<h2>Produtos em destaque no Guia Review</h2>
			</div>
			<div class="grv-grid">
				<?php while ( $recent->have_posts() ) : $recent->the_post(); grv_product_card( get_the_ID() ); endwhile; ?>
			</div>
		</section>
		<?php
	endif;
	wp_reset_postdata();
	?>
</main>
<?php get_footer(); ?>
